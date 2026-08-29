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
        $language = $this->preferredLanguage($question);

        if ($this->isGreeting($question)) {
            $liveContext = $booking ? $this->liveRequestContext($booking, $language) : null;
            $answer = $language === 'ar'
                ? ($booking
                    ? 'مرحباً! أنا هنا لمساعدتك في طلبك لدى سند. يمكنك سؤالي عن حالة الطلب، أو المستندات المطلوبة، أو الخطوات التالية.'
                    : 'مرحباً! أنا مساعد سند الذكي. كيف يمكنني مساعدتك اليوم؟')
                : ($booking
                    ? "Hello! I'm here to help with your Quick request. You can ask me about your request status, required documents, next steps, or anything else you need."
                    : "Hello! I'm Quick AI. How can I help you today?");
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
        $liveContext = $booking ? $this->liveRequestContext($booking, $language) : null;
        $serviceCatalog = $this->serviceCatalogContext($question, $booking, $language);
        $confidence = $this->confidence($matches, $booking, $serviceCatalog);
        $requiresEscalation = $confidence < (float) config('sanad.ai.requires_escalation_when_confidence_below', 0.65)
            || Str::contains(Str::lower($question), ['human', 'complaint', 'urgent', 'wrong', 'rejected', 'delay']);
        $aiDecision = $this->decideBehavior($question, $serviceCatalog, $requiresEscalation);

        $draftAnswer = $this->composeAnswer($question, $matches, $liveContext, $serviceCatalog, $requiresEscalation, $language);
        $answer = $draftAnswer;
        $providerMetadata = [
            'provider' => config('sanad.ai.provider'),
            'model' => config('sanad.ai.model'),
            'vector_store' => config('sanad.ai.vector_store'),
        ];

        if (config('sanad.ai.enabled') && config('sanad.ai.api_key')) {
            try {
                $messages = $this->messages($question, $matches, $liveContext, $serviceCatalog, $requiresEscalation, $language);
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
            $answer = $this->unsupportedServiceAnswer($serviceCatalog, $booking, $language);
            $requiresEscalation = false;
            $confidence = max($confidence, 0.74);
        }

        // Detect uncertainty or missing details in generated answer
        $uncertaintyKeywords = [
            'does not specify', 'do not specify', 'apologize', 'recommend checking',
            'contact the relevant', 'not mentioned', 'not available', 'do not have',
            'cannot confirm', 'unable to provide', 'do not have access', 'cannot provide',
            'exact duration', 'how many days', 'processing time', 'flagged for review',
            'غير محدد', 'لا تتوفر', 'لا يمكنني التأكيد', 'تواصل مع الجهة', 'قيد المراجعة'
        ];

        $lowerAnswer = Str::lower($answer);
        if (($aiDecision['action'] ?? null) !== 'unsupported_notice' && Str::contains($lowerAnswer, $uncertaintyKeywords)) {
            $requiresEscalation = true;
            if (!Str::contains($answer, 'escalated to the Quick operations team')) {
                $answer .= $language === 'ar'
                    ? "\n\n*(تم تحويل هذا الاستفسار تلقائياً إلى فريق عمليات كويك للمراجعة والاعتماد.)*"
                    : "\n\n*(This inquiry has been automatically escalated to the Quick operations team for review and approval.)*";
            }
        }

        if ($requiresEscalation) {
            $aiDecision = [
                'action' => 'human_handover',
                'reason' => $aiDecision['reason'] ?? 'low_confidence_or_sensitive_request',
            ];
        }

        $answer = $this->publicBrandText($answer);

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
                'title' => $this->publicBrandText($language === 'ar' && $match['item']->title_ar ? $match['item']->title_ar : $match['item']->title),
                'category' => $language === 'ar' && $match['item']->category_ar ? $match['item']->category_ar : ($match['item']->category ?: 'General'),
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

    private function composeAnswer(string $question, Collection $matches, ?array $liveContext, array $serviceCatalog, bool $requiresEscalation, string $language): string
    {
        $parts = [];
        $unsupportedServiceQuestion = $this->isServiceScopeQuestion($question)
            && !$this->isServiceListQuestion($question)
            && empty($serviceCatalog['matched_services'])
            && !empty($serviceCatalog['active_services']);

        if ($language === 'ar') {
            if ($liveContext) {
                $parts[] = "الطلب {$liveContext['reference']} في مرحلة: {$liveContext['stage']}. الخدمة: {$liveContext['service']}.";
                $parts[] = !empty($liveContext['pending_customer_actions'])
                    ? 'الإجراء المطلوب من العميل: ' . implode('؛ ', $liveContext['pending_customer_actions']) . '.'
                    : 'لا توجد إجراءات مطلوبة من العميل حالياً.';
                $parts[] = "حالة الدفع: {$liveContext['payment_status']}. الخطوة التالية: {$liveContext['next_step']}.";
            }

            if (!empty($serviceCatalog['matched_services'])) {
                $parts[] = "تقدم كويك حالياً الخدمات التالية:\n" . $this->serviceBulletList($serviceCatalog['matched_services']);
            } elseif ($unsupportedServiceQuestion) {
                $parts[] = "راجعت قائمة خدمات كويك النشطة، ولا توجد حالياً خدمة مطابقة لطلبك. تشمل الخدمات المتاحة:\n" . $this->serviceBulletList($serviceCatalog['active_services']) . "\n\nيمكن لفريق كويك مراجعة احتياجك وإرشادك إلى أقرب خدمة مناسبة.";
            } elseif (!empty($serviceCatalog['active_services'])) {
                $parts[] = "تشمل قائمة خدمات كويك النشطة:\n" . $this->serviceBulletList($serviceCatalog['active_services']);
            }

            if ($this->isServiceListQuestion($question)) {
                $parts[] = 'لاختيار الخدمة الأنسب، حدّد نوع المعاملة المطلوبة والمدة المناسبة، ثم راجع المتطلبات والمستندات الخاصة بالخدمة. ويمكنك إخباري باحتياجك لأرشح لك الخيار المناسب.';
            }

            if ($matches->isNotEmpty() && !$unsupportedServiceQuestion) {
                $matchedTopics = $matches->take(3)->map(fn ($match) => $match['item']->title_ar ?: $match['item']->title)->unique()->implode('، ');
                $parts[] = "وفقاً لإرشادات سند بشأن {$matchedTopics}، تعتمد الخطوات والمدة على مراجعة الجهة الحكومية والتحقق من المستندات.";
            } elseif (!$liveContext && !$unsupportedServiceQuestion) {
                $parts[] = 'تعتمد المدة والمتطلبات على إجراءات الجهة الحكومية المختصة، ويمكن لفريق كويك تأكيد تفاصيل طلبك.';
            }

            if ($requiresEscalation) {
                $parts[] = "\n*(تم تحويل الاستفسار إلى فريق عمليات كويك لضمان دقة الإجابة.)*";
            }

            return implode("\n\n", $parts);
        }

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
            $parts[] = "Quick currently offers support for:\n" . $this->serviceBulletList($serviceCatalog['matched_services']);
        } elseif ($unsupportedServiceQuestion) {
            $parts[] = "I checked Quick's active service catalog, and Quick does not currently list a service matching your request. Current supported services include:\n" . $this->serviceBulletList($serviceCatalog['active_services']) . "\n\nIf you would like, the Quick team can still review your requirement and advise whether there is a related supported process.";
        } elseif (!empty($serviceCatalog['active_services'])) {
            $parts[] = "Quick's active service catalog includes:\n" . $this->serviceBulletList($serviceCatalog['active_services']);
        }

        if ($this->isServiceListQuestion($question)) {
            $parts[] = 'To choose the best option, match the service to the transaction you need and the required validity period, then review its requirements and documents. Tell me your need and I can recommend the closest option.';
        }

        if ($matches->isNotEmpty() && !$unsupportedServiceQuestion) {
            $matchedTopics = $matches->take(3)->pluck('item.title')->unique()->implode(', ');
            $parts[] = "Based on official Quick guidance regarding {$matchedTopics}, processing steps and estimated completion timelines depend on official government authority review and document verification. Our operations team can assist you with exact progress tracking.";
        } elseif (!$liveContext && !$unsupportedServiceQuestion) {
            $parts[] = 'Processing times and requirements depend on official government department review steps. Our operations team is available to confirm exact details for your request.';
        }

        if ($requiresEscalation) {
            $parts[] = "\n*(This inquiry has been flagged for review by the Quick operations team to ensure accuracy.)*";
        }

        return implode("\n\n", $parts);
    }

    private function messages(string $question, Collection $matches, ?array $liveContext, array $serviceCatalog, bool $requiresEscalation, string $language): array
    {
        $context = $matches->map(function ($match) use ($language) {
            $item = $match['item'];
            $rawContent = $language === 'ar' && $item->content_ar
                ? $item->content_ar
                : (isset($match['chunk']) ? $match['chunk']->content : $item->content);
            $cleanContent = $this->sanitizeKnowledgeContent($rawContent);
            $title = $language === 'ar' && $item->title_ar ? $item->title_ar : $item->title;
            $category = $language === 'ar' && $item->category_ar ? $item->category_ar : ($item->category ?: 'General');

            return "Knowledge Item: {$title}\nCategory: {$category}\nContent: " . Str::limit($cleanContent, 3500, '');
        })->implode("\n\n---\n\n");

        $languageInstruction = $language === 'ar'
            ? 'Respond entirely in Arabic. Use the exact Arabic service names supplied in the Active Service Catalog; never translate them back to English and never include an English service name when an Arabic name is available.'
            : 'Respond entirely in English and use the English catalog names.';
        $prompt = "You are Quick AI, an expert operations assistant for the Quick platform. Never call the product Sanad; Quick is the final public brand. {$languageInstruction} Answer the user question directly, politely, and comprehensively using the provided Knowledge Base, Live Request Context, and Active Service Catalog. Use the Active Service Catalog as the source of truth for what Quick currently offers. Whenever you mention available Quick services, format the services as a bullet list, one service per line. If the customer asks about a service that is not in the catalog, say that Quick does not currently list that service, then list available supported services as bullet points and offer to connect them with the Quick team. Do NOT invent unsupported services. Do NOT write any draft outlines, mental refinement notes, or system prompt echoes. Output ONLY your final customer-facing response.\n\n" .
            "User Question:\n{$question}\n\n" .
            "Live Request Context:\n" . json_encode($liveContext ?: [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Active Service Catalog:\n" . json_encode($serviceCatalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
            "Retrieved Quick Knowledge Base:\n{$context}";

        return [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];
    }

    private function liveRequestContext(Booking $booking, string $language = 'en'): array
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

        return [
            'reference' => $booking->quick_reference,
            'stage' => Str::headline($booking->sanad_stage ?: $booking->status ?: 'submitted'),
            'service' => $this->publicBrandText($language === 'ar'
                ? (optional($booking->service)->name_ar ?: optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-')
                : (optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-')),
            'assigned_employee' => $language === 'ar' ? 'فريق كويك' : 'Quick team',
            'sla_due_at' => optional($booking->sla_due_at)->format('Y-m-d H:i'),
            'expected_completion_at' => optional($booking->expected_completion_at)->format('Y-m-d H:i'),
            'payment_status' => optional($booking->payment)->payment_status ?: 'pending',
            'documents_pending_review' => $booking->sanadDocuments->where('verification_status', 'pending')->count(),
            'pending_customer_actions' => $pendingActions,
            'next_step' => $this->nextStep($booking, $pendingActions),
        ];
    }

    private function serviceCatalogContext(string $question, ?Booking $booking = null, string $language = 'en'): array
    {
        $terms = $this->terms($question);
        $services = Service::query()
            ->with('category:id,name,name_ar,name_en')
            ->where('status', 1)
            ->where('service_type', 'service')
            ->orderBy('updated_at', 'desc')
            ->limit(60)
            ->get();

        if ($booking && $booking->service) {
            $services = $services
                ->prepend($booking->service->loadMissing('category:id,name,name_ar,name_en'))
                ->unique('id')
                ->values();
        }

        $normalized = $services->map(fn (Service $service) => $this->serviceCatalogItem($service, $language))->values();
        $matched = $normalized
            ->map(function (array $service) use ($terms) {
                $haystack = Str::lower(implode(' ', array_filter([
                    $service['name'],
                    $service['name_en'],
                    $service['name_ar'],
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
            'language' => $language,
            'matched_services' => $matched->all(),
            'active_services' => $normalized->take(20)->all(),
            'service_count' => $normalized->count(),
        ];
    }

    private function serviceCatalogItem(Service $service, string $language): array
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
            'name' => $this->publicBrandText($language === 'ar'
                ? ($service->name_ar ?: $service->name_en ?: $service->name)
                : ($service->name_en ?: $service->name)),
            'name_en' => $this->publicBrandText($service->name_en ?: $service->name),
            'name_ar' => $this->publicBrandText($service->name_ar),
            'category' => $this->publicBrandText($language === 'ar'
                ? (optional($service->category)->name_ar ?: optional($service->category)->name_en ?: optional($service->category)->name)
                : (optional($service->category)->name_en ?: optional($service->category)->name)),
            'government_entity' => $service->government_entity,
            'estimated_completion_time' => $service->estimated_completion_time,
            'government_fee' => $service->government_fee,
            'service_fee' => $service->service_fee,
            'required_documents' => collect($documents)->map(fn ($document) => $this->publicBrandText($document))->all(),
            'description' => $this->publicBrandText(Str::limit(strip_tags((string) $service->description), 500, '')),
            'instructions' => $this->publicBrandText(Str::limit(strip_tags((string) $service->service_instructions), 500, '')),
        ];
    }

    private function decideBehavior(string $question, array $serviceCatalog, bool $requiresEscalation): array
    {
        if ($this->isServiceListQuestion($question) && !empty($serviceCatalog['active_services'])) {
            return [
                'action' => 'answer',
                'reason' => 'active_service_catalog_requested',
            ];
        }

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

    private function unsupportedServiceAnswer(array $serviceCatalog, ?Booking $booking = null, string $language = 'en'): string
    {
        if ($language === 'ar') {
            $answer = 'شكراً لتواصلك مع كويك. راجعت قائمة خدماتنا النشطة، ولا نقدم هذه الخدمة حالياً.';
            $serviceList = $this->serviceBulletList($serviceCatalog['active_services'] ?? []);
            if ($serviceList !== '') {
                $answer .= "\n\nتشمل خدماتنا المتاحة حالياً:\n{$serviceList}";
            }
            $answer .= $booking
                ? "\n\nيمكنني مساعدتك في متابعة طلبك الحالي، والمستندات المطلوبة، والخطوات التالية."
                : "\n\nيمكنني مساعدتك في اختيار الخدمة الأنسب من خدمات كويك المتاحة.";
            return $answer;
        }

        $answer = "Thank you for checking with Quick. I reviewed our active service catalog, and we do not currently offer that service.";
        $serviceList = $this->serviceBulletList($serviceCatalog['active_services'] ?? []);

        if ($serviceList !== '') {
            $answer .= "\n\nAt the moment, our available services include:\n{$serviceList}";
        }

        $answer .= "\n\nWe will let customers know when Quick begins offering additional service categories.";

        if ($booking) {
            $answer .= " For your current request, I can still help with status updates, required documents, uploads, and next steps.";
        } else {
            $answer .= " If you need help choosing from the available Quick services, I can guide you.";
        }

        return $answer;
    }

    private function serviceBulletList(array $services, int $limit = 8): string
    {
        return collect($services)
            ->take($limit)
            ->pluck('name')
            ->filter()
            ->map(fn ($name) => '- ' . $this->publicBrandText($name))
            ->implode("\n");
    }

    private function publicBrandText(?string $text): string
    {
        $text = (string) $text;
        $text = preg_replace('/\bSanad\b/u', 'Quick', $text);
        $text = preg_replace('/\bSANAD-/u', 'QUICK-', $text);

        return preg_replace('/(?<!\p{L})سند(?!\p{L})/u', 'كويك', $text);
    }

    private function nextStep(Booking $booking, array $pendingActions): string
    {
        if ($pendingActions) {
            return 'Complete the pending customer action shown above.';
        }

        return match ($booking->sanad_stage) {
            'submitted', 'pending_review' => 'Quick will review the request and documents.',
            'assigned_to_partner', 'assigned_to_employee', 'in_progress' => 'The assigned Quick team is processing the request.',
            'awaiting_quality_review' => 'Quick quality review is in progress.',
            'completed', 'closed' => 'The request is complete. You can review documents, invoice, and rating options.',
            default => 'Quick will update the request timeline when the next action is available.',
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
            'مرحبا',
            'مرحباً',
            'السلام عليكم',
            'صباح الخير',
            'مساء الخير',
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
            'خدمة',
            'خدمات',
            'تقدمون',
            'تقدم',
            'توفرون',
            'رخصة',
            'تجديد',
            'تحقق',
            'جواز',
        ]);
    }

    private function isServiceListQuestion(string $question): bool
    {
        $normalized = Str::lower($question);

        return Str::contains($normalized, [
            'what services',
            'which services',
            'list services',
            'services do you provide',
            'services do you offer',
            'ما الخدمات',
            'ما هي الخدمات',
            'الخدمات التي تقدم',
            'الخدمات المتاحة',
            'قائمة الخدمات',
        ]);
    }

    private function preferredLanguage(string $question): string
    {
        if (app()->getLocale() === 'ar' || preg_match('/\p{Arabic}/u', $question)) {
            return 'ar';
        }

        return 'en';
    }
}
