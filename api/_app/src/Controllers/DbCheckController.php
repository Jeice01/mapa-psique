<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Http\JsonResponse;
use App\Support\Logger;
use Throwable;

final class DbCheckController
{
    public function show(): JsonResponse
    {
        try {
            Connection::pdo()->query('SELECT 1');

            return JsonResponse::ok([
                'status' => 'ok',
                'database' => 'connected',
            ]);
        } catch (Throwable $exception) {
            Logger::warning('database_check_failed', [
                'exception' => $exception::class,
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Database connection failed',
            ], 503);
        }
    }
}
