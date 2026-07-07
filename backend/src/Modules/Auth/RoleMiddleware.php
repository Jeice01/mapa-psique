<?php

declare(strict_types=1);

namespace App\Modules\Auth;

use App\Database\Repositories\AuditLogRepository;
use App\Http\JsonResponse;
use Throwable;

final class RoleMiddleware
{
    /**
     * @param list<string> $roles
     * @param array{user_id:string, role:string} $session
     */
    public function requireRole(array $session, array $roles): ?JsonResponse
    {
        if (in_array($session['role'], $roles, true)) {
            return null;
        }

        try {
            (new AuditLogRepository())->create([
                'actor_user_id' => $session['user_id'],
                'severity' => 'WARN',
                'action' => 'auth.forbidden',
                'entity_type' => 'auth',
                'metadata_json' => [
                    'route' => $_SERVER['REQUEST_URI'] ?? '',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                    'status_code' => 403,
                ],
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        } catch (Throwable) {
        }

        return JsonResponse::error('Forbidden', 403);
    }
}
