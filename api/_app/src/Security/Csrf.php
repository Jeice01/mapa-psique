<?php

declare(strict_types=1);

namespace App\Security;

use App\Support\Env;

final class Csrf
{
    public static function generate(): string
    {
        SessionManager::start();

        $token = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token'] = $token;

        return $token;
    }

    public static function validate(?string $token): bool
    {
        if (Env::get('CSRF_ENABLED', 'true') === 'false' && Env::get('APP_ENV', 'local') === 'local') {
            return true;
        }

        SessionManager::start();

        return is_string($token)
            && isset($_SESSION['_csrf_token'])
            && hash_equals((string) $_SESSION['_csrf_token'], $token);
    }

    public static function tokenFromRequest(): ?string
    {
        $header = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return is_string($header) ? $header : null;
    }
}
