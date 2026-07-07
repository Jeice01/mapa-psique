<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly array $payload,
        private readonly int $status = 200
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function ok(array $payload): self
    {
        return new self($payload);
    }

    public static function error(string $message, int $status): self
    {
        return new self(['status' => 'error', 'message' => $message], $status);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($this->payload, JSON_THROW_ON_ERROR);
    }
}
