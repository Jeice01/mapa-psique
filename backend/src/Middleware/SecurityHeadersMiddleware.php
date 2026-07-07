<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Env;

final class SecurityHeadersMiddleware
{
    public function handle(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        if (Env::get('APP_ENV', 'local') === 'production') {
            header("Content-Security-Policy: default-src 'self'; frame-ancestors 'none'; base-uri 'self'");
        } else {
            header("Content-Security-Policy: default-src 'self' http://localhost:* http://127.0.0.1:*; frame-ancestors 'none'; base-uri 'self'");
        }
    }
}
