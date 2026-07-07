<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\DatabaseConfig;
use PDO;

final class Connection
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = DatabaseConfig::mysql();

        self::$pdo = new PDO(
            $config['dsn'],
            $config['username'],
            $config['password'],
            $config['options']
        );

        return self::$pdo;
    }
}
