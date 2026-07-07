<?php

declare(strict_types=1);

namespace App\Modules\Patients;

use App\Http\JsonResponse;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use App\Modules\Shared\Request;
use App\Security\Csrf;
use InvalidArgumentException;
use Throwable;

final class PatientController
{
    public function index(): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $result = (new PatientService())->list(
                $session['user_id'],
                Request::queryString('q'),
                Request::queryString('status'),
                Request::queryInt('page', 1),
                Request::queryInt('per_page', 10)
            );
            Audit::record('patient.listed', $session['user_id'], 'patients', null, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok'] + $result);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Invalid filters', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not list patients', 500);
        }
    }

    public function create(): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $patient = (new PatientService())->create($session['user_id'], Request::json());
            Audit::record('patient.created', $session['user_id'], 'patients', (string) $patient['id'], ['status_code' => 200]);

            return JsonResponse::ok([
                'status' => 'ok',
                'patient' => $this->compactPatient($patient),
            ]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Could not create patient', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not create patient', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $session = AccessGuard::require(['profissional']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $patient = (new PatientService())->find($id, $session['user_id']);
            Audit::record('patient.viewed', $session['user_id'], 'patients', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok', 'patient' => $patient]);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Patient not found', 404);
        } catch (Throwable) {
            return JsonResponse::error('Could not load patient', 500);
        }
    }

    public function update(string $id): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $patient = (new PatientService())->update($id, $session['user_id'], Request::json());
            Audit::record('patient.updated', $session['user_id'], 'patients', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok', 'patient' => $patient]);
        } catch (InvalidArgumentException $exception) {
            if ($exception->getMessage() === 'Patient not found') { 
                return JsonResponse::error('Patient not found', 404);
            }

            return JsonResponse::error('Invalid patient data', 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not update patient', 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        $session = $this->guardMutable();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            (new PatientService())->archive($id, $session['user_id'], $session['user_id']);
            Audit::record('patient.archived', $session['user_id'], 'patients', $id, ['status_code' => 200]);

            return JsonResponse::ok(['status' => 'ok']);
        } catch (InvalidArgumentException) {
            return JsonResponse::error('Patient not found', 404);
        } catch (Throwable) {
            return JsonResponse::error('Could not archive patient', 500);
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
     * @param array<string, mixed> $patient
     * @return array<string, mixed>
     */
    private function compactPatient(array $patient): array
    {
        return [
            'id' => $patient['id'] ?? '',
            'name' => $patient['name'] ?? '',
            'internal_code' => $patient['internal_code'] ?? null,
            'age' => $patient['age'] ?? null,
            'status' => $patient['status'] ?? '',
        ];
    }
}
