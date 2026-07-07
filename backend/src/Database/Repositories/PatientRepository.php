<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class PatientRepository
{
    private const STATUSES = ['active', 'inactive', 'archived'];

    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByOwner(
        string $ownerUserId,
        ?string $query = null,
        ?string $status = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $filters = ['owner_user_id' => $ownerUserId];
        $where = $this->buildOwnerWhere($query, $status, $filters);
        $limit = max(1, min(50, $perPage));
        $offset = (max(1, $page) - 1) * $limit;

        $statement = $this->pdo->prepare(
            "SELECT id, name, internal_code, age, status, created_at
             FROM patients
             {$where}
             ORDER BY created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($filters);

        return $statement->fetchAll() ?: [];
    }

    public function countByOwner(string $ownerUserId, ?string $query = null, ?string $status = null): int
    {
        $filters = ['owner_user_id' => $ownerUserId];
        $where = $this->buildOwnerWhere($query, $status, $filters);

        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM patients {$where}");
        $statement->execute($filters);

        return (int) $statement->fetchColumn();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdAndOwner(string $id, string $ownerUserId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM patients
             WHERE id = :id
               AND owner_user_id = :owner_user_id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $id, 'owner_user_id' => $ownerUserId]);
        $patient = $statement->fetch();

        return is_array($patient) ? $patient : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $status = (string) ($data['status'] ?? 'active');

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO patients (id, owner_user_id, name, internal_code, age, notes, status, created_at)
             VALUES (:id, :owner_user_id, :name, :internal_code, :age, :notes, :status, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'id' => $id,
            'owner_user_id' => $data['owner_user_id'],
            'name' => $data['name'],
            'internal_code' => $data['internal_code'] ?? null,
            'age' => $data['age'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => $status,
        ]);

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateByOwner(string $id, string $ownerUserId, array $data): bool
    {
        $allowedFields = ['name', 'internal_code', 'age', 'notes', 'status'];
        $sets = [];
        $params = ['id' => $id, 'owner_user_id' => $ownerUserId];

        foreach ($allowedFields as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            if ($field === 'status' && !in_array((string) $data[$field], self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status');
            }

            $sets[] = "{$field} = :{$field}";
            $params[$field] = $data[$field];
        }

        if ($sets === []) {
            return false;
        }

        $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        $statement = $this->pdo->prepare(
            'UPDATE patients
             SET ' . implode(', ', $sets) . '
             WHERE id = :id
               AND owner_user_id = :owner_user_id
               AND deleted_at IS NULL'
        );

        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function softDeleteByOwner(string $id, string $ownerUserId, string $deletedBy): bool
    {
        $statement = $this->pdo->prepare(
            "UPDATE patients
             SET deleted_at = CURRENT_TIMESTAMP,
                 deleted_by = :deleted_by,
                 status = 'archived',
                 updated_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND owner_user_id = :owner_user_id
               AND deleted_at IS NULL"
        );

        $statement->execute([
            'id' => $id,
            'owner_user_id' => $ownerUserId,
            'deleted_by' => $deletedBy,
        ]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildOwnerWhere(?string $query, ?string $status, array &$filters): string
    {
        $where = [
            'owner_user_id = :owner_user_id',
            'deleted_at IS NULL',
        ];

        if ($query !== null && trim($query) !== '') {
            $where[] = '(name LIKE :query OR internal_code LIKE :query)';
            $filters['query'] = '%' . trim($query) . '%';
        }

        if ($status !== null && $status !== '') {
            if (!in_array($status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status');
            }

            $where[] = 'status = :status';
            $filters['status'] = $status;
        }

        return 'WHERE ' . implode(' AND ', $where);
    }
}
