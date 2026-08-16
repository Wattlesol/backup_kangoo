<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SanadAiKnowledgeItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SanadAiRagService
{
    public function __construct(
        private SanadNvidiaAiClient $ai,
        private SanadVectorStoreService $vectorStore,
        private SanadLangSmithTracer $tracer
    ) {
    }

    public function answer(string $question, ?Booking $booking = null, string $audience = 'customer'): array
    {
        $matches = $this->retrieve($question, $audience, 5);
        $liveContext = $booking ? $this->liveRequestContext($booking) : null;
        $confidence = $this->confidence($matches, $booking);
        $requiresEscalation = $confidence < (float) config('sanad.ai.requires_escalation_when_confidence_below', 0.65)
            || Str::contains(Str::lower($question), ['human', 'complaint', 'urgent', 'wrong', 'rejected', 'delay']);

        $draftAnswer = $this->composeAnswer($question, $matches, $liveContext, $requiresEscalation);
        $answer = $draftAnswer;
        $providerMetadata = [
            'provider' => config('sanad.ai.provider'),
            'model' => config('sanad.ai.model'),
            'vector_store' => config('sanad.ai.vector_store'),
        ];

        if (config('sanad.ai.enabled') && config('sanad.ai.api_key')) {
            try {
                $messages = $this->messages($question, $matches, $liveContext, $requiresEscalation);
                $completion = $this->ai->chat($messages);
                if (trim($completion['content']) !== '') {
                    $answer = trim($completion['content']);
                }
                $providerMetadata['nvidia'] = [
                    'raw_id' => data_get($completion, 'raw.id'),
                    'finish_reason' => data_get($completion, 'raw.choices.0.finish_reason'),
                    'usage' => data_get($completion, 'raw.usage'),
                ];
            } catch (\Throwable $e) {
                $providerMetadata['provider_error'] = $e->getMessage();
                $requiresEscalation = true;
            }
        }

        // Detect uncertainty or missing details in generated answer
        $uncertaintyKeywords = [
            'does not specify', 'do not specify', 'apologize', 'recommend checking',
            'contact the relevant', 'not mentioned', 'not available', 'do not have',
            'cannot confirm', 'unable to provide', 'do not have access', 'cannot provide',
            'exact duration', 'how many days', 'processing time', 'flagged for review'
        ];

        $lowerAnswer = Str::lower($answer);
        if (Str::contains($lowerAnswer, $uncertaintyKeywords)) {
            $requiresEscalation = true;
            if (!Str::contains($answer, 'escalated to the Sanad operations team')) {
                $answer .= "\n\n*(This inquiry has been automatically escalated to the Sanad operations team for review and approval.)*";
            }
        }

        $traceId = $this->tracer->trace('sanad-rag-answer', [
            'question' => $question,
            'audience' => $audience,
            'booking_id' => optional($booking)->id,
        ], [
            'answer' => $answer,
            'confidence' => $confidence,
            'requires_escalation' => $requiresEscalation,
        ], $providerMetadata);

        return [
            'answer' => $answer,
            'confidence' => $confidence,
            'requires_escalation' => $requiresEscalation,
            'sources' => $matches->map(fn ($match) => [
                'id' => $match['item']->id,
                'title' => $match['item']->title,
                'category' => $match['item']->category ?: 'General',
                'score' => $match['score'],
                'chunk' => isset($match['chunk']) ? $match['chunk']->id : null,
            ])->values()->all(),
            'live_context' => $liveContext,
            'provider_metadata' => $providerMetadata,
            'langsmith_run_id' => $traceId,
        ];
    }

    public function retrieve(string $question, string $audience = 'customer', int $limit = 5): Collection
    {
        $vectorMatches = $this->vectorStore->search($question, $audience, $limit);
        if ($vectorMatches->isNotEmpty()) {
            return $vectorMatches;
        }

        $terms = $this->terms($question);

        return SanadAiKnowledgeItem::where('is_active', true)
            ->get()
            ->filter(function ($item) use ($audience) {
                $visibleTo = $item->visible_to ?: [];
                return empty($visibleTo) || in_array($audience, $visibleTo, true) || in_array('user', $visibleTo, true) || in_array('customer', $visibleTo, true);
            })
            ->map(function ($item) use ($terms) {
                $haystack = Str::lower($item->title . ' ' . $item->category . ' ' . $item->content);
                $score = $terms->sum(fn ($term) => substr_count($haystack, $term));
                $titleBonus = $terms->sum(fn ($term) => Str::contains(Str::lower($item->title), $term) ? 2 : 0);

                return ['item' => $item, 'score' => $score + $titleBonus];
            })
            ->filter(fn ($match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    public function indexKnowledgeItem(SanadAiKnowledgeItem $item): void
    {
        $this->vectorStore->indexKnowledgeItem($item);
    }

    private function sanitizeKnowledgeContent(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\[×\]\(javascript:.*?\)/i', '', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text);
        $text = preg_replace('/Source:\s*https?:\/\/\S+/i', '', $text);
        $text = preg_replace('/https?:\/\/\S+/', '', $text);
        $text = preg_replace('/#+\s*/', '', $text);
        $text = preg_replace('/[\*\_\~]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function composeAnswer(string $question, Collection $matches, ?array $liveContext, bool $requiresEscalation): string
    {
        $parts = [];

        if ($liveContext) {
            $parts[] = "Request {$liveContext['reference']} is currently at stage: {$liveContext['stage']}. Service: {$liveContext['service']}.";
            if (!empty($liveContext['pending_customer_actions'])) {
                $parts[] = 'Pending customer action: ' . implode('; ', $liveContext['pending_customer_actions']) . '.';
            } else {
                $parts[] = 'There are no pending customer actions required right now.';
            }
            $parts[] = "Payment status: {$liveContext['payment_status']}. Next step: {$liveContext['next_step']}.";
        }

        if ($matches->isNotEmpty()) {
            $matchedTopics = $matches->take(3)->pluck('item.title')->unique()->implode(', ');
            $parts[] = "Based on official Sanad guidance regarding {$matchedTopics}, processing steps and estimated completion timelines depend on official government authority review and document verification. Our operations team can assist you with exact progress tracking.";
        } elseif (!$liveContext) {
            $parts[] = 'Processing times and requirements depend on official government department review steps. Our operations team is available to confirm exact details for your request.';
        }

        if ($requiresEscalation) {
            $parts[] = "\n*(This inquiry has been flagged for review by the Sanad operations team to ensure accuracy.)*";
        }

        return implode("\n\n", $parts);
    }

    private function messages(string $question, Collection $matches, ?array $liveContext, bool $requiresEscalation): array
    {
        $context = $matches->map(function ($match) {
            $item = $match['item'];
            $rawContent = isset($match['chunk']) ? $match['chunk']->content : $item->content;
            $cleanContent = $this->sanitizeKnowledgeContent($rawContent);

            return "Knowledge Item: {$item->title}\nCategory: " . ($item->category ?: 'General') . "\nContent: " . Str::limit($cleanContent, 3500, '');
        })->implode("\n\n---\n\n");

        $prompt = "You are Sanad AI, an expert operations assistant for the Sanad platform. Answer the user question directly, politely, and comprehensively using the provided Knowledge Base and Live Request Context. Do NOT write any draft outlines, mental refinement notes, or system prompt echoes. Output ONLY your final customer-facing response.\n\n" .
            "User Question:\n{$question}\n\n" .
            "Live Request Context:\n" . json_encode($liveContext ?: [], JSON_PRETTY_PRINT) . "\n\n" .
            "Retrieved Sanad Knowledge Base:\n{$context}";

        return [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
    }

    private function liveRequestContext(Booking $booking): array
    {
        $booking->loadMissing([
            'service',
            'payment',
            'sanadDocuments',
            'sanadDocumentRequests',
            'sanadBuzzAlerts',
            'sanadRequestActions',
            'handymanAdded.handyman',
        ]);

        $pendingActions = [];
        foreach ($booking->sanadDocumentRequests->where('requested_from', 'customer')->whereIn('status', ['pending', 'rejected', 'replacement_requested']) as $request) {
            $pendingActions[] = 'Upload ' . $request->document_name;
        }
        foreach ($booking->sanadBuzzAlerts->where('status', 'unread') as $buzz) {
            $pendingActions[] = $buzz->message;
        }

        $assignedMapping = $booking->handymanAdded->first();

        return [
            'reference' => $booking->sanad_reference ?: '#' . $booking->id,
            'stage' => Str::headline($booking->sanad_stage ?: $booking->status ?: 'submitted'),
            'service' => optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-',
            'assigned_employee' => optional(optional($assignedMapping)->handyman)->display_name ?: '-',
            'sla_due_at' => optional($booking->sla_due_at)->format('Y-m-d H:i'),
            'expected_completion_at' => optional($booking->expected_completion_at)->format('Y-m-d H:i'),
            'payment_status' => optional($booking->payment)->payment_status ?: 'pending',
            'documents_pending_review' => $booking->sanadDocuments->where('verification_status', 'pending')->count(),
            'pending_customer_actions' => $pendingActions,
            'next_step' => $this->nextStep($booking, $pendingActions),
        ];
    }

    private function nextStep(Booking $booking, array $pendingActions): string
    {
        if ($pendingActions) {
            return 'Complete the pending customer action shown above.';
        }

        return match ($booking->sanad_stage) {
            'submitted', 'pending_review' => 'Sanad will review the request and documents.',
            'assigned_to_partner', 'assigned_to_employee', 'in_progress' => 'The assigned Sanad team is processing the request.',
            'awaiting_quality_review' => 'Sanad quality review is in progress.',
            'completed', 'closed' => 'The request is complete. You can review documents, invoice, and rating options.',
            default => 'Sanad will update the request timeline when the next action is available.',
        };
    }

    private function confidence(Collection $matches, ?Booking $booking): float
    {
        if ($matches->isEmpty()) {
            return $booking ? 0.30 : 0.10;
        }

        $topScore = (float) $matches->max('score');
        $scoreFactor = min(1.0, max(0.0, $topScore / 0.40));
        $contextBonus = $booking ? 0.15 : 0.05;

        $calculated = 0.35 + ($scoreFactor * 0.50) + $contextBonus;

        return round(min(0.98, $calculated), 2);
    }

    private function terms(string $question): Collection
    {
        return collect(preg_split('/[^\pL\pN]+/u', Str::lower($question)))
            ->filter(fn ($term) => mb_strlen($term) > 2)
            ->reject(fn ($term) => in_array($term, ['what', 'when', 'where', 'how', 'the', 'and', 'for', 'from', 'with', 'now', 'this', 'that'], true))
            ->unique()
            ->values();
    }
}
