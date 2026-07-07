<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class AuditLogRepository
{
    private const SEVERITIES = ['INFO', 'WARN', 'ERROR', 'CRITICAL'];
    private const SENSITIVE_METADATA_KEYS = [
        'analysis_text',
        'clinical_content',
        'content',
        'file_name',
        'filename',
        'map_notes',
        'password',
        'prompt',
        'prompt_used',
        'senha',
    ];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $severity = (string) ($data['severity'] ?? 'INFO');

        if (!in_array($severity, self::SEVERITIES, true)) {
            throw new InvalidArgumentException('Invalid severity');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO audit_logs (
                id, actor_user_id, request_id, session_id, severity, action, entity_type,
                entity_id, metadata_json, ip_address, user_agent, created_at
             ) VALUES (
                :id, :actor_user_id, :request_id, :session_id, :severity, :action, :entity_type,
                :entity_id, :metadata_json, :ip_address, :user_agent, CURRENT_TIMESTAMP
             )'
        );

        $statement->execute([
            'id' => $id,
            'actor_user_id' => $data['actor_user_id'] ?? null,
            'request_id' => $data['request_id'] ?? null,
            'session_id' => $data['session_id'] ?? null,
            'severity' => $severity,
            'action' => $data['action'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'] ?? null,
            'metadata_json' => isset($data['metadata_json'])
                ? json_encode(self::sanitizeMetadata((array) $data['metadata_json']), JSON_THROW_ON_ERROR)
                : null,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
        ]);

        return $id;
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private static function sanitizeMetadata(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_METADATA_KEYS, true)) {
                $metadata[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $metadata[$key] = self::sanitizeMetadata($value);
            }
        }

        return $metadata;
    }
}
