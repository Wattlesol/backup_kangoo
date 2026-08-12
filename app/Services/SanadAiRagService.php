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
                return empty($visibleTo) || in_array($audience, $visibleTo, true) || in_array('user', $visibleTo, true);
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

    private function composeAnswer(string $question, Collection $matches, ?array $liveContext, bool $requiresEscalation): string
    {
        $parts = [];

        if ($liveContext) {
            $parts[] = "Request {$liveContext['reference']} is currently at {$liveContext['stage']}.";
            $parts[] = "Service: {$liveContext['service']}.";
            if ($liveContext['pending_customer_actions']) {
                $parts[] = 'Pending customer action: ' . implode('; ', $liveContext['pending_customer_actions']) . '.';
            } else {
                $parts[] = 'There are no pending customer actions detected right now.';
            }
            $parts[] = "Payment status: {$liveContext['payment_status']}.";
            $parts[] = "Next step: {$liveContext['next_step']}.";
        }

        if ($matches->isNotEmpty()) {
            $parts[] = 'Relevant Sanad knowledge:';
            foreach ($matches->take(3) as $match) {
                $item = $match['item'];
                $parts[] = "- {$item->title}: " . Str::limit(trim(strip_tags($item->content)), 420);
            }
        } elseif (!$liveContext) {
            $parts[] = 'I do not have enough Sanad knowledge base content to answer this confidently yet.';
        }

        if ($requiresEscalation) {
            $parts[] = 'I have marked this for Sanad team review because the answer may need human confirmation.';
        }

        return implode("\n", $parts);
    }

    private function messages(string $question, Collection $matches, ?array $liveContext, bool $requiresEscalation): array
    {
        $context = $matches->map(function ($match) {
            $item = $match['item'];
            $content = isset($match['chunk']) ? $match['chunk']->content : $item->content;

            return "Title: {$item->title}\nCategory: " . ($item->category ?: 'General') . "\nContent: " . Str::limit(trim(strip_tags($content)), 1400, '');
        })->implode("\n\n---\n\n");

        return [
            [
                'role' => 'system',
                'content' => 'You are Sanad AI, an operations assistant for a service request platform. Answer only from the supplied Sanad knowledge and live request context. Be concise, practical, and mention when human review is required.',
            ],
            [
                'role' => 'user',
                'content' => "Question:\n{$question}\n\nLive request context:\n" . json_encode($liveContext ?: [], JSON_PRETTY_PRINT) . "\n\nRetrieved knowledge:\n{$context}\n\nHuman review required: " . ($requiresEscalation ? 'yes' : 'no'),
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
        $score = min(0.55, $matches->sum('score') / 20);
        $context = $booking ? 0.3 : 0.0;
        $base = $matches->isNotEmpty() ? 0.2 : 0.1;

        return round(min(0.95, $base + $score + $context), 2);
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
