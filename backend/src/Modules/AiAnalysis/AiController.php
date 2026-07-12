<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

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
     * Triggers (or regenerates) the AI analysis for the given map.
     */
    public function generate(string $id): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        try {
            $analysis = (new AiService())->generate($id, $session['user_id']);

            Audit::record(
                'map.ai_analysis_generated',
                $session['user_id'],
                'maps',
                $id,
                ['status_code' => 200, 'model_text' => $analysis['model_text'] ?? null]
            );

            return JsonResponse::ok([
                'success' => true,
                'message' => 'Análise gerada com sucesso.',
                'data'    => $analysis,
            ]);
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::error($exception->getMessage(), 400);
        } catch (RuntimeException $runtimeEx) {
            $prev = $runtimeEx->getPrevious();
            $detail = $prev
                ? get_class($prev) . ': ' . $prev->getMessage()
                : $runtimeEx->getMessage();
            return JsonResponse::error('Erro: ' . $detail, 503);
        } catch (Throwable) {
            return JsonResponse::error('Erro interno ao gerar a análise.', 500);
        }
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
