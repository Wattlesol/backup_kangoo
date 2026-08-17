<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class SanadNvidiaAiClient
{
    public function chat(array $messages, array $options = []): array
    {
        $primaryModel = $options['model'] ?? config('sanad.ai.model', 'nvidia/nemotron-3.5-lightning-30b-a3b');
        $fallbackModels = $options['fallback_models'] ?? ['nvidia/nemotron-3.5-lightning-30b-a3b', 'meta/llama-3.1-70b-instruct'];
        $modelsToTry = array_unique(array_filter(array_merge([$primaryModel], $fallbackModels ?: [])));

        $payload = [
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? config('sanad.ai.temperature', 0.2)),
            'max_tokens' => (int) ($options['max_tokens'] ?? config('sanad.ai.max_tokens', 2048)),
        ];
        foreach (['top_p', 'reasoning_effort', 'chat_template_kwargs'] as $optionKey) {
            if (array_key_exists($optionKey, $options)) {
                $payload[$optionKey] = $options[$optionKey];
            }
        }

        $lastException = null;
        $response = null;

        foreach ($modelsToTry as $model) {
            $payload['model'] = $model;
            try {
                $res = $this->http()->post($this->url('/chat/completions'), $payload);
                if ($res->successful()) {
                    $response = $res;
                    break;
                }
                $lastException = new RuntimeException("Model {$model} failed: " . $res->body());
            } catch (\Throwable $e) {
                $lastException = $e;
            }
        }

        if (!$response) {
            throw $lastException ?? new RuntimeException('All NVIDIA AI models failed.');
        }

        $data = $response->json();
        $message = data_get($data, 'choices.0.message', []);
        $content = (string) data_get($message, 'content', '');
        $reasoning = (string) data_get($message, 'reasoning_content', '');

        // If content is empty or contains prompt/thinking echoes, clean it up using parseNemotronResponse
        $cleanContent = $this->parseNemotronResponse($content);

        return [
            'content' => $cleanContent !== '' ? $cleanContent : trim($content),
            'reasoning' => $reasoning,
            'raw' => $data,
        ];
    }

    private function parseNemotronResponse(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        // 1. Extract content inside <answer>...</answer> tags if present
        if (preg_match('/<answer>\s*(.*?)\s*<\/answer>/is', $content, $m)) {
            $content = trim($m[1]);
        } elseif (preg_match('/<answer>\s*(.*)/is', $content, $m)) {
            $content = trim($m[1]);
        } elseif (preg_match('/FINAL ANSWER:\s*(.*)/is', $content, $m)) {
            $content = trim($m[1]);
        } elseif (preg_match('/(?:\*\*)?(?:Final Response|Response|Answer|Summary|Conclusion)(?:\*\*)?:\s*(.*)/is', $content, $m)) {
            $content = trim($m[1]);
        }

        // 2. Split response into double-newline paragraphs
        $paragraphs = array_filter(array_map('trim', explode("\n\n", $content)));
        $cleanParagraphs = [];

        foreach ($paragraphs as $p) {
            $pClean = trim($p);

            // Skip CoT draft self-talk paragraphs
            if (preg_match('/^(?:Let\'s|Let\b|Draft|Mental|Check|Make sure|Structure|To cover|Covering|Extracting|Identifying|Synthesizing|Key points|Prerequisites:|- No raw|- Must not|- User wants)/i', $pClean)) {
                continue;
            }
            if (preg_match('/(?:extract exact|relevant bits|I need to|I should|I must|Mental Refinement|Draft -|Here\'s a thinking|\d+\.\s*\*\*\w+|\(mental refinement\))/i', $pClean)) {
                continue;
            }
            if (preg_match('/^\s*\d+\.\s*\*\*(?:Draft|Mental|Analyze|Identify|Extract|Synthesize|Refinement)/i', $pClean)) {
                continue;
            }

            $cleanParagraphs[] = $pClean;
        }

        if (!empty($cleanParagraphs)) {
            return trim(implode("\n\n", $cleanParagraphs));
        }

        return $content;
    }

    public function embed(string $text, string $inputType = 'query'): ?array
    {
        $model = config('sanad.ai.embedding_model');
        $apiKey = config('sanad.ai.api_key');
        if (!$model || !$apiKey) {
            return null;
        }

        try {
            $response = Http::timeout(3)
                ->acceptJson()
                ->withToken($apiKey)
                ->post($this->url('/embeddings'), [
                    'model' => $model,
                    'input' => $text,
                    'input_type' => $inputType,
                    'truncate' => 'END',
                ]);

            if (!$response->successful()) {
                return null;
            }

            return data_get($response->json(), 'data.0.embedding');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function http()
    {
        $apiKey = config('sanad.ai.api_key');
        if (!$apiKey) {
            throw new RuntimeException('NVIDIA_API_KEY is not configured.');
        }

        return Http::timeout(45)
            ->retry(2, 500)
            ->acceptJson()
            ->withToken($apiKey);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('sanad.ai.base_url'), '/') . '/' . ltrim($path, '/');
    }
}
