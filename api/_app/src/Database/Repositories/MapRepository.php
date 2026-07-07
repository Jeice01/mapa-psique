<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use InvalidArgumentException;
use PDO;

final class MapRepository
{
    private const STATUSES = ['draft', 'ready_for_analysis', 'analyzed', 'archived'];

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
        ?string $patientId = null,
        int $page = 1,
        int $perPage = 10
    ): array {
        $filters = ['owner_user_id' => $ownerUserId];
        $where = $this->buildOwnerWhere($query, $status, $patientId, $filters);
        $limit = max(1, min(50, $perPage));
        $offset = (max(1, $page) - 1) * $limit;

        $statement = $this->pdo->prepare(
            "SELECT maps.id, maps.title, maps.patient_id, patients.name AS patient_name, maps.status, maps.created_at
             FROM maps
             LEFT JOIN patients
               ON patients.id = maps.patient_id
              AND patients.owner_user_id = maps.owner_user_id
              AND patients.deleted_at IS NULL
             {$where}
             ORDER BY maps.created_at DESC
             LIMIT {$limit} OFFSET {$offset}"
        );
        $statement->execute($filters);

        return $statement->fetchAll() ?: [];
    }

    public function countByOwner(
        string $ownerUserId,
        ?string $query = null,
        ?string $status = null,
        ?string $patientId = null
    ): int {
        $filters = ['owner_user_id' => $ownerUserId];
        $where = $this->buildOwnerWhere($query, $status, $patientId, $filters);

        $statement = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM maps
             LEFT JOIN patients
               ON patients.id = maps.patient_id
              AND patients.owner_user_id = maps.owner_user_id
              AND patients.deleted_at IS NULL
             {$where}"
        );
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
             FROM maps
             WHERE id = :id
               AND owner_user_id = :owner_user_id
               AND deleted_at IS NULL
             LIMIT 1'
        );
        $statement->execute(['id' => $id, 'owner_user_id' => $ownerUserId]);
        $map = $statement->fetch();

        return is_array($map) ? $map : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): string
    {
        $id = (string) ($data['id'] ?? Uuid::v4());
        $status = (string) ($data['status'] ?? 'draft');

        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO maps (id, owner_user_id, patient_id, title, reason, status, canvas_json, created_at)
             VALUES (:id, :owner_user_id, :patient_id, :title, :reason, :status, :canvas_json, CURRENT_TIMESTAMP)'
        );

        $statement->execute([
            'id' => $id,
            'owner_user_id' => $data['owner_user_id'],
            'patient_id' => $data['patient_id'] ?? null,
            'title' => $data['title'],
            'reason' => $data['reason'] ?? null,
            'status' => $status,
            'canvas_json' => $data['canvas_json'] ?? null,
        ]);

        return $id;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateByOwner(string $id, string $ownerUserId, array $data): bool
    {
        $allowedFields = ['title', 'patient_id', 'reason', 'status'];
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
            'UPDATE maps
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
            "UPDATE maps
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
     * @return array{maps_count:int,draft_maps_count:int,analyzed_maps_count:int}
     */
    public function countDashboardByOwner(string $ownerUserId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS maps_count,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_maps_count,
                SUM(CASE WHEN status = 'analyzed' THEN 1 ELSE 0 END) AS analyzed_maps_count
             FROM maps
             WHERE owner_user_id = :owner_user_id
               AND deleted_at IS NULL"
        );
        $statement->execute(['owner_user_id' => $ownerUserId]);
        $counts = $statement->fetch() ?: [];

        return [
            'maps_count' => (int) ($counts['maps_count'] ?? 0),
            'draft_maps_count' => (int) ($counts['draft_maps_count'] ?? 0),
            'analyzed_maps_count' => (int) ($counts['analyzed_maps_count'] ?? 0),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function buildOwnerWhere(?string $query, ?string $status, ?string $patientId, array &$filters): string
    {
        $where = [
            'maps.owner_user_id = :owner_user_id',
            'maps.deleted_at IS NULL',
        ];

        if ($query !== null && trim($query) !== '') {
            $where[] = 'maps.title LIKE :query';
            $filters['query'] = '%' . trim($query) . '%';
        }

        if ($status !== null && $status !== '') {
            if (!in_array($status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status');
            }

            $where[] = 'maps.status = :status';
            $filters['status'] = $status;
        }

        if ($patientId !== null && $patientId !== '') {
            $where[] = 'maps.patient_id = :patient_id';
            $filters['patient_id'] = $patientId;
        }

        return 'WHERE ' . implode(' AND ', $where);
    }
}
