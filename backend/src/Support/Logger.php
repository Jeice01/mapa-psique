<?php

declare(strict_types=1);

namespace App\Support;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $event, array $context = []): void
    {
        self::write('info', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function warning(string $event, array $context = []): void
    {
        self::write('warning', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $event, array $context): void
    {
        $directory = dirname(__DIR__, 2) . '/storage/logs';

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $entry = [
            'timestamp' => gmdate(DATE_ATOM),
            'level' => $level,
            'event' => $event,
            'context' => self::redact($context),
        ];

        file_put_contents(
            "{$directory}/app.log",
            json_encode($entry, JSON_THROW_ON_ERROR) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private static function redact(array $context): array
    {
        $blockedKeys = ['password', 'senha', 'clinical_content', 'prompt', 'ai_response'];

        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), $blockedKeys, true)) {
                $context[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
