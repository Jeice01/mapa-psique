<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Http\JsonResponse;

final class HealthController
{
    public function show(): JsonResponse
    {
        return JsonResponse::ok([
            'status' => 'ok',
            'service' => 'mapa-psique-api',
        ]);
    }
}
