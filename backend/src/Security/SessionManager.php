<?php

declare(strict_types=1);

namespace App\Security;

use App\Support\Env;

final class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            self::expireIfNeeded();

            return;
        }

        $secure = Env::get('APP_ENV', 'local') === 'production';

        session_name(Env::get('SESSION_COOKIE_NAME', 'mapa_psique_session') ?? 'mapa_psique_session');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::expireIfNeeded();
    }

    /**
     * @param array{id:string, role:string} $user
     */
    public static function login(array $user): void
    {
        self::start();
        session_regenerate_id(true);

        $now = time();
        $_SESSION['auth'] = [
            'user_id' => $user['id'],
            'role' => $user['role'],
            'authenticated_at' => $now,
            'expires_at' => $now + self::lifetimeSeconds(),
        ];
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            self::clearCookie();
        }

        session_destroy();
    }

    /**
     * @return array{user_id:string, role:string, authenticated_at:int, expires_at:int}|null
     */
    public static function current(): ?array
    {
        self::start();
        $auth = $_SESSION['auth'] ?? null;

        if (!is_array($auth) || empty($auth['user_id']) || empty($auth['role'])) {
            return null;
        }

        return [
            'user_id' => (string) $auth['user_id'],
            'role' => (string) $auth['role'],
            'authenticated_at' => (int) $auth['authenticated_at'],
            'expires_at' => (int) $auth['expires_at'],
        ];
    }

    public static function isAuthenticated(): bool
    {
        return self::current() !== null;
    }

    private static function expireIfNeeded(): void
    {
        $auth = $_SESSION['auth'] ?? null;

        if (is_array($auth) && isset($auth['expires_at']) && time() > (int) $auth['expires_at']) {
            $_SESSION = [];
            self::clearCookie();
            session_destroy();
        }
    }

    private static function lifetimeSeconds(): int
    {
        return max(1, (int) (Env::get('SESSION_LIFETIME_MINUTES', '120') ?? '120')) * 60;
    }

    private static function clearCookie(): void
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) $params['secure'],
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
