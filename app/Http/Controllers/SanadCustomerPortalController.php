<?php

namespace App\Http\Controllers;

use App\Events\SanadConversationUpdated;
use App\Models\Booking;
use App\Models\BookingActivity;
use App\Models\BookingRating;
use App\Models\Category;
use App\Models\Payment;
use App\Models\SanadAiInteraction;
use App\Models\SanadAiKnowledgeItem;
use App\Models\SanadAuditLog;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatMessage;
use App\Models\SanadChatThread;
use App\Models\SanadCustomerComplaint;
use App\Models\SanadDocumentRequest;
use App\Models\SanadDocumentVaultItem;
use App\Models\Service;
use App\Models\User;
use App\Services\SanadAiRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SanadCustomerPortalController extends Controller
{
    public function dashboard()
    {
        $user = $this->customer();
        $requests = $this->customerRequests($user)->with(['service', 'payment', 'sanadDocuments', 'sanadBuzzAlerts', 'handymanAdded.handyman'])->latest('updated_at')->get();
        $activeStages = ['submitted', 'pending_review', 'assigned_to_partner', 'assigned_to_employee', 'in_progress', 'awaiting_customer_action', 'awaiting_quality_review', 'escalated'];
        $pendingActions = $this->pendingCustomerActions($user)->take(8)->get();
        $activities = $this->recentActivity($user)->take(8)->get();

        return view('customer-portal.dashboard', [
            'user' => $user,
            'activeRequests' => $requests->filter(fn ($request) => in_array($request->sanad_stage, $activeStages, true)),
            'completedRequests' => $requests->filter(fn ($request) => in_array($request->sanad_stage ?: $request->status, ['completed', 'closed'], true))->count(),
            'pendingActions' => $pendingActions,
            'activities' => $activities,
            'stats' => [
                'active' => $requests->filter(fn ($request) => in_array($request->sanad_stage, $activeStages, true))->count(),
                'completed' => $requests->filter(fn ($request) => in_array($request->sanad_stage ?: $request->status, ['completed', 'closed'], true))->count(),
                'pending_actions' => $pendingActions->count(),
                'latest_activity' => optional($activities->first())->created_at,
            ],
        ]);
    }

    public function catalog(Request $request)
    {
        $categories = Category::where('status', 1)->orderBy('display_order')->orderBy('name')->get();
        $services = Service::with(['category', 'subcategory'])
            ->where('status', 1)
            ->when($request->category_id, fn ($query) => $query->where('category_id', $request->category_id))
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%")
                        ->orWhere('name_ar', 'like', "%{$search}%")
                        ->orWhere('government_entity', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('customer-portal.catalog', compact('categories', 'services'));
    }

    public function service(Service $service)
    {
        $service->load(['category', 'subcategory', 'serviceAddon']);
        $relatedServices = Service::where('status', 1)
            ->where('id', '!=', $service->id)
            ->where('category_id', $service->category_id)
            ->take(4)
            ->get();

        return view('customer-portal.service-show', compact('service', 'relatedServices'));
    }

    public function createRequest(Request $request)
    {
        $service = $request->service_id ? Service::where('status', 1)->findOrFail($request->service_id) : null;
        $services = Service::where('status', 1)->orderBy('name')->get();
        $vaultDocuments = SanadDocumentVaultItem::where('owner_id', Auth::id())
            ->whereNull('booking_id')
            ->where('source', 'vault')
            ->latest()
            ->get();

        return view('customer-portal.request-create', compact('service', 'services', 'vaultDocuments'));
    }

    public function storeRequest(Request $request)
    {
        $user = $this->customer();
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'description' => ['nullable', 'string', 'max:4000'],
            'required_documents.*' => ['nullable', 'file', 'max:10240'],
            'vault_document_ids' => ['nullable', 'array'],
            'vault_document_ids.*' => ['integer'],
        ]);

        $service = Service::findOrFail($data['service_id']);
        $booking = Booking::create([
            'customer_id' => $user->id,
            'service_id' => $service->id,
            'provider_id' => null,
            'date' => now()->toDateString(),
            'amount' => (float) ($service->service_fee ?? $service->price ?? 0),
            'total_amount' => (float) (($service->service_fee ?? $service->price ?? 0) + ($service->government_fee ?? 0)),
            'description' => $data['description'] ?? null,
            'status' => 'pending',
            'sanad_reference' => $this->nextReference(),
            'sanad_stage' => 'submitted',
            'sanad_priority' => 'normal',
            'expected_completion_at' => $this->expectedCompletion($service),
            'sla_due_at' => $this->expectedCompletion($service),
        ]);
        Payment::create([
            'customer_id' => $user->id,
            'booking_id' => $booking->id,
            'datetime' => now(),
            'discount' => 0,
            'total_amount' => $booking->total_amount,
            'payment_type' => 'pending',
            'payment_status' => 'pending',
        ]);

        foreach ($request->file('required_documents', []) as $key => $file) {
            if (!$file) {
                continue;
            }
            $document = SanadDocumentVaultItem::create([
                'booking_id' => $booking->id,
                'service_id' => $service->id,
                'owner_id' => $user->id,
                'uploaded_by' => $user->id,
                'document_type' => Str::headline($key),
                'document_key' => $key,
                'required' => true,
                'source' => 'request',
                'verification_status' => 'pending',
                'visible_to' => ['user', 'customer', 'admin', 'employee'],
                'file_name' => $file->getClientOriginalName(),
            ]);
            $document->addMedia($file)->toMediaCollection('sanad_document');
        }

        foreach ($request->input('vault_document_ids', []) as $vaultId) {
            $vault = SanadDocumentVaultItem::where('owner_id', $user->id)->where('source', 'vault')->find($vaultId);
            if (!$vault) {
                continue;
            }
            SanadDocumentVaultItem::create([
                'booking_id' => $booking->id,
                'service_id' => $service->id,
                'owner_id' => $user->id,
                'uploaded_by' => $user->id,
                'document_type' => $vault->document_type,
                'document_key' => $vault->document_key,
                'required' => false,
                'source' => 'request',
                'verification_status' => 'pending',
                'visible_to' => ['user', 'customer', 'admin', 'employee'],
                'file_name' => $vault->file_name,
            ]);
        }

        $this->audit('customer_request_submitted', $booking, ['service_id' => $service->id]);

        return redirect()->route('customer-portal.requests.show', $booking->id)->withSuccess('Request submitted successfully.');
    }

    public function requests(Request $request)
    {
        $query = $this->customerRequests($this->customer())->with(['service', 'payment'])
            ->when($request->status, fn ($q) => $q->where('sanad_stage', $request->status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('sanad_reference', 'like', "%{$search}%")
                        ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"));
                });
            });

        return view('customer-portal.requests-index', [
            'requests' => $query->latest('updated_at')->paginate(15)->withQueryString(),
        ]);
    }

    public function showRequest($id)
    {
        $booking = $this->customerRequests($this->customer())
            ->with(['service.category', 'service.subcategory', 'payment', 'sanadDocuments', 'sanadDocumentRequests.document', 'sanadBuzzAlerts', 'sanadRequestActions.actor', 'handymanAdded.handyman'])
            ->findOrFail($id);
        $thread = $this->customerThread($booking);
        $complaints = SanadCustomerComplaint::where('booking_id', $booking->id)->latest()->get();
        $vaultDocuments = SanadDocumentVaultItem::where('owner_id', Auth::id())->where('source', 'vault')->latest()->get();

        return view('customer-portal.request-show', compact('booking', 'thread', 'complaints', 'vaultDocuments'));
    }

    public function uploadRequestDocument(Request $request, $id)
    {
        $booking = $this->customerRequests($this->customer())->with('service')->findOrFail($id);
        $data = $request->validate([
            'document_key' => ['nullable', 'string', 'max:120'],
            'document_type' => ['required', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $document = SanadDocumentVaultItem::create([
            'booking_id' => $booking->id,
            'service_id' => $booking->service_id,
            'owner_id' => Auth::id(),
            'uploaded_by' => Auth::id(),
            'document_type' => $data['document_type'],
            'document_key' => $data['document_key'] ?: Str::slug($data['document_type'], '_'),
            'required' => false,
            'source' => 'request',
            'verification_status' => 'pending',
            'visible_to' => ['user', 'customer', 'admin', 'employee'],
            'file_name' => $request->file('file')->getClientOriginalName(),
        ]);
        $document->addMedia($request->file('file'))->toMediaCollection('sanad_document');
        $this->audit('customer_request_document_uploaded', $document, ['booking_id' => $booking->id]);

        return back()->withSuccess('Document uploaded for review.');
    }

    public function uploadDocumentRequest(Request $request, $id, $documentRequestId)
    {
        $booking = $this->customerRequests($this->customer())->findOrFail($id);
        $documentRequest = SanadDocumentRequest::where('booking_id', $booking->id)
            ->where('requested_from', 'customer')
            ->findOrFail($documentRequestId);

        $request->validate([
            'file' => ['required_without:vault_document_id', 'nullable', 'file', 'max:10240'],
            'vault_document_id' => ['required_without:file', 'nullable', 'integer'],
        ]);

        if ($request->filled('vault_document_id')) {
            $vault = SanadDocumentVaultItem::where('owner_id', Auth::id())->findOrFail($request->vault_document_id);
            $document = SanadDocumentVaultItem::create([
                'booking_id' => $booking->id,
                'service_id' => $booking->service_id,
                'owner_id' => Auth::id(),
                'uploaded_by' => Auth::id(),
                'document_type' => $documentRequest->document_name,
                'document_key' => $documentRequest->document_key,
                'required' => $documentRequest->required,
                'source' => 'request',
                'verification_status' => 'pending',
                'visible_to' => ['user', 'customer', 'admin', 'employee'],
                'file_name' => $vault->file_name,
            ]);
            $media = $vault->getFirstMedia('sanad_document');
            if ($media && file_exists($media->getPath())) {
                $document->addMedia($media->getPath())->preservingOriginal()->toMediaCollection('sanad_document');
            }
        } else {
            $document = SanadDocumentVaultItem::create([
                'booking_id' => $booking->id,
                'service_id' => $booking->service_id,
                'owner_id' => Auth::id(),
                'uploaded_by' => Auth::id(),
                'document_type' => $documentRequest->document_name,
                'document_key' => $documentRequest->document_key,
                'required' => $documentRequest->required,
                'source' => 'request',
                'verification_status' => 'pending',
                'visible_to' => ['user', 'customer', 'admin', 'employee'],
                'file_name' => $request->file('file')->getClientOriginalName(),
            ]);
            $document->addMedia($request->file('file'))->toMediaCollection('sanad_document');
        }

        $documentRequest->update(['status' => 'submitted', 'document_id' => $document->id]);
        $this->audit('customer_document_request_submitted', $documentRequest, ['document_id' => $document->id]);

        return back()->withSuccess('Requested document submitted.');
    }

    public function vault()
    {
        $documents = SanadDocumentVaultItem::where('owner_id', Auth::id())
            ->whereNull('booking_id')
            ->where('source', 'vault')
            ->latest()
            ->get();

        return view('customer-portal.vault', compact('documents'));
    }

    public function storeVaultDocument(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', 'string', 'max:190'],
            'file' => ['required', 'file', 'max:10240'],
        ]);

        $document = SanadDocumentVaultItem::create([
            'owner_id' => Auth::id(),
            'uploaded_by' => Auth::id(),
            'document_type' => $data['document_type'],
            'document_key' => Str::slug($data['document_type'], '_'),
            'source' => 'vault',
            'verification_status' => 'stored',
            'visible_to' => ['user', 'customer'],
            'file_name' => $request->file('file')->getClientOriginalName(),
        ]);
        $document->addMedia($request->file('file'))->toMediaCollection('sanad_document');
        $this->audit('customer_vault_document_uploaded', $document);

        return back()->withSuccess('Vault document saved.');
    }

    public function deleteVaultDocument($id)
    {
        $document = SanadDocumentVaultItem::where('owner_id', Auth::id())->where('source', 'vault')->findOrFail($id);
        $document->delete();
        $this->audit('customer_vault_document_deleted', $document);

        return back()->withSuccess('Vault document deleted.');
    }

    public function messages(Request $request)
    {
        $user = $this->customer();
        $bookingIds = $this->customerRequests($user)->pluck('id');

        $query = Booking::whereIn('id', $bookingIds)->with([
            'customer',
            'service',
            'provider',
            'sanadChatThreads.messages.sender',
            'sanadBuzzAlerts.replies.sender',
            'sanadDocumentRequests.document',
        ]);

        if ($request->action_state === 'open_chat') {
            $query->whereHas('sanadChatThreads', fn ($chatQuery) => $chatQuery->where('status', 'open'));
        }
        if ($request->action_state === 'unread_buzz') {
            $query->whereHas('sanadBuzzAlerts', fn ($buzzQuery) => $buzzQuery->where('status', 'unread'));
        }
        if ($request->action_state === 'pending_documents') {
            $query->whereHas('sanadDocumentRequests', fn ($docQuery) => $docQuery->whereIn('status', ['pending', 'submitted', 'replacement_requested']));
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('sanad_reference', 'like', "%{$search}%")
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"));
            });
        }

        $conversations = $query->latest('updated_at')->paginate(20)->withQueryString();

        $selectedBooking = null;
        if ($request->filled('booking_id')) {
            $selectedBooking = Booking::whereIn('id', $bookingIds)->with([
                'customer',
                'service',
                'provider',
                'sanadDocumentRequests.document',
                'sanadBuzzAlerts.replies.sender',
            ])->find($request->booking_id);
        }
        $selectedBooking = $selectedBooking ?: $conversations->first();

        $thread = $selectedBooking ? $this->customerThread($selectedBooking) : null;
        $messages = $thread
            ? $thread->messages()->with(['sender', 'buzzAlert.replies.sender', 'documentRequest.document'])->get()
            : collect();
        $visibleMessages = $messages->reject(fn ($message) => in_array($message->message_type, ['buzz', 'document_request'], true) || $message->buzz_alert_id || $message->document_request_id);
        $buzzAlerts = $selectedBooking ? $selectedBooking->sanadBuzzAlerts()->with('replies.sender')->latest()->get() : collect();
        $documentRequests = $selectedBooking ? $selectedBooking->sanadDocumentRequests()->with('document')->latest()->get() : collect();
        $vaultDocuments = SanadDocumentVaultItem::where('owner_id', Auth::id())->where('source', 'vault')->latest()->get();

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
            'pageTitle' => 'Sanad Messages & Chat Workspace',
            'auth_user' => Auth::user(),
            'conversations' => $conversations,
            'selectedBooking' => $selectedBooking,
            'thread' => $thread,
            'messages' => $visibleMessages,
            'buzzAlerts' => $buzzAlerts,
            'documentRequests' => $documentRequests,
            'timeline' => $timeline,
            'vaultDocuments' => $vaultDocuments,
            'aiEscalations' => collect(),
            'reviewExamples' => collect(),
            'isAdmin' => false,
            'canCreateBuzz' => false,
            'canRequestDocuments' => false,
            'highlightBuzzId' => $request->filled('buzz_id') ? (int) $request->input('buzz_id') : null,
            'isCustomerPortal' => true,
        ]);
    }

    public function sendMessage(Request $request, $id)
    {
        $booking = $this->customerRequests($this->customer())->findOrFail($id);
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'max:10240'],
            'vault_document_id' => ['nullable', 'integer'],
            'buzz_alert_id' => ['nullable', 'integer'],
        ]);

        if (blank($data['message'] ?? null) && !$request->hasFile('attachment') && !$request->filled('vault_document_id')) {
            return back()->withErrors(['message' => 'Message, attachment, or vault document is required.']);
        }

        $thread = $this->customerThread($booking);
        $buzz = null;
        if ($request->filled('buzz_alert_id')) {
            $buzz = SanadBuzzAlert::where('booking_id', $booking->id)
                ->where(function ($query) {
                    $query->where('recipient_id', Auth::id())
                        ->orWhereIn('recipient_role', ['user', 'customer']);
                })
                ->findOrFail($request->buzz_alert_id);
        }
        $isAttachment = $request->hasFile('attachment') || $request->filled('vault_document_id');
        $message = SanadChatMessage::create([
            'thread_id' => $thread->id,
            'sender_id' => Auth::id(),
            'sender_role' => 'user',
            'message' => $this->filterContactInformation($data['message'] ?? ''),
            'message_type' => $buzz ? 'buzz_reply' : ($isAttachment ? 'attachment' : 'text'),
            'buzz_alert_id' => optional($buzz)->id,
            'visible_to' => ['user', 'customer', 'admin', 'employee'],
        ]);
        if ($request->hasFile('attachment')) {
            $message->addMedia($request->file('attachment'))->toMediaCollection('sanad_chat_attachment');
        } elseif ($request->filled('vault_document_id')) {
            $vault = SanadDocumentVaultItem::where('owner_id', Auth::id())->find($request->vault_document_id);
            if ($vault) {
                $media = $vault->getFirstMedia('sanad_document');
                if ($media && file_exists($media->getPath())) {
                    $message->addMedia($media->getPath())->preservingOriginal()->toMediaCollection('sanad_chat_attachment');
                }
            }
        }
        $thread->update(['last_message_at' => now()]);
        if ($buzz) {
            $buzz->increment('reply_count');
            $buzz->forceFill(['last_reply_at' => now()])->save();
        }
        $this->audit('customer_message_sent', $message, ['booking_id' => $booking->id]);
        $this->broadcastConversationUpdate($booking->id, $buzz ? 'buzz.reply_created' : 'chat.message_created', [
            'message_id' => $message->id,
            'buzz_alert_id' => optional($buzz)->id,
        ]);

        return back()->withSuccess('Message sent.');
    }

    public function replyToBuzz(Request $request, $id, $buzzId)
    {
        $request->merge(['buzz_alert_id' => $buzzId]);

        return $this->sendMessage($request, $id);
    }

    private function broadcastConversationUpdate(int $bookingId, string $type, array $payload = []): void
    {
        try {
            broadcast(new SanadConversationUpdated($bookingId, $type, $payload))->toOthers();
        } catch (\Throwable $exception) {
            \Log::warning('Sanad customer conversation broadcast failed', [
                'booking_id' => $bookingId,
                'type' => $type,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function notifications()
    {
        return view('customer-portal.notifications', [
            'notifications' => Auth::user()->notifications()->latest()->paginate(20),
            'buzzAlerts' => SanadBuzzAlert::where(function ($query) {
                $query->where('recipient_id', Auth::id())
                    ->orWhereIn('recipient_role', ['user', 'customer']);
            })->latest()->paginate(10),
        ]);
    }

    public function billing()
    {
        $requests = $this->customerRequests($this->customer())->with(['service', 'payment'])->latest()->paginate(15);
        $payments = Payment::whereIn('booking_id', $this->customerRequests($this->customer())->pluck('id'))->latest()->get();

        return view('customer-portal.billing', compact('requests', 'payments'));
    }

    public function support()
    {
        $requests = $this->complaintEligibleRequests($this->customer())->with(['service', 'provider'])->latest()->get();
        $complaints = SanadCustomerComplaint::where('customer_id', Auth::id())->with(['booking.service', 'booking.provider'])->latest()->paginate(12);
        $complaintTypes = $this->complaintTypes();

        return view('customer-portal.support', compact('requests', 'complaints', 'complaintTypes'));
    }

    public function storeComplaint(Request $request)
    {
        $data = $request->validate([
            'booking_id' => ['required', 'integer'],
            'complaint_type' => ['required', 'string', 'in:' . implode(',', array_keys($this->complaintTypes()))],
            'description' => ['required', 'string', 'max:4000'],
            'priority' => ['required', 'in:normal,high,urgent'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);
        $booking = $this->complaintEligibleRequests($this->customer())->findOrFail($data['booking_id']);

        $complaint = SanadCustomerComplaint::create($data + [
            'booking_id' => $booking->id,
            'customer_id' => Auth::id(),
            'status' => 'open',
        ]);
        if ($request->hasFile('attachment')) {
            $complaint->addMedia($request->file('attachment'))->toMediaCollection('sanad_complaint_attachment');
        }
        $this->audit('customer_complaint_created', $complaint, ['booking_id' => $booking->id]);

        return back()->withSuccess('Complaint submitted.');
    }

    private function complaintEligibleRequests(User $user)
    {
        return $this->customerRequests($user)->where(function ($query) {
            $query->whereIn('sanad_stage', [
                'submitted',
                'pending_review',
                'assigned_to_partner',
                'assigned_to_employee',
                'in_progress',
                'awaiting_customer_action',
                'awaiting_quality_review',
                'escalated',
                'completed',
                'closed',
            ])->orWhereIn('status', ['pending', 'accept', 'in_progress', 'completed']);
        });
    }

    private function complaintTypes(): array
    {
        return [
            'document_issue' => 'Document Issue',
            'payment_billing' => 'Payment / Billing',
            'request_delay' => 'Request Delay',
            'status_update' => 'Status Update',
            'service_quality' => 'Service Quality',
            'communication_issue' => 'Communication Issue',
            'incorrect_information' => 'Incorrect Information',
            'other' => 'Other',
        ];
    }

    public function ai()
    {
        $requests = $this->customerRequests($this->customer())->with('service')->latest()->get();
        $interactions = SanadAiInteraction::where('user_id', Auth::id())->latest()->take(20)->get();

        return view('customer-portal.ai', compact('requests', 'interactions'));
    }

    public function askAi(Request $request, SanadAiRagService $rag)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'booking_id' => ['nullable', 'integer'],
        ]);
        $booking = null;
        if (!empty($data['booking_id'])) {
            $booking = $this->customerRequests($this->customer())->with('service')->findOrFail($data['booking_id']);
        }

        $answer = $rag->answer($data['question'], $booking, 'customer');
        $interaction = SanadAiInteraction::create([
            'user_id' => Auth::id(),
            'booking_id' => optional($booking)->id,
            'question' => $data['question'],
            'answer' => $answer['answer'],
            'confidence' => $answer['confidence'],
            'requires_escalation' => $answer['requires_escalation'],
            'status' => $answer['requires_escalation'] ? 'handover_required' : 'answered',
            'metadata' => [
                'sources' => $answer['sources'],
                'live_context' => $answer['live_context'],
                'provider' => $answer['provider_metadata'] ?? [],
                'langsmith_run_id' => $answer['langsmith_run_id'] ?? null,
            ],
        ]);
        $this->audit('customer_ai_question_asked', $interaction, ['booking_id' => optional($booking)->id]);

        return back()->withSuccess('Sanad AI response generated.');
    }

    public function profile()
    {
        return view('customer-portal.profile', ['user' => $this->customer()]);
    }

    private function customer()
    {
        $user = Auth::user();
        abort_unless($user && in_array($user->user_type, ['user', 'customer'], true), 403);

        return $user;
    }

    private function customerRequests(User $user)
    {
        return Booking::where('customer_id', $user->id);
    }

    private function pendingCustomerActions(User $user)
    {
        return Booking::where('customer_id', $user->id)
            ->with(['service', 'sanadDocumentRequests'])
            ->where(function ($query) {
                $query->where('sanad_stage', 'awaiting_customer_action')
                    ->orWhereHas('sanadDocumentRequests', fn ($q) => $q->where('requested_from', 'customer')->whereIn('status', ['pending', 'replacement_requested', 'rejected']))
                    ->orWhereHas('sanadBuzzAlerts', fn ($q) => $q->where('status', 'unread'));
            })
            ->latest('updated_at');
    }

    private function recentActivity(User $user)
    {
        return BookingActivity::whereIn('booking_id', Booking::where('customer_id', $user->id)->pluck('id'))
            ->latest();
    }

    private function customerThread(Booking $booking)
    {
        return SanadChatThread::firstOrCreate([
            'booking_id' => $booking->id,
            'thread_type' => 'shared',
        ], [
            'participant_roles' => ['user', 'customer', 'admin', 'employee', 'provider'],
            'created_by' => Auth::id(),
            'status' => 'open',
            'last_message_at' => now(),
        ]);
    }

    private function nextReference(): string
    {
        $next = (int) Booking::max('id') + 1;
        return 'SANAD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function expectedCompletion(Service $service)
    {
        preg_match('/\d+/', (string) $service->estimated_completion_time, $matches);
        $days = isset($matches[0]) ? max(1, (int) $matches[0]) : 3;
        return now()->addDays($days);
    }

    private function filterContactInformation(string $message): string
    {
        $patterns = [
            '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
            '/(?:https?:\/\/|www\.)\S+/i',
            '/(?:(?:\+|00)\d{1,3}[\s.-]?)?(?:\(?\d{2,4}\)?[\s.-]?){2,}\d{3,}/',
            '/(?:instagram|facebook|twitter|x\.com|linkedin|snapchat|tiktok|telegram|whatsapp)\S*/i',
        ];

        return trim(preg_replace($patterns, '[removed contact information]', $message));
    }

    private function aiAnswer(string $question, ?Booking $booking, string $knowledge): array
    {
        $context = $booking
            ? 'Your request ' . ($booking->sanad_reference ?: '#' . $booking->id) . ' is currently at ' . Str::headline($booking->sanad_stage ?: $booking->status) . '. The next expected step is managed by the Sanad team.'
            : 'I can help you choose a Sanad service, understand requirements, processing time, pricing, and next steps.';
        $knowledgeHint = $knowledge ? ' I found related Sanad knowledge and request guidance for this topic.' : '';
        $requiresEscalation = Str::contains(Str::lower($question), ['complaint', 'urgent', 'delay', 'reject', 'wrong', 'human']);

        return [
            'text' => $context . $knowledgeHint . ($requiresEscalation ? ' I will flag this for the Sanad team because it may need human review.' : ''),
            'confidence' => $requiresEscalation ? 0.62 : 0.82,
            'requires_escalation' => $requiresEscalation,
        ];
    }

    private function audit(string $action, $model, array $metadata = []): void
    {
        if (!Schema::hasTable('sanad_audit_logs')) {
            return;
        }

        SanadAuditLog::create([
            'actor_id' => Auth::id(),
            'actor_role' => optional(Auth::user())->user_type,
            'action' => $action,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id ?? null,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }
}
