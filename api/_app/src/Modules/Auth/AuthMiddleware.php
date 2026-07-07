<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Database\Repositories\AuditLogRepository;
use App\Database\Repositories\UserRepository;
use App\Http\JsonResponse;
use App\Security\SessionManager;
use Throwable;

final class AuthMiddleware
{
    /**
     * @return array{user_id:string, role:string, authenticated_at:int, expires_at:int}|JsonResponse
     */
    public function requireAuth(): array|JsonResponse
    {
        $session = SessionManager::current();

        if ($session === null) {
            $this->auditUnauthorized();

            return JsonResponse::error('Unauthorized', 401);
        }

        try {
            $user = (new UserRepository())->findById($session['user_id']);

            if ($user === null || (string) ($user['status'] ?? '') !== 'active' || !empty($user['deleted_at'])) {
                SessionManager::logout();
                $this->auditUnauthorized();

                return JsonResponse::error('Unauthorized', 401);
            }
        } catch (Throwable) {
            SessionManager::logout();
            $this->auditUnauthorized();

            return JsonResponse::error('Unauthorized', 401);
        }

        return $session;
    }

    private function auditUnauthorized(): void
    {
        try {
            (new AuditLogRepository())->create([
                'severity' => 'WARN',
                'action' => 'auth.unauthorized',
                'entity_type' => 'auth',
                'metadata_json' => [
                    'route' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'status_code' => 401,
                ],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }
    }
}
