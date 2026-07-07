<?php

declare(strict_types=1);

namespace App\Security;

use InvalidArgumentException;

final class InputSanitizer
{
    public static function sanitizeString(?string $value): string
    {
        return trim(strip_tags((string) $value));
    }

    public static function normalizeEmail(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    public static function maxLength(string $value, int $maxLength): string
    {
        return substr($value, 0, $maxLength);
    }

    public static function required(?string $value, string $field): string
    {
        $sanitized = self::sanitizeString($value);

        if ($sanitized === '') {
            throw new InvalidArgumentException("{$field} is required");
        }

        return $sanitized;
    }
}
