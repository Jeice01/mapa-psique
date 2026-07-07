<?php

declare(strict_types=1);

namespace App\Modules\Shared;

final class Request
{
    /**
     * @return array<string, mixed>
     */
    public static function json(): array
    {
        $decoded = json_decode((string) file_get_contents('php://input'), true);

        return is_array($decoded) ? $decoded : [];
    }

    public static function queryString(string $key): ?string
    {
        $value = $_GET[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public static function queryInt(string $key, int $default): int
    {
        $value = $_GET[$key] ?? null;

        return is_numeric($value) ? (int) $value : $default;
    }
}
