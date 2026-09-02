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

    public function showRequest(Request $request, $id)
    {
        $booking = Booking::with([
            'service.category',
            'service.subcategory',
            'payment',
            'sanadDocuments',
            'sanadDocumentRequests.document',
            'sanadBuzzAlerts',
            'sanadRequestActions.actor',
            'customer',
            'provider',
            'handymanAdded.handyman',
        ])
        ->myBooking()
        ->findOrFail($id);

        $stage = $booking->sanad_stage ?: $booking->status ?: 'submitted';
        $progress = in_array($stage, ['completed', 'closed'], true)
            ? 100
            : (['submitted' => 15, 'pending_review' => 25, 'assigned_to_partner' => 40, 'assigned_to_employee' => 55, 'in_progress' => 70, 'awaiting_customer_action' => 65, 'awaiting_quality_review' => 85, 'escalated' => 60][$stage] ?? 20);

        // Required Documents
        $docs = collect(optional($booking->service)->required_documents ?: []);
        $requiredDocuments = $docs->map(function ($doc) use ($booking) {
            $storedName = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc;
            $key = is_array($doc) ? ($doc['key'] ?? Str::slug($storedName, '_')) : Str::slug($storedName, '_');
            $docItem = $booking->sanadDocuments->first(function ($d) use ($storedName, $key) {
                return ($d->document_key && $d->document_key === $key) || ($d->document_type === $storedName);
            });
            $status = 'required';
            if ($docItem) {
                $status = ($docItem->verification_status === 'approved') ? 'verified' : 'submitted';
            }
            return [
                'key' => $key,
                'name' => is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $storedName) : $storedName,
                'status' => $status,
                'required' => is_array($doc) ? (bool)($doc['required'] ?? true) : true,
                'mime_types' => is_array($doc) ? ($doc['mime_types'] ?? []) : [],
            ];
        })->values();

        // Document Choices for uploading
        $approvedDocuments = $booking->sanadDocuments->where('verification_status', 'approved');
        $documentChoices = collect();
        foreach ($docs as $document) {
            $storedName = is_array($document) ? ($document['name'] ?? $document['document_name'] ?? $document['key'] ?? 'Document') : $document;
            $key = is_array($document) ? ($document['key'] ?? Str::slug($storedName, '_')) : Str::slug($storedName, '_');
            $alreadyApproved = $approvedDocuments->contains(function ($approved) use ($key, $storedName) {
                return ($approved->document_key && $approved->document_key === $key) || (!$approved->document_key && $approved->document_type === $storedName);
            });
            if (!$alreadyApproved) {
                $documentChoices->push([
                    'id' => 'service:' . $key,
                    'key' => $key,
                    'name' => $storedName,
                    'label' => is_array($document) ? ($document['name'] ?? $storedName) : $storedName,
                    'required' => true,
                    'document_request_id' => null,
                ]);
            }
        }
        foreach ($booking->sanadDocumentRequests->whereIn('requested_from', ['customer', 'user'])->whereIn('status', ['pending', 'rejected', 'replacement_requested']) as $dr) {
            $documentChoices->push([
                'id' => 'request:' . $dr->id,
                'key' => $dr->document_key ?: Str::slug($dr->document_name, '_'),
                'name' => $dr->document_name,
                'label' => $dr->document_name,
                'required' => (bool)$dr->required,
                'document_request_id' => $dr->id,
            ]);
        }

        // Open Buzz Alerts
        $openBuzzAlerts = $booking->sanadBuzzAlerts
            ->where('status', 'unread')
            ->map(function ($buzz) {
                return [
                    'id' => $buzz->id,
                    'message' => $buzz->message,
                    'severity' => $buzz->severity ?: 'urgent',
                    'created_at' => optional($buzz->created_at)->toIso8601String(),
                ];
            })->values();

        // Verified Documents
        $verifiedDocuments = $booking->sanadDocuments
            ->where('verification_status', 'approved')
            ->sortByDesc('approved_at')
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'document_key' => $doc->document_key,
                    'file_name' => $doc->file_name,
                    'file_url' => $doc->publicDocumentUrl(),
                    'approved_at' => optional($doc->approved_at)->toIso8601String(),
                    'status' => 'verified',
                ];
            })->values();

        // Pending Document Requests
        $pendingDocumentRequests = $booking->sanadDocumentRequests
            ->whereIn('requested_from', ['customer', 'user'])
            ->whereIn('status', ['pending', 'rejected', 'replacement_requested'])
            ->map(function ($dr) {
                return [
                    'id' => $dr->id,
                    'document_name' => $dr->document_name,
                    'reason' => $dr->reason,
                    'instructions' => $dr->instructions,
                    'status' => $dr->status,
                    'due_at' => optional($dr->due_at)->toIso8601String(),
                ];
            })->values();

        // Timeline Actions
        $timeline = $booking->sanadRequestActions
            ->sortBy('created_at')
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'action' => $a->action,
                    'title' => Str::headline($a->action ?? 'Update'),
                    'note' => $a->note ?: ($a->reason ?: 'Request status updated.'),
                    'actor' => optional($a->actor)->display_name ?: 'Quick Support',
                    'created_at' => optional($a->created_at)->toIso8601String(),
                ];
            })->values();

        if ($timeline->isEmpty()) {
            $timeline = collect([[
                'id' => 0,
                'action' => 'request_created',
                'title' => 'Request Created',
                'note' => 'Your request has been submitted.',
                'actor' => 'Quick System',
                'created_at' => optional($booking->created_at)->toIso8601String(),
            ]]);
        }

        // Business Logic for State & Cancellation:
        // Direct cancellation is ONLY allowed if request is still 'pending' / 'submitted' AND not yet accepted.
        $cancellationAllowed = in_array($stage, ['submitted', 'pending'], true) && $booking->status === 'pending';
        $cancellationNotice = $cancellationAllowed
            ? 'You may cancel this request before review begins.'
            : 'This request is already ' . Str::headline($stage) . '. Cancellation requires a conversation with the Quick support team or a formal change request.';

        return comman_custom_response([
            'data' => [
                'id' => $booking->id,
                'sanad_reference' => $booking->sanad_reference ?: ('QUICK-' . str_pad($booking->id, 6, '0', STR_PAD_LEFT)),
                'quick_reference' => $booking->quick_reference,
                'service_id' => $booking->service_id,
                'service_name' => optional($booking->service)->name,
                'service_description' => optional($booking->service)->description,
                'service_price' => optional($booking->service)->price,
                'service_provider' => 'Quick',
                'support_team' => 'Quick team',
                'status' => $booking->status,
                'sanad_stage' => $stage,
                'stage_label' => Str::headline($stage),
                'stage_description' => 'The next expected step is managed by the Quick team.',
                'sanad_priority' => $booking->sanad_priority ?: 'normal',
                'progress' => $progress,
                'sla_due_at' => optional($booking->sla_due_at)->toIso8601String(),
                'expected_completion_at' => optional($booking->expected_completion_at)->toIso8601String(),
                'created_at' => optional($booking->created_at)->toIso8601String(),
                'address' => $booking->address,
                'customer_name' => optional($booking->customer)->display_name ?: (optional($booking->customer)->first_name ? trim(optional($booking->customer)->first_name . ' ' . optional($booking->customer)->last_name) : 'Customer'),
                'customer_email' => optional($booking->customer)->email,
                'customer_contact' => optional($booking->customer)->contact_number,
                'partner_name' => optional($booking->provider)->display_name ?: 'Quick internal',
                'partner_email' => optional($booking->provider)->email,
                'partner_contact' => optional($booking->provider)->contact_number,
                'assigned_employees' => $booking->handymanAdded->map(fn($m) => optional($m->handyman)->display_name)->filter()->values()->all(),
                'cancellation_allowed' => $cancellationAllowed,
                'cancellation_notice' => $cancellationNotice,
                'open_buzz_alerts' => $openBuzzAlerts,
                'timeline' => $timeline,
                'required_documents' => $requiredDocuments,
                'document_choices' => $documentChoices,
                'pending_document_requests' => $pendingDocumentRequests,
                'verified_documents' => $verifiedDocuments,
                'billing' => [
                    'invoice_available' => (bool)$booking->payment,
                    'invoice_url' => $booking->payment ? route('invoice_pdf', $booking->id) : null,
                    'service_fee' => optional($booking->service)->price ?: ($booking->amount ?: 0),
                    'vat' => $booking->final_total_tax ?: 0,
                    'total_amount' => $booking->total_amount ?: ($booking->amount ?: 0),
                    'payment_status' => optional($booking->payment)->payment_status ?: 'pending',
                    'payment_method' => optional($booking->payment)->payment_type ?: 'Not Specified',
                ],
            ]
        ]);
    }

    public function uploadRequestDocument(Request $request, $id)
    {
        $booking = Booking::myBooking()->with('service')->findOrFail($id);
        $request->validate([
            'document_selection' => 'required|string',
            'file' => 'required|file|max:10240',
        ]);

        $selection = $request->document_selection;
        $docKey = $selection;
        $docName = Str::headline($selection);
        $docRequestId = null;

        if (Str::startsWith($selection, 'service:')) {
            $docKey = Str::after($selection, 'service:');
            $docName = Str::headline($docKey);
        } elseif (Str::startsWith($selection, 'request:')) {
            $docRequestId = (int) Str::after($selection, 'request:');
            $dr = SanadDocumentRequest::where('booking_id', $booking->id)->find($docRequestId);
            if ($dr) {
                $docKey = $dr->document_key ?: Str::slug($dr->document_name, '_');
                $docName = $dr->document_name;
            }
        }

        $user = auth()->user();
        $file = $request->file('file');
        $document = SanadDocumentVaultItem::create([
            'booking_id' => $booking->id,
            'service_id' => $booking->service_id,
            'owner_id' => $user->id,
            'uploaded_by' => $user->id,
            'document_type' => $docName,
            'document_key' => $docKey,
            'required' => true,
            'source' => 'request',
            'verification_status' => 'pending',
            'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
            'file_name' => $file->getClientOriginalName(),
        ]);
        $document->addMedia($file)->toMediaCollection('sanad_document');

        if ($docRequestId) {
            SanadDocumentRequest::where('booking_id', $booking->id)
                ->whereKey($docRequestId)
                ->update(['status' => 'submitted', 'document_id' => $document->id]);
        }

        return comman_custom_response([
            'message' => 'Document uploaded for review.',
            'data' => [
                'id' => $document->id,
                'document_type' => $document->document_type,
                'file_name' => $document->file_name,
                'verification_status' => $document->verification_status,
            ]
        ]);
    }

    public function cancelRequest(Request $request, $id)
    {
        $booking = Booking::myBooking()->findOrFail($id);
        $stage = $booking->sanad_stage ?: $booking->status ?: 'submitted';

        // Direct cancellation is ONLY allowed when still pending / submitted
        $cancellationAllowed = in_array($stage, ['submitted', 'pending'], true) && $booking->status === 'pending';

        if (!$cancellationAllowed) {
            return comman_custom_response([
                'status' => false,
                'message' => 'This request has already been accepted and is being processed. Cancellation requires contacting Quick Support.',
                'cancellation_allowed' => false,
            ], 400);
        }

        $booking->status = 'cancelled';
        $booking->sanad_stage = 'cancelled';
        $booking->reason = $request->input('reason', 'Cancelled by customer');
        $booking->closed_at = now();
        $booking->save();

        if (class_exists(\App\Models\SanadRequestAction::class)) {
            \App\Models\SanadRequestAction::create([
                'booking_id' => $booking->id,
                'actor_id' => auth()->id(),
                'action' => 'cancelled',
                'reason' => $booking->reason,
                'note' => 'Request cancelled by customer.',
            ]);
        }

        return comman_custom_response([
            'status' => true,
            'message' => 'Request cancelled successfully.',
            'data' => $booking,
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

    public function partnerPerformance(Request $request)
    {
        $performances = \App\Models\SanadPartnerServicePerformance::with(['provider', 'service'])
            ->when($request->filled('provider_id'), fn ($query) => $query->where('provider_id', $request->provider_id))
            ->when($request->filled('service_id'), fn ($query) => $query->where('service_id', $request->service_id))
            ->orderByDesc('quality_score')
            ->orderByDesc('completed_orders')
            ->paginate($request->per_page ?: 25);

        $performanceRows = collect($performances->items());
        $totalRecords = $performances->total();
        $averageQuality = $totalRecords > 0 ? round((float) $performanceRows->avg('quality_score'), 1) : 0;
        $averageSla = $totalRecords > 0 ? round((float) $performanceRows->avg('sla_compliance_rate'), 1) : 0;
        $averageAcceptance = $totalRecords > 0 ? round((float) $performanceRows->avg('acceptance_rate'), 1) : 0;
        $completedOrders = (int) $performanceRows->sum('completed_orders');

        $performanceProviderIds = \App\Models\SanadPartnerServicePerformance::query()->whereNotNull('provider_id')->distinct()->pluck('provider_id');
        $performanceServiceIds = \App\Models\SanadPartnerServicePerformance::query()->whereNotNull('service_id')->distinct()->pluck('service_id');
        $performancePartners = \App\Models\User::query()->whereIn('id', $performanceProviderIds)->orderBy('display_name')->get(['id', 'display_name', 'first_name', 'last_name']);
        $performanceServices = \App\Models\Service::query()->whereIn('id', $performanceServiceIds)->orderBy('name')->get(['id', 'name']);

        return comman_custom_response([
            'pagination' => [
                'total_items' => $performances->total(),
                'per_page' => $performances->perPage(),
                'currentPage' => $performances->currentPage(),
                'totalPages' => $performances->lastPage(),
            ],
            'kpis' => [
                'average_quality' => $averageQuality,
                'average_sla' => $averageSla,
                'average_acceptance' => $averageAcceptance,
                'completed_orders' => $completedOrders,
            ],
            'partners' => $performancePartners,
            'services' => $performanceServices,
            'data' => $performances->items(),
        ]);
    }
}
