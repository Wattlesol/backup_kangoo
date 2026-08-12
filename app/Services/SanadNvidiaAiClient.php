<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SanadNvidiaAiClient
{
    public function chat(array $messages, array $options = []): array
    {
        $payload = [
            'model' => $options['model'] ?? config('sanad.ai.model'),
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? config('sanad.ai.temperature', 0.2)),
            'max_tokens' => (int) ($options['max_tokens'] ?? config('sanad.ai.max_tokens', 700)),
        ];

        $response = $this->http()->post($this->url('/chat/completions'), $payload);

        if (!$response->successful()) {
            throw new RuntimeException('NVIDIA chat request failed: ' . $response->body());
        }

        $data = $response->json();

        return [
            'content' => data_get($data, 'choices.0.message.content', ''),
            'raw' => $data,
        ];
    }

    public function embed(string $text): ?array
    {
        $model = config('sanad.ai.embedding_model');
        if (!$model || !config('sanad.ai.api_key')) {
            return null;
        }

        $response = $this->http()->post($this->url('/embeddings'), [
            'model' => $model,
            'input' => $text,
        ]);

        if (!$response->successful()) {
            return null;
        }

        return data_get($response->json(), 'data.0.embedding');
    }

    private function http()
    {
        $apiKey = config('sanad.ai.api_key');
        if (!$apiKey) {
            throw new RuntimeException('NVIDIA_API_KEY is not configured.');
        }

        return Http::timeout(45)
            ->acceptJson()
            ->withToken($apiKey);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('sanad.ai.base_url'), '/') . '/' . ltrim($path, '/');
    }
}
