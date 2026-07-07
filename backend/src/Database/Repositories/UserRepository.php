<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class UserRepository
{
    private const ROLES = ['administrador', 'profissional', 'paciente', 'auditor'];
    private const STATUSES = ['active', 'inactive', 'blocked', 'pending'];

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
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $statement->execute(['email' => self::normalizeEmail($email)]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $role = (string) ($data['role'] ?? 'profissional');
        $status = (string) ($data['status'] ?? 'pending');

        self::assertAllowed($role, self::ROLES, 'role');
        self::assertAllowed($status, self::STATUSES, 'status');

        $statement = $this->pdo->prepare(
            'INSERT INTO users (id, name, email, password_hash, role, status, created_at)
             VALUES (:id, :name, :email, :password_hash, :role, :status, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'id' => $id,
            'name' => $data['name'],
            'email' => self::normalizeEmail((string) $data['email']),
            'password_hash' => $data['password_hash'],
            'role' => $role,
            'status' => $status,
        ]);

        return $id;
    }

    public function softDelete(string $id, ?string $deletedBy): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE users SET deleted_at = CURRENT_TIMESTAMP, deleted_by = :deleted_by, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND deleted_at IS NULL'
        );

        $statement->execute(['id' => $id, 'deleted_by' => $deletedBy]);

        return $statement->rowCount() > 0;
    }

    public function updateLastLogin(string $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE users
             SET last_login_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND deleted_at IS NULL'
        );

        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    private static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
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
