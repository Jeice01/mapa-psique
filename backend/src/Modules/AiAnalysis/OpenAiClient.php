<?php

declare(strict_types=1);

namespace App\Modules\AiAnalysis;

use App\Support\Env;
use RuntimeException;

final class OpenAiClient
{
    private string $apiKey;
    private string $textModel;
    private string $imageModel;

    public function __construct()
    {
        $this->apiKey     = (string) (Env::get('OPENAI_API_KEY') ?? '');
        $this->textModel  = (string) (Env::get('OPENAI_TEXT_MODEL') ?? 'gpt-4o');
        $this->imageModel = (string) (Env::get('OPENAI_IMAGE_MODEL') ?? 'dall-e-3');
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    public function getTextModel(): string
    {
        return $this->textModel;
    }

    public function getImageModel(): string
    {
        return $this->imageModel;
    }

    /**
     * Call the chat completions endpoint and request a JSON object response.
     */
    public function chat(string $systemPrompt, string $userMessage): string
    {
        $payload = json_encode([
            'model'           => $this->textModel,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userMessage],
            ],
            'max_tokens'  => 4096,
            'temperature' => 0.7,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Failed to encode OpenAI request payload.');
        }

        $response = $this->post('https://api.openai.com/v1/chat/completions', $payload);
        $data     = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from OpenAI.');
        }

        if (isset($data['error'])) {
            $errorMessage = (string) ($data['error']['message'] ?? 'unknown OpenAI error');
            throw new RuntimeException("OpenAI error: {$errorMessage}");
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!is_string($content)) {
            throw new RuntimeException('No content in OpenAI chat response.');
        }

        return $content;
    }

    /**
     * Call chat completions with a base64 image + text (GPT-4o vision).
     * Returns the raw text content (may be JSON or plain text).
     */
    public function chatWithVision(string $systemPrompt, string $userText, string $imageBase64, string $mimeType): string
    {
        $payload = json_encode([
            'model'           => $this->textModel,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                ['role' => 'system', 'content' => $systemPrompt],
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url'    => "data:{$mimeType};base64,{$imageBase64}",
                                'detail' => 'high',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $userText,
                        ],
                    ],
                ],
            ],
            'max_tokens'  => 4096,
            'temperature' => 0.7,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Failed to encode OpenAI vision request payload.');
        }

        $response = $this->post('https://api.openai.com/v1/chat/completions', $payload);
        $data     = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from OpenAI vision.');
        }

        if (isset($data['error'])) {
            $errorMessage = (string) ($data['error']['message'] ?? 'unknown OpenAI error');
            throw new RuntimeException("OpenAI vision error: {$errorMessage}");
        }

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!is_string($content)) {
            throw new RuntimeException('No content in OpenAI vision response.');
        }

        return $content;
    }

    /**
     * Generate an image and return it as base64.
     * Supports both DALL-E (dall-e-2, dall-e-3) and gpt-image-* models.
     */
    public function generateImage(string $prompt): string
    {
        $isDallE = str_starts_with($this->imageModel, 'dall-e');

        $params = [
            'model'   => $this->imageModel,
            'prompt'  => $prompt,
            'n'       => 1,
            'size'    => '1024x1024',
            'quality' => $isDallE ? 'standard' : 'medium',
        ];

        if ($isDallE) {
            $params['response_format'] = 'b64_json';
        }

        $payload = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Failed to encode OpenAI image request payload.');
        }

        $response = $this->post('https://api.openai.com/v1/images/generations', $payload);
        $data     = json_decode($response, true);

        if (!is_array($data)) {
            throw new RuntimeException('Invalid JSON response from OpenAI Images.');
        }

        if (isset($data['error'])) {
            $errorMessage = (string) ($data['error']['message'] ?? 'unknown OpenAI image error');
            throw new RuntimeException("OpenAI Images error: {$errorMessage}");
        }

        $b64 = $data['data'][0]['b64_json'] ?? null;

        if (!is_string($b64)) {
            throw new RuntimeException('No image data in OpenAI Images response.');
        }

        return $b64;
    }

    private function post(string $url, string $payload): string
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Failed to initialize cURL for OpenAI.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("cURL error calling OpenAI: {$error}");
        }

        return (string) $response;
    }
}
