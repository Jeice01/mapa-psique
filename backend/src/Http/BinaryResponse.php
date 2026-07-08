<?php

declare(strict_types=1);

namespace App\Http;

final class BinaryResponse implements ResponseInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $content,
        private readonly string $contentType,
        private readonly array $headers = [],
        private readonly int $status = 200
    ) {
    }

    /**
     * @param array<string, string> $headers
     */
    public static function download(string $content, string $contentType, string $filename, array $headers = []): self
    {
        return new self(
            $content,
            $contentType,
            [
                'Content-Disposition' => 'attachment; filename="' . self::sanitizeFilename($filename) . '"',
                'Content-Length' => (string) strlen($content),
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ] + $headers
        );
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: ' . $this->contentType);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo $this->content;
    }

    private static function sanitizeFilename(string $filename): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename) ?? 'download.pdf';
        $safe = trim($safe, '.-');

        return $safe === '' ? 'download.pdf' : $safe;
    }
}
