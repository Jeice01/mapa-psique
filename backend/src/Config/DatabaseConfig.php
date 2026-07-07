<?php

declare(strict_types=1);

namespace App\Config;

use App\Support\Env;

final class DatabaseConfig
{
    /**
     * @return array{dsn:string, username:string, password:string, options:array<int, mixed>}
     */
    public static function mysql(): array
    {
        $host = Env::get('DB_HOST', 'localhost');
        $port = Env::get('DB_PORT', '3306');
        $database = Env::get('DB_DATABASE', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        return [
            'dsn' => "mysql:host={$host};port={$port};dbname={$database};charset={$charset}",
            'username' => Env::get('DB_USERNAME', ''),
            'password' => Env::get('DB_PASSWORD', ''),
            'options' => [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }
}
