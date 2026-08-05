<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingHandymanMapping;
use App\Models\Payment;
use App\Models\SanadAiInteraction;
use App\Models\SanadAiKnowledgeItem;
use App\Models\SanadAuditLog;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\SanadDocumentVaultItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SanadWebController extends Controller
{
    public function aiConsole(Request $request)
    {
        $user = auth()->user();
        $knowledgeItems = SanadAiKnowledgeItem::latest()->take(10)->get();
        $interactions = SanadAiInteraction::with('user')
            ->when(!$user->hasAnyRole(['admin', 'demo_admin']), function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->take(10)
            ->get();

        return view('sanad.ai-console', [
            'pageTitle' => 'Sanad AI Assistant',
            'auth_user' => authSession(),
            'knowledgeItems' => $knowledgeItems,
            'interactions' => $interactions,
            'aiSummary' => [
                'knowledge_items' => SanadAiKnowledgeItem::count(),
                'active_knowledge_items' => SanadAiKnowledgeItem::where('is_active', true)->count(),
                'interactions' => SanadAiInteraction::when(!$user->hasAnyRole(['admin', 'demo_admin']), function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->count(),
                'escalations' => SanadAiInteraction::when(!$user->hasAnyRole(['admin', 'demo_admin']), function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })->where('requires_escalation', true)->count(),
            ],
        ]);
    }

    public function storeAiKnowledge(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return redirect()->back()->withErrors('Only admins can manage Sanad AI knowledge.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'content' => 'required|string',
            'visible_to' => 'nullable|array',
        ]);

        $item = SanadAiKnowledgeItem::create([
            'title' => $request->title,
            'category' => $request->category,
            'content' => $request->content,
            'visible_to' => $request->visible_to ?: config('sanad.document_visibility'),
            'is_active' => true,
            'created_by' => optional(auth()->user())->id,
        ]);

        $this->audit($request, 'sanad.ai.knowledge_created', $item);

        return redirect()->back()->withSuccess('Sanad AI knowledge item added.');
    }

    public function askAi(Request $request)
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

        return redirect()->back()->withSuccess('Sanad AI response recorded.');
    }

    public function indexRequests(Request $request)
    {
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
        $bookingdata = Booking::with(['customer', 'provider', 'service', 'payment.paymentHistory', 'handymanAdded.handyman'])
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
            'billing'
        ));
    }

    public function updatePaymentStatus(Request $request, $id)
    {
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
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'document_type' => 'required|string|max:255',
            'file_name' => 'nullable|string|max:255',
            'file_path' => 'nullable|string|max:255',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string',
            'retention_until' => 'nullable|date',
        ]);

        $document = SanadDocumentVaultItem::create([
            'booking_id' => $booking->id,
            'owner_id' => $booking->customer_id,
            'uploaded_by' => optional(auth()->user())->id,
            'document_type' => $request->document_type,
            'visible_to' => $request->visible_to ?: ['admin'],
            'file_name' => $request->file_name,
            'file_path' => $request->file_path,
            'retention_until' => $request->retention_until,
        ]);

        $this->audit($request, 'sanad.document.created', $document);

        return redirect()->back()->withSuccess('Sanad document added.');
    }

    public function approveDocument(Request $request, $id, $documentId)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $document = $this->visibleDocumentsQuery($booking)->findOrFail($documentId);

        $document->verification_status = 'approved';
        $document->approved_at = now();
        $document->approved_by = optional(auth()->user())->id;
        $document->save();

        $this->audit($request, 'sanad.document.approved', $document);

        return redirect()->back()->withSuccess('Sanad document approved.');
    }

    public function storeBuzz(Request $request, $id)
    {
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
        $booking = Booking::myBooking()->findOrFail($id);
        $request->validate([
            'message' => 'required|string|max:2000',
            'visible_to' => 'nullable|array',
            'visible_to.*' => 'string',
        ]);

        $visibleTo = $request->visible_to ?: config('sanad.document_visibility');
        $thread = $this->visibleChatThread($booking) ?: SanadChatThread::create([
            'booking_id' => $booking->id,
            'participant_roles' => $visibleTo,
            'created_by' => optional(auth()->user())->id,
        ]);

        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => optional(auth()->user())->id,
            'sender_role' => optional(auth()->user())->user_type,
            'message' => $request->message,
            'visible_to' => $visibleTo,
        ]);

        $this->audit($request, 'sanad.chat.message_created', $message);

        return redirect()->back()->withSuccess('Sanad chat message sent.');
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
