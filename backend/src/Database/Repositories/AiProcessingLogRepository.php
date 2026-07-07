<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class AiProcessingLogRepository
{
    private const ACTIONS = [
        'upload',
        'analysis_generation',
        'file_deletion',
        'vector_store_creation',
        'vector_store_deletion',
    ];

    private const STATUSES = [
        'success',
        'failed',
        'pending_delete',
        'pending_retry',
        'processing',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM ai_processing_logs WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $log = $statement->fetch();

        return is_array($log) ? $log : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $action = (string) $data['action'];
        $status = (string) ($data['status'] ?? 'processing');

        self::assertAllowed($action, self::ACTIONS, 'action');
        self::assertAllowed($status, self::STATUSES, 'status');

        $statement = $this->pdo->prepare(
            'INSERT INTO ai_processing_logs (
                id, map_id, map_file_id, provider, provider_file_id, provider_vector_store_id,
                action, status, error_message, retry_count, created_at
             ) VALUES (
                :id, :map_id, :map_file_id, :provider, :provider_file_id, :provider_vector_store_id,
                :action, :status, :error_message, :retry_count, CURRENT_TIMESTAMP
             )'
        );

        $statement->execute([
            'id' => $id,
            'map_id' => $data['map_id'] ?? null,
            'map_file_id' => $data['map_file_id'] ?? null,
            'provider' => $data['provider'] ?? 'openai',
            'provider_file_id' => $data['provider_file_id'] ?? null,
            'provider_vector_store_id' => $data['provider_vector_store_id'] ?? null,
            'action' => $action,
            'status' => $status,
            'error_message' => $data['error_message'] ?? null,
            'retry_count' => $data['retry_count'] ?? 0,
        ]);

        return $id;
    }

    public function updateStatus(string $id, string $status, ?string $errorMessage = null): bool
    {
        self::assertAllowed($status, self::STATUSES, 'status');

        $statement = $this->pdo->prepare(
            'UPDATE ai_processing_logs
             SET status = :status, error_message = :error_message, updated_at = CURRENT_TIMESTAMP,
                 resolved_at = CASE WHEN :status IN (\'success\', \'failed\') THEN CURRENT_TIMESTAMP ELSE resolved_at END
             WHERE id = :id'
        );

        $statement->execute([
            'id' => $id,
            'status' => $status,
            'error_message' => $errorMessage,
        ]);

        return $statement->rowCount() > 0;
    }

    public function markRetry(string $id, ?string $errorMessage = null): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE ai_processing_logs
             SET status = 'pending_retry',
                 retry_count = retry_count + 1,
                 error_message = :error_message,
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id"
        );

        $statement->execute([
            'id' => $id,
            'error_message' => $errorMessage,
        ]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param list<string> $allowed
     */
    private static function assertAllowed(string $value, array $allowed, string $field): void
    {
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Invalid {$field}");
        }
    }
}
