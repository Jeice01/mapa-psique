<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Http\JsonResponse;
use App\Modules\Shared\AccessGuard;
use App\Modules\Shared\Audit;
use Throwable;

final class DashboardController
{
    public function summary(): JsonResponse
    {
        $session = AccessGuard::require(['profissional', 'administrador']);

        if ($session instanceof JsonResponse) {
            return $session;
        }

        try {
            $summary = (new DashboardService())->summary($session['user_id']);
            Audit::record('dashboard.summary.viewed', $session['user_id'], 'dashboard', null, ['status_code' => 200]);

            return JsonResponse::ok([
                'status' => 'ok',
                'summary' => $summary,
            ]);
        } catch (Throwable) {
            return JsonResponse::error('Could not load dashboard summary', 500);
        }
    }
}
