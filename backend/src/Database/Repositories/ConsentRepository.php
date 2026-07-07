<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class ConsentRepository
{
    private const STATUSES = ['accepted', 'revoked'];

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
        $statement = $this->pdo->prepare('SELECT * FROM consent_terms WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $consent = $statement->fetch();

        return is_array($consent) ? $consent : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findActiveTerm(): ?array
    {
        $statement = $this->pdo->query(
            'SELECT id, version, title, content
             FROM consent_terms
             WHERE active = TRUE
             ORDER BY created_at DESC
             LIMIT 1'
        );

        $term = $statement !== false ? $statement->fetch() : false;

        return is_array($term) ? $term : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestAcceptedByUser(string $userId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT uc.*
             FROM user_consents uc
             INNER JOIN consent_terms ct ON ct.id = uc.consent_term_id
             WHERE uc.user_id = :user_id
               AND uc.status = 'accepted'
               AND ct.active = TRUE
             ORDER BY uc.accepted_at DESC
             LIMIT 1"
        );

        $statement->execute(['user_id' => $userId]);
        $consent = $statement->fetch();

        return is_array($consent) ? $consent : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findAcceptedByUserAndTerm(string $userId, string $consentTermId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT *
             FROM user_consents
             WHERE user_id = :user_id
               AND consent_term_id = :consent_term_id
               AND status = 'accepted'
             ORDER BY accepted_at DESC
             LIMIT 1"
        );

        $statement->execute([
            'user_id' => $userId,
            'consent_term_id' => $consentTermId,
        ]);

        $consent = $statement->fetch();

        return is_array($consent) ? $consent : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $status = (string) ($data['status'] ?? 'accepted');

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO user_consents (
                id, user_id, consent_term_id, status, accepted_at, ip_address, user_agent, metadata, created_at
             ) VALUES (
                :id, :user_id, :consent_term_id, :status, CURRENT_TIMESTAMP, :ip_address, :user_agent, :metadata, CURRENT_TIMESTAMP
             )'
        );

        $statement->execute([
            'id' => $id,
            'user_id' => $data['user_id'],
            'consent_term_id' => $data['consent_term_id'],
            'status' => $status,
            'ip_address' => $data['ip_address'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata'], JSON_THROW_ON_ERROR) : null,
        ]);

        return $id;
    }

    public function revoke(string $id): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE user_consents SET status = 'revoked', revoked_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'accepted'"
        );

        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }
}
