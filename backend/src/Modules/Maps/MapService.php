<?php

declare(strict_types=1);

namespace App\Modules\Maps;

use App\Database\Repositories\MapRepository;
use App\Database\Repositories\PatientRepository;
use App\Security\InputSanitizer;
use InvalidArgumentException;
use Throwable;

final class MapService
{
    private const STATUSES = ['draft', 'ready_for_analysis', 'analyzed', 'archived'];

    public function __construct(
        private readonly MapRepository $maps = new MapRepository(),
        private readonly PatientRepository $patients = new PatientRepository()
    ) {
    }

    /**
     * @return array{data:list<array<string,mixed>>,pagination:array{page:int,per_page:int,total:int}}
     */
    public function list(
        string $ownerUserId,
        ?string $query,
        ?string $status,
        ?string $patientId,
        int $page,
        int $perPage
    ): array {
        $query = $query === null ? null : InputSanitizer::maxLength(InputSanitizer::sanitizeString($query), 100);
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $status = $status === '' ? null : $status;
        $patientId = $patientId === '' ? null : $patientId;

        if ($patientId !== null) {
            $this->assertPatientOwnership($patientId, $ownerUserId);
        }

        $data = $this->maps->listByOwner($ownerUserId, $query, $status, $patientId, $page, $perPage);
        $total = $this->maps->countByOwner($ownerUserId, $query, $status, $patientId);

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
        $data = $this->sanitizePayload($payload, true, $ownerUserId);
        $data['owner_user_id'] = $ownerUserId;
        $data['status'] = 'draft';
        $data['canvas_json'] = null;
        $id = $this->maps->create($data);

        return $this->maps->findByIdAndOwner($id, $ownerUserId) ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function find(string $id, string $ownerUserId): array
    {
        $map = $this->maps->findByIdAndOwner($id, $ownerUserId);

        if ($map === null) {
            throw new InvalidArgumentException('Map not found');
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(string $id, string $ownerUserId, array $payload): array
    {
        if ($this->maps->findByIdAndOwner($id, $ownerUserId) === null) {
            throw new InvalidArgumentException('Map not found');
        }

        $data = $this->sanitizePayload($payload, false, $ownerUserId);
        $shouldVersionCanvas = array_key_exists('canvas_json', $data);

        if ($shouldVersionCanvas) {
            $this->maps->beginTransaction();

            try {
                $this->maps->updateByOwner($id, $ownerUserId, $data);
                $this->maps->createCanvasVersion(
                    $id,
                    $ownerUserId,
                    (string) ($data['canvas_json'] ?? 'null'),
                    'Snapshot do canvas'
                );
                $this->maps->commit();
            } catch (Throwable $exception) {
                $this->maps->rollBack();

                throw $exception;
            }

            return $this->maps->findByIdAndOwner($id, $ownerUserId) ?? [];
        }

        $this->maps->updateByOwner($id, $ownerUserId, $data);

        return $this->maps->findByIdAndOwner($id, $ownerUserId) ?? [];
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listCanvasVersions(string $id, string $ownerUserId): array
    {
        if ($this->maps->findByIdAndOwner($id, $ownerUserId) === null) {
            throw new InvalidArgumentException('Map not found');
        }

        return $this->maps->listCanvasVersions($id);
    }

    /**
     * @return array<string,mixed>
     */
    public function findCanvasVersion(string $id, string $versionId, string $ownerUserId): array
    {
        if ($this->maps->findByIdAndOwner($id, $ownerUserId) === null) {
            throw new InvalidArgumentException('Map not found');
        }

        $version = $this->maps->findCanvasVersionById($id, $versionId);

        if ($version === null) {
            throw new InvalidArgumentException('Canvas version not found');
        }

        $canvasData = (string) ($version['canvas_data'] ?? '');
        $decodedCanvas = json_decode($canvasData, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $version['canvas_data'] = $decodedCanvas;
        }

        return $version;
    }

    /**
     * @return array<string,mixed>
     */
    public function restoreCanvasVersion(string $id, string $versionId, string $ownerUserId): array
    {
        if ($this->maps->findByIdAndOwner($id, $ownerUserId) === null) {
            throw new InvalidArgumentException('Map not found');
        }

        if ($this->maps->findCanvasVersionById($id, $versionId) === null) {
            throw new InvalidArgumentException('Canvas version not found');
        }

        return $this->maps->restoreCanvasFromVersion($id, $ownerUserId, $versionId, $ownerUserId);
    }

    public function archive(string $id, string $ownerUserId, string $deletedBy): void
    {
        if (!$this->maps->softDeleteByOwner($id, $ownerUserId, $deletedBy)) {
            throw new InvalidArgumentException('Map not found');
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload, bool $requireTitle, string $ownerUserId): array
    {
        $data = [];

        if ($requireTitle || array_key_exists('title', $payload)) {
            $data['title'] = InputSanitizer::maxLength(
                InputSanitizer::required((string) ($payload['title'] ?? ''), 'title'),
                255
            );
        }

        if (array_key_exists('patient_id', $payload)) {
            $patientId = trim((string) ($payload['patient_id'] ?? ''));
            $data['patient_id'] = $patientId === '' ? null : $patientId;

            if ($data['patient_id'] !== null) {
                $this->assertPatientOwnership((string) $data['patient_id'], $ownerUserId);
            }
        }

        if (array_key_exists('reason', $payload)) {
            $data['reason'] = InputSanitizer::maxLength(trim((string) $payload['reason']), 3000);
        }

        if (array_key_exists('status', $payload)) {
            $status = (string) $payload['status'];

            if (!in_array($status, self::STATUSES, true)) {
                throw new InvalidArgumentException('Invalid status');
            }

            $data['status'] = $status;
        }

        if (array_key_exists('canvas_json', $payload)) {
            if ($payload['canvas_json'] === null) {
                $data['canvas_json'] = null;
            } elseif (is_array($payload['canvas_json'])) {
                $encodedCanvas = json_encode(
                    $payload['canvas_json'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );

                if ($encodedCanvas === false) {
                    throw new InvalidArgumentException('Invalid canvas data');
                }

                $data['canvas_json'] = $encodedCanvas;
            } elseif (is_string($payload['canvas_json'])) {
                json_decode($payload['canvas_json'], true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new InvalidArgumentException('Invalid canvas data');
                }

                $data['canvas_json'] = $payload['canvas_json'];
            } else {
                throw new InvalidArgumentException('Invalid canvas data');
            }
        }

        return $data;
    }

    private function assertPatientOwnership(string $patientId, string $ownerUserId): void
    {
        if ($this->patients->findByIdAndOwner($patientId, $ownerUserId) === null) {
            throw new InvalidArgumentException('Patient not found');
        }
    }
}
