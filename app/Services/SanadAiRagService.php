<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\SanadAiKnowledgeItem;
use App\Models\Service;
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
        if ($this->isGreeting($question)) {
            $liveContext = $booking ? $this->liveRequestContext($booking) : null;
            $answer = $booking
                ? "Hello! I'm here to help with your Sanad request. You can ask me about your request status, required documents, next steps, or anything else you need."
                : "Hello! I'm Sanad AI. How can I help you today?";
            $providerMetadata = [
                'provider' => 'sanad_first_responder',
                'intent' => 'greeting',
                'vector_store' => config('sanad.ai.vector_store'),
            ];
            $traceId = $this->tracer->trace('sanad-rag-answer', [
                'question' => $question,
                'audience' => $audience,
                'booking_id' => optional($booking)->id,
            ], [
                'answer' => $answer,
                'confidence' => 0.95,
                'requires_escalation' => false,
            ], $providerMetadata);

            return [
                'answer' => $answer,
                'confidence' => 0.95,
                'requires_escalation' => false,
                'sources' => [],
                'live_context' => $liveContext,
                'provider_metadata' => $providerMetadata,
                'langsmith_run_id' => $traceId,
            ];
        }

        $matches = $this->retrieve($question, $audience, 5);
        $liveContext = $booking ? $this->liveRequestContext($booking) : null;
        $serviceCatalog = $this->serviceCatalogContext($question, $booking);
        $confidence = $this->confidence($matches, $booking, $serviceCatalog);
        $requiresEscalation = $confidence < (float) config('sanad.ai.requires_escalation_when_confidence_below', 0.65)
            || Str::contains(Str::lower($question), ['human', 'complaint', 'urgent', 'wrong', 'rejected', 'delay']);
        $aiDecision = $this->decideBehavior($question, $serviceCatalog, $requiresEscalation);

        $draftAnswer = $this->composeAnswer($question, $matches, $liveContext, $serviceCatalog, $requiresEscalation);
        $answer = $draftAnswer;
        $providerMetadata = [
            'provider' => config('sanad.ai.provider'),
            'model' => config('sanad.ai.model'),
            'vector_store' => config('sanad.ai.vector_store'),
        ];

        if (config('sanad.ai.enabled') && config('sanad.ai.api_key')) {
            try {
                $messages = $this->messages($question, $matches, $liveContext, $serviceCatalog, $requiresEscalation);
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

        if (($aiDecision['action'] ?? null) === 'unsupported_notice') {
            $answer = $this->unsupportedServiceAnswer($serviceCatalog, $booking);
            $requiresEscalation = false;
            $confidence = max($confidence, 0.74);
        }

        // Detect uncertainty or missing details in generated answer
        $uncertaintyKeywords = [
            'does not specify', 'do not specify', 'apologize', 'recommend checking',
            'contact the relevant', 'not mentioned', 'not available', 'do not have',
            'cannot confirm', 'unable to provide', 'do not have access', 'cannot provide',
            'exact duration', 'how many days', 'processing time', 'flagged for review'
        ];

        $lowerAnswer = Str::lower($answer);
        if (($aiDecision['action'] ?? null) !== 'unsupported_notice' && Str::contains($lowerAnswer, $uncertaintyKeywords)) {
            $requiresEscalation = true;
            if (!Str::contains($answer, 'escalated to the Sanad operations team')) {
                $answer .= "\n\n*(This inquiry has been automatically escalated to the Sanad operations team for review and approval.)*";
            }
        }

        if ($requiresEscalation) {
            $aiDecision = [
                'action' => 'human_handover',
                'reason' => $aiDecision['reason'] ?? 'low_confidence_or_sensitive_request',
            ];
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
            'service_catalog_context' => $serviceCatalog,
            'ai_decision' => $aiDecision,
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

    private function composeAnswer(string $question, Collection $matches, ?array $liveContext, array $serviceCatalog, bool $requiresEscalation): string
    {
        $parts = [];
        $unsupportedServiceQuestion = $this->isServiceScopeQuestion($question)
            && empty($serviceCatalog['matched_services'])
            && !empty($serviceCatalog['active_services']);

        if ($liveContext) {
            $parts[] = "Request {$liveContext['reference']} is currently at stage: {$liveContext['stage']}. Service: {$liveContext['service']}.";
            if (!empty($liveContext['pending_customer_actions'])) {
                $parts[] = 'Pending customer action: ' . implode('; ', $liveContext['pending_customer_actions']) . '.';
            } else {
                $parts[] = 'There are no pending customer actions required right now.';
            }
            $parts[] = "Payment status: {$liveContext['payment_status']}. Next step: {$liveContext['next_step']}.";
        }

        if (!empty($serviceCatalog['matched_services'])) {
            $parts[] = "Sanad currently offers support for:\n" . $this->serviceBulletList($serviceCatalog['matched_services']);
        } elseif ($unsupportedServiceQuestion) {
            $parts[] = "I checked Sanad's active service catalog, and Sanad does not currently list a service matching your request. Current supported services include:\n" . $this->serviceBulletList($serviceCatalog['active_services']) . "\n\nIf you would like, the Sanad team can still review your requirement and advise whether there is a related supported process.";
        } elseif (!empty($serviceCatalog['active_services'])) {
            $parts[] = "Sanad's active service catalog includes:\n" . $this->serviceBulletList($serviceCatalog['active_services']);
        }

        if ($matches->isNotEmpty() && !$unsupportedServiceQuestion) {
            $matchedTopics = $matches->take(3)->pluck('item.title')->unique()->implode(', ');
            $parts[] = "Based on official Sanad guidance regarding {$matchedTopics}, processing steps and estimated completion timelines depend on official government authority review and document verification. Our operations team can assist you with exact progress tracking.";
        } elseif (!$liveContext && !$unsupportedServiceQuestion) {
            $parts[] = 'Processing times and requirements depend on official government department review steps. Our operations team is available to confirm exact details for your request.';
        }

        if ($requiresEscalation) {
            $parts[] = "\n*(This inquiry has been flagged for review by the Sanad operations team to ensure accuracy.)*";
        }

        return implode("\n\n", $parts);
    }

    private function messages(string $question, Collection $matches, ?array $liveContext, array $serviceCatalog, bool $requiresEscalation): array
    {
        $context = $matches->map(function ($match) {
            $item = $match['item'];
            $rawContent = isset($match['chunk']) ? $match['chunk']->content : $item->content;
            $cleanContent = $this->sanitizeKnowledgeContent($rawContent);

            return "Knowledge Item: {$item->title}\nCategory: " . ($item->category ?: 'General') . "\nContent: " . Str::limit($cleanContent, 3500, '');
        })->implode("\n\n---\n\n");

        $prompt = "You are Sanad AI, an expert operations assistant for the Sanad platform. Answer the user question directly, politely, and comprehensively using the provided Knowledge Base, Live Request Context, and Active Service Catalog. Use the Active Service Catalog as the source of truth for what Sanad currently offers. Whenever you mention available Sanad services, format the services as a bullet list, one service per line. If the customer asks about a service that is not in the catalog, say that Sanad does not currently list that service, then list available supported services as bullet points and offer to connect them with the Sanad team. Do NOT invent unsupported services. Do NOT write any draft outlines, mental refinement notes, or system prompt echoes. Output ONLY your final customer-facing response.\n\n" .
            "User Question:\n{$question}\n\n" .
            "Live Request Context:\n" . json_encode($liveContext ?: [], JSON_PRETTY_PRINT) . "\n\n" .
            "Active Service Catalog:\n" . json_encode($serviceCatalog, JSON_PRETTY_PRINT) . "\n\n" .
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

    private function serviceCatalogContext(string $question, ?Booking $booking = null): array
    {
        $terms = $this->terms($question);
        $services = Service::query()
            ->with('category:id,name')
            ->where('status', 1)
            ->where('service_type', 'service')
            ->orderBy('updated_at', 'desc')
            ->limit(60)
            ->get();

        if ($booking && $booking->service) {
            $services = $services
                ->prepend($booking->service->loadMissing('category:id,name'))
                ->unique('id')
                ->values();
        }

        $normalized = $services->map(fn (Service $service) => $this->serviceCatalogItem($service))->values();
        $matched = $normalized
            ->map(function (array $service) use ($terms) {
                $haystack = Str::lower(implode(' ', array_filter([
                    $service['name'],
                    $service['category'],
                    $service['government_entity'],
                    $service['description'],
                    implode(' ', $service['required_documents']),
                ])));
                $score = $terms->sum(fn ($term) => substr_count($haystack, $term));

                return $service + ['match_score' => $score];
            })
            ->filter(fn (array $service) => $service['match_score'] > 0)
            ->sortByDesc('match_score')
            ->take(8)
            ->values();

        return [
            'question' => $question,
            'matched_services' => $matched->all(),
            'active_services' => $normalized->take(20)->all(),
            'service_count' => $normalized->count(),
        ];
    }

    private function serviceCatalogItem(Service $service): array
    {
        $documents = collect($service->required_documents ?: [])
            ->map(function ($document) {
                if (is_array($document)) {
                    return $document['name'] ?? $document['document_name'] ?? $document['key'] ?? null;
                }

                return $document;
            })
            ->filter()
            ->values()
            ->all();

        return [
            'id' => $service->id,
            'name' => $service->name_en ?: $service->name,
            'category' => optional($service->category)->name,
            'government_entity' => $service->government_entity,
            'estimated_completion_time' => $service->estimated_completion_time,
            'government_fee' => $service->government_fee,
            'service_fee' => $service->service_fee,
            'required_documents' => $documents,
            'description' => Str::limit(strip_tags((string) $service->description), 500, ''),
            'instructions' => Str::limit(strip_tags((string) $service->service_instructions), 500, ''),
        ];
    }

    private function decideBehavior(string $question, array $serviceCatalog, bool $requiresEscalation): array
    {
        if ($this->isServiceScopeQuestion($question)) {
            if (!empty($serviceCatalog['matched_services'])) {
                return [
                    'action' => 'answer',
                    'reason' => 'matched_active_service_catalog',
                ];
            }

            if (!empty($serviceCatalog['active_services'])) {
                return [
                    'action' => 'unsupported_notice',
                    'reason' => 'no_matching_active_service',
                ];
            }
        }

        if ($requiresEscalation) {
            return [
                'action' => 'human_handover',
                'reason' => 'low_confidence_or_sensitive_request',
            ];
        }

        return [
            'action' => 'answer',
            'reason' => 'sufficient_context',
        ];
    }

    private function unsupportedServiceAnswer(array $serviceCatalog, ?Booking $booking = null): string
    {
        $answer = "Thank you for checking with Sanad. I reviewed our active service catalog, and we do not currently offer that service.";
        $serviceList = $this->serviceBulletList($serviceCatalog['active_services'] ?? []);

        if ($serviceList !== '') {
            $answer .= "\n\nAt the moment, our available services include:\n{$serviceList}";
        }

        $answer .= "\n\nWe will let customers know when Sanad begins offering additional service categories.";

        if ($booking) {
            $answer .= " For your current request, I can still help with status updates, required documents, uploads, and next steps.";
        } else {
            $answer .= " If you need help choosing from the available Sanad services, I can guide you.";
        }

        return $answer;
    }

    private function serviceBulletList(array $services, int $limit = 8): string
    {
        return collect($services)
            ->take($limit)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => '- ' . $name)
            ->implode("\n");
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

    private function isGreeting(string $question): bool
    {
        $normalized = Str::lower($question);
        $normalized = preg_replace('/[^\pL\pN\s]+/u', ' ', $normalized) ?: '';
        $normalized = trim(preg_replace('/\s+/', ' ', $normalized) ?: '');

        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, [
            'hi',
            'hello',
            'hey',
            'salam',
            'salaam',
            'assalamualaikum',
            'assalamu alaikum',
            'good morning',
            'good afternoon',
            'good evening',
        ], true);
    }

    private function confidence(Collection $matches, ?Booking $booking, array $serviceCatalog = []): float
    {
        $hasServiceContext = !empty($serviceCatalog['matched_services']);
        $hasCatalogForScopeQuestion = !empty($serviceCatalog['active_services']) && $this->isServiceScopeQuestion($serviceCatalog['question'] ?? '');

        if ($matches->isEmpty()) {
            return $booking
                ? ($hasServiceContext || $hasCatalogForScopeQuestion ? 0.72 : 0.30)
                : ($hasServiceContext || $hasCatalogForScopeQuestion ? 0.68 : 0.10);
        }

        $topScore = (float) $matches->max('score');
        $scoreFactor = min(1.0, max(0.0, $topScore / 0.40));
        $contextBonus = ($booking ? 0.15 : 0.05) + ($hasServiceContext ? 0.15 : 0.0);

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

    private function isServiceScopeQuestion(string $question): bool
    {
        $normalized = Str::lower($question);

        return Str::contains($normalized, [
            'do you deal',
            'deal in',
            'do you provide',
            'do you offer',
            'can you help with',
            'can sanad help',
            'service',
            'services',
            'license',
            'licence',
            'renewal',
            'verification',
            'passport',
            'visa',
            'iqama',
        ]);
    }
}
