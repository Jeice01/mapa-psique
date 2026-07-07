<?php

declare(strict_types=1);

namespace App\Modules\Shared;

use App\Http\JsonResponse;
use App\Modules\Auth\AuthMiddleware;
use App\Modules\Auth\AuthService;
use App\Modules\Auth\RoleMiddleware;
use Throwable;

final class AccessGuard
{
    /**
     * @param list<string> $roles
     * @return array{user_id:string, role:string, authenticated_at:int, expires_at:int}|JsonResponse
     */
    public static function require(array $roles, bool $requireConsent = true): array|JsonResponse
    {
        $session = (new AuthMiddleware())->requireAuth();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        if ($requireConsent) {
            try {
                if ((new AuthService())->requiresConsent($session['user_id'])) {
                    return JsonResponse::error('Consent required', 403);
                }
            } catch (Throwable) {
                return JsonResponse::error('Consent required', 403);
            }
        }

        $roleFailure = (new RoleMiddleware())->requireRole($session, $roles);

        if ($roleFailure instanceof JsonResponse) {
            return $roleFailure;
        }

        return $session;
    }
}
