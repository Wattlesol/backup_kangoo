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
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanadController extends Controller
{
    public function foundation()
    {
        return comman_custom_response([
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

    public function updateRequestLifecycle(Request $request, $id)
    {
        $request->validate([
            'sanad_stage' => 'required|string',
            'sanad_priority' => 'nullable|string',
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
            'recipient_role' => 'nullable|string',
            'priority' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $alert = SanadBuzzAlert::create([
            'booking_id' => $request->booking_id,
            'sender_id' => optional(auth()->user())->id,
            'recipient_id' => $request->recipient_id,
            'recipient_role' => $request->recipient_role,
            'priority' => $request->priority ?: 'urgent',
            'message' => $request->message,
        ]);

        $this->audit($request, 'sanad.buzz.created', $alert);

        return comman_custom_response(['data' => $alert]);
    }

    public function buzzAlerts(Request $request)
    {
        $user = auth()->user();
        $query = SanadBuzzAlert::with('booking')->latest();

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('recipient_id', $user->id)
                    ->orWhere('recipient_role', $user->user_type);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return comman_custom_response(['data' => $query->paginate($request->per_page ?: 15)]);
    }

    public function acknowledgeBuzz(Request $request, $id)
    {
        $alert = SanadBuzzAlert::findOrFail($id);
        $alert->status = 'acknowledged';
        $alert->acknowledged_at = now();
        $alert->save();

        $this->audit($request, 'sanad.buzz.acknowledged', $alert);

        return comman_custom_response(['data' => $alert]);
    }

    public function documentVault(Request $request)
    {
        $user = auth()->user();
        $role = optional($user)->user_type;

        $query = SanadDocumentVaultItem::query()->latest();
        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user, $role) {
                $q->where('owner_id', $user->id)
                    ->orWhere('uploaded_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($role) {
                        $this->whereJsonArrayContains($visibilityQuery, 'visible_to', $role);
                    });
            });
        }

        return comman_custom_response(['data' => $query->paginate($request->per_page ?: 15)]);
    }

    public function storeDocumentVault(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'booking_id' => 'nullable|integer',
            'owner_id' => 'nullable|integer',
            'visible_to' => 'nullable|array',
            'file_name' => 'nullable|string',
            'file_path' => 'nullable|string',
        ]);

        $item = SanadDocumentVaultItem::create([
            'booking_id' => $request->booking_id,
            'owner_id' => $request->owner_id ?: optional(auth()->user())->id,
            'uploaded_by' => optional(auth()->user())->id,
            'document_type' => $request->document_type,
            'visible_to' => $request->visible_to ?: ['admin'],
            'file_name' => $request->file_name,
            'file_path' => $request->file_path,
        ]);

        $this->audit($request, 'sanad.document.created', $item);

        return comman_custom_response(['data' => $item]);
    }

    public function chatThreads(Request $request)
    {
        $user = auth()->user();
        $query = SanadChatThread::with('messages')->latest();

        if (!$user->hasRole('admin') && !$user->hasRole('demo_admin')) {
            $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhere(function ($visibilityQuery) use ($user) {
                        $this->whereJsonArrayContains($visibilityQuery, 'participant_roles', $user->user_type);
                    });
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
            'message' => 'required|string',
            'visible_to' => 'nullable|array',
        ]);

        $thread = $request->thread_id
            ? SanadChatThread::findOrFail($request->thread_id)
            : SanadChatThread::create([
                'booking_id' => $request->booking_id,
                'participant_roles' => $request->visible_to ?: config('sanad.document_visibility'),
                'created_by' => optional(auth()->user())->id,
            ]);

        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => optional(auth()->user())->id,
            'sender_role' => optional(auth()->user())->user_type,
            'message' => $request->message,
            'visible_to' => $request->visible_to ?: $thread->participant_roles,
        ]);

        $this->audit($request, 'sanad.chat.message_created', $message);

        return comman_custom_response(['data' => $message->load('thread')]);
    }

    public function aiAsk(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'booking_id' => 'nullable|integer',
        ]);

        $answer = $this->answerFromKnowledgeBase($request->question);
        $confidence = $answer ? 0.75 : 0.25;
        $requiresEscalation = $confidence < (float) config('sanad.ai.requires_escalation_when_confidence_below');

        $interaction = SanadAiInteraction::create([
            'user_id' => optional(auth()->user())->id,
            'booking_id' => $request->booking_id,
            'question' => $request->question,
            'answer' => $answer ?: 'Your question has been sent to the Sanad support team for review.',
            'confidence' => $confidence,
            'requires_escalation' => $requiresEscalation,
            'status' => $requiresEscalation ? 'escalated' : 'answered',
        ]);

        $this->audit($request, 'sanad.ai.asked', $interaction);

        return comman_custom_response(['data' => $interaction]);
    }

    public function storeAiKnowledge(Request $request)
    {
        if (!auth()->user()->hasRole('admin') && !auth()->user()->hasRole('demo_admin')) {
            return comman_custom_response(['message' => 'Only admins can manage Sanad AI knowledge.'], 403);
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
}
