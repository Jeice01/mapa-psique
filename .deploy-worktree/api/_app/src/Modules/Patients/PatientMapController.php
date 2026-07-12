<?php

declare(strict_types=1);

namespace App\Modules\Patients;

use App\Database\Repositories\MapRepository;
use App\Database\Repositories\PatientRepository;
use App\Http\JsonResponse;
use App\Http\ResponseInterface;
use App\Modules\Maps\MapImageService;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Security\Csrf;
use InvalidArgumentException;
use RuntimeException;

final class PatientMapController
{
    private const ALLOWED_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];
    private const MAX_BYTES = 10 * 1024 * 1024; // 10 MB

    /**
     * POST /api/patients/{id}/create-map
     * Multipart: map_image (file) + map_notes (text, optional)
     *
     * Cria um rascunho de mapa vinculado ao paciente e salva a imagem do mapa físico.
     * O canvas é gerado em seguida pelo endpoint POST /api/maps/{map_id}/generate-canvas.
     */
    public function createMap(string $id): ResponseInterface
    {
        $session = AccessGuard::require(['profissional']);
        if ($session instanceof JsonResponse) {
            return $session;
        }

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Token CSRF inválido.', 419);
        }

        // 1. Verificar que o paciente pertence ao terapeuta
        $patientRepo = new PatientRepository();
        $patient = $patientRepo->findByIdAndOwner($id, $session['user_id']);
        if ($patient === null) {
            return JsonResponse::error('Paciente não encontrado.', 404);
        }

        // 2. Validar arquivo enviado
        $file = $_FILES['map_image'] ?? null;

        if ($file === null || !isset($file['tmp_name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $code = is_array($file) ? ($file['error'] ?? UPLOAD_ERR_NO_FILE) : UPLOAD_ERR_NO_FILE;
            return JsonResponse::error('Nenhum arquivo enviado ou erro no upload (código ' . $code . ').', 422);
        }

        $tmpPath = (string) $file['tmp_name'];
        $size    = (int) ($file['size'] ?? 0);

        if ($size > self::MAX_BYTES) {
            return JsonResponse::error('O arquivo excede o limite de 10 MB.', 422);
        }

        $mime = mime_content_type($tmpPath) ?: '';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            return JsonResponse::error(
                'Formato não suportado. Envie uma imagem (JPEG, PNG, WebP) ou PDF do mapa físico.',
                422
            );
        }

        // 3. Observações específicas do mapa (opcional)
        $mapNotes = trim((string) ($_POST['map_notes'] ?? ''));

        // 4. Criar registro do mapa
        $patientName = (string) ($patient['name'] ?? 'Paciente');
        $mapRepo = new MapRepository();
        $mapId   = $mapRepo->create([
            'owner_user_id' => $session['user_id'],
            'patient_id'    => $id,
            'title'         => "Mapa de {$patientName}",
            'reason'        => $mapNotes !== '' ? $mapNotes : null,
            'status'        => 'draft',
        ]);

        // 5. Salvar imagem no disco
        try {
            (new MapImageService())->saveImage($mapId, $session['user_id'], $tmpPath, $mime);
        } catch (InvalidArgumentException $e) {
            return JsonResponse::error($e->getMessage(), 404);
        } catch (RuntimeException $e) {
            return JsonResponse::error('Erro ao salvar o arquivo: ' . $e->getMessage(), 500);
        }

        Audit::record('patient.map.created', $session['user_id'], 'maps', $mapId, [
            'patient_id' => $id,
        ]);

        return JsonResponse::ok([
            'success' => true,
            'data'    => [
                'map_id'      => $mapId,
                'patient_id'  => $id,
                'patient_name' => $patientName,
            ],
        ]);
    }
}
