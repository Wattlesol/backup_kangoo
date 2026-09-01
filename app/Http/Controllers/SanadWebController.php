<?php

namespace App\Http\Controllers;

use App\Events\SanadConversationUpdated;
use App\Models\Booking;
use App\Models\BookingHandymanMapping;
use App\Models\Payment;
use App\Models\SanadAiInteraction;
use App\Models\SanadAiKnowledgeItem;
use App\Models\SanadAiReviewExample;
use App\Services\SanadAiFirstResponderService;
use App\Services\SanadAiRagService;
use App\Services\SanadCrawlerIngestionService;
use App\Services\SanadVectorStoreService;
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
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\SanadAssignmentDecision;
use App\Services\SanadAssignmentService;
use App\Models\Notification;
use App\Models\SanadDocumentRequest;
use App\Services\SanadKnowledgeArabicTranslationService;

class SanadWebController extends Controller
{
    public function assignments(Request $request, SanadAssignmentService $assignmentService)
    {
        abort_unless($this->canUseAssignmentModule(), 403);
        $query = Booking::with(['customer', 'service', 'provider'])->latest();
        if ($request->assignment_state === 'unassigned') $query->whereNull('provider_id');
        if ($request->assignment_state === 'assigned') $query->whereNotNull('provider_id');
        if ($request->assignment_state === 'waiting_acceptance') $query->whereNotNull('provider_id')->whereNotIn('status', ['accept', 'accepted', 'in_progress', 'completed', 'cancelled']);
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
        return view('sanad.assignments', compact('orders', 'recommendations', 'partners', 'latestDecisions'), ['pageTitle' => app()->getLocale() === 'ar' ? 'الإسناد والتوزيع' : 'Assignments']);
    }

    public function chatWorkspace(Request $request)
    {
        abort_unless($this->canUseChatModule(), 403);

        $user = auth()->user();
        $isAdmin = $user->hasAnyRole(['admin', 'demo_admin']);
        $query = Booking::myBooking()->with([
            'customer',
            'service',
            'provider',
            'sanadChatThreads.messages.sender',
            'sanadBuzzAlerts.replies.sender',
            'sanadDocumentRequests.document',
            'sanadAiInteractions',
        ]);

        if ($request->action_state === 'open_chat') {
            $query->whereHas('sanadChatThreads', fn ($chatQuery) => $chatQuery->where('status', 'open'));
        }
        if ($request->action_state === 'unread_buzz') {
            $query->whereHas('sanadBuzzAlerts', function ($buzzQuery) {
                $this->whereVisibleBuzzForCurrentUser($buzzQuery->where('status', 'unread'));
            });
        }
        if ($request->action_state === 'pending_documents') {
            $query->whereHas('sanadDocumentRequests', fn ($docQuery) => $docQuery->whereIn('status', ['pending', 'submitted', 'replacement_requested']));
        }
        if ($request->action_state === 'ai_escalations' && $isAdmin) {
            $query->whereHas('sanadAiInteractions', function ($aiQuery) {
                $aiQuery->where('requires_escalation', true)
                    ->orWhereIn('status', ['escalated', 'handover_required', 'needs_revision']);
            });
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('sanad_reference', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->latest('updated_at')->paginate(20)->withQueryString();
        $selectedBooking = null;
        if ($request->filled('booking_id')) {
            $selectedBooking = Booking::myBooking()->with([
                'customer',
                'service',
                'provider',
                'sanadDocumentRequests.document',
                'sanadBuzzAlerts.replies.sender',
                'sanadAiInteractions.user',
            ])->find($request->booking_id);
        }
        $selectedBooking = $selectedBooking ?: $conversations->first();

        $thread = $selectedBooking ? $this->visibleChatThread($selectedBooking) : null;
        $messages = $thread
            ? $thread->messages()->with(['sender', 'buzzAlert.replies.sender', 'documentRequest.document', 'aiInteraction'])->get()
            : collect();
        $roleAliases = $this->chatParticipantRoleAliases(auth()->user());
        $visibleMessages = $messages->reject(function ($message) use ($roleAliases) {
            $visibleTo = $message->visible_to ?: [];
            return in_array($message->message_type, ['buzz', 'document_request'], true)
                || $message->buzz_alert_id
                || $message->document_request_id
                || ($visibleTo
                    && !in_array($message->sender_role, ['user', 'customer'], true)
                    && empty(array_intersect($visibleTo, $roleAliases)));
        });
        $buzzAlerts = $selectedBooking ? $this->visibleBuzzQuery($selectedBooking)->with('replies.sender')->latest()->get() : collect();
        $documentRequests = $selectedBooking ? $selectedBooking->sanadDocumentRequests()->with('document')->latest()->get() : collect();
        $aiEscalations = $isAdmin
            ? SanadAiInteraction::query()
                ->when(request('action_state') !== 'ai_escalations' && $selectedBooking, function ($q) use ($selectedBooking) {
                    $q->where('booking_id', $selectedBooking->id);
                })
                ->where(function ($aiQuery) {
                    $aiQuery->where('requires_escalation', true)
                        ->orWhereIn('status', ['escalated', 'handover_required', 'needs_revision']);
                })
                ->latest()
                ->take(50)
                ->get()
            : collect();
        $reviewExamples = $isAdmin ? SanadAiReviewExample::with('promotedKnowledgeItem')->latest()->take(8)->get() : collect();

        $timeline = collect();
        foreach ($buzzAlerts as $buzz) {
            $timeline->push((object)['type' => 'buzz', 'created_at' => $buzz->created_at, 'data' => $buzz]);
        }
        foreach ($documentRequests as $docReq) {
            $timeline->push((object)['type' => 'document', 'created_at' => $docReq->created_at, 'data' => $docReq]);
        }
        foreach ($visibleMessages as $msg) {
            $timeline->push((object)['type' => 'message', 'created_at' => $msg->created_at, 'data' => $msg]);
        }
        $timeline = $timeline->sortBy('created_at')->values();

        return view('sanad.chat-workspace', [
            'pageTitle' => 'Sanad Chat Workspace',
            'auth_user' => authSession(),
            'conversations' => $conversations,
            'selectedBooking' => $selectedBooking,
            'thread' => $thread,
            'messages' => $visibleMessages,
            'buzzAlerts' => $buzzAlerts,
            'documentRequests' => $documentRequests,
            'timeline' => $timeline,
            'aiEscalations' => $aiEscalations,
            'reviewExamples' => $reviewExamples,
            'isAdmin' => $isAdmin,
            'canCreateBuzz' => $this->employeeHasFlag('send_buzz') || $isAdmin || $user->hasRole('provider'),
            'canRequestDocuments' => $this->employeeHasFlag('review_documents') || $isAdmin || $user->hasRole('provider'),
            'highlightBuzzId' => $request->filled('buzz_id') ? (int) $request->input('buzz_id') : null,
        ]);
    }

    public function chatWorkspaceSnapshot(Request $request)
    {
        abort_unless($this->canUseChatModule(), 403);

        $user = auth()->user();
        $booking = Booking::myBooking()->with(['customer', 'service', 'provider'])->findOrFail($request->booking_id);
        $thread = $this->visibleChatThread($booking);
        $messages = $thread
            ? $thread->messages()->with(['sender', 'buzzAlert.replies.sender', 'documentRequest.document', 'aiInteraction'])->get()
            : collect();
        $roleAliases = $this->chatParticipantRoleAliases($user);
        $visibleMessages = $messages->reject(function ($message) use ($roleAliases) {
            $visibleTo = $message->visible_to ?: [];
            return in_array($message->message_type, ['buzz', 'document_request'], true)
                || $message->buzz_alert_id
                || $message->document_request_id
                || ($visibleTo
                    && !in_array($message->sender_role, ['user', 'customer'], true)
                    && empty(array_intersect($visibleTo, $roleAliases)));
        });
        $buzzAlerts = $this->visibleBuzzQuery($booking)->with('replies.sender')->latest()->get();
        $documentRequests = $booking->sanadDocumentRequests()->with('document')->latest()->get();
        $isAdmin = $user->hasAnyRole(['admin', 'demo_admin']);
        $isCustomer = in_array(optional($user)->user_type, ['user', 'customer'], true);
        $storeRoute = $isCustomer ? 'customer-portal.requests.messages.store' : 'sanad.requests.chat.store';
        $requestShowRoute = $isCustomer ? 'customer-portal.requests.show' : 'sanad.requests.show';
        $canCreateBuzz = $this->employeeHasFlag('send_buzz') || $isAdmin || $user->hasRole('provider');
        $canRequestDocuments = $this->employeeHasFlag('review_documents') || $isAdmin || $user->hasRole('provider');
        $directMessageLock = $this->directMessageLock($booking, $user);
        $requiredDocuments = $booking->service
            ? collect($booking->service->required_documents ?: [])->map(function ($doc) {
                $storedName = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc;
                return [
                    'key' => is_array($doc) ? ($doc['key'] ?? Str::slug($storedName, '_')) : Str::slug($storedName, '_'),
                    'name' => localized_service_document_name($doc),
                ];
            })->values()
            : collect();
        $aiEscalations = $isAdmin
            ? SanadAiInteraction::query()
                ->when(request('action_state') !== 'ai_escalations' && $booking, function ($q) use ($booking) {
                    $q->where('booking_id', $booking->id);
                })
                ->where(function ($aiQuery) {
                    $aiQuery->where('requires_escalation', true)
                        ->orWhereIn('status', ['escalated', 'handover_required', 'needs_revision']);
                })
                ->latest()
                ->take(50)
                ->get()
            : collect();

        $timelineData = collect();
        foreach ($buzzAlerts as $buzz) {
            $timelineData->push([
                'type' => 'buzz',
                'id' => 'buzz-' . $buzz->id,
                'timestamp' => optional($buzz->created_at)->timestamp ?: 0,
                'created_at' => optional($buzz->created_at)->format('Y-m-d H:i'),
                'priority' => Str::headline($buzz->priority),
                'status' => Str::headline($buzz->status),
                'raw_status' => $buzz->status,
                'message' => $buzz->message,
                'recipient_role' => Str::headline($buzz->recipient_role ?: 'customer'),
                'recipient_id' => $buzz->recipient_id,
                'action_type' => $buzz->action_type,
                'action_status' => $buzz->action_status,
                'accept_url' => route('sanad.requests.buzz.acknowledge', [$booking->id, $buzz->id]),
                'can_accept' => $buzz->action_type === 'chat_assignment_accept'
                    && $buzz->status === 'unread'
                    && (int) $buzz->recipient_id === (int) auth()->id(),
                'replies' => $buzz->replies->map(fn ($r) => [
                    'sender' => optional($r->sender)->display_name ?: Str::headline($r->sender_role ?: 'user'),
                    'message' => $r->message,
                    'created_at' => optional($r->created_at)->format('Y-m-d H:i'),
                ])->values(),
            ]);
        }
        foreach ($documentRequests as $doc) {
            $timelineData->push([
                'type' => 'document',
                'id' => 'doc-' . $doc->id,
                'timestamp' => optional($doc->created_at)->timestamp ?: 0,
                'created_at' => optional($doc->created_at)->format('Y-m-d H:i'),
                'status' => Str::headline($doc->status),
                'document_name' => $doc->document_name,
                'requested_from' => Str::headline($doc->requested_from ?: 'customer'),
                'instructions' => $doc->instructions ?: $doc->reason,
                'due_at' => optional($doc->due_at)->format('Y-m-d'),
                'due_label' => $doc->due_at ? $doc->due_at->diffForHumans() : null,
                'has_file' => (bool) $doc->document,
                'file_url' => $doc->document ? $doc->document->publicDocumentUrl() : null,
            ]);
        }
        foreach ($visibleMessages as $message) {
            $timelineData->push([
                'type' => 'message',
                'id' => 'msg-' . $message->id,
                'timestamp' => optional($message->created_at)->timestamp ?: 0,
                'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                'sender' => $message->sender_role === 'system' ? 'Quick AI' : (optional($message->sender)->display_name ?: Str::headline($message->sender_role ?: 'system')),
                'sender_role' => $message->sender_role,
                'message' => $message->message,
                'message_type' => $message->message_type ?: 'text',
                'ai_interaction_id' => $message->ai_interaction_id,
                'handover_status' => optional($message->aiInteraction)->status,
                'attachment_url' => $message->getFirstMediaUrl('sanad_chat_attachment') ?: $message->getFirstMediaUrl('attachment'),
                'attachment_name' => optional($message->getFirstMedia('sanad_chat_attachment'))->file_name,
            ]);
        }
        $timelineData = $timelineData->sortBy('timestamp')->values();

        return response()->json([
            'status' => true,
            'request' => [
                'id' => $booking->id,
                'reference' => $booking->quick_reference,
                'customer' => optional($booking->customer)->display_name ?: optional($booking->customer)->email ?: 'Customer',
                'avatar' => Str::upper(Str::substr(optional($booking->customer)->display_name ?: optional($booking->customer)->email ?: 'C', 0, 1)),
                'service' => app()->getLocale() === 'ar'
                    ? (optional($booking->service)->name_ar ?: optional($booking->service)->name_en ?: optional($booking->service)->name ?: 'لا توجد خدمة')
                    : (optional($booking->service)->name_en ?: optional($booking->service)->name ?: 'No service'),
                'stage' => Str::headline($booking->sanad_stage ?: $booking->status),
                'priority' => Str::headline($booking->sanad_priority ?: 'normal'),
                'sla' => optional($booking->sla_due_at)->format('Y-m-d H:i') ?: '-',
                'partner' => optional($booking->provider)->display_name ?: '-',
                'request_url' => route($requestShowRoute, $booking->id),
                'updated_at' => optional($booking->updated_at)->toIso8601String(),
                'ai_first_responder_enabled' => $booking->ai_first_responder_enabled !== false,
                'chat_owner_type' => $booking->chat_owner_type ?: 'ai',
                'chat_owner_user_id' => $booking->chat_owner_user_id,
                'chat_owner_team' => $this->chatOwnerTeam($booking),
                'chat_assignment_label' => $this->chatAssignmentLabel($booking),
            ],
            'composer' => [
                'booking_id' => $booking->id,
                'store_url' => route($storeRoute, $booking->id),
                'can_create_buzz' => $canCreateBuzz,
                'can_request_documents' => $canRequestDocuments,
                'required_documents' => $requiredDocuments,
                'ai_toggle_url' => !$isCustomer ? route('sanad.requests.ai-first-responder', $booking->id) : null,
                'chat_assignment_url' => !$isCustomer ? route('sanad.requests.chat-assignment', $booking->id) : null,
                'assignable_chat_targets' => !$isCustomer ? $this->assignableChatTargets($booking) : [],
                'direct_message_locked' => $directMessageLock['locked'],
                'direct_message_lock_message' => $directMessageLock['message'],
            ],
            'timeline' => $timelineData,
            'messages' => $visibleMessages->map(fn ($message) => [
                'id' => $message->id,
                'sender' => $message->sender_role === 'system' ? 'Quick AI' : (optional($message->sender)->display_name ?: Str::headline($message->sender_role ?: 'system')),
                'sender_role' => $message->sender_role,
                'message' => $message->message,
                'message_type' => $message->message_type ?: 'text',
                'buzz_alert_id' => $message->buzz_alert_id,
                'document_request_id' => $message->document_request_id,
                'ai_interaction_id' => $isAdmin ? $message->ai_interaction_id : null,
                'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                'attachment_url' => $message->getFirstMediaUrl('sanad_chat_attachment') ?: $message->getFirstMediaUrl('attachment'),
            ])->values(),
            'buzz_alerts' => $buzzAlerts->map(fn ($buzz) => [
                'id' => $buzz->id,
                'priority' => Str::headline($buzz->priority),
                'status' => Str::headline($buzz->status),
                'raw_status' => $buzz->status,
                'message' => $buzz->message,
                'recipient_role' => Str::headline($buzz->recipient_role ?: 'customer'),
                'recipient_id' => $buzz->recipient_id,
                'action_type' => $buzz->action_type,
                'action_status' => $buzz->action_status,
                'accept_url' => route('sanad.requests.buzz.acknowledge', [$booking->id, $buzz->id]),
                'can_accept' => $buzz->action_type === 'chat_assignment_accept'
                    && $buzz->status === 'unread'
                    && (int) $buzz->recipient_id === (int) auth()->id(),
                'reply_count' => $buzz->reply_count,
                'created_at' => optional($buzz->created_at)->format('Y-m-d H:i'),
                'replies' => $buzz->replies->map(fn ($reply) => [
                    'sender' => optional($reply->sender)->display_name ?: Str::headline($reply->sender_role ?: 'user'),
                    'message' => $reply->message,
                    'created_at' => optional($reply->created_at)->format('Y-m-d H:i'),
                ])->values(),
            ])->values(),
            'documents' => $documentRequests->map(fn ($documentRequest) => [
                'id' => $documentRequest->id,
                'document_name' => $documentRequest->document_name,
                'status' => Str::headline($documentRequest->status),
                'instructions' => $documentRequest->instructions ?: $documentRequest->reason,
                'requested_from' => Str::headline($documentRequest->requested_from),
                'due_at' => optional($documentRequest->due_at)->format('Y-m-d'),
                'due_label' => $documentRequest->due_at ? $documentRequest->due_at->diffForHumans() : null,
                'file_url' => $documentRequest->document ? $documentRequest->document->publicDocumentUrl() : null,
            ])->values(),
            'ai_escalations' => $aiEscalations->map(fn ($interaction) => [
                'id' => $interaction->id,
                'question' => $interaction->question,
                'answer' => $interaction->answer,
                'confidence' => round(($interaction->confidence ?? 0) * 100),
                'status' => Str::headline($interaction->status),
            ])->values(),
        ]);
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
        $pageTitle = app()->getLocale() === 'ar' ? 'لوحة تحكم عمليات كويك' : ('Quick ' . Str::headline($role) . ' Dashboard');
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

        $performanceProviderIds = SanadPartnerServicePerformance::query()->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $performanceServiceIds = SanadPartnerServicePerformance::query()->whereNotNull('service_id')->distinct()->pluck('service_id');
        $performancePartners = User::query()->whereIn('id', $performanceProviderIds)->orderBy('display_name')->get(['id', 'display_name', 'first_name', 'last_name']);
        $performanceServices = Service::query()->whereIn('id', $performanceServiceIds)->orderBy('name')->get(['id', 'name']);

        return view('sanad.partner-performance', [
            'pageTitle' => 'Partner Performance',
            'auth_user' => authSession(),
            'performances' => $performances,
            'performancePartners' => $performancePartners,
            'performanceServices' => $performanceServices,
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
            'pageTitle' => 'Quick AI Assistant',
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

    public function storeAiKnowledge(Request $request, SanadAiRagService $rag, SanadKnowledgeIngestionService $ingestion, SanadCrawlerIngestionService $crawler, SanadKnowledgeArabicTranslationService $translator)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => 'Only admins can manage Quick AI knowledge.'], 403);
            }
            return redirect()->back()->withErrors('Only admins can manage Quick AI knowledge.');
        }

        try {
            $request->validate([
                'title' => 'required_without:website_url|nullable|string|max:255',
                'content' => 'nullable|string',
                'title_en' => 'nullable|string|max:255|required_with:title_ar,content_en,content_ar',
                'title_ar' => 'nullable|string|max:255|required_with:title_en,content_en,content_ar',
                'content_en' => 'nullable|string|required_with:title_en,title_ar,content_ar',
                'content_ar' => 'nullable|string|required_with:title_en,title_ar,content_en',
                'knowledge_pdfs' => 'nullable|array',
                'knowledge_pdfs.*' => 'file|mimes:pdf|max:20480',
                'google_doc_url' => 'nullable|url|max:1000',
                'website_url' => 'nullable|url|max:1000',
                'crawl_mode' => 'nullable|in:single_url,same_domain',
                'crawl_page_limit' => 'nullable|integer|min:1|max:50',
                'visible_to' => 'nullable|array',
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => implode(' ', Arr::flatten($ve->errors()))], 422);
            }
            throw $ve;
        }

        $manualPair = $request->filled('title_en')
            && $request->filled('title_ar')
            && $request->filled('content_en')
            && $request->filled('content_ar');
        if ($manualPair) {
            $request->merge([
                'title' => $request->input('title_en'),
                'content' => $request->input('content_en'),
            ]);
        }

        try {
            $isScrape = $request->filled('website_url');
            $ingested = $isScrape
                ? $crawler->scrape($request->input('website_url'), $request->input('crawl_mode', 'single_url'), (int) $request->input('crawl_page_limit', 10))
                : $ingestion->extract(
                    $request->input('content'),
                    $request->file('knowledge_pdfs', []),
                    $request->input('google_doc_url')
                );
        } catch (\Throwable $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->withErrors($e->getMessage());
        }

        if ($ingested['content'] === '') {
            $errorMsg = 'Add content, upload a readable PDF, provide a public Google Docs link, or scrape a readable website URL.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $errorMsg], 422);
            }
            return redirect()->back()->withErrors($errorMsg);
        }

        $title = $request->title ?: ($isScrape ? parse_url($request->input('website_url'), PHP_URL_HOST) : null);
        try {
            $pair = $manualPair ? [
                'source_language' => 'bilingual_manual',
                'title_en' => trim((string) $request->input('title_en')),
                'title_ar' => trim((string) $request->input('title_ar')),
                'content_en' => trim((string) $request->input('content_en')),
                'content_ar' => trim((string) $request->input('content_ar')),
            ] : $translator->bilingualPair($title, $ingested['content']);
        } catch (\Throwable $e) {
            $message = app()->getLocale() === 'ar'
                ? 'فشلت الترجمة، لذلك لم يتم حفظ أي نسخة. ' . $e->getMessage()
                : 'Translation failed, so neither version was saved. ' . $e->getMessage();
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['status' => false, 'message' => $message], 422)
                : redirect()->back()->withErrors($message)->withInput();
        }

        $classification = $this->classifyKnowledge($pair['title_en'], $pair['content_en']);
        $metadata = $translator->translationMetadata([
            'tags' => $classification['tags'],
            'ingestion' => $ingested['metadata'],
            'agent_confidence' => $classification['confidence'],
            'source_url' => data_get($ingested, 'metadata.source_url'),
        ], $pair, $ingested['content']);

        $item = DB::transaction(fn () => SanadAiKnowledgeItem::create([
            'title' => $pair['title_en'],
            'title_ar' => $pair['title_ar'],
            'category' => $classification['category'],
            'category_ar' => $translator->arabicCategory($classification['category']),
            'content' => $pair['content_en'],
            'content_ar' => $pair['content_ar'],
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'metadata' => $metadata,
            'is_active' => true,
            'created_by' => optional(auth()->user())->id,
        ]));

        $rag->indexKnowledgeItem($item);

        $this->audit($request, $isScrape ? 'sanad.ai.knowledge_scraped' : 'sanad.ai.knowledge_created', $item);

        $msg = app()->getLocale() === 'ar'
            ? 'تم حفظ النسختين الإنجليزية والعربية وفهرستهما بنجاح.'
            : 'Both English and Arabic versions were saved and indexed successfully.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => $msg,
                'item' => [
                    'id' => $item->id,
                    'title' => app()->getLocale() === 'ar' ? $item->title_ar : $item->title,
                    'content' => Str::limit(app()->getLocale() === 'ar' ? $item->content_ar : $item->content, 130),
                    'category' => app()->getLocale() === 'ar' ? $item->category_ar : ($item->category ?: 'General'),
                    'chunks_count' => $item->chunks_count ?? 1,
                    'visible_to' => $item->visible_to,
                    'is_active' => $item->is_active,
                ],
            ]);
        }

        return redirect()->back()->withSuccess($msg);
    }

    public function scrapeKnowledgeAsync(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);

        $request->validate([
            'website_url' => 'required|url|max:1000',
            'crawl_mode' => 'nullable|in:single_url,same_domain',
            'crawl_page_limit' => 'nullable|integer|min:1|max:50',
            'title' => 'nullable|string|max:255',
            'visible_to' => 'nullable|array',
        ]);

        $url = $request->input('website_url');
        $title = $request->input('title') ?: parse_url($url, PHP_URL_HOST);
        $mode = $request->input('crawl_mode', 'single_url');
        $limit = (int) $request->input('crawl_page_limit', 10);

        $item = SanadAiKnowledgeItem::create([
            'title' => $title,
            'category' => 'Scraping',
            'content' => 'Scraping and indexing content...',
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'metadata' => [
                'status' => 'processing',
                'progress_step' => 'Scraping & Indexing...',
                'source_url' => $url,
                'crawl_mode' => $mode,
                'page_limit' => $limit,
            ],
            'is_active' => false,
            'created_by' => optional(auth()->user())->id,
        ]);

        return response()->json([
            'status' => true,
            'item_id' => $item->id,
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => 'Scraping',
                'content' => 'Scraping and indexing content...',
                'status' => 'processing',
                'progress_step' => 'Scraping & Indexing...',
                'source_url' => $url,
                'crawl_mode' => $mode,
                'page_limit' => $limit,
            ],
        ]);
    }

    public function runKnowledgeScrape(Request $request, $id, SanadAiRagService $rag, SanadCrawlerIngestionService $crawler, SanadKnowledgeArabicTranslationService $translator)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);

        $item = SanadAiKnowledgeItem::findOrFail($id);
        $url = data_get($item->metadata, 'source_url');
        $mode = data_get($item->metadata, 'crawl_mode', 'single_url');
        $limit = (int) data_get($item->metadata, 'page_limit', 10);

        $metadata = $item->metadata ?: [];
        $metadata['progress_step'] = 'Crawling URL and extracting content...';
        $item->update(['metadata' => $metadata]);

        try {
            $ingested = $crawler->scrape($url, $mode, $limit);
        } catch (\Throwable $e) {
            $item->forceDelete();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'item_id' => $item->id,
            ], 422);
        }

        try {
            $metadata['progress_step'] = app()->getLocale() === 'ar'
                ? 'جارٍ ترجمة المحتوى كاملاً والتحقق من النسختين...'
                : 'Translating the complete content and validating both versions...';
            $item->update(['metadata' => $metadata]);
            $pair = $translator->bilingualPair($item->title, $ingested['content']);
        } catch (\Throwable $e) {
            $message = app()->getLocale() === 'ar' ? 'فشلت الترجمة ولم يتم الحفظ.' : 'Translation failed; nothing was saved.';
            $item->forceDelete();
            return response()->json([
                'status' => false,
                'message' => $message . ' ' . $e->getMessage(),
                'item_id' => $item->id,
            ], 422);
        }

        $classification = $this->classifyKnowledge($pair['title_en'], $pair['content_en']);

        $metadata['status'] = 'completed';
        $metadata['progress_step'] = 'Indexed';
        $metadata['tags'] = $classification['tags'];
        $metadata['ingestion'] = $ingested['metadata'];
        $metadata['agent_confidence'] = $classification['confidence'];
        $metadata['scraped_at'] = now()->toIso8601String();
        $metadata = $translator->translationMetadata($metadata, $pair, $ingested['content']);

        DB::transaction(fn () => $item->update([
            'title' => $pair['title_en'],
            'title_ar' => $pair['title_ar'],
            'category' => $classification['category'],
            'category_ar' => $translator->arabicCategory($classification['category']),
            'content' => $pair['content_en'],
            'content_ar' => $pair['content_ar'],
            'metadata' => $metadata,
            'is_active' => true,
        ]));

        $rag->indexKnowledgeItem($item);
        $this->audit($request, 'sanad.ai.knowledge_scraped', $item);

        return response()->json([
            'status' => true,
            'message' => app()->getLocale() === 'ar'
                ? 'تم حفظ النسختين الإنجليزية والعربية وفهرستهما بنجاح.'
                : 'Both English and Arabic versions were saved and indexed successfully.',
            'item' => [
                'id' => $item->id,
                'title' => app()->getLocale() === 'ar' ? $item->title_ar : $item->title,
                'content' => Str::limit(app()->getLocale() === 'ar' ? $item->content_ar : $item->content, 130),
                'category' => app()->getLocale() === 'ar' ? $item->category_ar : ($item->category ?: 'General'),
                'chunks_count' => $item->chunks_count ?? 1,
                'visible_to' => $item->visible_to,
                'is_active' => $item->is_active,
                'status' => 'completed',
            ],
        ]);
    }

    public function knowledgeScrapeStatus($id)
    {
        $item = SanadAiKnowledgeItem::findOrFail($id);
        return response()->json([
            'status' => true,
            'item' => [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'content' => Str::limit($item->content, 130),
                'status' => data_get($item->metadata, 'status', 'completed'),
                'progress_step' => data_get($item->metadata, 'progress_step', 'Done'),
                'error' => data_get($item->metadata, 'error'),
                'chunks_count' => $item->chunks_count ?? 1,
                'is_active' => $item->is_active,
            ],
        ]);
    }

    public function updateAiKnowledge(Request $request, $id, SanadAiRagService $rag, SanadKnowledgeArabicTranslationService $translator)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'visible_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $item = SanadAiKnowledgeItem::findOrFail($id);
        try {
            $pair = $translator->bilingualPair($request->title, $request->content);
        } catch (\Throwable $e) {
            $message = app()->getLocale() === 'ar'
                ? 'فشلت الترجمة، لذلك لم يتم تحديث أي نسخة. ' . $e->getMessage()
                : 'Translation failed, so neither version was updated. ' . $e->getMessage();
            return $request->wantsJson() || $request->ajax()
                ? response()->json(['status' => false, 'message' => $message], 422)
                : redirect()->back()->withErrors($message)->withInput();
        }

        $classification = $this->classifyKnowledge($pair['title_en'], $pair['content_en']);
        $metadata = $item->metadata ?: [];
        $metadata['tags'] = $classification['tags'];
        $metadata['agent_confidence'] = $classification['confidence'];
        $metadata['fine_tuned_at'] = now()->toIso8601String();
        $metadata = $translator->translationMetadata($metadata, $pair, $request->content);

        DB::transaction(fn () => $item->update([
            'title' => $pair['title_en'],
            'title_ar' => $pair['title_ar'],
            'category' => $classification['category'],
            'category_ar' => $translator->arabicCategory($classification['category']),
            'content' => $pair['content_en'],
            'content_ar' => $pair['content_ar'],
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'metadata' => $metadata,
            'is_active' => $request->boolean('is_active', true),
        ]));

        $rag->indexKnowledgeItem($item);
        $this->audit($request, 'sanad.ai.knowledge_updated', $item);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => app()->getLocale() === 'ar'
                    ? 'تم تحديث النسختين الإنجليزية والعربية وفهرستهما بنجاح.'
                    : 'Both English and Arabic versions were updated and indexed successfully.',
                'item' => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'content' => Str::limit($item->content, 130),
                    'category' => $item->category ?: 'General',
                    'chunks_count' => $item->chunks_count ?? 1,
                    'visible_to' => $item->visible_to,
                    'is_active' => $item->is_active,
                ],
            ]);
        }

        return redirect()->back()->withSuccess(app()->getLocale() === 'ar'
            ? 'تم تحديث النسختين الإنجليزية والعربية وفهرستهما بنجاح.'
            : 'Both English and Arabic versions were updated and indexed successfully.');
    }

    public function deleteAiKnowledge(Request $request, $id, SanadVectorStoreService $vectorStore)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);

        $item = SanadAiKnowledgeItem::with('chunks')->findOrFail($id);
        $title = $item->title;

        DB::transaction(function () use ($request, $item, $vectorStore) {
            $vectorStore->deleteKnowledgeItemVectors($item);
            $this->audit($request, 'sanad.ai.knowledge_deleted', $item);
            $item->chunks()->delete();
            $item->forceDelete();
        });

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => "Knowledge item '{$title}' deleted.",
                'item_id' => (int) $id,
            ]);
        }

        return redirect()->back()->withSuccess("Knowledge item '{$title}' deleted.");
    }

    public function askAi(Request $request, SanadAiRagService $rag)
    {
        abort_unless($this->canUseSanadModule('ai_tools', 'write'), 403);
        $question = trim((string) $request->input('question'));
        if ($question === '') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please enter a valid question.',
                ], 422);
            }
            return redirect()->back()->with('error', 'Please enter a valid question.');
        }

        $bookingId = $request->filled('booking_id') ? (int) $request->input('booking_id') : null;
        $booking = $bookingId ? Booking::find($bookingId) : null;
        $startedAt = microtime(true);
        $answer = $rag->answer($question, $booking, optional(auth()->user())->user_type ?: 'admin');
        $responseMs = (int) round((microtime(true) - $startedAt) * 1000);
        $confidence = $answer['confidence'];
        $requiresEscalation = $answer['requires_escalation'];

        $interaction = SanadAiInteraction::create([
            'user_id' => optional(auth()->user())->id,
            'booking_id' => $bookingId,
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
                'response_ms' => $responseMs,
            ],
        ]);

        $this->audit($request, 'sanad.ai.asked', $interaction);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'interaction' => [
                    'id' => $interaction->id,
                    'question' => $interaction->question,
                    'answer' => $interaction->answer,
                    'confidence' => $interaction->confidence,
                    'requires_escalation' => $interaction->requires_escalation,
                    'status' => $interaction->status,
                    'sources' => data_get($interaction->metadata, 'sources', []),
                    'response_ms' => $responseMs,
                    'user_name' => optional(auth()->user())->display_name ?: optional(auth()->user())->email ?: 'Admin',
                    'user_avatar' => Str::upper(Str::substr(optional(auth()->user())->display_name ?: 'A', 0, 1)),
                    'created_at' => $interaction->created_at->format('Y-m-d H:i'),
                ],
            ]);
        }

        return redirect()->back()->withSuccess('Quick AI response recorded.');
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
            'review_action' => 'required|in:approve,edit_approve,resolve,needs_revision,delete',
            'answer' => 'nullable|string',
            'review_note' => 'nullable|string|max:2000',
        ]);

        $interaction = SanadAiInteraction::findOrFail($id);

        if ($request->review_action === 'delete') {
            $interaction->delete();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => 'AI escalation deleted successfully.',
                    'interaction_id' => (int) $id,
                    'action' => 'delete',
                ]);
            }
            return back()->withSuccess('AI escalation deleted successfully.');
        }

        if ($request->review_action === 'edit_approve' && !trim((string) $request->answer)) {
            return back()->withErrors('Add the corrected answer before saving and approving.');
        }

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

        if (in_array($action, ['approve', 'edit_approve'], true)) {
            $this->recordAiReviewExample($interaction, $previous, $action, $request->review_note);
            $this->publishApprovedAiResponseToChat($interaction);
            if ($interaction->booking_id) {
                $this->broadcastConversationUpdate($interaction->booking_id, 'ai_response.approved', ['ai_interaction_id' => $interaction->id]);
            }
        }

        $this->audit($request, 'sanad.ai.escalation_reviewed', $interaction, [
            'action' => $action,
            'previous' => $previous,
            'current' => Arr::only($interaction->toArray(), ['answer', 'status', 'requires_escalation']),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'AI escalation review saved.',
                'interaction_id' => $interaction->id,
                'action' => $action,
            ]);
        }

        return back()->withSuccess('AI escalation review saved.');
    }

    public function deleteAiEscalation(Request $request, $id)
    {
        abort_unless($this->canUseSanadModule('ai_tools', 'write'), 403);

        $interaction = SanadAiInteraction::findOrFail($id);
        $interaction->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'AI escalation deleted successfully.',
                'interaction_id' => (int) $id,
                'action' => 'delete',
            ]);
        }

        return back()->withSuccess('AI escalation deleted successfully.');
    }

    public function promoteAiReviewExample(Request $request, $id, SanadVectorStoreService $vectorStore)
    {
        abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);
        abort_unless($this->canUseSanadModule('ai_tools', 'write'), 403);

        $example = SanadAiReviewExample::findOrFail($id);
        if ($example->promoted_knowledge_item_id) {
            return back()->withErrors('This review example has already been promoted.');
        }

        $item = SanadAiKnowledgeItem::create([
            'title' => 'Reviewed AI Answer: ' . Str::limit($example->question, 80),
            'category' => 'Reviewed Escalations',
            'content' => "Question:\n{$example->question}\n\nApproved answer:\n{$example->corrected_answer}",
            'visible_to' => ['admin', 'demo_admin', 'employee', 'provider', 'user'],
            'metadata' => [
                'source' => 'ai_review_example',
                'review_example_id' => $example->id,
                'ai_interaction_id' => $example->ai_interaction_id,
                'redaction_policy' => 'request_context_summarized',
            ],
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);
        $vectorStore->indexKnowledgeItem($item);

        $example->update([
            'status' => 'promoted',
            'promoted_knowledge_item_id' => $item->id,
            'promoted_at' => now(),
        ]);

        $this->audit($request, 'sanad.ai.review_example_promoted', $example, [
            'knowledge_item_id' => $item->id,
        ]);

        return back()->withSuccess('Reviewed answer promoted into Quick AI knowledge.');
    }

    private function recordAiReviewExample(SanadAiInteraction $interaction, array $previous, string $action, ?string $reviewNote): SanadAiReviewExample
    {
        $metadata = $interaction->metadata ?: [];

        return SanadAiReviewExample::updateOrCreate(
            ['ai_interaction_id' => $interaction->id],
            [
                'booking_id' => $interaction->booking_id,
                'reviewed_by' => auth()->id(),
                'actor_role' => optional($interaction->user)->user_type ?: data_get($metadata, 'actor_role'),
                'question' => $interaction->question,
                'original_answer' => $previous['answer'] ?? null,
                'corrected_answer' => $interaction->answer,
                'confidence' => $interaction->confidence,
                'review_action' => $action,
                'status' => 'ready',
                'context_summary' => $this->summarizeAiReviewContext($interaction),
                'sources' => data_get($metadata, 'sources', []),
                'metadata' => [
                    'langchain_dataset_stage' => 'curated_review_example',
                    'review_note' => $reviewNote,
                    'sensitive_data_policy' => 'summarized_context_only',
                    'created_from_status' => $previous['status'] ?? null,
                ],
            ]
        );
    }

    private function summarizeAiReviewContext(SanadAiInteraction $interaction): array
    {
        $booking = $interaction->booking;
        if (!$booking) {
            return [];
        }

        return [
            'request_reference' => $booking->quick_reference,
            'stage' => $booking->sanad_stage ?: $booking->status,
            'priority' => $booking->sanad_priority,
            'service' => optional($booking->service)->name,
            'payment_status' => optional($booking->payment)->payment_status,
            'has_customer' => (bool) $booking->customer_id,
            'has_partner' => (bool) $booking->provider_id,
        ];
    }

    private function publishApprovedAiResponseToChat(SanadAiInteraction $interaction): void
    {
        $bookingId = $interaction->booking_id;
        if (!$bookingId) {
            $latestBooking = Booking::latest()->first();
            $bookingId = $latestBooking ? $latestBooking->id : null;
        }

        if (!$bookingId || !$interaction->answer) {
            return;
        }

        $thread = SanadChatThread::firstOrCreate(
            ['booking_id' => $bookingId, 'thread_type' => 'shared'],
            ['participant_roles' => ['admin','demo_admin','employee','handyman','provider','user','customer'], 'created_by' => auth()->id()]
        );

        SanadChatMessage::updateOrCreate(
            ['ai_interaction_id' => $interaction->id, 'message_type' => 'ai_response'],
            [
                'thread_id' => $thread->id,
                'sender_id' => auth()->id(),
                'sender_role' => 'system',
                'message' => $interaction->answer,
                'visible_to' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
            ]
        );
        $thread->update(['last_message_at' => now()]);
    }

    private function broadcastConversationUpdate(int $bookingId, string $type, array $payload = []): void
    {
        try {
            broadcast(new SanadConversationUpdated($bookingId, $type, $payload))->toOthers();
        } catch (\Throwable $exception) {
            \Log::warning('Sanad conversation broadcast failed', [
                'booking_id' => $bookingId,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
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
                $query->whereNotNull('provider_id');
            }
            if ($request->assignment_state === 'unassigned') {
                $query->whereNull('provider_id');
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
                        ->orWhereNull('provider_id')
                        ->orWhere(function ($slaQuery) {
                            $slaQuery->whereNotNull('sla_due_at')->where('sla_due_at', '<', now());
                        })
                        ->orWhereHas('sanadDocuments', function ($documentQuery) {
                            $documentQuery->where('verification_status', 'pending');
                        })
                        ->orWhereHas('sanadBuzzAlerts', function ($buzzQuery) {
                            $this->whereVisibleBuzzForCurrentUser($buzzQuery->where('status', 'unread'));
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
                    $this->whereVisibleBuzzForCurrentUser($buzzQuery->where('status', 'unread'));
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
        $pageTitle = app()->getLocale() === 'ar' ? 'طلبات وعمليات كويك' : 'Quick Requests';
        $auth_user = authSession();

        return view('sanad.requests-index', compact('requests', 'pageTitle', 'auth_user', 'summary'));
    }

    public function showRequest($id)
    {
        abort_unless($this->canUseOrdersModule(), 403);
        $bookingdata = Booking::with(['customer', 'provider', 'service', 'payment.paymentHistory', 'handymanAdded.handyman', 'sanadRequestActions.actor'])
            ->myBooking()
            ->findOrFail($id);

        $pageTitle = (app()->getLocale() === 'ar' ? 'طلب كويك #' : 'Quick Request #') . $bookingdata->quick_reference;
        $auth_user = authSession();
        $documents = $this->visibleDocumentsQuery($bookingdata)->latest()->get();
        $buzzAlerts = $this->visibleBuzzQuery($bookingdata)->latest()->get();
        $chatThread = $this->visibleChatThread($bookingdata);
        $assignableEmployees = $this->assignableEmployees($bookingdata);
        $assignablePartners = $this->assignablePartners();
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
            'assignableEmployees',
            'assignablePartners',
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

        return redirect()->back()->withSuccess('Quick request action recorded.');
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

        return redirect()->back()->withSuccess('Quick payment status updated.');
    }

    public function assignEmployees(Request $request, $id)
    {
        abort_unless($this->canUseTeamEmployeeModule(true), 403);
        $this->abortUnlessEmployeeFlag('team_collaboration');
        $booking = Booking::with('handymanAdded')->myBooking()->findOrFail($id);
        $request->validate([
            'assignment_scope' => 'nullable|string|in:partner,self,employees_only',
            'provider_id' => 'nullable|integer',
            'handyman_id' => 'nullable|array',
            'handyman_id.*' => 'integer',
        ]);

        $assignmentScope = $request->input('assignment_scope', 'employees_only');
        $previousProviderId = $booking->provider_id;
        $isAdminAssignmentUser = auth()->user()->hasAnyRole(['admin', 'demo_admin']);

        if (!$isAdminAssignmentUser && in_array($assignmentScope, ['partner', 'self'], true)) {
            abort(403);
        }

        if ($assignmentScope === 'partner') {
            $partner = $this->assignablePartners()->firstWhere('id', (int) $request->provider_id);
            if (!$partner) {
                return redirect()->back()->withErrors('Please select an active partner for this request.');
            }

            $previousEmployeeIds = $booking->handymanAdded()->pluck('handyman_id')->map(function ($id) {
                return (int) $id;
            })->all();

            $booking->handymanAdded()->delete();
            $booking->provider_id = $partner->id;
            $booking->assignment_mode = 'manual';
            $booking->assigned_by = optional(auth()->user())->id;
            $booking->assigned_at = now();
            $booking->status = 'accept';
            $booking->sanad_stage = 'assigned_to_partner';
            $booking->save();

            SanadAssignmentDecision::create([
                'booking_id' => $booking->id,
                'recommended_provider_id' => null,
                'selected_provider_id' => $partner->id,
                'assignment_mode' => 'manual',
                'status' => 'selected',
                'decided_by' => optional(auth()->user())->id,
                'score_snapshot' => [
                    'source' => 'request_detail_assignment',
                    'previous_provider_id' => $previousProviderId,
                    'previous_employee_ids' => $previousEmployeeIds,
                ],
            ]);

            $this->audit($request, 'sanad.request.partner_assigned', $booking, [
                'previous_provider_id' => $previousProviderId,
                'current_provider_id' => $partner->id,
                'cleared_employee_ids' => $previousEmployeeIds,
            ]);

            return redirect()->back()->withSuccess('Request assigned to partner.');
        }

        $employeePool = $assignmentScope === 'self'
            ? $this->internalAssignableEmployees()
            : $this->assignableEmployees($booking);

        $allowedEmployeeIds = $employeePool->pluck('id')->map(function ($id) {
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
        if ($assignmentScope === 'self' && $requestedEmployeeIds->isEmpty()) {
            return redirect()->back()->withErrors('Please select at least one Quick employee when handling the request internally.');
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

        if ($assignmentScope === 'self') {
            $booking->provider_id = null;
            $booking->assignment_mode = 'internal';
        }
        $booking->assigned_by = optional(auth()->user())->id;
        $booking->assigned_at = now();
        if ($requestedEmployeeIds->isNotEmpty()) {
            $booking->status = 'accept';
            $booking->sanad_stage = 'assigned_to_employee';
        }
        $booking->save();

        $this->audit($request, 'sanad.request.employees_assigned', $booking, [
            'assignment_scope' => $assignmentScope,
            'previous_provider_id' => $previousProviderId,
            'current_provider_id' => $booking->provider_id,
            'previous_employee_ids' => $previousEmployeeIds,
            'current_employee_ids' => $requestedEmployeeIds->all(),
        ]);

        return redirect()->back()->withSuccess('Sanad employees assigned.');
    }

    public function toggleAiFirstResponder(Request $request, $id, SanadAiFirstResponderService $firstResponder)
    {
        abort_unless($this->canUseChatModule(true), 403);
        $booking = Booking::myBooking()->findOrFail($id);
        abort_unless($this->canManageChatControls($booking), 403);

        $data = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $firstResponder->setAiEnabled($booking, (bool) $data['enabled'], auth()->user());
        $this->audit($request, 'sanad.chat.ai_first_responder_' . ((bool) $data['enabled'] ? 'enabled' : 'disabled'), $booking);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'enabled' => (bool) $data['enabled'],
                'message' => (bool) $data['enabled'] ? 'AI first responder re-enabled.' : 'Manual takeover enabled.',
            ]);
        }

        return redirect()->back()->withSuccess((bool) $data['enabled'] ? 'AI first responder re-enabled.' : 'Manual takeover enabled.');
    }

    public function assignChat(Request $request, $id, SanadAiFirstResponderService $firstResponder)
    {
        abort_unless($this->canUseChatModule(true), 403);
        $booking = Booking::myBooking()->findOrFail($id);
        abort_unless($this->canManageChatControls($booking), 403);

        $data = $request->validate([
            'target_type' => 'required|in:sanad_team,partner_team,user',
            'target_user_id' => 'nullable|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        $targetType = $data['target_type'];
        $targetUser = null;
        if (!empty($data['target_user_id'])) {
            $targetPayload = $this->assignableChatTargets($booking)->firstWhere('id', (int) $data['target_user_id']);
            if (!$targetPayload || !$this->chatTargetMatchesTeam($targetPayload, $targetType, $booking)) {
                $message = 'Selected team member cannot be assigned to this chat.';
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['status' => false, 'message' => $message], 422);
                }
                return redirect()->back()->withErrors($message);
            }
            $targetUser = User::findOrFail($targetPayload['id']);
            $targetType = 'user';
        }

        if ($data['target_type'] === 'partner_team' && !$this->canAssignChatToPartnerTeam($booking)) {
            abort(403);
        }

        $firstResponder->assignChat($booking, $targetType, $targetUser, auth()->user(), $data['note'] ?? null);
        $this->audit($request, 'sanad.chat.assigned', $booking, [
            'target_type' => $targetType,
            'target_user_id' => optional($targetUser)->id,
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Chat assignment updated.',
                'assignment' => [
                    'owner_type' => $booking->fresh()->chat_owner_type,
                    'owner_user_id' => $booking->fresh()->chat_owner_user_id,
                    'label' => $this->chatAssignmentLabel($booking->fresh()),
                ],
            ]);
        }

        return redirect()->back()->withSuccess('Chat assignment updated.');
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
            return redirect()->back()->withErrors('Invalid Quick request lifecycle stage.');
        }

        $booking = Booking::myBooking()->findOrFail($id);
        $previous = Arr::only($booking->toArray(), ['sanad_stage', 'sanad_priority', 'sla_due_at']);

        $booking->fill($request->only(['sanad_stage', 'sanad_priority', 'sla_due_at']));
        if (empty($booking->sanad_reference)) {
            $booking->sanad_reference = 'QUICK-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT);
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

        return redirect()->back()->withSuccess('Quick request lifecycle updated.');
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

        return redirect()->back()->withSuccess('Quick document added.');
    }

    private function serviceDocumentOptions($service)
    {
        return collect(optional($service)->required_documents ?: [])
            ->map(function ($document, $index) {
                if (is_array($document)) {
                    $storedName = trim((string) ($document['name'] ?? $document['label'] ?? $document['title'] ?? $document['key'] ?? ''));
                    $name = localized_service_document_name($document, '');
                    $key = trim((string) ($document['key'] ?? Str::slug($storedName ?: 'document-'.$index, '_')));

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
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'message' => 'nullable|string|max:1000',
            'document_name' => 'nullable|string|max:255',
            'recipient_role' => 'nullable|string|in:customer,partner,handyman,provider',
            'action_type' => 'nullable|string|max:100',
        ]);

        $recipientRole = $request->input('recipient_role') ?: 'customer';
        $recipientId = ($recipientRole === 'partner' || $recipientRole === 'provider')
            ? ($booking->provider_id ?: $booking->customer_id)
            : $booking->customer_id;

        $message = trim((string) $request->input('message'));
        $docName = trim((string) $request->input('document_name'));

        if ($message === '') {
            $message = $this->composeAiDocumentReminderMessage($booking, $docName ?: 'المستند المطلوب', $recipientRole);
        }

        $alert = SanadBuzzAlert::create([
            'booking_id' => $booking->id,
            'sender_id' => optional(auth()->user())->id,
            'recipient_id' => $recipientId,
            'recipient_role' => $recipientRole,
            'priority' => $request->priority ?: 'urgent',
            'message' => $message,
            'action_type' => $request->input('action_type') ?: ($docName ? 'document_reminder' : null),
            'action_status' => 'pending',
            'status' => 'unread',
        ]);

        $thread = SanadChatThread::firstOrCreate(
            ['booking_id' => $booking->id, 'thread_type' => 'shared'],
            ['participant_roles' => ['admin','demo_admin','employee','handyman','provider','user','customer'], 'created_by' => auth()->id()]
        );
        SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => auth()->id(),
            'sender_role' => optional(auth()->user())->user_type ?: 'admin',
            'message' => $message,
            'visible_to' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
            'message_type' => 'buzz',
            'buzz_alert_id' => $alert->id,
            'recipient_id' => $alert->recipient_id,
        ]);
        $thread->update(['last_message_at' => now()]);

        $this->audit($request, 'sanad.buzz.created', $alert);
        $this->broadcastConversationUpdate($booking->id, 'buzz.created', ['buzz_alert_id' => $alert->id]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'status' => true,
                'message' => __('messages.buzz_alert_sent') ?: 'تم إرسال تنبيه وتذكير العميل بالمستند المطلوب بنجاح.',
                'buzz_id' => $alert->id,
                'chat_url' => route('sanad.chat.workspace', ['booking_id' => $booking->id, 'buzz_id' => $alert->id]),
            ]);
        }

        return redirect()->to(route('sanad.chat.workspace', ['booking_id' => $booking->id, 'buzz_id' => $alert->id]))->withSuccess('Quick Buzz alert sent.');
    }

    private function composeAiDocumentReminderMessage(Booking $booking, string $documentName, string $recipientRole = 'customer'): string
    {
        $ref = $booking->quick_reference ?: 'QUICK-' . str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT);
        $serviceName = optional($booking->service)->name_en ?: optional($booking->service)->name ?: 'Government Service';
        $customerName = optional($booking->customer)->display_name ?: optional($booking->customer)->first_name ?: 'العميل العزيز';

        try {
            if (class_exists(\App\Services\SanadNvidiaAiClient::class)) {
                /** @var \App\Services\SanadNvidiaAiClient $aiClient */
                $aiClient = app(\App\Services\SanadNvidiaAiClient::class);
                $systemPrompt = 'You are Quick Sanad AI Operations Assistant. Compose a concise, polite, urgent 1-sentence reminder message in Arabic followed by a 1-sentence English translation. Put your response strictly inside <answer>...</answer> tags. Do not include thinking or preamble outside the tags.';
                $userPrompt = "Target: {$customerName} ({$recipientRole}). Request reference: {$ref}. Service: {$serviceName}. Required document missing: '{$documentName}'. Politely request them to reply in chat or upload this file so processing can continue without delay.";

                $res = $aiClient->chat([
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ], [
                    'max_tokens' => 180,
                    'temperature' => 0.2,
                ]);

                $content = trim($res['content'] ?? '');
                if (preg_match('/<answer>\s*(.*?)\s*<\/answer>/is', $content, $m)) {
                    $content = trim($m[1]);
                }
                if (!empty($content) && Str::length($content) > 15 && !Str::startsWith($content, ["Here's", "Let's", "Draft"])) {
                    return $content;
                }
            }
        } catch (\Throwable $e) {
            // Fallback to standard high-quality template
        }

        return "تذكير عاجل من كويك: يرجى تزويدنا بمستند ({$documentName}) المطلوب لإكمال معالجة طلبكم رقم ({$ref}) لخدمة ({$serviceName}). نرجو إرفاق الملف أو الرد مباشرة هنا.\n\nQuick Reminder: Please provide the required document ({$documentName}) to complete your request ({$ref}). You can reply directly in this chat or upload the file.";
    }

    public function acknowledgeBuzz(Request $request, $id, $alertId)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $alert = $this->visibleBuzzQuery($booking)->findOrFail($alertId);

        $alert->status = 'acknowledged';
        $alert->acknowledged_at = now();
        if ($alert->action_type === 'chat_assignment_accept') {
            $alert->action_status = 'accepted';
            $booking->forceFill([
                'chat_owner_type' => 'user',
                'chat_owner_user_id' => $alert->recipient_id,
                'chat_assigned_at' => now(),
            ])->save();
        }
        $alert->save();

        $this->audit($request, 'sanad.buzz.acknowledged', $alert);
        $this->broadcastConversationUpdate($booking->id, 'buzz.acknowledged', ['buzz_alert_id' => $alert->id]);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json(['status' => true, 'buzz_id' => $alert->id, 'action_status' => $alert->action_status]);
        }

        return redirect()->back()->withSuccess('Quick Buzz alert acknowledged.');
    }

    public function storeChatMessage(Request $request, $id)
    {
        abort_unless($this->canUseChatModule(true), 403);
        $this->abortUnlessEmployeeFlag('customer_chat');
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'message' => 'required|string|max:2000',
            'delivery_mode' => 'nullable|in:message,buzz,document',
            'buzz_priority' => 'nullable|string|in:low,normal,high,urgent',
            'document_key' => 'nullable|string|max:255',
            'document_name' => 'nullable|string|max:255',
            'requested_from' => 'nullable|in:customer',
            'due_at' => 'nullable|date',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string',
            'thread_type' => 'nullable|in:shared,internal,partner_internal',
            'message_type' => 'nullable|in:text,attachment,buzz_reply,document_request,ai_response',
            'buzz_alert_id' => 'nullable|integer',
            'document_request_id' => 'nullable|integer',
            'ai_interaction_id' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        if ($request->input('delivery_mode') === 'buzz') {
            $this->abortUnlessEmployeeFlag('send_buzz');

            $alert = SanadBuzzAlert::create([
                'booking_id' => $booking->id,
                'sender_id' => optional(auth()->user())->id,
                'recipient_id' => $booking->customer_id,
                'recipient_role' => 'customer',
                'priority' => $request->buzz_priority ?: 'urgent',
                'message' => $request->message,
            ]);

            $thread = SanadChatThread::where('booking_id', $booking->id)->where('thread_type', 'shared')->latest()->first() ?: SanadChatThread::create([
                'booking_id' => $booking->id,
                'thread_type' => 'shared',
                'participant_roles' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
                'created_by' => optional(auth()->user())->id,
            ]);

            SanadChatMessage::create([
                'thread_id' => $thread->id,
                'sender_id' => optional(auth()->user())->id,
                'sender_role' => optional(auth()->user())->user_type,
                'message' => $request->message,
                'visible_to' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
                'message_type' => 'buzz',
                'buzz_alert_id' => $alert->id,
                'recipient_id' => $booking->customer_id,
            ]);
            $thread->update(['last_message_at' => now()]);

            $this->audit($request, 'sanad.buzz.created_from_chat', $alert);
            $this->broadcastConversationUpdate($booking->id, 'buzz.created', ['buzz_alert_id' => $alert->id]);

            return redirect()->to(route('sanad.chat.workspace', ['booking_id' => $booking->id, 'buzz_id' => $alert->id]))->withSuccess('Customer Buzz sent.');
        }

        if ($request->input('delivery_mode') === 'document') {
            abort_unless($this->canUseDocumentReviewModule(true), 403);
            $this->abortUnlessEmployeeFlag('review_documents');

            $documentName = trim((string) $request->input('document_name'));
            if ($documentName === '') {
                return back()->withErrors('Please choose or enter the document name.');
            }

            $item = SanadDocumentRequest::create([
                'booking_id' => $booking->id,
                'service_id' => $booking->service_id,
                'document_key' => $request->input('document_key') ?: Str::slug($documentName, '_'),
                'document_name' => $documentName,
                'requested_from' => 'customer',
                'requested_from_user_id' => $booking->customer_id,
                'requested_by' => auth()->id(),
                'reason' => $request->message,
                'instructions' => $request->message,
                'due_at' => $request->input('due_at'),
                'required' => true,
            ]);

            $thread = SanadChatThread::where('booking_id', $booking->id)->where('thread_type', 'shared')->latest()->first() ?: SanadChatThread::create([
                'booking_id' => $booking->id,
                'thread_type' => 'shared',
                'participant_roles' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
                'created_by' => optional(auth()->user())->id,
            ]);

            SanadChatMessage::create([
                'thread_id' => $thread->id,
                'sender_id' => optional(auth()->user())->id,
                'sender_role' => optional(auth()->user())->user_type,
                'message' => 'Document requested: ' . $item->document_name,
                'visible_to' => ['admin','demo_admin','employee','handyman','provider','user','customer'],
                'message_type' => 'document_request',
                'document_request_id' => $item->id,
                'recipient_id' => $booking->customer_id,
            ]);
            $thread->update(['last_message_at' => now()]);

            $this->audit($request, 'sanad.document_request.created_from_chat', $item);
            $this->broadcastConversationUpdate($booking->id, 'document_request.created', ['document_request_id' => $item->id]);

            return redirect()->to(route('sanad.chat.workspace', ['booking_id' => $booking->id]))->withSuccess('Customer document request sent.');
        }

        if ($booking->ai_first_responder_enabled !== false && ($request->input('delivery_mode') ?: 'message') === 'message') {
            $message = 'Disable AI first responder to send a direct message.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $message], 422);
            }

            return redirect()->back()->withErrors($message);
        }

        $directMessageLock = $this->directMessageLock($booking, auth()->user());
        if (($request->input('delivery_mode') ?: 'message') === 'message' && $directMessageLock['locked']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['status' => false, 'message' => $directMessageLock['message']], 422);
            }

            return redirect()->back()->withErrors($directMessageLock['message']);
        }

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
            'internal' => ['admin', 'demo_admin', 'employee', 'handyman'],
            'partner_internal' => ['admin', 'demo_admin', 'employee', 'handyman', 'provider'],
            default => ['admin', 'demo_admin', 'employee', 'handyman', 'provider', 'user', 'customer'],
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
            'message_type' => $request->message_type ?: 'text',
            'buzz_alert_id' => $request->buzz_alert_id,
            'document_request_id' => $request->document_request_id,
            'ai_interaction_id' => $request->ai_interaction_id,
        ]);
        if ($request->hasFile('attachment')) storeMediaFile($message, $request->file('attachment'), 'attachment');
        $thread->update(['last_message_at' => now()]);

        $this->audit($request, 'sanad.chat.message_created', $message);
        $this->broadcastConversationUpdate($booking->id, 'chat.message_created', ['message_id' => $message->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Sanad chat message sent.',
                'chat_message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_role' => $message->sender_role,
                    'sender_name' => optional(auth()->user())->display_name ?: optional(auth()->user())->email ?: 'User',
                    'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                    'buzz_alert_id' => $message->buzz_alert_id,
                    'document_request_id' => $message->document_request_id,
                    'ai_interaction_id' => $message->ai_interaction_id,
                ],
            ]);
        }

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
        $thread = SanadChatThread::firstOrCreate(['booking_id' => $booking->id, 'thread_type' => 'shared'], ['participant_roles' => ['admin','demo_admin','employee','handyman','provider','user','customer'], 'created_by' => auth()->id()]);
        SanadChatMessage::create(['thread_id' => $thread->id, 'sender_id' => auth()->id(), 'sender_role' => auth()->user()->user_type, 'message' => 'Document requested: '.$item->document_name, 'visible_to' => ['admin','demo_admin','employee','handyman','provider','user','customer'], 'message_type' => 'document_request', 'document_request_id' => $item->id]);
        $thread->update(['last_message_at' => now()]);
        $this->audit($request, 'sanad.document_request.created', $item);
        $this->broadcastConversationUpdate($booking->id, 'document_request.created', ['document_request_id' => $item->id]);
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
        $this->broadcastConversationUpdate($booking->id, 'document_request.submitted', ['document_request_id' => $item->id]);
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
        $this->broadcastConversationUpdate($item->booking_id, 'document_request.reviewed', ['document_request_id' => $item->id, 'status' => $request->status]);
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
                        $this->whereJsonArrayContainsAny($visibilityQuery, 'visible_to', $this->chatParticipantRoleAliases($user));
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
        $slaTrackedOrders = (clone $query)->whereNotNull('sla_due_at')->count();
        $slaCompliantOrders = (clone $query)
            ->whereNotNull('sla_due_at')
            ->where(function ($slaQuery) {
                $slaQuery->whereNull('closed_at')
                    ->where('sla_due_at', '>=', now())
                    ->orWhere(function ($closedQuery) {
                        $closedQuery->whereNotNull('closed_at')
                            ->whereColumn('closed_at', '<=', 'sla_due_at');
                    });
            })
            ->count();
        $waitingCustomer = (clone $query)->where('sanad_stage', 'waiting_for_customer')->count();
        $waitingGovernment = (clone $query)->where('sanad_stage', 'government_processing')->count();
        $revenue = (clone $query)->whereHas('payment', function ($paymentQuery) {
            $paymentQuery->where('payment_status', 'paid');
        })->sum('total_amount');
        $processedTodayCount = (clone $query)
            ->whereDate('updated_at', now()->toDateString())
            ->count();
        $processedTodayVolume = (clone $query)
            ->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })
            ->whereDate('updated_at', now()->toDateString())
            ->sum('total_amount');
        $pendingActionCount = (clone $query)
            ->whereIn('sanad_stage', ['pending_review', 'awaiting_customer_action', 'awaiting_quality_review', 'escalated'])
            ->count();
        $unassignedCount = (clone $query)
            ->whereNotNull('sanad_stage')
            ->whereNull('provider_id')
            ->count();

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
            'active_operations' => $activeOrders,
            'pending_action_count' => $pendingActionCount,
            'sla_compliance' => $slaTrackedOrders > 0 ? round(($slaCompliantOrders / $slaTrackedOrders) * 100, 1) : null,
            'sla_tracked_orders' => $slaTrackedOrders,
            'overdue_orders' => $overdueOrders,
            'unassigned_orders' => $unassignedCount,
            'processed_today_count' => $processedTodayCount,
            'processed_today_volume' => $processedTodayVolume,
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
            'unassigned' => (clone $query)->whereNull('provider_id')->count(),
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
        $openBuzz = $booking->sanadBuzzAlerts()
            ->whereIn('status', ['unread', 'sent'])
            ->where(function ($buzzQuery) {
                $buzzQuery->whereNull('action_type')
                    ->orWhere('action_type', '!=', 'chat_assignment_accept');
            })
            ->count();
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
        $query = User::with('providers')->where('user_type', 'handyman')->where('status', 1)->orderBy('display_name');
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

            return $query->get()->filter(function ($employee) use ($assignedEmployeeIds) {
                return $employee->dailyAvailableCapacity(now()) > 0 || in_array($employee->id, $assignedEmployeeIds);
            })->values();
        }

        if ($user->hasRole('provider')) {
            return $query->where('provider_id', $user->id)->get()
                ->filter(fn ($employee) => $employee->dailyAvailableCapacity(now()) > 0 || in_array($employee->id, $assignedEmployeeIds))
                ->values();
        }

        if ($user->hasRole('handyman')) {
            return $query->where('id', $user->id)->get()
                ->filter(fn ($employee) => $employee->dailyAvailableCapacity(now()) > 0)
                ->values();
        }

        return collect();
    }

    private function assignablePartners()
    {
        return User::where('user_type', 'provider')
            ->where('status', 1)
            ->orderBy('display_name')
            ->orderBy('email')
            ->get();
    }

    private function internalAssignableEmployees()
    {
        return User::with('providers')
            ->where('user_type', 'handyman')
            ->where('status', 1)
            ->where(function ($query) {
                $query->whereNull('provider_id')->orWhere('provider_id', 0);
            })
            ->orderBy('display_name')
            ->orderBy('email')
            ->get()
            ->filter(fn ($employee) => $employee->dailyAvailableCapacity(now()) > 0)
            ->values();
    }

    public function assignableChatTargets(Booking $booking)
    {
        $user = auth()->user();
        $query = User::with('providers')
            ->whereIn('user_type', ['provider', 'handyman'])
            ->where('status', 1)
            ->orderBy('display_name');

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return $query->where(function ($q) use ($booking) {
                $q->where(function ($sanad) {
                    $sanad->where('user_type', 'handyman')
                        ->where(function ($staff) {
                            $staff->whereNull('provider_id')->orWhere('provider_id', 0);
                        });
                });
                if ($booking->provider_id) {
                    $q->orWhere('id', $booking->provider_id)
                        ->orWhere('provider_id', $booking->provider_id);
                }
            })->get()->map(fn ($target) => $this->chatTargetPayload($target))->values();
        }

        if ($user->hasRole('provider')) {
            return $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                    ->orWhere('provider_id', $user->id);
            })->get()->map(fn ($target) => $this->chatTargetPayload($target))->values();
        }

        if ($user->hasRole('handyman')) {
            if (!empty($user->provider_id)) {
                return $query->where(function ($q) use ($user) {
                    $q->where('id', $user->provider_id)
                        ->orWhere('provider_id', $user->provider_id);
                })->get()->map(fn ($target) => $this->chatTargetPayload($target))->values();
            }

            return $query->where(function ($q) {
                $q->where('user_type', 'handyman')
                    ->where(function ($staff) {
                        $staff->whereNull('provider_id')->orWhere('provider_id', 0);
                    });
            })->get()->map(fn ($target) => $this->chatTargetPayload($target))->values();
        }

        return collect();
    }

    private function chatTargetPayload(User $target): array
    {
        $name = trim(implode(' ', array_filter([$target->first_name, $target->last_name])));
        if ($name === '') {
            $name = $target->display_name ?: $target->email;
        }

        $association = $target->user_type === 'provider'
            ? 'Partner'
            : ($target->provider_id
            ? (optional($target->providers)->display_name ?: optional($target->providers)->first_name ?: 'Partner')
            : 'Sanad');

        return [
            'id' => $target->id,
            'name' => $name,
            'role' => $association,
            'team' => ($target->provider_id || $target->user_type === 'provider') ? 'partner_team' : 'sanad_team',
            'provider_id' => $target->user_type === 'provider' ? $target->id : $target->provider_id,
        ];
    }

    private function chatTargetMatchesTeam(array $targetPayload, string $targetType, Booking $booking): bool
    {
        if ($targetType === 'user') {
            return true;
        }

        if (($targetPayload['team'] ?? null) !== $targetType) {
            return false;
        }

        if ($targetType === 'partner_team') {
            return !empty($booking->provider_id) && (int) ($targetPayload['provider_id'] ?? 0) === (int) $booking->provider_id;
        }

        return true;
    }

    private function chatOwnerTeam(Booking $booking): string
    {
        if ($booking->chat_owner_type === 'partner_team') {
            return 'partner_team';
        }

        if ($booking->chat_owner_type === 'user' && $booking->chat_owner_user_id) {
            $owner = User::find($booking->chat_owner_user_id);
            if ($owner && ($owner->provider_id || $owner->user_type === 'provider')) {
                return 'partner_team';
            }
        }

        return 'sanad_team';
    }

    private function canManageChatControls(Booking $booking): bool
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return (int) $booking->provider_id === (int) $user->id
                || (int) $booking->chat_owner_user_id === (int) $user->id;
        }

        if ($user->hasRole('handyman')) {
            if (!empty($user->provider_id)) {
                return (int) $booking->chat_owner_user_id === (int) $user->id
                    || ((int) $booking->provider_id === (int) $user->provider_id && $booking->chat_owner_type === 'partner_team');
            }

            return (int) $booking->chat_owner_user_id === (int) $user->id
                || $booking->handymanAdded()->where('handyman_id', $user->id)->exists()
                || $user->can('booking list');
        }

        return false;
    }

    private function canAssignChatToPartnerTeam(Booking $booking): bool
    {
        $user = auth()->user();

        if (!$booking->provider_id) {
            return false;
        }

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return true;
        }

        if ($user->hasRole('provider')) {
            return (int) $booking->provider_id === (int) $user->id
                || (int) $booking->chat_owner_user_id === (int) $user->id;
        }

        if ($user->hasRole('handyman') && !empty($user->provider_id)) {
            return (int) $booking->provider_id === (int) $user->provider_id
                || (int) $booking->chat_owner_user_id === (int) $user->id;
        }

        return false;
    }

    public function directMessageLock(Booking $booking, ?User $user = null): array
    {
        $user = $user ?: auth()->user();

        if (!$user || in_array(optional($user)->user_type, ['user', 'customer'], true)) {
            return ['locked' => false, 'message' => null];
        }

        if ($booking->ai_first_responder_enabled !== false) {
            return [
                'locked' => true,
                'message' => 'Disable AI first responder to send a direct message.',
            ];
        }

        $assignmentBuzzQuery = SanadBuzzAlert::where('booking_id', $booking->id)
            ->where('action_type', 'chat_assignment_accept')
            ->latest();

        if ($booking->chat_owner_type === 'user' && $booking->chat_owner_user_id) {
            if ((int) $booking->chat_owner_user_id !== (int) $user->id) {
                return [
                    'locked' => true,
                    'message' => 'This chat is assigned to another team member.',
                ];
            }

            $latestAssignment = (clone $assignmentBuzzQuery)
                ->where('recipient_id', $user->id)
                ->first();

            if ($latestAssignment && $latestAssignment->action_status !== 'accepted') {
                return [
                    'locked' => true,
                    'message' => 'Accept the chat assignment before sending a direct message.',
                ];
            }

            return ['locked' => false, 'message' => null];
        }

        $pendingAssignment = (clone $assignmentBuzzQuery)
            ->where('recipient_id', $user->id)
            ->where(function ($assignmentQuery) {
                $assignmentQuery->whereNull('action_status')
                    ->orWhere('action_status', 'pending');
            })
            ->exists();

        if ($pendingAssignment) {
            return [
                'locked' => true,
                'message' => 'Accept the chat assignment before sending a direct message.',
            ];
        }

        return ['locked' => false, 'message' => null];
    }

    private function chatAssignmentLabel(Booking $booking): string
    {
        if (($booking->chat_owner_type ?: 'ai') === 'ai') {
            return 'AI First Responder';
        }

        if ($booking->chat_owner_type === 'sanad_team') {
            return 'Quick Team';
        }

        if ($booking->chat_owner_type === 'partner_team') {
            return 'Partner Team';
        }

        if ($booking->chat_owner_type === 'user' && $booking->chat_owner_user_id) {
            $target = User::find($booking->chat_owner_user_id);
            return $target ? ($target->display_name ?: $target->first_name ?: $target->email) : 'Assigned Team Member';
        }

        return 'Unassigned';
    }

    private function visibleBuzzQuery(Booking $booking)
    {
        return $this->whereVisibleBuzzForCurrentUser(SanadBuzzAlert::where('booking_id', $booking->id), $booking);
    }

    private function whereVisibleBuzzForCurrentUser($query, ?Booking $booking = null)
    {
        $user = auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($visibilityQuery) use ($user, $booking) {
            $visibilityQuery->where(function ($assignmentQuery) use ($user) {
                $assignmentQuery->where('action_type', 'chat_assignment_accept')
                    ->where('recipient_id', $user->id);
            })->orWhere(function ($regularQuery) use ($user, $booking) {
                $regularQuery->where(function ($notAssignmentQuery) {
                    $notAssignmentQuery->whereNull('action_type')
                        ->orWhere('action_type', '!=', 'chat_assignment_accept');
                });

                if ($user->hasRole('admin') || $user->hasRole('demo_admin')) {
                    return;
                }

                if ($booking && $user->hasRole('provider') && (int) $booking->provider_id === (int) $user->id) {
                    return;
                }

                if ($booking && $user->hasRole('handyman')) {
                    $assignedEmployeeIds = $booking->handymanAdded()->pluck('handyman_id')->map(function ($id) {
                        return (int) $id;
                    });

                    if ((int) $booking->handyman_id === (int) $user->id || $assignedEmployeeIds->contains((int) $user->id)) {
                        return;
                    }
                }

                $regularQuery->where(function ($roleQuery) use ($user) {
                    $roleQuery->where('sender_id', $user->id)
                        ->orWhere('recipient_id', $user->id)
                        ->orWhere('recipient_role', $user->user_type);
                });
            });
        });
    }

    private function visibleChatThread(Booking $booking)
    {
        $user = auth()->user();
        $query = SanadChatThread::where('booking_id', $booking->id);

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($user) {
                        $this->whereJsonArrayContainsAny($visibilityQuery, 'participant_roles', $this->chatParticipantRoleAliases($user));
                    });
            });
        }

        $sharedThread = (clone $query)->where('thread_type', 'shared')->latest()->first();
        return $sharedThread ?: ($query->latest()->first() ?: SanadChatThread::firstOrCreate([
            'booking_id' => $booking->id,
            'thread_type' => 'shared',
        ], [
            'participant_roles' => ['admin', 'demo_admin', 'employee', 'handyman', 'provider', 'user', 'customer'],
            'created_by' => optional($user)->id,
            'status' => 'open',
            'last_message_at' => now(),
        ]));
    }

    private function chatParticipantRoleAliases(User $user): array
    {
        $roles = array_filter([(string) $user->user_type]);

        if ($user->hasRole('handyman') || $user->user_type === 'handyman') {
            $roles[] = 'employee';
            $roles[] = 'handyman';
        }

        if ($user->hasRole('employee') || $user->user_type === 'employee') {
            $roles[] = 'employee';
            $roles[] = 'handyman';
        }

        if (in_array($user->user_type, ['user', 'customer'], true)) {
            $roles[] = 'user';
            $roles[] = 'customer';
        }

        if ($user->hasRole('provider') || $user->user_type === 'provider') {
            $roles[] = 'provider';
        }

        return array_values(array_unique($roles));
    }

    private function whereJsonArrayContainsAny($query, $column, array $values)
    {
        return $query->where(function ($roleQuery) use ($column, $values) {
            foreach ($values as $value) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $roleQuery->orWhere($column, 'like', '%"' . $value . '"%');
                } else {
                    $roleQuery->orWhereJsonContains($column, $value);
                }
            }
        });
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
