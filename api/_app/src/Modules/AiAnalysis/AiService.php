<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Database\Repositories\AiAnalysisRepository;
use App\Modules\Maps\MapImageService;
use App\Modules\Maps\MapService;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AiService
{
    private AiAnalysisRepository $repository;

    public function __construct()
    {
        $this->repository = new AiAnalysisRepository();
    }

    /**
     * Return existing analysis for a map, or null if none exists yet.
     *
     * @return array<string,mixed>|null
     */
    public function findAnalysis(string $mapId, string $ownerUserId): ?array
    {
        // Verify ownership — throws InvalidArgumentException if not found
        (new MapService())->find($mapId, $ownerUserId);

        $analysis = $this->repository->findByMapId($mapId);

        if ($analysis === null) {
            return null;
        }

        return $this->decodeAnalysis($analysis);
    }

    /**
     * Generate (or regenerate) AI analysis for the given map.
     *
     * @return array<string,mixed>
     * @throws InvalidArgumentException if canvas is empty or map not found
     * @throws RuntimeException if no AI provider is configured or both fail
     */

    /**
     * Fast synchronous validation — no AI calls.
     * Called by AiController before kicking off background generation.
     *
     * @throws InvalidArgumentException
     */
    public function validateForGeneration(string $mapId, string $ownerUserId): void
    {
        $map    = (new MapService())->find($mapId, $ownerUserId);
        $canvas = $this->extractCanvas($map);

        if (!$this->canvasHasContent($canvas)) {
            throw new InvalidArgumentException(
                'O canvas deve ter pelo menos um campo preenchido antes de gerar a análise.'
            );
        }

        $structuredReading = $canvas['structured_reading'] ?? null;
        if (is_array($structuredReading) && !StructuredReading::isReviewed($structuredReading)) {
            throw new InvalidArgumentException(
                'Revise e confirme a leitura estruturada do mapa antes de gerar a análise completa.'
            );
        }
    }

    public function generate(string $mapId, string $ownerUserId): array
    {
        $map = (new MapService())->find($mapId, $ownerUserId);

        $canvas = $this->extractCanvas($map);

        if (!$this->canvasHasContent($canvas)) {
            throw new InvalidArgumentException(
                'O canvas deve ter pelo menos um campo preenchido antes de gerar a análise.'
            );
        }

        $structuredReading = $canvas['structured_reading'] ?? null;
        if (is_array($structuredReading)) {
            if (!StructuredReading::isReviewed($structuredReading)) {
                throw new InvalidArgumentException(
                    'Revise e confirme a leitura estruturada do mapa antes de gerar a análise completa.'
                );
            }
        }

        // Hostinger encerra tarefas longas; mantenha a chamada dentro de uma janela curta.
        set_time_limit(55);

        $openAi    = new OpenAiClient();
        $anthropic = new AnthropicClient();

        if (!$openAi->isAvailable() && !$anthropic->isAvailable()) {
            throw new RuntimeException(
                'Nenhum provedor de IA configurado. Defina OPENAI_API_KEY ou ANTHROPIC_API_KEY no servidor.'
            );
        }

        // Captura notas do terapeuta e análise anterior ANTES do upsert (que sobrescreve).
        $existing        = $this->repository->findByMapId($mapId);
        $therapistNotes  = isset($existing['therapist_notes']) && $existing['therapist_notes'] !== ''
            ? (string) $existing['therapist_notes']
            : null;
        $previousAnalysis = ($existing !== null
            && $existing['status'] === 'completed'
            && $existing['professional_analysis'] !== null)
            ? (string) $existing['professional_analysis']
            : null;

        try {
            // Mark as processing inside the guarded block so persistence errors
            // cannot escape as an opaque HTTP 500.
            $this->repository->upsert($mapId, ['status' => 'processing']);

            // ── Text generation ───────────────────────────────────────────────
            $systemPrompt = AiPromptBuilder::systemPrompt($map);
            $userPrompt   = AiPromptBuilder::userPrompt($map, $therapistNotes, $previousAnalysis);
            $textResponse = null;
            $modelText    = null;
            $openAiFailure = null;

            if ($openAi->isAvailable()) {
                $startedAt = microtime(true);
                try {
                    $textResponse = $openAi->chat($systemPrompt, $userPrompt);
                    $modelText    = $openAi->getTextModel();
                    self::logProvider('openai', 'text', $mapId, $startedAt, null);
                } catch (Throwable $exception) {
                    self::logProvider('openai', 'text', $mapId, $startedAt, $exception);
                    $openAiFailure = $exception;
                }
            }

            if ($textResponse === null && $anthropic->isAvailable()) {
                if ($openAiFailure !== null && (microtime(true) - $startedAt) >= 8) {
                    throw new RuntimeException(
                        'A OpenAI excedeu a janela segura do servidor; tente novamente.',
                        0,
                        $openAiFailure
                    );
                }
                $startedAt = microtime(true);
                try {
                    $textResponse = $anthropic->chat($systemPrompt, $userPrompt);
                    $modelText    = $anthropic->getModel();
                    self::logProvider('anthropic', 'text', $mapId, $startedAt, null);
                } catch (Throwable $exception) {
                    self::logProvider('anthropic', 'text', $mapId, $startedAt, $exception);
                    throw $exception;
                }
            }

            if ($textResponse === null) {
                throw new RuntimeException('Ambos os provedores de IA falharam ao gerar a análise.');
            }

            // ── Parse JSON response ───────────────────────────────────────────
            $parsed = json_decode($textResponse, true);

            if (!is_array($parsed)) {
                throw new RuntimeException('A IA retornou um JSON inválido. Tente novamente.');
            }

            $professionalAnalysis = $parsed['professional_analysis'] ?? null;
            $patientReport        = trim((string) ($parsed['patient_report'] ?? ''));
            $imagePrompt          = trim((string) ($parsed['image_prompt'] ?? ''));
            $infographicSummary   = $parsed['infographic_summary'] ?? null;

            // Embed infographic_summary into professional_analysis (no extra DB column needed)
            if (is_array($professionalAnalysis) && is_array($infographicSummary)) {
                $professionalAnalysis['infographic_summary'] = $infographicSummary;
            }

            // ── Persist ───────────────────────────────────────────────────────
            $this->repository->upsert($mapId, [
                'professional_analysis' => is_array($professionalAnalysis)
                    ? json_encode($professionalAnalysis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                'patient_report'  => $patientReport !== '' ? $patientReport : null,
                'image_path'      => null,
                'image_prompt'    => $imagePrompt !== '' ? $imagePrompt : null,
                'model_text'      => $modelText,
                'model_image'     => null,
                'status'          => 'completed',
                'error_message'   => null,
                'generated_at'    => date('Y-m-d H:i:s'),
            ]);

        } catch (Throwable $exception) {
            error_log(sprintf(
                'ai_analysis_generation_failed map=%s type=%s message=%s',
                $mapId,
                $exception::class,
                $exception->getMessage()
            ));

            try {
                $this->repository->upsert($mapId, [
                    'status'        => 'failed',
                    'error_message' => $exception->getMessage(),
                    'generated_at'  => null,
                ]);
            } catch (Throwable $persistenceException) {
                error_log(sprintf(
                    'ai_analysis_failure_persistence_failed map=%s type=%s message=%s',
                    $mapId,
                    $persistenceException::class,
                    $persistenceException->getMessage()
                ));
            }

            if ($exception instanceof InvalidArgumentException || $exception instanceof RuntimeException) {
                throw $exception;
            }

            throw new RuntimeException('Falha inesperada durante a geração da análise.', 0, $exception);
        }

        return $this->findAnalysis($mapId, $ownerUserId) ?? [];
    }

    public function generateInfographic(string $mapId, string $ownerUserId): void
    {
        (new MapService())->find($mapId, $ownerUserId);
        $analysis = $this->repository->findByMapId($mapId);
        $prompt = trim((string) ($analysis['image_prompt'] ?? ''));

        if ($analysis === null || $analysis['status'] !== 'completed' || $prompt === '') {
            throw new RuntimeException('Relatório textual ou prompt visual indisponível.');
        }

        $openAi = new OpenAiClient();
        if (!$openAi->isAvailable()) {
            throw new RuntimeException('OpenAI não configurada para gerar o infográfico.');
        }

        set_time_limit(55);
        $startedAt = microtime(true);
        try {
            $imagePath = $this->saveImage($mapId, $openAi->generateImage($prompt));
            $this->repository->updateImageResult($mapId, [
                'image_path' => $imagePath,
                'model_image' => $openAi->getImageModel(),
            ]);
            self::logProvider('openai', 'image', $mapId, $startedAt, null);
        } catch (Throwable $exception) {
            self::logProvider('openai', 'image', $mapId, $startedAt, $exception);
            throw $exception;
        }
    }

    /**
     * Return the full filesystem path of the AI infographic image.
     *
     * @throws InvalidArgumentException if analysis or image file not found
     */
    public function getImagePath(string $mapId, string $ownerUserId): string
    {
        // Verify ownership
        (new MapService())->find($mapId, $ownerUserId);

        $analysis = $this->repository->findByMapId($mapId);

        if ($analysis === null || empty($analysis['image_path'])) {
            throw new InvalidArgumentException('Nenhuma imagem encontrada para esta análise.');
        }

        $fullPath = $this->imageStorageDir() . '/' . basename((string) $analysis['image_path']);

        if (!file_exists($fullPath)) {
            throw new InvalidArgumentException('Arquivo de imagem não encontrado no servidor.');
        }

        return $fullPath;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * @param array<string,mixed> $map
     * @return array<string,mixed>
     */
    private function extractCanvas(array $map): array
    {
        $raw = $map['canvas_json'] ?? null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($raw) ? $raw : [];
    }

    /**
     * @param array<string,mixed> $canvas
     */
    private function canvasHasContent(array $canvas): bool
    {
        $keys = [
            'main_demand', 'current_context', 'emotional_history', 'recurring_patterns',
            'core_beliefs', 'defense_strategies', 'internal_resources',
            'reflective_hypotheses', 'next_steps',
        ];

        foreach ($keys as $key) {
            if (trim((string) ($canvas[$key] ?? '')) !== '') {
                return true;
            }
        }

        $reading = $canvas['structured_reading'] ?? null;
        if (is_array($reading)) {
            if (trim((string) ($reading['summary'] ?? '')) !== '') {
                return true;
            }
            if (!empty($reading['elements']) || !empty($reading['arrows'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decode professional_analysis JSON string back to array.
     *
     * @param array<string,mixed> $analysis
     * @return array<string,mixed>
     */
    private function decodeAnalysis(array $analysis): array
    {
        if (is_string($analysis['professional_analysis'])) {
            $decoded = json_decode($analysis['professional_analysis'], true);
            if (is_array($decoded)) {
                $analysis['professional_analysis'] = $decoded;
            }
        }

        return $analysis;
    }

    /**
     * Decode base64 PNG and save to storage. Returns the relative filename.
     */
    private function saveImage(string $mapId, string $b64Image): string
    {
        $dir = $this->imageStorageDir();

        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create AI image storage directory: {$dir}");
        }

        $safe     = (string) (preg_replace('/[^a-zA-Z0-9_-]/', '', $mapId) ?? 'map');
        $filename = "infographic-{$safe}.png";
        $fullPath = $dir . '/' . $filename;

        $decoded = base64_decode($b64Image, true);

        if ($decoded === false) {
            throw new RuntimeException('Failed to decode base64 image from AI.');
        }

        if (file_put_contents($fullPath, $decoded) === false) {
            throw new RuntimeException('Failed to write AI image to disk.');
        }

        return $filename;
    }

    private function imageStorageDir(): string
    {
        // backend/storage/uploads/ai/
        return dirname(__DIR__, 4) . '/storage/uploads/ai';
    }

    private static function logProvider(
        string $provider,
        string $operation,
        string $mapId,
        float $startedAt,
        ?Throwable $exception
    ): void {
        error_log(sprintf(
            'ai_provider_call provider=%s operation=%s map=%s duration_ms=%d result=%s%s',
            $provider,
            $operation,
            $mapId,
            (int) round((microtime(true) - $startedAt) * 1000),
            $exception === null ? 'success' : 'error',
            $exception === null ? '' : ' message=' . $exception->getMessage()
        ));
    }

    /**
     * Lê a imagem do mapa via visão e retorna os campos do canvas preenchidos.
     *
     * @return array<string,mixed>
     */
    public function generateCanvas(string $mapId, string $ownerUserId): array
    {
        $mapService = new MapService();
        $map = $mapService->find($mapId, $ownerUserId);
        $imagePath = $map['map_image_path'] ?? null;

        if ($imagePath === null || $imagePath === '') {
            throw new InvalidArgumentException('Este mapa não possui imagem do Mapa da Psiquê. Faça o upload primeiro.');
        }

        $storageDir = (new MapImageService())->storageDir();
        $fullPath = $storageDir . DIRECTORY_SEPARATOR . basename((string) $imagePath);

        if (!file_exists($fullPath)) {
            throw new RuntimeException('Arquivo de imagem não encontrado no servidor.');
        }

        $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
        $imageContent = file_get_contents($fullPath);

        if ($imageContent === false) {
            throw new RuntimeException('Falha ao ler o arquivo de imagem.');
        }

        $patientNotes = null;
        $patientName = (string) ($map['patient_name'] ?? 'Paciente');

        if (!empty($map['patient_id'])) {
            try {
                $patient = $mapService->findPatientForMap($mapId, $ownerUserId);
                $patientNotes = $patient['notes'] ?? null;
                $patientName = (string) ($patient['name'] ?? $patientName);
            } catch (Throwable) {
                // Continua sem observações quando o paciente não for encontrado.
            }
        }

        $openAi = new OpenAiClient();
        if (!$openAi->isAvailable()) {
            throw new RuntimeException('OpenAI não está configurado. Defina OPENAI_API_KEY no .env.');
        }

        set_time_limit(120);

        $rawJson = $openAi->chatWithVision(
            AiPromptBuilder::canvasFillerSystemPrompt(),
            AiPromptBuilder::canvasFillerUserPrompt(
                $patientName,
                is_string($patientNotes) ? $patientNotes : null,
                !empty($map['reason']) ? (string) $map['reason'] : null
            ),
            base64_encode($imageContent),
            $mimeType
        );

        $rawJson = (string) preg_replace('/^```(?:json)?\s*/i', '', trim($rawJson));
        $rawJson = (string) preg_replace('/\s*```$/', '', $rawJson);
        $canvas = json_decode($rawJson, true);

        if (!is_array($canvas)) {
            throw new RuntimeException('A IA retornou um JSON inválido para o canvas.');
        }

        $result = ['schema_version' => 2];
        foreach ([
            'main_demand', 'current_context', 'emotional_history', 'recurring_patterns',
            'core_beliefs', 'defense_strategies', 'internal_resources',
            'reflective_hypotheses', 'next_steps',
        ] as $field) {
            $result[$field] = isset($canvas[$field]) ? (string) $canvas[$field] : '';
        }

        $result['structured_reading'] = StructuredReading::normalizeExtraction(
            is_array($canvas['structured_reading'] ?? null) ? $canvas['structured_reading'] : []
        );

        return $result;
    }

}
