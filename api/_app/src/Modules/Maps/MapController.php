<?php

declare(strict_types=1);

namespace App\Modules\Maps;

use App\Http\JsonResponse;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Modules\Shared\Request;
use App\Security\Csrf;
use InvalidArgumentException;
use Throwable;

final class MapController
{
    public function index(): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $result = (new MapService())->list(
                $session['user_id'],
                Request::queryString('q'),
                Request::queryString('status'),
                Request::queryString('patient_id'),
                Request::queryInt('page', 1),
                Request::queryInt('per_page', 10)
            );
            Audit::record('map.listed', $session['user_id'], 'maps', null, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok'] + $result);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Invalid filters', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not list maps', 500);
        }
    }

    public function create(): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $map = (new MapService())->create($session['user_id'], Request::json());
            Audit::record('map.created', $session['user_id'], 'maps', (string) $map['id'], ['status_code' => 200]);

            return JsonResponse::ok([
                'status' => 'ok',
                'map' => $this->compactMap($map),
            ]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Could not create map', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not create map', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $map = (new MapService())->find($id, $session['user_id']);
            Audit::record('map.viewed', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok', 'map' => $map]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Map not found', 404);
        } catch (Throwable) {
            return JsonResponse::error('Could not load map', 500);
        }
    }

    public function canvasVersions(string $id): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $versions = (new MapService())->listCanvasVersions($id, $session['user_id']);
            Audit::record('map.canvas_versions_listed', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok([
                'success' => true,
                'data' => $versions,
            ]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Map not found', 404);
        } catch (Throwable) {
            return JsonResponse::error('Could not list canvas versions', 500);
        }
    }

    public function update(string $id): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $map = (new MapService())->update($id, $session['user_id'], Request::json());
            Audit::record('map.updated', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok', 'map' => $map]);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Map not found') {
                return JsonResponse::error('Map not found', 404);
            }
            return JsonResponse::error('Invalid map data', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not update map', 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            (new MapService())->archive($id, $session['user_id'], $session['user_id']);
            Audit::record('map.archived', $session['user_id'], 'maps', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok']);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Map not found', 404);
        } catch (Throwable) {
            return JsonResponse::error('Could not archive map', 500);
        }
    }

    /**
     * @return array{user_id:string, role:string, authenticated_at:int, expires_at:int}|JsonResponse
     */
    private function guardMutable(): array|JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        return $session;
    }

    /**
     * @param array<string, mixed> $map
     * @return array<string, mixed>
     */
    private function compactMap(array $map): array
    {
        return [
            'id' => $map['id'] ?? '',
            'title' => $map['title'] ?? '',
            'patient_id' => $map['patient_id'] ?? null,
            'status' => $map['status'] ?? '',
        ];
    }
}
