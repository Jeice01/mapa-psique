<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Database\Repositories\AuditLogRepository;
use App\Http\JsonResponse;
use App\Security\Csrf;
use InvalidArgumentException;
use Throwable;

final class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function csrfToken(): JsonResponse
    {
        $token = Csrf::generate();
        $this->auditCsrfIssued();

        return JsonResponse::ok([
            'status' => 'ok',
            'csrf_token' => $token,
        ]);
    }

    public function register(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        try {
            return JsonResponse::ok($this->service->register($this->jsonBody()));
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::error($exception->getMessage(), 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not register user', 400);
        }
    }

    public function login(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        try {
            return JsonResponse::ok($this->service->login($this->jsonBody()));
        } catch (Throwable) {
            return JsonResponse::error('Invalid email or password', 401);
        }
    }

    public function forgotPassword(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        return JsonResponse::ok($this->service->requestPasswordReset($this->jsonBody()));
    }

    public function resetPassword(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        try {
            return JsonResponse::ok($this->service->resetPassword($this->jsonBody()));
        } catch (InvalidArgumentException $exception) {
            return JsonResponse::error($exception->getMessage(), 400);
        } catch (Throwable) {
            return JsonResponse::error('Could not reset password', 400);
        }
    }

    public function logout(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        $session = (new AuthMiddleware())->requireAuth();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        $this->service->logout($session['user_id']);

        return JsonResponse::ok(['status' => 'ok']);
    }

    public function me(): JsonResponse
    {
        $session = (new AuthMiddleware())->requireAuth();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            return JsonResponse::ok($this->service->me($session['user_id']));
        } catch (Throwable) {
            return JsonResponse::error('Unauthorized', 401);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonBody(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function auditCsrfIssued(): void
    {
        try {
            (new AuditLogRepository())->create([
                'severity' => 'INFO',
                'action' => 'csrf.token.issued',
                'entity_type' => 'csrf',
                'metadata_json' => [
                    'route' => '/api/csrf-token',
                    'method' => 'GET',
                    'status_code' => 200,
                ],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }
    }
}
