<?php

declare(strict_types=1);

namespace App\Modules\Patients;

use App\Database\Repositories\PatientRepository;
use App\Security\InputSanitizer;
use InvalidArgumentException;

final class PatientService
{
    private const STATUSES = ['active', 'inactive', 'archived'];

    public function __construct(private readonly PatientRepository $patients = new PatientRepository())
    {
    }

    /**
     * @return array{data:list<array<string,mixed>>,pagination:array{page:int,per_page:int,total:int}}
     */
    public function list(string $ownerUserId, ?string $query, ?string $status, int $page, int $perPage): array
    {
        $query = $query === null ? null : InputSanitizer::maxLength(InputSanitizer::sanitizeString($query), 100);
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $status = $status === '' ? null : $status;

        $data = $this->patients->listByOwner($ownerUserId, $query, $status, $page, $perPage);
        $total = $this->patients->countByOwner($ownerUserId, $query, $status);

        return [
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function create(string $ownerUserId, array $payload): array
    {
        $data = $this->sanitizePayload($payload, true);
        $data['owner_user_id'] = $ownerUserId;
        $data['status'] = 'active';
        $id = $this->patients->create($data);

        return $this->patients->findByIdAndOwner($id, $ownerUserId) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id, string $ownerUserId): array
    {
        $patient = $this->patients->findByIdAndOwner($id, $ownerUserId);

        if ($patient === null) {
            throw new InvalidArgumentException('Patient not found');
        }

        return $patient;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, string $ownerUserId, array $payload): array
    {
        if ($this->patients->findByIdAndOwner($id, $ownerUserId) === null) {
            throw new InvalidArgumentException('Patient not found');
        }

        $data = $this->sanitizePayload($payload, false);
        $this->patients->updateByOwner($id, $ownerUserId, $data);

        return $this->patients->findByIdAndOwner($id, $ownerUserId) ?? [];
    }

    public function archive(string $id, string $ownerUserId, string $deletedBy): void
    {
        if (!$this->patients->softDeleteByOwner($id, $ownerUserId, $deletedBy)) {
            throw new InvalidArgumentException('Patient not found');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload, bool $requireName): array
    {
        $data = [];

        if ($requireName || array_key_exists('name', $payload)) {
            $data['name'] = InputSanitizer::maxLength(
                InputSanitizer::required((string) ($payload['name'] ?? ''), 'name'),
                255
            );
        }

        if (array_key_exists('internal_code', $payload)) {
            $data['internal_code'] = InputSanitizer::maxLength(InputSanitizer::sanitizeString((string) $payload['internal_code']), 100);
        }

        if (array_key_exists('age', $payload)) {
            $rawAge = $payload['age'];
            $age = null;

            if ($rawAge !== null && $rawAge !== '') {
                $age = filter_var($rawAge, FILTER_VALIDATE_INT);

                if ($age === false) {
                    throw new InvalidArgumentException('Invalid age');
                }
            }

            if ($age !== null && ($age < 0 || $age > 120)) {
                throw new InvalidArgumentException('Invalid age');
            }

            $data['age'] = $age;
        }

        if (array_key_exists('notes', $payload)) {
            $data['notes'] = InputSanitizer::maxLength(trim((string) $payload['notes']), 5000);
        }

        if (array_key_exists('status', $payload)) {
            $status = (string) $payload['status'];

            if (!in_array($status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status');
            }

            $data['status'] = $status;
        }

        return $data;
    }
}
