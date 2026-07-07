<?php

declare(strict_types=1);

namespace App\Modules\Consents;

use App\Http\JsonResponse;
use App\Modules\Auth\AuthMiddleware;
use App\Security\Csrf;
use InvalidArgumentException;
use Throwable;

final class ConsentController
{
    private ConsentService $service;

    public function __construct()
    {
        $this->service = new ConsentService();
    }

    public function active(): JsonResponse
    {
        try {
            return JsonResponse::ok($this->service->activeTerm());
        } catch (Throwable) {
            return JsonResponse::error('No active consent term found', 404);
        }
    }

    public function accept(): JsonResponse
    {
        if (!Csrf::validate(Csrf::tokenFromRequest())) {
            return JsonResponse::error('Invalid CSRF token', 419);
        }

        $session = (new AuthMiddleware())->requireAuth();

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $this->service->accept($session['user_id'], $this->jsonBody());

            return JsonResponse::ok(['status' => 'ok']);
        } catch (Throwable) {
            return JsonResponse::error('Could not accept consent', 400);
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
}
