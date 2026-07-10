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
    public function generate(string $mapId, string $ownerUserId): array
    {
        $map = (new MapService())->find($mapId, $ownerUserId);

        if (!$this->canvasHasContent($this->extractCanvas($map))) {
            throw new InvalidArgumentException(
                'O canvas deve ter pelo menos um campo preenchido antes de gerar a análise.'
            );
        }

        // Extend execution time for AI API calls (timeout up to 150 s)
        set_time_limit(150);

        $openAi    = new OpenAiClient();
        $anthropic = new AnthropicClient();

        if (!$openAi->isAvailable() && !$anthropic->isAvailable()) {
            throw new RuntimeException(
                'Nenhum provedor de IA configurado. Defina OPENAI_API_KEY ou ANTHROPIC_API_KEY no servidor.'
            );
        }

        // Mark as processing
        $this->repository->upsert($mapId, ['status' => 'processing']);

        try {
            // ── Text generation ───────────────────────────────────────────────
            $systemPrompt = AiPromptBuilder::systemPrompt();
            $userPrompt   = AiPromptBuilder::userPrompt($map);
            $textResponse = null;
            $modelText    = null;

            if ($openAi->isAvailable()) {
                try {
                    $textResponse = $openAi->chat($systemPrompt, $userPrompt);
                    $modelText    = $openAi->getTextModel();
                } catch (Throwable) {
                    // Fall through to Anthropic
                }
            }

            if ($textResponse === null && $anthropic->isAvailable()) {
                $textResponse = $anthropic->chat($systemPrompt, $userPrompt);
                $modelText    = $anthropic->getModel();
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

            // ── Image generation (optional — graceful degradation) ─────────────
            $imagePath  = null;
            $modelImage = null;

            if ($imagePrompt !== '' && $openAi->isAvailable()) {
                try {
                    $b64Image   = $openAi->generateImage($imagePrompt);
                    $imagePath  = $this->saveImage($mapId, $b64Image);
                    $modelImage = $openAi->getImageModel();
                } catch (Throwable) {
                    // Image is optional — continue without it
                }
            }

            // ── Persist ───────────────────────────────────────────────────────
            $this->repository->upsert($mapId, [
                'professional_analysis' => is_array($professionalAnalysis)
                    ? json_encode($professionalAnalysis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null,
                'patient_report'  => $patientReport !== '' ? $patientReport : null,
                'image_path'      => $imagePath,
                'image_prompt'    => $imagePrompt !== '' ? $imagePrompt : null,
                'model_text'      => $modelText,
                'model_image'     => $modelImage,
                'status'          => 'completed',
                'error_message'   => null,
                'generated_at'    => date('Y-m-d H:i:s'),
            ]);

        } catch (Throwable $exception) {
            $this->repository->upsert($mapId, [
                'status'        => 'failed',
                'error_message' => $exception->getMessage(),
                'generated_at'  => null,
            ]);

            throw $exception;
        }

        return $this->findAnalysis($mapId, $ownerUserId) ?? [];
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
        return dirname(__DIR__