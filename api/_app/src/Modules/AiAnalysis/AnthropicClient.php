<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Support\Env;
use RuntimeException;

final class AnthropicClient
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = (string) (Env::get('ANTHROPIC_API_KEY') ?? '');
        $this->model  = (string) (Env::get('ANTHROPIC_TEXT_MODEL') ?? 'claude-opus-4-8');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Call the Anthropic Messages API and return the text content.
     * Claude may wrap JSON in markdown fences — this method strips them.
     */
    public function chat(string $systemPrompt, string $userMessage): string
    {
        $payload = json_encode([
            'model'      => $this->model,
            'max_tokens' => 8000,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => $userMessage],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Failed to encode Anthropic request payload.');
        }

        $response = $this->post('https://api.anthropic.com/v1/messages', $payload);
        $data     = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from Anthropic.');
        }

        if (isset($data['error'])) {
            $errorMessage = (string) ($data['error']['message'] ?? 'unknown Anthropic error');
            throw new RuntimeException("Anthropic error: {$errorMessage}");
        }

        $content = $data['content'][0]['text'] ?? null;

        if (!is_string($content)) {
            throw new RuntimeException('No content in Anthropic response.');
        }

        // Strip possible markdown JSON fences that Claude sometimes adds
        $content = (string) preg_replace('/^```json\s*/i', '', trim($content));
        $content = (string) preg_replace('/\s*```\s*$/', '', $content);

        return trim($content);
    }

    private function post(string $url, string $payload): string
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL for Anthropic.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("cURL error calling Anthropic: {$error}");
        }

        return (string) $response;
    }
}
