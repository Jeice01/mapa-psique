<?php

declare(strict_types=1);

namespace App\Database\Repositories;

use App\Database\Connection;
use App\Support\Uuid;
use PDO;

final class AiAnalysisRepository
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Connection::pdo();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByMapId(string $mapId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, map_id, professional_analysis, patient_report,
                    image_path, image_prompt, model_text, model_image,
                    status, error_message, generated_at, created_at, updated_at
             FROM map_ai_analyses
             WHERE map_id = :map_id
             LIMIT 1'
        );
        $stmt->execute(['map_id' => $mapId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function upsert(string $mapId, array $data): void
    {
        $existing = $this->findByMapId($mapId);

        if ($existing === null) {
            $this->insert($mapId, $data);
        } else {
            $this->update((string) $existing['id'], $data);
        }
    }

    /**
     * @param array<string,mixed> $data
     */
    private function insert(string $mapId, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO map_ai_analyses
                (id, map_id, professional_analysis, patient_report, image_path, image_prompt,
                 model_text, model_image, status, error_message, generated_at)
             VALUES
                (:id, :map_id, :professional_analysis, :patient_report, :image_path, :image_prompt,
                 :model_text, :model_image, :status, :error_message, :generated_at)'
        );
        $stmt->execute([
            'id'                    => Uuid::v4(),
            'map_id'                => $mapId,
            'professional_analysis' => $data['professional_analysis'] ?? null,
            'patient_report'        => $data['patient_report'] ?? null,
            'image_path'            => $data['image_path'] ?? null,
            'image_prompt'          => $data['image_prompt'] ?? null,
            'model_text'            => $data['model_text'] ?? null,
            'model_image'           => $data['model_image'] ?? null,
            'status'                => $data['status'] ?? 'pending',
            'error_message'         => $data['error_message'] ?? null,
            'generated_at'          => $data['generated_at'] ?? null,
        ]);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function update(string $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE map_ai_analyses
             SET professional_analysis = :professional_analysis,
                 patient_report        = :patient_report,
                 image_path            = :image_path,
                 image_prompt          = :image_prompt,
                 model_text            = :model_text,
                 model_image           = :model_image,
                 status                = :status,
                 error_message         = :error_message,
                 generated_at          = :generated_at
             WHERE id = :id'
        );
        $stmt->execute([
            'id'                    => $id,
            'professional_analysis' => $data['professional_analysis'] ?? null,
            'patient_report'        => $data['patient_report'] ?? null,
            'image_path'            => $data['image_path'] ?? null,
            'image_prompt'          => $data['image_prompt'] ?? null,
            'model_text'            => $data['model_text'] ?? null,
            'model_image'           => $data['model_image'] ?? null,
            'status'                => $data['status'] ?? 'completed',
            'error_message'         => $data['error_message'] ?? null,
            'generated_at'          => $data['generated_at'] ?? null,
        ]);
    }
}
