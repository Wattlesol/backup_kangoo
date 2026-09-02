<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SanadLangSmithTracer
{
    public function trace(string $name, array $inputs, array $outputs = [], array $metadata = [], ?string $projectName = null): ?string
    {
        if (!config('sanad.ai.langsmith.enabled') || !config('sanad.ai.langsmith.api_key')) {
            return null;
        }

        $runId = (string) Str::uuid();
        $baseEndpoint = rtrim((string) config('sanad.ai.langsmith.endpoint', 'https://api.smith.langchain.com'), '/');
        $endpoint = Str::endsWith($baseEndpoint, '/api/v1') ? $baseEndpoint : $baseEndpoint . '/api/v1';

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->withHeaders(['x-api-key' => config('sanad.ai.langsmith.api_key')])
                ->post($endpoint . '/runs', [
                    'id' => $runId,
                    'name' => $name,
                    'run_type' => 'chain',
                    'project_name' => $projectName ?: config('sanad.ai.langsmith.project', 'sanad-ai'),
                    'inputs' => $inputs,
                    'outputs' => $outputs,
                    'extra' => ['metadata' => $metadata],
                    'start_time' => now()->toIso8601String(),
                    'end_time' => now()->toIso8601String(),
                ]);

            if (!$response->successful()) {
                \Log::warning('LangSmith trace request failed: [' . $response->status() . '] ' . $response->body());
            }
        } catch (\Throwable $e) {
            \Log::error('LangSmith trace error: ' . $e->getMessage());
            return null;
        }

        return $runId;
    }
}
