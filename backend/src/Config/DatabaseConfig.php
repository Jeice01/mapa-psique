<?php

declare(strict_types=1);

namespace App\Config;

use App\Support\Env;
use RuntimeException;

final class DatabaseConfig
{
    /**
     * @return array{dsn:string, username:string, password:string, options:array<int, mixed>}
     */
    public static function mysql(): array
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $database = self::firstConfigured('DB_DATABASE', 'DB_NAME');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        if ($database === '') {
            throw new RuntimeException('Database name is not configured');
        }

        return [
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
            'username' => self::firstConfigured('DB_USERNAME', 'DB_USER'),
            'password' => self::firstConfigured('DB_PASSWORD', 'DB_PASS'),
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }

    private static function firstConfigured(string $primary, string $fallback): string
    {
        $value = Env::get($primary, '');

        if ($value !== null && trim($value) !== '') {
            return trim($value);
        }

        return trim((string) Env::get($fallback, ''));
    }
}
