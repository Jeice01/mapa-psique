<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Database\Repositories\AiAnalysisRepository;
use App\Http\BinaryResponse;
use App\Http\JsonResponse;
use App\Http\ResponseInterface;
use App\Modules\Shared\AccessGuard;
use App\Security\Csrf;
use InvalidArgumentException;
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
     * Validates synchronously, enqueues the analysis, and returns immediately.
     * A cron worker (bin/worker.php) picks up 'pending' items and calls AiService::generate().
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

        // Enqueue — cron worker processes asynchronously
        (new AiAnalysisRepository())->upsert($id, ['status' => 'pending']);

        return JsonResponse::ok([
            'success' => true,
            'message' => 'Análise enfileirada.',
            'data'    => ['status' => 'pending'],
        ]);
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
