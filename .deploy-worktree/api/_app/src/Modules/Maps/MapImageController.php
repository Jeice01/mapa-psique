<?php

declare(strict_types=1);

namespace App\Modules\Maps;

use App\Http\BinaryResponse;
use App\Http\JsonResponse;
use App\Http\ResponseInterface;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Security\Csrf;
use RuntimeException;

final class MapImageController
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_BYTES     = 10 * 1024 * 1024; // 10 MB

    /**
     * POST /api/maps/{id}/image
     * Upload da foto do Mapa da Psiquê (multipart/form-data, campo "map_image").
     */
    public function upload(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Token CSRF inválido.', 419);
        }

        $service = new MapImageService();

        try {
            $file = $_FILES['map_image'] ?? null;

            if ($file === null || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
                return JsonResponse::error('Nenhum arquivo enviado ou erro no upload (código ' . $code . ').', 422);
            }

            if ($file['size'] > self::MAX_BYTES) {
                return JsonResponse::error('A imagem não pode ultrapassar 10 MB.', 422);
            }

            $mime = mime_content_type($file['tmp_name']) ?: '';
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                return JsonResponse::error('Formato não suportado. Use JPG, PNG, WebP ou GIF.', 422);
            }

            $path = $service->saveImage($id, $session['user_id'], $file['tmp_name'], $mime);

            Audit::record('map.image.uploaded', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok([
                'success'    => true,
                'image_path' => $path,
            ]);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        } catch (RuntimeException $e) {
            return JsonResponse::error('Erro ao salvar imagem: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/maps/{id}/image
     * Serve a foto do mapa para o frontend.
     */
    public function show(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        $service = new MapImageService();

        try {
            [$content, $mime] = $service->getImageContent($id, $session['user_id']);
            return BinaryResponse::download($content, $mime, 'mapa-psique.jpg');
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        }
    }
}
