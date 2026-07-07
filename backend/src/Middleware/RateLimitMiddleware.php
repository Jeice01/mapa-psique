<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\JsonResponse;
use App\Support\Logger;
use Throwable;

final class RateLimitMiddleware
{
    private const WINDOW_SECONDS = 60;
    private const MAX_REQUESTS = 120;

    public function handle(string $ip, string $route, int $maxRequests = self::MAX_REQUESTS): void
    {
        try {
            $directory = dirname(__DIR__, 2) . '/storage/temp/rate-limit';

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            $key = hash('sha256', $ip . '|' . $route);
            $file = "{$directory}/{$key}.json";
            $now = time();
            $entry = ['window_started_at' => $now, 'count' => 0];

            if (is_file($file)) {
                $decoded = json_decode((string) file_get_contents($file), true);

                if (is_array($decoded)) {
                    $entry = array_merge($entry, $decoded);
                }
            }

            if (($now - (int) $entry['window_started_at']) >= self::WINDOW_SECONDS) {
                $entry = ['window_started_at' => $now, 'count' => 0];
            }

            $entry['count'] = (int) $entry['count'] + 1;
            file_put_contents($file, json_encode($entry, JSON_THROW_ON_ERROR), LOCK_EX);

            if ($entry['count'] > $maxRequests) {
                Logger::warning('rate_limit.exceeded', [
                    'route' => $route,
                    'ip_hash' => hash('sha256', $ip),
                ]);

                (JsonResponse::error('Too many requests', 429))->send();
                exit;
            }
        } catch (Throwable $exception) {
            Logger::warning('rate_limit.failed', ['exception' => $exception::class]);
        }
    }
}
