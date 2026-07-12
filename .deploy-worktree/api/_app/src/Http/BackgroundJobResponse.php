<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Sends the HTTP response immediately via fastcgi_finish_request(),
 * then executes a background callback — bypassing nginx gateway timeout.
 */
final class BackgroundJobResponse implements ResponseInterface
{
    /** @var callable */
    private $callback;

    public function __construct(
        private readonly string $body,
        private readonly int $status,
        callable $callback
    ) {
        $this->callback = $callback;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Length: ' . strlen($this->body));
        echo $this->body;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        } elseif (ob_get_level() > 0) {
            ob_end_flush();
            flush();
        } else {
            flush();
        }

        ignore_user_abort(true);
        set_time_limit(300);

        ($this->callback)();
    }
}
