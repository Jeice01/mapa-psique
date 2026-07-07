<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Env;

final class CorsMiddleware
{
    public function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '' && in_array($origin, $this->allowedOrigins(), true)) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }

        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');
    }

    public function isPreflight(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS';
    }

    /**
     * @return list<string>
     */
    private function allowedOrigins(): array
    {
        $raw = Env::get('APP_ALLOWED_ORIGINS', 'http://localhost:5173') ?? '';

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
