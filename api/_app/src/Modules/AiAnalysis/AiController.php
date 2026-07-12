<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Database\Repositories\AiAnalysisRepository;
use App\Http\BackgroundJobResponse;
use App\Http\BinaryResponse;
use App\Http\JsonResponse;
use App\Http\ResponseInterface;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Security\Csrf;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class AiController
{
    /**
     * GET /api/maps/{id}/analysis
     * Returns the existing AI analysis or null if not generated yet.
     */
    public function show(string $id): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $analysis = (new AiService())->findAnalysis($id, $session['user_id']);

            return JsonResponse::ok([
                'success' => true,
                'data'    => $analysis,
            ]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Mapa não encontrado.', 404);
        } catch (Throwable) {
            return JsonResponse::error('Não foi possível carregar a análise.', 500);
        }
    }

    /**
     * POST /api/maps/{id}/analysis
     * Validates synchronously, marks as processing, then generates in background
     * via fastcgi_finish_request() to bypass nginx 60 s gateway timeout.
     */
    public function generate(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        // Fast synchronous validation (no AI calls)
        try {
            (new AiService())->validateForGeneration($id, $session['user_id']);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::error($exception->getMessage(), 400);
        } catch (Throwable) {
            return JsonResponse::error('Erro ao validar o mapa.', 500);
        }

        // Mark as processing so frontend can start polling
        (new AiAnalysisRepository())->upsert($id, ['status' => 'processing']);

        $userId = $session['user_id'];
        $mapId  = $id;

        $body = (string) json_encode(
            ['success' => true, 'message' => 'Análise iniciada.', 'data' => ['status' => 'processing']],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        return new BackgroundJobResponse($body, 200, function () use ($mapId, $userId): void {
            try {
                $analysis = (new AiService())->generate($mapId, $userId);
                Audit::record(
                    'map.ai_analysis_generated',
                    $userId,
                    'maps',
                    $mapId,
                    ['status_code' => 200, 'model_text' => $analysis['model_text'] ?? null]
                );
            } catch (Throwable) {
                // AiService already persists status = 'failed' in DB
            }
        });
    }

    /**
     * GET /api/maps/{id}/analysis/image
     * Serves the AI-generated infographic PNG for the given map.
     */
    public function image(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $imagePath = (new AiService())->getImagePath($id, $session['user_id']);
            $content   = file_get_contents($imagePath);

            if ($content === false) {
                return JsonResponse::error('Imagem não encontrada.', 404);
            }

            return BinaryResponse::download($content, 'image/png', 'infografico.png');
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Imagem não encontrada.', 404);
        } catch (Throwable) {
            return JsonResponse::error('Não foi possível servir a imagem.', 500);
        }
    }
}
