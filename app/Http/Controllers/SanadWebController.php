<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingHandymanMapping;
use App\Models\Payment;
use App\Models\SanadAiInteraction;
use App\Models\SanadAiKnowledgeItem;
use App\Services\SanadAiRagService;
use App\Services\SanadKnowledgeIngestionService;
use App\Models\SanadAuditLog;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\SanadDocumentVaultItem;
use App\Models\SanadRequestAction;
use App\Models\SanadPartnerServicePerformance;
use App\Models\User;
use App\Models\ProviderDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\SanadAssignmentDecision;
use App\Services\SanadAssignmentService;
use App\Models\Notification;
use App\Models\SanadDocumentRequest;

class SanadWebController extends Controller
{
    public function assignments(Request $request, SanadAssignmentService $assignmentService)
    {
        abort_unless($this->canUseAssignmentModule(), 403);
        $query = Booking::with(['customer', 'service', 'provider'])->latest();
        if ($request->assignment_state === 'unassigned') $query->whereNull('provider_id');
        if ($request->assignment_state === 'assigned') $query->whereNotNull('provider_id');
        $orders = $query->paginate(25)->withQueryString();
        $partners = User::where('user_type', 'provider')->where('status', 1)->orderBy('display_name')->get();
        $latestDecisions = SanadAssignmentDecision::with(['selectedProvider', 'actor'])
            ->whereIn('booking_id', $orders->pluck('id'))
            ->latest()
            ->get()
            ->groupBy('booking_id')
            ->map(fn ($items) => $items->first());
        $recommendations = [];
        foreach ($orders as $order) {
            $recommendations[$order->id] = $assignmentService->candidates($order)->take(3);
            $top = $recommendations[$order->id]->first();
            if ($top && !$order->provider_id && !SanadAssignmentDecision::where('booking_id', $order->id)->where('status', 'recommended')->exists()) {
                SanadAssignmentDecision::create([
                    'booking_id' => $order->id,
                    'recommended_provider_id' => $top->id,
                    'assignment_mode' => 'suggested',
                    'status' => 'recommended',
                    'score_snapshot' => ['selected_score' => $top->assignment_score, 'metrics' => $top->assignment_metrics],
                ]);
            }
        }
        return view('sanad.assignments', compact('orders', 'recommendations', 'partners', 'latestDecisions'));
    }

    public function confirmAssignment(Request $request, $id, SanadAssignmentService $assignmentService)
    {
        abort_unless($this->canUseAssignmentModule(true), 403);
        $booking = Booking::findOrFail($id);
        $request->validate(['provider_id' => 'required|exists:users,id', 'reason' => 'nullable|string|max:2000']);
        if ($booking->provider_id && !$request->reason) return back()->withErrors('A reason is required when reassigning an order.');
        $candidates = $assignmentService->candidates($booking);
        $selected = $candidates->firstWhere('id', (int) $request->provider_id)
            ?: User::where('user_type', 'provider')->where('status', 1)->find($request->provider_id);
        if (!$selected) return back()->withErrors('The selected Partner is inactive or unavailable.');
        $selectedScore = $selected->assignment_score ?? null;
        $selectedMetrics = $selected->assignment_metrics ?? [];
        $decision = SanadAssignmentDecision::create([
            'booking_id' => $booking->id,
            'recommended_provider_id' => optional($candidates->first())->id,
            'selected_provider_id' => $selected->id,
            'assignment_mode' => $request->mode ?: 'suggested',
            'status' => 'pending_partner_acceptance', 'reason' => $request->reason,
            'score_snapshot' => ['selected_score' => $selectedScore, 'metrics' => $selectedMetrics, 'candidates' => $candidates->take(3)->map(fn ($p) => ['id' => $p->id, 'score' => $p->assignment_score])->values()],
            'decided_by' => auth()->id(),
        ]);
        $booking->provider_id = $selected->id;
        $booking->assignment_mode = $request->mode ?: 'suggested';
        $booking->assignment_reason = $request->reason;
        $booking->assigned_by = auth()->id();
        $booking->assigned_at = now();
        $booking->status = 'pending';
        $booking->sanad_stage = 'assigned_to_partner';
        $booking->save();
        return back()->withSuccess('Partner assignment sent. Waiting for Partner acceptance.');
    }
    public function dashboard()
    {
        abort_unless($this->canUseSanadModule('dashboard'), 403);
        $auth_user = authSession();
        $user = auth()->user();
        $role = $this->sanadRole($user);
        $pageTitle = 'Sanad ' . Str::headline($role) . ' Dashboard';
        $dashboard = $this->roleDashboardData($role);

        return view('sanad.dashboard', compact('pageTitle', 'auth_user', 'role', 'dashboard'));
    }

    public function partnerPerformance(Request $request)
    {
        $performances = SanadPartnerServicePerformance::with(['provider', 'service'])
            ->when($request->filled('provider_id'), fn ($query) => $query->where('provider_id', $request->provider_id))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->service_id))
            ->orderByDesc('quality_score')
            ->orderByDesc('completed_orders')
            ->paginate(25)
            ->withQueryString();

        return view('sanad.partner-performance', [
            'pageTitle' => 'Partner Performance',
            'auth_user' => authSession(),
            'performances' => $performances,
        ]);
    }

    public function aiConsole(Request $request)
    {
        abort_unless($this->canUseSanadModule('ai_tools'), 403);
        $user = auth()->user();
        $knowledgeItems = SanadAiKnowledgeItem::withCount('chunks')
            ->where('title', 'not like', '%Smoke Test%')
            ->where('title', 'not like', '%Integrated QA Knowledge%')
            ->where('title', 'not like', '%Driving License Renewal Customer Requirements%')
            ->where('title', 'not like', '%Payment help%')
            ->latest()
            ->paginate(20);

        $interactionQuery = SanadAiInteraction::query()
            ->where('question', 'not like', '%Smoke Test%')
            ->where('answer', 'not like', '%Smoke test%')
            ->where('question', 'not like', '%Integrated QA Knowledge%')
            ->where('answer', 'not like', '%Integrated QA%')
            ->when(!$user->hasAnyRole(['admin', 'demo_admin']), fn ($query) => $query->where('user_id', $user->id));

        $interactions = (clone $interactionQuery)->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('sanad.ai-console', [
            'pageTitle' => 'Sanad AI Assistant',
            'auth_user' => authSession(),
            'knowledgeItems' => $knowledgeItems,
            'interactions' => $interactions,
            'aiSummary' => [
                'knowledge_items' => SanadAiKnowledgeItem::where('title', 'not like', '%Smoke Test%')
                    ->where('title', 'not like', '%Integrated QA Knowledge%')
                    ->where('title', 'not like', '%Driving License Renewal Customer Requirements%')
                    ->where('title', 'not like', '%Payment help%')
                    ->count(),
                'active_knowledge_items' => SanadAiKnowledgeItem::where('is_active', true)
                    ->where('title', 'not like', '%Smoke Test%')
                    ->where('title', 'not like', '%Integrated QA Knowledge%')
                    ->where('title', 'not like', '%Driving License Renewal Customer Requirements%')
                    ->where('title', 'not like', '%Payment help%')
                    ->count(),
                'agent_confidence' => round((float) (clone $interactionQuery)->avg('confidence') * 100),
                'interactions' => (clone $interactionQuery)->count(),
                'escalations' => (clone $interactionQuery)->where('requires_escalation', true)->count(),
            ],
        ]);
    }

    public function storeAiKnowledge(Request $request, SanadAiRagService $rag, SanadKnowledgeIngestionService $ingestion)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return redirect()->back()->withErrors('Only admins can manage Sanad AI knowledge.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'knowledge_pdfs' => 'nullable|array',
            'knowledge_pdfs.*' => 'file|mimes:pdf|max:20480',
            'google_doc_url' => 'nullable|url|max:1000',
            'visible_to' => 'nullable|array',
        ]);

        $ingested = $ingestion->extract(
            $request->input('content'),
            $request->file('knowledge_pdfs', []),
            $request->input('google_doc_url')
        );

        if ($ingested['content'] === '') {
            return redirect()->back()->withErrors('Add content, upload a readable PDF, or provide a public Google Docs link.');
        }

        $classification = $this->classifyKnowledge($request->title, $ingested['content']);

        $item = SanadAiKnowledgeItem::create([
            'title' => $request->title,
            'category' => $classification['category'],
            'content' => $ingested['content'],
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'metadata' => [
                'tags' => $classification['tags'],
                'ingestion' => $ingested['metadata'],
                'agent_confidence' => $classification['confidence'],
            ],
            'is_active' => true,
            'created_by' => optional(auth()->user())->id,
        ]);

        $rag->indexKnowledgeItem($item);

        $this->audit($request, 'sanad.ai.knowledge_created', $item);

        return redirect()->back()->withSuccess('Sanad AI knowledge item added.');
    }

    public function updateAiKnowledge(Request $request, $id, SanadAiRagService $rag)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'visible_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $item = SanadAiKnowledgeItem::findOrFail($id);
        $classification = $this->classifyKnowledge($request->title, $request->content);
        $metadata = $item->metadata ?: [];
        $metadata['tags'] = $classification['tags'];
        $metadata['agent_confidence'] = $classification['confidence'];
        $metadata['fine_tuned_at'] = now()->toIso8601String();

        $item->update([
            'title' => $request->title,
            'category' => $classification['category'],
            'content' => $request->content,
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'metadata' => $metadata,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $rag->indexKnowledgeItem($item);
        $this->audit($request, 'sanad.ai.knowledge_updated', $item);

        return redirect()->back()->withSuccess('Sanad AI knowledge item updated.');
    }

    public function askAi(Request $request, SanadAiRagService $rag)
    {
        abort_unless($this->canUseSanadModule('ai_tools', 'write'), 403);
        $request->validate([
            'question' => 'required|string',
            'booking_id' => 'nullable|integer',
        ]);

        $booking = $request->booking_id ? Booking::find($request->booking_id) : null;
        $answer = $rag->answer($request->question, $booking, optional(auth()->user())->user_type ?: 'admin');
        $confidence = $answer['confidence'];
        $requiresEscalation = $answer['requires_escalation'];

        $interaction = SanadAiInteraction::create([
            'user_id' => optional(auth()->user())->id,
            'booking_id' => $request->booking_id,
            'question' => $request->question,
            'answer' => $answer['answer'],
            'confidence' => $confidence,
            'requires_escalation' => $requiresEscalation,
            'status' => $requiresEscalation ? 'escalated' : 'answered',
            'metadata' => [
                'sources' => $answer['sources'],
                'live_context' => $answer['live_context'],
                'provider' => $answer['provider_metadata'] ?? [],
                'langsmith_run_id' => $answer['langsmith_run_id'] ?? null,
            ],
        ]);

        $this->audit($request, 'sanad.ai.asked', $interaction);

        return redirect()->back()->withSuccess('Sanad AI response recorded.');
    }

    public function aiEscalations(Request $request)
    {
        abort_unless($this->canUseSanadModule('ai_tools'), 403);

        $baseQuery = $this->realAiInteractionsQuery()
            ->with(['user', 'booking.customer', 'booking.service']);

        $status = $request->input('status', 'open');
        $query = clone $baseQuery;

        if ($status === 'open') {
            $query->where(function ($builder) {
                $builder->where('requires_escalation', true)
                    ->orWhereIn('status', ['escalated', 'handover_required', 'needs_revision']);
            });
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $interactions = $query->latest()->paginate(15)->withQueryString();

        $summaryQuery = $this->realAiInteractionsQuery();
        $summary = [
            'open' => (clone $summaryQuery)->where(function ($builder) {
                $builder->where('requires_escalation', true)
                    ->orWhereIn('status', ['escalated', 'handover_required', 'needs_revision']);
            })->count(),
            'approved' => (clone $summaryQuery)->where('status', 'approved')->count(),
            'resolved' => (clone $summaryQuery)->where('status', 'resolved')->count(),
            'needs_revision' => (clone $summaryQuery)->where('status', 'needs_revision')->count(),
            'avg_confidence' => round((float) (clone $summaryQuery)->avg('confidence') * 100),
        ];

        return view('sanad.ai-escalations', [
            'pageTitle' => 'AI Escalation Workspace',
            'auth_user' => authSession(),
            'interactions' => $interactions,
            'summary' => $summary,
            'status' => $status,
        ]);
    }

    public function reviewAiEscalation(Request $request, $id)
    {
        abort_unless($this->canUseSanadModule('ai_tools', 'write'), 403);

        $request->validate([
            'review_action' => 'required|in:approve,edit_approve,resolve,needs_revision',
            'answer' => 'nullable|string',
            'review_note' => 'nullable|string|max:2000',
        ]);

        if ($request->review_action === 'edit_approve' && !trim((string) $request->answer)) {
            return back()->withErrors('Add the corrected answer before saving and approving.');
        }

        $interaction = SanadAiInteraction::findOrFail($id);
        $previous = Arr::only($interaction->toArray(), ['answer', 'status', 'requires_escalation']);
        $metadata = $interaction->metadata ?: [];
        $action = $request->review_action;

        if (in_array($action, ['edit_approve', 'approve'], true) && $request->filled('answer')) {
            $interaction->answer = $request->answer;
        }

        $interaction->status = [
            'approve' => 'approved',
            'edit_approve' => 'approved',
            'resolve' => 'resolved',
            'needs_revision' => 'needs_revision',
        ][$action];
        $interaction->requires_escalation = $action === 'needs_revision';
        $metadata['review'] = [
            'action' => $action,
            'note' => $request->review_note,
            'reviewed_by' => auth()->id(),
            'reviewed_by_name' => optional(auth()->user())->display_name ?: optional(auth()->user())->first_name ?: optional(auth()->user())->email,
            'reviewed_at' => now()->toIso8601String(),
            'previous' => $previous,
        ];
        $interaction->metadata = $metadata;
        $interaction->save();

        $this->audit($request, 'sanad.ai.escalation_reviewed', $interaction, [
            'action' => $action,
            'previous' => $previous,
            'current' => Arr::only($interaction->toArray(), ['answer', 'status', 'requires_escalation']),
        ]);

        return back()->withSuccess('AI escalation review saved.');
    }

    public function indexRequests(Request $request)
    {
        abort_unless($this->canUseOrdersModule(), 403);
        $query = Booking::with(['customer', 'provider', 'service', 'payment', 'handymanAdded.handyman', 'sanadDocuments', 'sanadBuzzAlerts', 'sanadChatThreads'])
            ->myBooking()
            ->latest();

        if ($request->filled('sanad_stage')) {
            $query->where('sanad_stage', $request->sanad_stage);
        }

        if ($request->filled('sanad_priority')) {
            $query->where('sanad_priority', $request->sanad_priority);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('sla_state')) {
            if ($request->sla_state === 'overdue') {
                $query->whereNotNull('sla_due_at')->where('sla_due_at', '<', now());
            }
            if ($request->sla_state === 'due_soon') {
                $query->whereBetween('sla_due_at', [now(), now()->addDay()]);
            }
            if ($request->sla_state === 'none') {
                $query->whereNull('sla_due_at');
            }
        }

        if ($request->filled('assignment_state')) {
            if ($request->assignment_state === 'assigned') {
                $query->whereHas('handymanAdded');
            }
            if ($request->assignment_state === 'unassigned') {
                $query->whereDoesntHave('handymanAdded');
            }
        }

        if ($request->filled('payment_state')) {
            if ($request->payment_state === 'no_payment') {
                $query->whereDoesntHave('payment');
            } else {
                $query->whereHas('payment', function ($paymentQuery) use ($request) {
                    $paymentQuery->where('payment_status', $request->payment_state);
                });
            }
        }

        if ($request->filled('action_state')) {
            if ($request->action_state === 'needs_action') {
                $query->where(function ($q) {
                    $q->whereNull('assigned_at')
                        ->orWhereDoesntHave('handymanAdded')
                        ->orWhere(function ($slaQuery) {
                            $slaQuery->whereNotNull('sla_due_at')->where('sla_due_at', '<', now());
                        })
                        ->orWhereHas('sanadDocuments', function ($documentQuery) {
                            $documentQuery->where('verification_status', 'pending');
                        })
                        ->orWhereHas('sanadBuzzAlerts', function ($buzzQuery) {
                            $buzzQuery->where('status', 'unread');
                        });
                });
            }
            if ($request->action_state === 'pending_documents') {
                $query->whereHas('sanadDocuments', function ($documentQuery) {
                    $documentQuery->where('verification_status', 'pending');
                });
            }
            if ($request->action_state === 'unread_buzz') {
                $query->whereHas('sanadBuzzAlerts', function ($buzzQuery) {
                    $buzzQuery->where('status', 'unread');
                });
            }
            if ($request->action_state === 'open_chat') {
                $query->whereHas('sanadChatThreads', function ($chatQuery) {
                    $chatQuery->where('status', 'open');
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sanad_reference', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('display_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('provider', function ($providerQuery) use ($search) {
                        $providerQuery->where('display_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $summary = $this->requestQueueSummary();
        $requests = $query->paginate(15)->appends($request->query());
        $pageTitle = 'Sanad Requests';
        $auth_user = authSession();

        return view('sanad.requests-index', compact('requests', 'pageTitle', 'auth_user', 'summary'));
    }

    public function showRequest($id)
    {
        abort_unless($this->canUseOrdersModule(), 403);
        $bookingdata = Booking::with(['customer', 'provider', 'service', 'payment.paymentHistory', 'handymanAdded.handyman', 'sanadRequestActions.actor'])
            ->myBooking()
            ->findOrFail($id);

        $pageTitle = 'Sanad Request #' . ($bookingdata->sanad_reference ?: $bookingdata->id);
        $auth_user = authSession();
        $documents = $this->visibleDocumentsQuery($bookingdata)->latest()->get();
        $buzzAlerts = $this->visibleBuzzQuery($bookingdata)->latest()->get();
        $chatThread = $this->visibleChatThread($bookingdata);
        $chatMessages = $chatThread ? $chatThread->messages()->latest()->take(25)->get()->reverse()->values() : collect();
        $assignableEmployees = $this->assignableEmployees($bookingdata);
        $monitoring = $this->requestMonitoring($bookingdata, $documents, $buzzAlerts, $chatThread);
        $billing = $this->requestBilling($bookingdata);
        $requestActions = $bookingdata->sanadRequestActions()->with('actor')->latest()->take(12)->get();
        $qualityControl = $this->requestQualityControl($bookingdata);

        return view('sanad.request-show', compact(
            'bookingdata',
            'pageTitle',
            'auth_user',
            'documents',
            'buzzAlerts',
            'chatThread',
            'chatMessages',
            'assignableEmployees',
            'monitoring',
            'billing',
            'requestActions',
            'qualityControl'
        ));
    }

    public function storeRequestAction(Request $request, $id)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'action' => 'required|string|in:accept_order,reject_order,request_missing_documents,reassign_employees,add_internal_note,complete_current_stage,request_admin_review,quality_approve,quality_reject,quality_rework,mark_completed',
            'reason' => 'nullable|string|max:1000',
            'internal_note' => 'nullable|string|max:2000',
        ]);

        if (in_array($request->action, ['reject_order', 'request_missing_documents', 'reassign_employees', 'request_admin_review', 'quality_reject', 'quality_rework'], true) && empty($request->reason)) {
            return redirect()->back()->withErrors('A reason is required for this Sanad action.');
        }

        if (in_array($request->action, ['quality_approve', 'quality_reject', 'quality_rework'], true) && !auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return redirect()->back()->withErrors('Only admins can record Sanad quality control decisions.');
        }
        if ($request->action === 'add_internal_note') {
            $this->abortUnlessEmployeeFlag('internal_notes');
        }
        if ($request->action === 'complete_current_stage') {
            $this->abortUnlessEmployeeFlag('complete_stage');
        }
        if (in_array($request->action, ['request_missing_documents', 'request_admin_review'], true)) {
            $this->abortUnlessEmployeeFlag('review_documents');
        }
        if ($request->action === 'mark_completed' && auth()->user()->user_type === 'handyman') {
            abort(403);
        }

        $previousStatus = $booking->status;
        $previousStage = $booking->sanad_stage;
        $stage = $booking->sanad_stage ?: 'submitted';
        $status = $booking->status;

        switch ($request->action) {
            case 'accept_order':
                $status = 'accept';
                $stage = 'assigned_to_partner';
                break;
            case 'reject_order':
                $status = 'rejected';
                $stage = 'rejected';
                break;
            case 'request_missing_documents':
                $stage = 'waiting_for_documents';
                break;
            case 'reassign_employees':
                $stage = 'assigned_to_employee';
                break;
            case 'complete_current_stage':
                $stage = $this->nextLifecycleStage($stage);
                if ($stage === 'completed') {
                    $status = 'completed';
                }
                break;
            case 'request_admin_review':
                $stage = 'awaiting_quality_review';
                break;
            case 'quality_approve':
                $stage = 'ready_for_delivery';
                break;
            case 'quality_reject':
                $stage = 'legal_review';
                break;
            case 'quality_rework':
                $stage = 'in_progress';
                break;
            case 'mark_completed':
                $status = 'completed';
                $stage = 'completed';
                break;
        }

        $booking->status = $status;
        $booking->sanad_stage = $stage;
        if ($request->filled('reason')) {
            $booking->reason = $request->reason;
        }
        if ($stage === 'completed' && empty($booking->closed_at)) {
            $booking->closed_at = now();
        }
        if ($stage === 'assigned_to_partner' && empty($booking->assigned_at)) {
            $booking->assigned_at = now();
        }
        $booking->save();

        if ($request->action === 'accept_order') {
            $assignmentDecision = SanadAssignmentDecision::where('booking_id', $booking->id)
                ->where('selected_provider_id', $booking->provider_id)
                ->latest()
                ->first();
            if ($assignmentDecision) {
                $snapshot = $assignmentDecision->score_snapshot ?: [];
                $snapshot['accepted_at'] = now()->toIso8601String();
                $assignmentDecision->status = 'accepted';
                $assignmentDecision->score_snapshot = $snapshot;
                $assignmentDecision->save();
            }
        }

        $action = SanadRequestAction::create([
            'booking_id' => $booking->id,
            'actor_id' => optional(auth()->user())->id,
            'actor_role' => optional(auth()->user())->user_type,
            'action' => $request->action,
            'previous_status' => $previousStatus,
            'current_status' => $booking->status,
            'previous_stage' => $previousStage,
            'current_stage' => $booking->sanad_stage,
            'reason' => $request->reason,
            'internal_note' => $request->internal_note,
            'metadata' => [
                'source' => 'web_dashboard',
                'role' => $this->sanadRole(auth()->user()),
            ],
        ]);

        $this->audit($request, 'sanad.request.action_recorded', $action, [
            'booking_id' => $booking->id,
            'action' => $request->action,
            'previous_stage' => $previousStage,
            'current_stage' => $booking->sanad_stage,
        ]);

        return redirect()->back()->withSuccess('Sanad request action recorded.');
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        abort_unless($this->canUseSanadModule('payments'), 403);
        if (auth()->user()->user_type === 'handyman') {
            abort(403);
        }
        $booking = Booking::with('payment')->myBooking()->findOrFail($id);
        $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,advanced_paid,pending_by_admin,refunded',
        ]);

        $payment = $booking->payment;
        if (!$payment) {
            return redirect()->back()->withErrors('This request does not have a payment record yet.');
        }

        $previous = $payment->payment_status;
        $payment->payment_status = $request->payment_status;
        $payment->save();

        $this->audit($request, 'sanad.payment.status_updated', $payment, [
            'booking_id' => $booking->id,
            'previous_payment_status' => $previous,
            'current_payment_status' => $payment->payment_status,
        ]);

        return redirect()->back()->withSuccess('Sanad payment status updated.');
    }

    public function assignEmployees(Request $request, $id)
    {
        abort_unless($this->canUseTeamEmployeeModule(true), 403);
        $this->abortUnlessEmployeeFlag('team_collaboration');
        $booking = Booking::with('handymanAdded')->myBooking()->findOrFail($id);
        $request->validate([
            'handyman_id' => 'nullable|array',
            'handyman_id.*' => 'integer',
        ]);

        $allowedEmployeeIds = $this->assignableEmployees($booking)->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $requestedEmployeeIds = collect($request->handyman_id ?: [])
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->unique()
            ->values();

        $invalidEmployeeIds = $requestedEmployeeIds->diff($allowedEmployeeIds);
        if ($invalidEmployeeIds->isNotEmpty()) {
            return redirect()->back()->withErrors('One or more selected employees cannot be assigned to this request.');
        }

        $previousEmployeeIds = $booking->handymanAdded()->pluck('handyman_id')->map(function ($id) {
            return (int) $id;
        })->all();

        $booking->handymanAdded()->delete();
        foreach ($requestedEmployeeIds as $employeeId) {
            BookingHandymanMapping::create([
                'booking_id' => $booking->id,
                'handyman_id' => $employeeId,
            ]);
        }

        $booking->assigned_by = optional(auth()->user())->id;
        $booking->assigned_at = now();
        if ($requestedEmployeeIds->isNotEmpty()) {
            $booking->status = 'accept';
            $booking->sanad_stage = 'assigned_to_employee';
        }
        $booking->save();

        $this->audit($request, 'sanad.request.employees_assigned', $booking, [
            'previous_employee_ids' => $previousEmployeeIds,
            'current_employee_ids' => $requestedEmployeeIds->all(),
        ]);

        return redirect()->back()->withSuccess('Sanad employees assigned.');
    }

    public function updateRequestLifecycle(Request $request, $id)
    {
        abort_unless($this->canUseOrdersModule(true), 403);
        $this->abortUnlessEmployeeFlag('complete_stage');
        $request->validate([
            'sanad_stage' => 'required|string',
            'sanad_priority' => 'nullable|string|in:low,normal,high,urgent',
            'sla_due_at' => 'nullable|date',
        ]);

        $allowedStages = config('sanad.request_lifecycle', []);
        if (!in_array($request->sanad_stage, $allowedStages, true)) {
            return redirect()->back()->withErrors('Invalid Sanad request lifecycle stage.');
        }

        $booking = Booking::myBooking()->findOrFail($id);
        $previous = Arr::only($booking->toArray(), ['sanad_stage', 'sanad_priority', 'sla_due_at']);

        $booking->fill($request->only(['sanad_stage', 'sanad_priority', 'sla_due_at']));
        if (empty($booking->sanad_reference)) {
            $booking->sanad_reference = 'SANAD-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
        }
        if ($request->sanad_stage === 'escalated' && empty($booking->escalated_at)) {
            $booking->escalated_at = now();
        }
        if (in_array($request->sanad_stage, ['completed', 'cancelled'], true) && empty($booking->closed_at)) {
            $booking->closed_at = now();
        }
        $booking->save();

        SanadAuditLog::create([
            'actor_id' => optional(auth()->user())->id,
            'actor_role' => optional(auth()->user())->user_type,
            'action' => 'sanad.request.lifecycle_updated',
            'auditable_type' => Booking::class,
            'auditable_id' => $booking->id,
            'metadata' => [
                'previous' => $previous,
                'current' => Arr::only($booking->toArray(), ['sanad_stage', 'sanad_priority', 'sla_due_at']),
                'source' => 'web_dashboard',
            ],
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);

        return redirect()->back()->withSuccess('Sanad request lifecycle updated.');
    }

    public function storeDocument(Request $request, $id)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);
        $this->abortUnlessEmployeeFlag('upload_documents');
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'document_type' => 'required|string|max:255',
            'document_key' => 'nullable|string|max:100',
            'source' => 'nullable|in:request,customer,partner',
            'provider_id' => 'nullable|exists:users,id',
            'file_name' => 'nullable|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'document' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string',
            'retention_until' => 'nullable|date',
        ]);

        $isPartnerSideUser = auth()->user()->hasRole('provider') || (auth()->user()->user_type === 'handyman' && !empty(auth()->user()->provider_id));
        $source = $request->source ?: ($isPartnerSideUser ? 'partner' : 'customer');
        if (auth()->user()->hasRole('provider') && (int) $request->provider_id !== auth()->id()) abort(403);
        if (auth()->user()->user_type === 'handyman' && !empty(auth()->user()->provider_id) && (int) $booking->provider_id !== (int) auth()->user()->provider_id) abort(403);
        $allowedDocuments = $this->serviceDocumentOptions($booking->service);
        if ($allowedDocuments->isNotEmpty() && auth()->user()->hasAnyRole(['provider', 'user']) && $request->document_key !== 'custom') {
            $submittedType = trim((string) $request->document_type);
            $submittedKey = trim((string) $request->document_key);
            $isConfigured = $allowedDocuments->contains(function ($document) use ($submittedType, $submittedKey) {
                return $document['name'] === $submittedType || ($submittedKey !== '' && $document['key'] === $submittedKey);
            });
            if (!$isConfigured) {
                return redirect()->back()->withErrors('Please select a configured document type for this service.');
            }
        }
        $document = SanadDocumentVaultItem::create([
            'booking_id' => $booking->id,
            'service_id' => $booking->service_id,
            'provider_id' => $source === 'partner' ? ($request->provider_id ?: $booking->provider_id) : null,
            'owner_id' => $booking->customer_id,
            'uploaded_by' => optional(auth()->user())->id,
            'document_type' => $request->document_type,
            'document_key' => $request->document_key,
            'source' => $source,
            'visible_to' => $request->visible_to ?: ['admin'],
            'file_name' => $request->file_name,
            'file_path' => $request->file_path,
            'retention_until' => $request->retention_until ?: now()->addHours(48),
        ]);
        if ($request->hasFile('document')) storeMediaFile($document, $request->file('document'), 'document');

        $this->audit($request, 'sanad.document.created', $document);

        return redirect()->back()->withSuccess('Sanad document added.');
    }

    private function serviceDocumentOptions($service)
    {
        return collect(optional($service)->required_documents ?: [])
            ->map(function ($document, $index) {
                if (is_array($document)) {
                    $name = trim((string) ($document['name'] ?? $document['label'] ?? $document['title'] ?? $document['key'] ?? ''));
                    $key = trim((string) ($document['key'] ?? Str::slug($name ?: 'document-'.$index, '_')));

                    return $name ? ['key' => $key, 'name' => $name] : null;
                }

                $name = trim((string) $document);

                return $name ? ['key' => Str::slug($name, '_'), 'name' => $name] : null;
            })
            ->filter()
            ->values();
    }

    public function approveDocument(Request $request, $id, $documentId)
    {
        return $this->reviewDocument($request->merge(['verification_status' => 'approved']), $id, $documentId);
    }

    public function reviewDocument(Request $request, $id, $documentId)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);
        $this->abortUnlessEmployeeFlag('review_documents');
        $booking = Booking::myBooking()->findOrFail($id);
        $document = $this->visibleDocumentsQuery($booking)->findOrFail($documentId);
        $request->validate([
            'verification_status' => 'required|in:approved,rejected,replacement_requested,pending',
            'reason' => 'nullable|string|max:2000',
        ]);
        if (in_array($request->verification_status, ['rejected', 'replacement_requested'], true) && !$request->reason) {
            return back()->withErrors('A reason is required for rejected or replacement-requested documents.');
        }

        $document->verification_status = $request->verification_status;
        $document->approved_at = $request->verification_status === 'approved' ? now() : null;
        $document->approved_by = $request->verification_status === 'approved' ? optional(auth()->user())->id : null;
        $document->reviewed_at = now();
        $document->reviewed_by = auth()->id();
        $document->review_reason = $request->reason;
        $document->save();

        foreach (array_filter([$document->owner_id, $document->provider_id]) as $recipientId) {
            Notification::create([
                'id' => Str::random(32),
                'type' => 'sanad_document_review',
                'notifiable_type' => User::class,
                'notifiable_id' => $recipientId,
                'data' => json_encode([
                    'type' => 'sanad_document_review', 'id' => $document->id,
                    'subject' => 'Document review',
                    'message' => 'Your document was marked '.Str::headline($document->verification_status).'.',
                ]),
            ]);
        }

        $this->audit($request, 'sanad.document.reviewed', $document, ['status' => $document->verification_status, 'reason' => $request->reason]);

        return redirect()->back()->withSuccess('Document review saved.');
    }

    public function documentQueue(Request $request)
    {
        abort_unless($this->canUseDocumentReviewModule(), 403);

        $partnerCards = ProviderDocument::with(['providers', 'document', 'media'])
            ->whereHas('providers', function ($query) {
                $query->where('user_type', 'provider');
            })
            ->whereHas('document', function ($query) {
                $query->where('status', 1);
            })
            ->when($request->filled('partner_status'), function ($query) use ($request) {
                if (in_array($request->partner_status, ['pending', 'approved', 'rejected'], true)) {
                    $query->where('verification_status', $request->partner_status);
                } else {
                    $query->where('is_verified', (int) $request->partner_status);
                }
            })
            ->latest()
            ->get()
            ->groupBy('provider_id')
            ->map(function ($documents) {
                $partner = optional($documents->first())->providers;
                $total = $documents->count();
                $approved = $documents->where('is_verified', 1)->count();
                $rejected = $documents->where('verification_status', 'rejected')->count();
                $uploaded = $documents->filter(fn ($document) => getMediaFileExit($document, 'provider_document'))->count();

                return [
                    'partner' => $partner,
                    'documents' => $documents,
                    'total' => $total,
                    'uploaded' => $uploaded,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'pending' => max($total - $approved - $rejected, 0),
                    'status' => $rejected > 0 ? 'needs_revision' : ($total > 0 && $approved === $total ? 'verified' : ($uploaded > 0 ? 'in_review' : 'waiting_for_uploads')),
                    'progress' => $total > 0 ? (int) round(($approved / $total) * 100) : 0,
                ];
            })
            ->sortBy(fn ($group) => optional($group['partner'])->display_name ?: '');

        $uploadedDocuments = SanadDocumentVaultItem::with(['booking.customer', 'booking.provider', 'booking.sanadDocumentRequests.document', 'service', 'provider'])
            ->whereNotNull('booking_id')
            ->whereHas('booking')
            ->where($this->realDocumentConstraint())
            ->when($request->filled('order_status'), fn ($q) => $q->where('verification_status', $request->order_status))
            ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
            ->latest()
            ->get();

        $documentRequestBookingIds = SanadDocumentRequest::whereHas('booking')->pluck('booking_id');

        $requestBookingIds = $uploadedDocuments->pluck('booking_id')->merge($documentRequestBookingIds)->filter()->unique();

        $requestCards = Booking::with(['customer', 'provider', 'service', 'sanadDocuments' => function ($query) use ($request) {
                $query->where($this->realDocumentConstraint())
                    ->when($request->filled('order_status'), fn ($q) => $q->where('verification_status', $request->order_status))
                    ->when($request->filled('source'), fn ($q) => $q->where('source', $request->source))
                    ->latest();
            }, 'sanadDocumentRequests.document', 'sanadDocumentRequests.requester'])
            ->whereIn('id', $requestBookingIds)
            ->latest()
            ->get()
            ->map(function ($booking) {
                $documents = $booking->sanadDocuments;
                $service = $booking->service;
                $requiredDocuments = collect(optional($service)->required_documents ?: [])->values();
                $submittedKeys = $documents->pluck('document_key')->filter()->map(fn ($key) => Str::slug($key, '_'))->all();
                $submittedNames = $documents->pluck('document_type')->filter()->map(fn ($name) => Str::slug($name, '_'))->all();
                $missingRequired = $requiredDocuments->filter(function ($document) use ($submittedKeys, $submittedNames) {
                    $key = Str::slug((string) ($document['key'] ?? $document['name'] ?? ''), '_');
                    $name = Str::slug((string) ($document['name'] ?? ''), '_');

                    return !in_array($key, $submittedKeys, true) && !in_array($name, $submittedNames, true);
                });
                $documentRequests = $booking->sanadDocumentRequests->sortByDesc('created_at')->values();
                $partnerRequests = $documentRequests->where('requested_from', 'customer')->values();
                $totalRequired = $requiredDocuments->count();
                $approved = $documents->where('verification_status', 'approved')->count();
                $pending = $documents->where('verification_status', 'pending')->count();

                return [
                    'booking' => $booking,
                    'service' => $service,
                    'documents' => $documents,
                    'document_requests' => $documentRequests,
                    'partner_requests' => $partnerRequests,
                    'missing_required' => $missingRequired->values(),
                    'required_count' => $totalRequired,
                    'submitted_count' => $documents->count(),
                    'approved_count' => $approved,
                    'pending_count' => $pending,
                    'progress' => $totalRequired > 0
                        ? (int) min(100, round(($approved / $totalRequired) * 100))
                        : ($documents->count() > 0 ? 100 : 0),
                ];
            })
            ->sortByDesc(fn ($group) => optional($group['booking'])->created_at);

        $partnerSummary = [
            'partners' => $partnerCards->count(),
            'pending' => $partnerCards->sum('pending'),
            'approved' => $partnerCards->sum('approved'),
        ];

        $orderSummary = [
            'orders' => $requestCards->count(),
            'pending' => $requestCards->sum('pending_count'),
            'approved' => $requestCards->sum('approved_count'),
        ];

        return view('sanad.document-queue', compact('partnerCards', 'requestCards', 'partnerSummary', 'orderSummary'));
    }

    private function realDocumentConstraint(): \Closure
    {
        return function ($query) {
            $query->where('document_type', 'not like', '%Privacy Check%')
                ->where('document_type', 'not like', '%Smoke%')
                ->where('document_type', 'not like', '%Integrated QA%')
                ->where(function ($fileQuery) {
                    $fileQuery->whereNull('file_name')
                        ->orWhere(function ($nestedFileQuery) {
                            $nestedFileQuery->where('file_name', 'not like', '%smoke%')
                                ->where('file_name', 'not like', '%integrated-qa%')
                                ->where('file_name', 'not like', '%qa.pdf%');
                        });
                })
                ->where(function ($pathQuery) {
                    $pathQuery->whereNull('file_path')
                        ->orWhere(function ($nestedPathQuery) {
                            $nestedPathQuery->where('file_path', 'not like', '%smoke%')
                                ->where('file_path', 'not like', '%integrated-qa%')
                                ->where('file_path', 'not like', '%qa.pdf%');
                        });
                });
        };
    }

    public function reviewPartnerDocument(Request $request, $documentId)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);

        $request->validate([
            'verification_status' => 'nullable|in:pending,approved,rejected',
            'is_verified' => 'nullable|in:0,1',
            'review_reason' => 'nullable|string|max:2000',
        ]);

        $document = ProviderDocument::findOrFail($documentId);
        $status = $request->verification_status ?: ($request->is_verified ? 'approved' : 'pending');
        if ($status === 'rejected' && !$request->filled('review_reason')) {
            return back()->withErrors('A rejection reason is required.');
        }

        $document->verification_status = $status;
        $document->is_verified = $status === 'approved' ? 1 : 0;
        $document->review_reason = $status === 'pending' ? null : $request->review_reason;
        $document->reviewed_by = auth()->id();
        $document->reviewed_at = now();
        $document->save();

        $this->audit($request, 'sanad.partner_document.reviewed', $document, ['verification_status' => $document->verification_status, 'reason' => $document->review_reason]);

        return redirect()->back()->withSuccess('Partner document review saved.');
    }

    public function storeBuzz(Request $request, $id)
    {
        $this->abortUnlessEmployeeFlag('send_buzz');
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'recipient_role' => 'nullable|string|max:255',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'message' => 'required|string|max:1000',
        ]);

        $alert = SanadBuzzAlert::create([
            'booking_id' => $booking->id,
            'sender_id' => optional(auth()->user())->id,
            'recipient_role' => $request->recipient_role ?: 'admin',
            'priority' => $request->priority ?: 'urgent',
            'message' => $request->message,
        ]);

        $this->audit($request, 'sanad.buzz.created', $alert);

        return redirect()->back()->withSuccess('Sanad Buzz alert sent.');
    }

    public function acknowledgeBuzz(Request $request, $id, $alertId)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $alert = $this->visibleBuzzQuery($booking)->findOrFail($alertId);

        $alert->status = 'acknowledged';
        $alert->acknowledged_at = now();
        $alert->save();

        $this->audit($request, 'sanad.buzz.acknowledged', $alert);

        return redirect()->back()->withSuccess('Sanad Buzz alert acknowledged.');
    }

    public function storeChatMessage(Request $request, $id)
    {
        abort_unless($this->canUseChatModule(true), 403);
        $this->abortUnlessEmployeeFlag('customer_chat');
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'message' => 'required|string|max:2000',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string',
            'thread_type' => 'nullable|in:shared,internal,partner_internal',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        $threadType = $request->thread_type ?: 'shared';
        if ($threadType === 'internal') {
            abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin', 'employee']) || auth()->user()->user_type === 'handyman', 403);
            $this->abortUnlessEmployeeFlag('internal_notes');
        }
        if ($threadType === 'partner_internal') {
            abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin', 'employee', 'provider']) || auth()->user()->user_type === 'handyman', 403);
            $this->abortUnlessEmployeeFlag('internal_notes');
        }
        $visibleTo = match ($threadType) {
            'internal' => ['admin', 'demo_admin', 'employee'],
            'partner_internal' => ['admin', 'demo_admin', 'employee', 'provider'],
            default => ['admin', 'demo_admin', 'employee', 'provider', 'user'],
        };
        $thread = SanadChatThread::where('booking_id', $booking->id)->where('thread_type', $threadType)->latest()->first() ?: SanadChatThread::create([
            'booking_id' => $booking->id,
            'thread_type' => $threadType,
            'participant_roles' => $visibleTo,
            'created_by' => optional(auth()->user())->id,
        ]);

        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => optional(auth()->user())->id,
            'sender_role' => optional(auth()->user())->user_type,
            'message' => $request->message,
            'visible_to' => $visibleTo,
            'message_type' => 'text',
        ]);
        if ($request->hasFile('attachment')) storeMediaFile($message, $request->file('attachment'), 'attachment');
        $thread->update(['last_message_at' => now()]);

        $this->audit($request, 'sanad.chat.message_created', $message);

        return redirect()->back()->withSuccess('Sanad chat message sent.');
    }

    public function createDocumentRequest(Request $request, $id)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);
        $this->abortUnlessEmployeeFlag('review_documents');
        $booking = Booking::myBooking()->findOrFail($id);
        if (auth()->user()->hasRole('provider') && (int) $booking->provider_id !== auth()->id()) abort(403);
        $request->validate(['document_name' => 'required|string|max:255', 'requested_from' => 'required|in:customer,partner', 'reason' => 'required|string|max:2000', 'instructions' => 'nullable|string|max:4000', 'due_at' => 'nullable|date']);
        $target = $request->requested_from === 'customer' ? $booking->customer_id : $booking->provider_id;
        $item = SanadDocumentRequest::create([
            'booking_id' => $booking->id, 'service_id' => $booking->service_id,
            'document_key' => $request->document_key, 'document_name' => $request->document_name,
            'requested_from' => $request->requested_from, 'requested_from_user_id' => $target,
            'requested_by' => auth()->id(), 'reason' => $request->reason, 'instructions' => $request->instructions,
            'required' => $request->boolean('required', true), 'due_at' => $request->due_at,
        ]);
        $thread = SanadChatThread::firstOrCreate(['booking_id' => $booking->id, 'thread_type' => 'shared'], ['participant_roles' => ['admin','demo_admin','employee','provider','user'], 'created_by' => auth()->id()]);
        SanadChatMessage::create(['thread_id' => $thread->id, 'sender_id' => auth()->id(), 'sender_role' => auth()->user()->user_type, 'message' => 'Document requested: '.$item->document_name, 'visible_to' => ['admin','demo_admin','employee','provider','user'], 'message_type' => 'document_request', 'document_request_id' => $item->id]);
        $thread->update(['last_message_at' => now()]);
        $this->audit($request, 'sanad.document_request.created', $item);
        return back()->withSuccess('Document request created.');
    }

    public function markChatRead(Request $request, $id, $threadId)
    {
        abort_unless($this->canUseChatModule(), 403);
        $booking = Booking::myBooking()->findOrFail($id);
        $thread = SanadChatThread::where('booking_id', $booking->id)->findOrFail($threadId);
        abort_unless($thread->thread_type === 'shared' || auth()->user()->hasAnyRole(['admin','demo_admin','employee']), 403);
        $thread->messages()->whereNull('read_at')->where('sender_id', '!=', auth()->id())->update(['read_at' => now()]);
        return back()->withSuccess('Conversation marked as read.');
    }

    public function uploadDocumentRequest(Request $request, $id, $documentRequestId)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);
        $booking = Booking::myBooking()->findOrFail($id);
        $item = $booking->sanadDocumentRequests()->findOrFail($documentRequestId);
        abort_unless(in_array(auth()->id(), array_filter([$booking->customer_id, $booking->provider_id, $item->requested_by])) || $this->employeeHasFlag('upload_documents'), 403);
        $request->validate(['document' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240']);
        $isPartnerSideUser = auth()->user()->hasRole('provider') || (auth()->user()->user_type === 'handyman' && !empty(auth()->user()->provider_id));
        $document = SanadDocumentVaultItem::create(['booking_id' => $booking->id, 'service_id' => $booking->service_id, 'provider_id' => $isPartnerSideUser ? $booking->provider_id : null, 'owner_id' => $booking->customer_id, 'uploaded_by' => auth()->id(), 'document_type' => $item->document_name, 'document_key' => $item->document_key, 'source' => $isPartnerSideUser ? 'partner' : 'customer', 'required' => $item->required]);
        storeMediaFile($document, $request->file('document'), 'document');
        $item->update(['document_id' => $document->id, 'status' => 'submitted']);
        $this->audit($request, 'sanad.document_request.submitted', $item);
        return back()->withSuccess('Document submitted for review.');
    }

    public function reviewDocumentRequest(Request $request, $id, $documentRequestId)
    {
        abort_unless($this->canUseDocumentReviewModule(true), 403);
        $this->abortUnlessEmployeeFlag('review_documents');
        $item = Booking::myBooking()->findOrFail($id)->sanadDocumentRequests()->findOrFail($documentRequestId);
        $request->validate(['status' => 'required|in:approved,rejected,replacement_requested,pending', 'review_reason' => 'nullable|string|max:2000']);
        if (in_array($request->status, ['rejected','replacement_requested'], true) && !$request->review_reason) return back()->withErrors('A reason is required.');
        $item->update(['status' => $request->status, 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_reason' => $request->review_reason]);
        $this->audit($request, 'sanad.document_request.reviewed', $item);
        return back()->withSuccess('Document request review saved.');
    }

    private function realAiInteractionsQuery()
    {
        return SanadAiInteraction::query()
            ->where('question', 'not like', '%Smoke Test%')
            ->where('answer', 'not like', '%Smoke test%')
            ->where('question', 'not like', '%Integrated QA Knowledge%')
            ->where('answer', 'not like', '%Integrated QA%');
    }

    private function visibleDocumentsQuery(Booking $booking)
    {
        $user = auth()->user();
        $query = SanadDocumentVaultItem::where('booking_id', $booking->id);

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhere('uploaded_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($user) {
                        $this->whereJsonArrayContains($visibilityQuery, 'visible_to', $user->user_type);
                    });
            });
        }

        return $query;
    }

    private function sanadRole($user)
    {
        if ($user->hasRole('admin') || $user->hasRole('demo_admin')) {
            return 'admin';
        }

        if ($user->hasRole('provider')) {
            return 'partner';
        }

        if ($user->hasRole('handyman')) {
            return 'employee';
        }

        return 'customer';
    }

    private function roleDashboardData($role)
    {
        $query = Booking::with(['customer', 'provider', 'service', 'payment', 'handymanAdded.handyman', 'sanadDocuments', 'sanadBuzzAlerts'])
            ->myBooking();

        $totalOrders = (clone $query)->count();
        $activeOrders = (clone $query)->whereNotIn('status', ['completed', 'cancelled'])->count();
        $completedOrders = (clone $query)->where('status', 'completed')->count();
        $cancelledOrders = (clone $query)->where('status', 'cancelled')->count();
        $overdueOrders = (clone $query)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count();
        $waitingCustomer = (clone $query)->where('sanad_stage', 'waiting_for_customer')->count();
        $waitingGovernment = (clone $query)->where('sanad_stage', 'government_processing')->count();
        $revenue = (clone $query)->whereHas('payment', function ($paymentQuery) {
            $paymentQuery->where('payment_status', 'paid');
        })->sum('total_amount');

        $baseMetrics = [
            ['label' => 'Total Orders', 'value' => $totalOrders, 'filter' => []],
            ['label' => 'Active Orders', 'value' => $activeOrders, 'filter' => ['status' => 'pending']],
            ['label' => 'Completed Orders', 'value' => $completedOrders, 'filter' => ['status' => 'completed']],
            ['label' => 'Delayed Orders', 'value' => $overdueOrders, 'filter' => ['sla_state' => 'overdue']],
        ];

        $roleMetrics = [
            'admin' => array_merge($baseMetrics, [
                ['label' => 'Monthly Revenue', 'value' => getPriceFormat($revenue), 'filter' => ['payment_state' => 'paid']],
                ['label' => 'Waiting Customer', 'value' => $waitingCustomer, 'filter' => ['sanad_stage' => 'waiting_for_customer']],
                ['label' => 'Waiting Government', 'value' => $waitingGovernment, 'filter' => ['sanad_stage' => 'government_processing']],
            ]),
            'partner' => array_merge($baseMetrics, [
                ['label' => 'Waiting Customer', 'value' => $waitingCustomer, 'filter' => ['sanad_stage' => 'waiting_for_customer']],
                ['label' => 'Waiting Government', 'value' => $waitingGovernment, 'filter' => ['sanad_stage' => 'government_processing']],
                ['label' => 'Pending Settlement', 'value' => getPriceFormat($revenue), 'filter' => ['payment_state' => 'paid']],
            ]),
            'employee' => [
                ['label' => 'Assigned Orders', 'value' => $totalOrders, 'filter' => []],
                ['label' => 'Today Tasks', 'value' => (clone $query)->whereDate('updated_at', now()->toDateString())->count(), 'filter' => []],
                ['label' => 'Pending Documents', 'value' => (clone $query)->whereHas('sanadDocuments', function ($documentQuery) {
                    $documentQuery->where('verification_status', 'pending');
                })->count(), 'filter' => ['action_state' => 'pending_documents']],
                ['label' => 'Overdue SLA', 'value' => $overdueOrders, 'filter' => ['sla_state' => 'overdue']],
            ],
            'customer' => [
                ['label' => 'My Requests', 'value' => $totalOrders, 'filter' => []],
                ['label' => 'In Progress', 'value' => $activeOrders, 'filter' => ['status' => 'pending']],
                ['label' => 'Completed', 'value' => $completedOrders, 'filter' => ['status' => 'completed']],
                ['label' => 'Payment Pending', 'value' => (clone $query)->where(function ($paymentStateQuery) {
                    $paymentStateQuery->whereDoesntHave('payment')
                        ->orWhereHas('payment', function ($paymentQuery) {
                            $paymentQuery->whereIn('payment_status', ['pending', 'pending_by_admin', 'advanced_paid']);
                        });
                })->count(), 'filter' => ['payment_state' => 'pending']],
            ],
        ];

        $recentOrders = (clone $query)->latest()->take(8)->get();
        $priorityOrders = (clone $query)
            ->where(function ($priorityQuery) {
                $priorityQuery->whereIn('sanad_priority', ['urgent', 'high'])
                    ->orWhereNotNull('sla_due_at')->where('sla_due_at', '<=', now()->addDay());
            })
            ->latest()
            ->take(6)
            ->get();

        $kanbanStages = ['submitted', 'waiting_for_documents', 'government_processing', 'legal_review', 'accounting', 'quality_review', 'ready_for_delivery', 'completed'];
        $kanban = collect($kanbanStages)->mapWithKeys(function ($stage) use ($query) {
            return [$stage => (clone $query)->where('sanad_stage', $stage)->latest()->take(5)->get()];
        });

        $employeeWorkload = User::where('user_type', 'handyman')
            ->when($role === 'partner', function ($employeeQuery) {
                $employeeQuery->where('provider_id', auth()->id());
            })
            ->withCount(['handyman as active_orders_count' => function ($bookingQuery) {
                $bookingQuery->whereHas('bookings', function ($requestQuery) {
                    $requestQuery->whereNotIn('status', ['completed', 'cancelled']);
                });
            }])
            ->orderByDesc('active_orders_count')
            ->take(8)
            ->get();

        return [
            'metrics' => $roleMetrics[$role] ?? $baseMetrics,
            'recent_orders' => $recentOrders,
            'priority_orders' => $priorityOrders,
            'kanban' => $kanban,
            'employee_workload' => $employeeWorkload,
        ];
    }

    private function nextLifecycleStage($currentStage)
    {
        $stages = config('sanad.request_lifecycle', []);
        $currentIndex = array_search($currentStage, $stages, true);

        if ($currentIndex === false) {
            return 'in_progress';
        }

        return $stages[$currentIndex + 1] ?? 'completed';
    }

    private function requestQueueSummary()
    {
        $query = Booking::query()->myBooking();

        return [
            'total' => (clone $query)->count(),
            'needs_action' => (clone $query)->where(function ($q) {
                $q->whereNull('assigned_at')
                    ->orWhereDoesntHave('handymanAdded')
                    ->orWhere(function ($slaQuery) {
                        $slaQuery->whereNotNull('sla_due_at')->where('sla_due_at', '<', now());
                    })
                    ->orWhereHas('sanadDocuments', function ($documentQuery) {
                        $documentQuery->where('verification_status', 'pending');
                    })
                    ->orWhereHas('sanadBuzzAlerts', function ($buzzQuery) {
                        $buzzQuery->where('status', 'unread');
                    });
            })->count(),
            'unassigned' => (clone $query)->whereDoesntHave('handymanAdded')->count(),
            'overdue_sla' => (clone $query)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count(),
            'pending_documents' => (clone $query)->whereHas('sanadDocuments', function ($documentQuery) {
                $documentQuery->where('verification_status', 'pending');
            })->count(),
            'unread_buzz' => (clone $query)->whereHas('sanadBuzzAlerts', function ($buzzQuery) {
                $buzzQuery->where('status', 'unread');
            })->count(),
            'open_chat' => (clone $query)->whereHas('sanadChatThreads', function ($chatQuery) {
                $chatQuery->where('status', 'open');
            })->count(),
            'paid' => (clone $query)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->count(),
            'payment_pending' => (clone $query)->where(function ($paymentQuery) {
                $paymentQuery->whereDoesntHave('payment')
                    ->orWhereHas('payment', function ($statusQuery) {
                        $statusQuery->whereIn('payment_status', ['pending', 'pending_by_admin', 'advanced_paid']);
                    });
            })->count(),
            'paid_revenue' => (clone $query)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->sum('total_amount'),
        ];
    }

    private function requestMonitoring(Booking $booking, $documents, $buzzAlerts, $chatThread)
    {
        $pendingDocuments = $documents->where('verification_status', 'pending')->count();
        $unreadBuzz = $buzzAlerts->where('status', 'unread')->count();
        $isOverdue = $booking->sla_due_at && $booking->sla_due_at->isPast();
        $isDueSoon = $booking->sla_due_at && !$isOverdue && $booking->sla_due_at->lessThanOrEqualTo(now()->addDay());
        $isUnassigned = $booking->handymanAdded->isEmpty();

        return [
            'needs_action' => $pendingDocuments > 0 || $unreadBuzz > 0 || $isOverdue || $isUnassigned,
            'pending_documents' => $pendingDocuments,
            'unread_buzz' => $unreadBuzz,
            'open_chat' => $chatThread && $chatThread->status === 'open',
            'is_overdue' => $isOverdue,
            'is_due_soon' => $isDueSoon,
            'is_unassigned' => $isUnassigned,
        ];
    }

    private function requestBilling(Booking $booking)
    {
        $payment = $booking->payment;
        $paymentStatus = optional($payment)->payment_status ?: 'no_payment';
        $isPaid = $paymentStatus === 'paid';

        return [
            'payment' => $payment,
            'payment_status' => $paymentStatus,
            'is_paid' => $isPaid,
            'amount' => $payment ? $payment->total_amount : $booking->total_amount,
            'payment_type' => optional($payment)->payment_type,
            'transaction_id' => optional($payment)->txn_id,
            'can_update' => (bool) $payment,
            'history_count' => $payment ? $payment->paymentHistory->count() : 0,
        ];
    }

    private function requestQualityControl(Booking $booking)
    {
        $pendingDocuments = $booking->sanadDocuments()->where('verification_status', 'pending')->count();
        $rejectedDocuments = $booking->sanadDocuments()->where('verification_status', 'rejected')->count();
        $openBuzz = $booking->sanadBuzzAlerts()->whereIn('status', ['unread', 'sent'])->count();
        $openChat = $booking->sanadChatThreads()->where('status', 'open')->count();
        $paymentStatus = optional($booking->payment)->payment_status ?: 'no_payment';
        $latestDecision = $booking->sanadRequestActions()
            ->whereIn('action', ['quality_approve', 'quality_reject', 'quality_rework'])
            ->latest()
            ->first();
        $isReadyForApproval = $pendingDocuments === 0
            && $rejectedDocuments === 0
            && $paymentStatus === 'paid'
            && $booking->handymanAdded()->exists()
            && in_array($booking->sanad_stage, ['quality_review', 'awaiting_quality_review', 'ready_for_delivery'], true);

        return [
            'pending_documents' => $pendingDocuments,
            'rejected_documents' => $rejectedDocuments,
            'open_buzz' => $openBuzz,
            'open_chat' => $openChat,
            'payment_status' => $paymentStatus,
            'has_assignment' => $booking->handymanAdded()->exists(),
            'latest_decision' => $latestDecision,
            'is_ready_for_approval' => $isReadyForApproval,
        ];
    }

    private function assignableEmployees(Booking $booking)
    {
        $user = auth()->user();
        $query = User::where('user_type', 'handyman')->where('status', 1)->orderBy('display_name');
        $assignedEmployeeIds = $booking->handymanAdded()->pluck('handyman_id')->filter()->all();

        if ($user->hasRole('admin') || $user->hasRole('demo_admin')) {
            if ($booking->provider_id) {
                $query->where(function ($q) use ($booking, $assignedEmployeeIds) {
                    $q->where('provider_id', $booking->provider_id);
                    if (!empty($assignedEmployeeIds)) {
                        $q->orWhereIn('id', $assignedEmployeeIds);
                    }
                });
            }

            return $query->get();
        }

        if ($user->hasRole('provider')) {
            return $query->where('provider_id', $user->id)->get();
        }

        if ($user->hasRole('handyman')) {
            return $query->where('id', $user->id)->get();
        }

        return collect();
    }

    private function visibleBuzzQuery(Booking $booking)
    {
        $user = auth()->user();
        $query = SanadBuzzAlert::where('booking_id', $booking->id);

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id)
                    ->orWhere('recipient_role', $user->user_type);
            });
        }

        return $query;
    }

    private function visibleChatThread(Booking $booking)
    {
        $user = auth()->user();
        $query = SanadChatThread::where('booking_id', $booking->id);

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($user) {
                        $this->whereJsonArrayContains($visibilityQuery, 'participant_roles', $user->user_type);
                    });
            });
        }

        return $query->latest()->first();
    }

    private function whereJsonArrayContains($query, $column, $value)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->where($column, 'like', '%"' . $value . '"%');
        }

        return $query->whereJsonContains($column, $value);
    }

    private function answerFromKnowledgeBase($question)
    {
        $terms = collect(preg_split('/\s+/', Str::lower($question)))
            ->filter(function ($term) {
                return strlen($term) > 3;
            })
            ->take(6);

        if ($terms->isEmpty()) {
            return null;
        }

        $item = SanadAiKnowledgeItem::where('is_active', true)
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('title', 'like', "%{$term}%")
                        ->orWhere('content', 'like', "%{$term}%");
                }
            })
            ->latest()
            ->first();

        return optional($item)->content;
    }

    private function classifyKnowledge(string $title, string $content): array
    {
        $text = Str::lower($title . ' ' . $content);
        $categories = [
            'Documents' => ['document', 'upload', 'id', 'passport', 'license', 'certificate', 'attachment', 'nafath'],
            'Payment' => ['payment', 'invoice', 'billing', 'paid', 'refund', 'fee', 'amount', 'wallet'],
            'Workflow' => ['stage', 'status', 'approval', 'review', 'assigned', 'processing', 'timeline', 'sla'],
            'Support' => ['complaint', 'support', 'escalation', 'urgent', 'issue', 'rejected', 'human'],
            'Legal' => ['legal', 'contract', 'terms', 'policy', 'government', 'compliance'],
        ];

        $scores = collect($categories)->mapWithKeys(function ($terms, $category) use ($text) {
            return [$category => collect($terms)->sum(fn ($term) => substr_count($text, $term))];
        });

        $category = $scores->sortDesc()->keys()->first() ?: 'General';
        if (($scores[$category] ?? 0) === 0) {
            $category = 'General';
        }

        $stopWords = ['the', 'and', 'for', 'with', 'from', 'that', 'this', 'are', 'will', 'can', 'should', 'sanad'];
        $tags = collect(preg_split('/[^\pL\pN]+/u', $text))
            ->filter(fn ($term) => mb_strlen($term) > 3)
            ->reject(fn ($term) => in_array($term, $stopWords, true))
            ->countBy()
            ->sortDesc()
            ->keys()
            ->take(8)
            ->values()
            ->all();

        $confidence = min(100, max(35, ($scores[$category] ?? 1) * 15 + count($tags) * 3));

        return [
            'category' => $category,
            'tags' => $tags,
            'confidence' => $confidence,
        ];
    }

    private function canUseAssignmentModule(bool $write = false): bool
    {
        return $this->canUseSanadModule('assignment', $write ? 'write' : 'read');
    }

    private function canUseDocumentReviewModule(bool $write = false): bool
    {
        return $this->canUseSanadModule('request_documents', $write ? 'write' : 'read')
            || ($write && $this->canUseSanadModule('upload_documents', 'write'));
    }

    private function canUseOrdersModule(bool $write = false): bool
    {
        $action = $write ? 'write' : 'read';

        return $this->canUseSanadModule('orders', $action)
            || $this->canUseSanadModule('my_tasks', $action);
    }

    private function canUseChatModule(bool $write = false): bool
    {
        return $this->canUseSanadModule('customer_chat', $write ? 'write' : 'read');
    }

    private function canUseTeamEmployeeModule(bool $write = false): bool
    {
        $action = $write ? 'write' : 'read';

        return $this->canUseSanadModule('employee', $action)
            || $this->canUseSanadModule('team_employees', $action);
    }

    private function canUseSanadModule(string $module, string $action = 'read'): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return in_array($module, [
                'dashboard',
                'my_tasks',
                'request_documents',
                'upload_documents',
                'customer_chat',
                'buzz_customer',
                'stage_progress',
                'payment_status',
                'internal_notes',
                'partner_profile',
                'team_employees',
            ], true);
        }

        if ($user->user_type !== 'handyman') {
            return false;
        }

        return $user->hasSanadModulePermission($module, $action);
    }

    private function abortUnlessEmployeeFlag(string $flag): void
    {
        if (auth()->user()->user_type !== 'handyman') {
            return;
        }

        abort_unless($this->employeeHasFlag($flag) || $this->employeeHasModuleForFlag($flag), 403);
    }

    private function employeeHasFlag(string $flag): bool
    {
        $user = auth()->user();

        if (!$user || $user->user_type !== 'handyman') {
            return false;
        }

        return in_array($flag, $user->sanad_permissions ?: [], true);
    }

    private function employeeHasModuleForFlag(string $flag): bool
    {
        $map = [
            'send_buzz' => [['buzz_customer', 'write'], ['customer_chat', 'write']],
            'complete_stage' => [['stage_progress', 'write'], ['orders', 'write'], ['my_tasks', 'write']],
            'review_documents' => [['request_documents', 'write']],
            'upload_documents' => [['upload_documents', 'write'], ['request_documents', 'write']],
            'view_payment_status' => [['payments', 'read'], ['payment_status', 'read']],
            'internal_notes' => [['internal_notes', 'write'], ['customer_chat', 'write']],
            'team_collaboration' => [['employee', 'write'], ['team_employees', 'write']],
            'manage_employees' => [['employee', 'write'], ['team_employees', 'write']],
            'customer_chat' => [['customer_chat', 'write']],
        ];

        foreach ($map[$flag] ?? [] as [$module, $action]) {
            if ($this->canUseSanadModule($module, $action)) {
                return true;
            }
        }

        return false;
    }

    private function audit(Request $request, $action, $model, array $metadata = [])
    {
        SanadAuditLog::create([
            'actor_id' => optional(auth()->user())->id,
            'actor_role' => optional(auth()->user())->user_type,
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'metadata' => $metadata,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ]);
    }
}
