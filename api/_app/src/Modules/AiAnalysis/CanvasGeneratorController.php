<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Http\JsonResponse;
use App\Http\ResponseInterface;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Security\Csrf;
use InvalidArgumentException;
use RuntimeException;

final class CanvasGeneratorController
{
    /**
     * POST /api/maps/{id}/generate-canvas
     * Lê a imagem do mapa via GPT-4o vision e retorna os 9 campos do canvas preenchidos.
     */
    public function generate(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        Csrf::validateHeader();

        try {
            $canvas = (new AiService())->generateCanvas($id, $session['user_id']);

            Audit::record('map.canvas.generated', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok([
                'success' => true,
                'data'    => $canvas,
            ]);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        } catch (RuntimeException $e) {
            return JsonResponse::error($e->getMessage(), 500);
        }
    }
}
