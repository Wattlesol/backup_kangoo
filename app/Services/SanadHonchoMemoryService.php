<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SanadHonchoMemoryService
{
    private string $baseUrl;
    private ?string $apiKey;
    private bool $enabled;

    public function __construct()
    {
        $this->baseUrl = config('sanad.ai.honcho.base_url', 'https://api.honcho.dev');
        $this->apiKey = config('sanad.ai.honcho.api_key');
        $this->enabled = (bool) config('sanad.ai.honcho.enabled', false);
    }

    /**
     * Get or create a peer session state in Honcho.
     */
    public function getPeerSession(?User $user, string $sessionId): array
    {
        if (!$this->enabled || !$this->apiKey) {
            return [
                'peer_id' => $user ? (string) $user->id : 'guest',
                'session_id' => $sessionId,
                'active_topic' => null,
                'user_profile' => [],
            ];
        }

        try {
            $peerId = $user ? 'user_' . $user->id : 'guest_' . Str::slug($sessionId);
            $res = Http::withToken($this->apiKey)
                ->timeout(5)
                ->get("{$this->baseUrl}/v1/peers/{$peerId}/sessions/{$sessionId}");

            if ($res->successful()) {
                return $res->json();
            }
        } catch (\Throwable $e) {
            // Log fallback silently
        }

        return [
            'peer_id' => $user ? (string) $user->id : 'guest',
            'session_id' => $sessionId,
            'active_topic' => null,
            'user_profile' => [],
        ];
    }

    /**
     * Contextualize ambiguous user query using past interaction topic.
     */
    public function contextualizeQuery(string $question, ?string $activeTopic): string
    {
        $cleanQuestion = trim($question);
        if (!$activeTopic || Str::contains(Str::lower($cleanQuestion), Str::lower($activeTopic))) {
            return $cleanQuestion;
        }

        // If question is short or generic, prepend active topic context
        if (mb_strlen($cleanQuestion) < 60 && !Str::contains(Str::lower($cleanQuestion), ['driving', 'license', 'absher', 'sanad', 'iqama'])) {
            return "Regarding {$activeTopic}: {$cleanQuestion}";
        }

        return $cleanQuestion;
    }

    /**
     * Record interaction to Honcho memory state asynchronously.
     */
    public function recordInteraction(?User $user, string $sessionId, string $question, string $answer): void
    {
        if (!$this->enabled || !$this->apiKey) {
            return;
        }

        try {
            $peerId = $user ? 'user_' . $user->id : 'guest_' . Str::slug($sessionId);
            Http::withToken($this->apiKey)
                ->async()
                ->post("{$this->baseUrl}/v1/peers/{$peerId}/sessions/{$sessionId}/events", [
                    'question' => $question,
                    'answer' => $answer,
                    'timestamp' => now()->toIso8601String(),
                ]);
        } catch (\Throwable $e) {
            // Fail gracefully
        }
    }
}
