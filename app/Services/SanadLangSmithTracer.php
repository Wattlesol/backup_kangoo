<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SanadLangSmithTracer
{
    public function trace(string $name, array $inputs, array $outputs = [], array $metadata = []): ?string
    {
        if (!config('sanad.ai.langsmith.enabled') || !config('sanad.ai.langsmith.api_key')) {
            return null;
        }

        $runId = (string) Str::uuid();
        $endpoint = rtrim((string) config('sanad.ai.langsmith.endpoint'), '/');

        try {
            Http::timeout(15)
                ->acceptJson()
                ->withHeaders(['x-api-key' => config('sanad.ai.langsmith.api_key')])
                ->post($endpoint . '/runs', [
                    'id' => $runId,
                    'name' => $name,
                    'run_type' => 'llm',
                    'project_name' => config('sanad.ai.langsmith.project'),
                    'inputs' => $inputs,
                    'outputs' => $outputs,
                    'extra' => ['metadata' => $metadata],
                    'start_time' => now()->toIso8601String(),
                    'end_time' => now()->toIso8601String(),
                ]);
        } catch (\Throwable $e) {
            return null;
        }

        return $runId;
    }
}
