<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SanadAiInteraction;
use App\Models\SanadAiKnowledgeItem;
use App\Models\SanadAuditLog;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\SanadDocumentVaultItem;
use App\Models\SanadDocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SanadAiRagService;

class SanadController extends Controller
{
    public function foundation()
    {
        return comman_custom_response([
            'brand' => config('sanad.brand'),
            'terminology' => config('sanad.terminology'),
            'roles' => config('sanad.roles'),
            'request_lifecycle' => config('sanad.request_lifecycle'),
            'document_visibility' => config('sanad.document_visibility'),
            'ai' => [
                'enabled' => (bool) config('sanad.ai.enabled'),
            ],
        ]);
    }

    public function requests(Request $request)
    {
        $query = Booking::with(['customer', 'provider', 'service', 'handymanAdded'])
            ->myBooking()
            ->list();

        if ($request->filled('sanad_stage')) {
            $query->where('sanad_stage', $request->sanad_stage);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('sanad_priority', $request->priority);
        }

        $perPage = $request->per_page ?: config('constant.PER_PAGE_LIMIT', 15);
        $requests = $query->paginate($perPage);

        return comman_custom_response([
            'pagination' => [
                'total_items' => $requests->total(),
                'per_page' => $requests->perPage(),
                'currentPage' => $requests->currentPage(),
                'totalPages' => $requests->lastPage(),
                'from' => $requests->firstItem(),
                'to' => $requests->lastItem(),
                'next_page' => $requests->nextPageUrl(),
                'previous_page' => $requests->previousPageUrl(),
            ],
            'data' => $requests->items(),
        ]);
    }

    public function communication(Request $request, $id)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $threads = SanadChatThread::where('booking_id', $booking->id)
            ->where(function ($q) { $q->where('thread_type', 'shared')->orWhere('created_by', auth()->id()); })
            ->with(['messages' => fn ($q) => $q->latest()->take(50)])->get();
        return comman_custom_response(['data' => ['threads' => $threads, 'document_requests' => $booking->sanadDocumentRequests()->with('document')->latest()->get()]]);
    }

    public function sendCommunication(Request $request, $id)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate(['message' => 'required|string|max:2000', 'thread_type' => 'nullable|in:shared,internal']);
        $type = $request->thread_type ?: 'shared';
        if ($type === 'internal' && !$this->isAdmin() && !in_array(auth()->user()->user_type, ['handyman', 'employee'], true)) abort(403);
        $thread = SanadChatThread::firstOrCreate(['booking_id' => $booking->id, 'thread_type' => $type], ['participant_roles' => $type === 'internal' ? ['admin','demo_admin','employee','handyman'] : ['admin','demo_admin','employee','handyman','provider','user','customer'], 'created_by' => auth()->id()]);
        $message = SanadChatMessage::create(['thread_id' => $thread->id, 'sender_id' => auth()->id(), 'sender_role' => auth()->user()->user_type, 'message' => $request->message, 'visible_to' => $thread->participant_roles, 'message_type' => 'text']);
        $thread->update(['last_message_at' => now()]);
        return comman_custom_response(['data' => $message]);
    }

    public function markCommunicationRead(Request $request, $id, $threadId)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $thread = SanadChatThread::where('booking_id', $booking->id)->findOrFail($threadId);
        abort_unless($thread->thread_type === 'shared' || $this->isAdmin() || in_array(auth()->user()->user_type, ['handyman', 'employee'], true), 403);
        $thread->messages()->whereNull('read_at')->where('sender_id', '!=', auth()->id())->update(['read_at' => now()]);
        return comman_custom_response(['message' => 'Conversation marked as read.']);
    }

    public function createDocumentRequest(Request $request, $id)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        if (!$this->isOperationsUser()) abort(403);
        $request->validate(['document_name'=>'required|string|max:255','requested_from'=>'required|in:customer,partner','reason'=>'required|string|max:2000','instructions'=>'nullable|string|max:4000','due_at'=>'nullable|date']);
        if ($this->isPartner(auth()->user()) && (int)$booking->provider_id !== auth()->id()) abort(403);
        $target = $request->requested_from === 'customer' ? $booking->customer_id : $booking->provider_id;
        $item = SanadDocumentRequest::create(['booking_id'=>$booking->id,'service_id'=>$booking->service_id,'document_key'=>$request->document_key,'document_name'=>$request->document_name,'requested_from'=>$request->requested_from,'requested_from_user_id'=>$target,'requested_by'=>auth()->id(),'reason'=>$request->reason,'instructions'=>$request->instructions,'required'=>$request->boolean('required',true),'due_at'=>$request->due_at]);
        return comman_custom_response(['data'=>$item]);
    }

    public function updateRequestLifecycle(Request $request, $id)
    {
        $request->validate([
            'sanad_stage' => 'required|string',
            'sanad_priority' => 'nullable|string|in:low,normal,high,urgent',
            'sla_due_at' => 'nullable|date',
        ]);

        $allowedStages = config('sanad.request_lifecycle');
        if (!in_array($request->sanad_stage, $allowedStages, true)) {
            return comman_custom_response([
                'message' => 'Invalid request lifecycle stage.',
                'allowed_stages' => $allowedStages,
            ], 422);
        }

        $booking = Booking::myBooking()->findOrFail($id);
        $this->authorizeWorkflowMutation($booking);
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

        $this->audit($request, 'sanad.request.lifecycle_updated', $booking, [
            'previous' => $previous,
            'current' => Arr::only($booking->toArray(), ['sanad_stage', 'sanad_priority', 'sla_due_at']),
        ]);

        return comman_custom_response(['data' => $booking]);
    }

    public function createBuzz(Request $request)
    {
        $request->validate([
            'booking_id' => 'nullable|integer',
            'recipient_id' => 'nullable|integer',
            'recipient_role' => 'nullable|string|in:admin,demo_admin,employee,handyman,provider,user,customer',
            'priority' => 'nullable|string|in:low,normal,high,urgent',
            'message' => 'required|string|max:1000',
        ]);

        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::myBooking()->findOrFail($request->booking_id);
            $this->authorizeBuzzMutation($booking);
        } elseif (!$this->isAdmin()) {
            abort(403);
        }

        if ($request->filled('recipient_id') && !$this->isAdmin()) {
            $allowedRecipientIds = collect([
                optional($booking)->customer_id,
                optional($booking)->provider_id,
            ])->merge(optional($booking)->handymanAdded?->pluck('handyman_id') ?: [])->filter()->map(fn ($id) => (int) $id);
            abort_unless($allowedRecipientIds->contains((int) $request->recipient_id), 403);
        }

        $alert = SanadBuzzAlert::create([
            'booking_id' => $request->booking_id,
            'sender_id' => optional(auth()->user())->id,
            'recipient_id' => $request->recipient_id,
            'recipient_role' => $request->recipient_role ?: 'admin',
            'priority' => $request->priority ?: 'urgent',
            'message' => $request->message,
        ]);

        $this->audit($request, 'sanad.buzz.created', $alert);

        return comman_custom_response(['data' => $alert]);
    }

    public function buzzAlerts(Request $request)
    {
        $query = $this->visibleBuzzQuery()->with('booking')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        return comman_custom_response(['data' => $query->paginate($request->per_page ?: 15)]);
    }

    public function acknowledgeBuzz(Request $request, $id)
    {
        $alert = $this->visibleBuzzQuery()->findOrFail($id);
        $alert->status = 'acknowledged';
        $alert->acknowledged_at = now();
        $alert->save();

        $this->audit($request, 'sanad.buzz.acknowledged', $alert);

        return comman_custom_response(['data' => $alert]);
    }

    private function visibleBuzzQuery()
    {
        $user = auth()->user();
        $query = SanadBuzzAlert::query();

        if (!$this->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('recipient_id', $user->id)
                    ->orWhere('recipient_role', $user->user_type);
            })->where(function ($scope) {
                $scope->whereNull('booking_id')
                    ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->myBooking());
            });
        }

        return $query;
    }

    private function visibleDocumentVaultQuery()
    {
        $user = auth()->user();
        $roles = $this->roleAliases($user);
        $query = SanadDocumentVaultItem::query();

        if (!$this->isAdmin()) {
            $query->where(function ($q) use ($user, $roles) {
                $q->where('owner_id', $user->id)
                    ->orWhere('uploaded_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($roles) {
                        foreach ($roles as $index => $role) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $visibilityQuery->{$method}(function ($roleQuery) use ($role) {
                                $this->whereJsonArrayContains($roleQuery, 'visible_to', $role);
                            });
                        }
                    });
            });
        }

        return $query;
    }

    public function documentVault(Request $request)
    {
        $query = $this->visibleDocumentVaultQuery()->latest();

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->verification_status);
        }

        return comman_custom_response(['data' => $query->paginate($request->per_page ?: 15)]);
    }

    public function storeDocumentVault(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|max:255',
            'booking_id' => 'nullable|integer',
            'owner_id' => 'nullable|integer',
            'provider_id' => 'nullable|exists:users,id',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string|in:admin,demo_admin,employee,handyman,provider,user,customer',
            'file_name' => 'nullable|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'retention_until' => 'nullable|date',
        ]);

        $booking = null;
        if ($request->filled('booking_id')) {
            $booking = Booking::myBooking()->findOrFail($request->booking_id);
        }

        $user = auth()->user();
        $ownerId = optional($booking)->customer_id ?: optional($user)->id;
        if ($user && $this->isPartner($user)) {
            $ownerId = $user->id;
            if ($request->filled('provider_id') && (int) $request->provider_id !== $user->id) abort(403);
        }
        if ($user && $user->user_type === 'handyman' && $request->filled('provider_id')) {
            abort_unless((int) $request->provider_id === (int) $user->provider_id, 403);
        }
        if ($user && in_array($user->user_type, ['user', 'customer'], true) && $request->filled('provider_id')) {
            abort(403);
        }
        $item = SanadDocumentVaultItem::create([
            'booking_id' => $request->booking_id,
            'service_id' => optional($booking)->service_id,
            'provider_id' => $request->provider_id ?: optional($booking)->provider_id,
            'owner_id' => $ownerId,
            'uploaded_by' => optional(auth()->user())->id,
            'document_type' => $request->document_type,
            'visible_to' => $request->visible_to ?: ['admin'],
            'file_name' => $request->file_name,
            'file_path' => $request->file_path,
            'retention_until' => $request->retention_until ?: now()->addHours(48),
        ]);

        $this->audit($request, 'sanad.document.created', $item);

        return comman_custom_response(['data' => $item]);
    }

    public function verifyDocumentVaultItem(Request $request, $id)
    {
        if (!$this->isAdmin()) {
            return comman_message_response('Only admins can verify Quick government documents.', 403);
        }

        $request->validate([
            'verification_status' => 'required|string|in:approved,rejected,pending',
        ]);

        $item = SanadDocumentVaultItem::findOrFail($id);
        $previousStatus = $item->verification_status;
        $item->verification_status = $request->verification_status;
        $item->approved_at = $request->verification_status === 'approved' ? now() : null;
        $item->approved_by = $request->verification_status === 'approved' ? optional(auth()->user())->id : null;
        $item->save();

        $this->audit($request, 'sanad.document.verification_updated', $item, [
            'previous_status' => $previousStatus,
            'current_status' => $item->verification_status,
        ]);

        return comman_custom_response(['data' => $item]);
    }

    public function chatThreads(Request $request)
    {
        $user = auth()->user();
        $query = SanadChatThread::with('messages')->latest();

        if (!$this->isAdmin()) {
            $roles = $this->roleAliases($user);
            $query->where(function ($q) use ($user, $roles) {
                $q->where('created_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($roles) {
                        foreach ($roles as $index => $role) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $visibilityQuery->{$method}(function ($roleQuery) use ($role) {
                                $this->whereJsonArrayContains($roleQuery, 'participant_roles', $role);
                            });
                        }
                    });
            })->where(function ($scope) {
                $scope->whereNull('booking_id')
                    ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->myBooking());
            });
        }

        if ($request->filled('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        return comman_custom_response(['data' => $query->paginate($request->per_page ?: 15)]);
    }

    public function storeChatMessage(Request $request)
    {
        $request->validate([
            'thread_id' => 'nullable|integer',
            'booking_id' => 'nullable|integer',
            'message' => 'required|string|max:4000',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string|in:admin,demo_admin,employee,handyman,provider,user,customer',
        ]);

        if ($request->filled('thread_id')) {
            $thread = $this->visibleChatThreadQuery()->findOrFail($request->thread_id);
        } else {
            $booking = $request->filled('booking_id')
                ? Booking::myBooking()->findOrFail($request->booking_id)
                : null;
            if (!$booking && !$this->isAdmin()) abort(403);
            $thread = SanadChatThread::create([
                'booking_id' => optional($booking)->id,
                'participant_roles' => $this->allowedVisibility($request->visible_to),
                'created_by' => optional(auth()->user())->id,
            ]);
        }

        $messageVisibility = array_values(array_intersect(
            $this->allowedVisibility($request->visible_to),
            $thread->participant_roles ?: config('sanad.document_visibility')
        ));
        if (empty($messageVisibility)) {
            $messageVisibility = $thread->participant_roles ?: config('sanad.document_visibility');
        }

        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => optional(auth()->user())->id,
            'sender_role' => optional(auth()->user())->user_type,
            'message' => $request->message,
            'visible_to' => $messageVisibility,
        ]);

        $thread->update(['last_message_at' => now()]);

        $this->audit($request, 'sanad.chat.message_created', $message);

        return comman_custom_response(['data' => $message->load('thread')]);
    }

    public function aiAsk(Request $request, SanadAiRagService $rag)
    {
        $request->validate([
            'question' => 'required|string',
            'booking_id' => 'nullable|integer',
        ]);

        $booking = $request->booking_id ? Booking::myBooking()->find($request->booking_id) : null;
        $answer = $rag->answer($request->question, $booking, optional(auth()->user())->user_type ?: 'user');
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

        return comman_custom_response(['data' => $interaction]);
    }

    public function storeAiKnowledge(Request $request, SanadAiRagService $rag)
    {
        if (!$this->isAdmin()) {
            return comman_custom_response(['message' => 'Only admins can manage Quick AI knowledge.'], 403);
        }

        $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'category' => 'nullable|string',
            'visible_to' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $item = SanadAiKnowledgeItem::create([
            'title' => $request->title,
            'category' => $request->category,
            'content' => $request->content,
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'created_by' => optional(auth()->user())->id,
        ]);

        $rag->indexKnowledgeItem($item);

        $this->audit($request, 'sanad.ai.knowledge_created', $item);

        return comman_custom_response(['data' => $item]);
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

    private function whereJsonArrayContains($query, $column, $value)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->where($column, 'like', '%"' . $value . '"%');
        }

        return $query->whereJsonContains($column, $value);
    }

    private function isAdmin(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'demo_admin']);
    }

    private function isOperationsUser(): bool
    {
        $user = auth()->user();

        return $this->isAdmin()
            || $user->hasRole('provider')
            || in_array($user->user_type, ['provider', 'handyman', 'employee'], true);
    }

    private function isPartner($user): bool
    {
        return $user->hasAnyRole(['provider', 'partner'])
            || in_array($user->user_type, ['provider', 'partner'], true);
    }

    private function authorizeWorkflowMutation(Booking $booking): void
    {
        if ($this->isAdmin()) return;

        $user = auth()->user();
        if ($this->isPartner($user)) {
            abort_unless((int) $booking->provider_id === (int) $user->id, 403);
            return;
        }

        if (in_array($user->user_type, ['handyman', 'employee'], true)) {
            abort_unless(
                $user->hasSanadModulePermission('stage_progress', 'write')
                || $user->hasSanadModulePermission('my_tasks', 'write')
                || $user->hasSanadModulePermission('orders', 'write'),
                403
            );
            return;
        }

        abort(403);
    }

    private function authorizeBuzzMutation(Booking $booking): void
    {
        if ($this->isAdmin()) return;

        $user = auth()->user();
        if ($this->isPartner($user)) {
            abort_unless((int) $booking->provider_id === (int) $user->id, 403);
            return;
        }

        if (in_array($user->user_type, ['handyman', 'employee'], true)) {
            abort_unless(
                $user->hasSanadModulePermission('buzz_customer', 'write')
                || $user->hasSanadModulePermission('customer_chat', 'write'),
                403
            );
            return;
        }

        abort(403);
    }

    private function visibleChatThreadQuery()
    {
        $user = auth()->user();
        $query = SanadChatThread::query();

        if ($this->isAdmin()) return $query;

        $roles = $this->roleAliases($user);
        return $query->where(function ($scope) {
            $scope->whereNull('booking_id')
                ->orWhereHas('booking', fn ($bookingQuery) => $bookingQuery->myBooking());
        })->where(function ($visibility) use ($user, $roles) {
            $visibility->where('created_by', $user->id)
                ->orWhere(function ($roleVisibility) use ($roles) {
                    foreach ($roles as $index => $role) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $roleVisibility->{$method}(function ($roleQuery) use ($role) {
                            $this->whereJsonArrayContains($roleQuery, 'participant_roles', $role);
                        });
                    }
                });
        });
    }

    private function roleAliases($user): array
    {
        return match ($user->user_type) {
            'user', 'customer' => ['user', 'customer'],
            'handyman', 'employee' => ['handyman', 'employee'],
            'provider', 'partner' => ['provider', 'partner'],
            default => array_values(array_unique([$user->user_type])),
        };
    }

    private function allowedVisibility(?array $roles): array
    {
        $allowed = ['admin', 'demo_admin', 'employee', 'handyman', 'provider', 'user', 'customer'];
        $roles = $roles ?: config('sanad.document_visibility', $allowed);

        return array_values(array_unique(array_intersect($allowed, $roles)));
    }
}
