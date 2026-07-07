<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use PDO;

final class PasswordResetTokenRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    public function create(string $userId, string $tokenHash, string $expiresAt, ?string $requestIp, ?string $userAgent): string
    {
        $id = Uuid::v4();
        $statement = $this->pdo->prepare(
            'INSERT INTO password_reset_tokens (id, user_id, token_hash, expires_at, request_ip, user_agent, created_at)
             VALUES (:id, :user_id, :token_hash, :expires_at, :request_ip, :user_agent, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'request_ip' => $requestIp,
            'user_agent' => $userAgent,
        ]);

        return $id;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findValidByHash(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT password_reset_tokens.*, users.status AS user_status
             FROM password_reset_tokens
             INNER JOIN users ON users.id = password_reset_tokens.user_id
             WHERE password_reset_tokens.token_hash = :token_hash
               AND password_reset_tokens.used_at IS NULL
               AND password_reset_tokens.expires_at > CURRENT_TIMESTAMP
               AND users.deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $token = $statement->fetch();

        return is_array($token) ? $token : null;
    }

    public function markUsed(string $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE password_reset_tokens
             SET used_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND used_at IS NULL'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function revokeActiveForUser(string $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE password_reset_tokens
             SET used_at = CURRENT_TIMESTAMP
             WHERE user_id = :user_id
               AND used_at IS NULL'
        );
        $statement->execute(['user_id' => $userId]);
    }
}
