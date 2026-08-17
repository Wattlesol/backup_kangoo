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
use App\Services\SanadDocumentOcrAgent;
use App\Services\SanadAiFirstResponderService;
use App\Services\SanadAiRagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
                'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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
                'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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
            'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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
                'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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
                'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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
        return $this->confirmVaultDocument($request);
    }

    public function analyzeVaultDocument(Request $request, SanadDocumentOcrAgent $ocrAgent)
    {
        $data = $request->validate($this->vaultDocumentRules(true), $this->vaultDocumentMessages());

        $file = $request->file('file');
        $token = (string) Str::uuid();
        $tempPath = $file->storeAs(
            'sanad-vault-temp/' . Auth::id(),
            $token . '.' . strtolower($file->getClientOriginalExtension())
        );

        $analysis = $ocrAgent->analyzeFile(
            $data['document_type'],
            $file->getClientOriginalName(),
            Storage::path($tempPath),
            $file->getMimeType()
        );

        $expiryDate = $analysis['expiry_date'] ?? null;
        $reminderDate = $analysis['expiry_reminder_at'] ?? null;
        $mode = $expiryDate ? 'expiry_detected' : 'manual_reminder';

        Cache::put($this->vaultUploadCacheKey($token), [
            'owner_id' => Auth::id(),
            'document_type' => $data['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'path' => $tempPath,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'analysis' => $analysis,
        ], now()->addMinutes(30));

        return response()->json([
            'status' => true,
            'mode' => $mode,
            'token' => $token,
            'document_type' => $data['document_type'],
            'file_name' => $file->getClientOriginalName(),
            'expiry_date' => $expiryDate,
            'expiry_reminder_at' => $reminderDate,
            'ocr_status' => $analysis['ocr_status'],
            'ocr_confidence' => $analysis['ocr_confidence'],
            'message' => $expiryDate
                ? 'Sanad AI found an expiry date. Please confirm before saving.'
                : ($analysis['message'] ?: 'Sanad AI could not find an expiry date. Please set a follow-up reminder before saving.'),
        ]);
    }

    public function confirmVaultDocument(Request $request)
    {
        $data = $request->validate([
            'upload_token' => ['required', 'string'],
            'expiry_date' => ['nullable', 'date'],
            'expiry_reminder_at' => ['required', 'date'],
            'expiry_reminder_enabled' => ['nullable', 'boolean'],
        ]);

        $cacheKey = $this->vaultUploadCacheKey($data['upload_token']);
        $pendingUpload = Cache::pull($cacheKey);

        if (!$pendingUpload || (int) ($pendingUpload['owner_id'] ?? 0) !== Auth::id()) {
            return $this->vaultConfirmError($request, 'This upload session expired. Please attach the document again.');
        }

        if (empty($pendingUpload['path']) || !Storage::exists($pendingUpload['path'])) {
            return $this->vaultConfirmError($request, 'The temporary upload could not be found. Please attach the document again.');
        }

        $document = SanadDocumentVaultItem::create([
            'owner_id' => Auth::id(),
            'uploaded_by' => Auth::id(),
            'document_type' => $pendingUpload['document_type'],
            'document_key' => Str::slug($pendingUpload['document_type'], '_'),
            'source' => 'vault',
            'verification_status' => 'stored',
            'visible_to' => ['user', 'customer'],
            'file_name' => $pendingUpload['file_name'],
            'ocr_status' => data_get($pendingUpload, 'analysis.ocr_status', 'needs_review'),
            'ocr_confidence' => data_get($pendingUpload, 'analysis.ocr_confidence'),
            'ocr_metadata' => data_get($pendingUpload, 'analysis.metadata'),
            'ocr_processed_at' => now(),
        ]);
        $document->addMediaFromDisk($pendingUpload['path'])->toMediaCollection('sanad_document');
        Storage::delete($pendingUpload['path']);
        $this->applyVaultExpiry($document, $data['expiry_date'] ?? null, $data['expiry_reminder_at'], $request->boolean('expiry_reminder_enabled', true));

        $this->audit('customer_vault_document_uploaded', $document);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'message' => 'Vault document saved.',
                'redirect_url' => route('customer-portal.vault'),
            ]);
        }

        return redirect()->route('customer-portal.vault')->withSuccess('Vault document saved.');
    }

    public function cancelVaultDocumentUpload(Request $request)
    {
        $data = $request->validate([
            'upload_token' => ['required', 'string'],
        ]);

        $pendingUpload = Cache::pull($this->vaultUploadCacheKey($data['upload_token']));
        if ($pendingUpload && (int) ($pendingUpload['owner_id'] ?? 0) === Auth::id() && !empty($pendingUpload['path'])) {
            Storage::delete($pendingUpload['path']);
        }

        return response()->json(['status' => true]);
    }

    public function updateVaultReminder(Request $request, $id)
    {
        $document = SanadDocumentVaultItem::where('owner_id', Auth::id())
            ->where('source', 'vault')
            ->findOrFail($id);

        $data = $request->validate([
            'expiry_date' => ['nullable', 'date'],
            'expiry_reminder_at' => ['nullable', 'date'],
            'expiry_reminder_enabled' => ['nullable', 'boolean'],
        ]);

        $expiryDate = $data['expiry_date'] ?? null;
        $reminderDate = $data['expiry_reminder_at'] ?? null;

        $this->applyVaultExpiry($document, $expiryDate, $reminderDate, $request->boolean('expiry_reminder_enabled'));

        $this->audit('customer_vault_document_reminder_updated', $document);

        return back()->withSuccess('Document reminder saved.');
    }

    public function updateVaultDocument(Request $request, $id, SanadDocumentOcrAgent $ocrAgent)
    {
        $document = SanadDocumentVaultItem::where('owner_id', Auth::id())
            ->where('source', 'vault')
            ->findOrFail($id);

        $data = $request->validate($this->vaultDocumentRules(false), $this->vaultDocumentMessages());

        $expiryDate = $data['expiry_date'] ?? null;
        $reminderDate = $data['expiry_reminder_at'] ?? null;

        if ($expiryDate && !$reminderDate) {
            $reminderDate = \Carbon\Carbon::parse($expiryDate)->subMonthNoOverflow()->toDateString();
        }

        $fileReplaced = $request->hasFile('file');
        $document->forceFill([
            'document_type' => $data['document_type'],
            'document_key' => Str::slug($data['document_type'], '_'),
            'expiry_date' => $expiryDate,
            'expiry_reminder_at' => $reminderDate,
            'expiry_reminder_enabled' => $request->boolean('expiry_reminder_enabled'),
            'expiry_reminder_sent_at' => null,
        ]);

        if ($fileReplaced) {
            $document->forceFill([
                'file_name' => $request->file('file')->getClientOriginalName(),
                'ocr_status' => 'pending',
                'ocr_confidence' => null,
                'ocr_metadata' => null,
                'ocr_processed_at' => null,
            ]);
        }

        $document->save();

        if ($fileReplaced) {
            $document->clearMediaCollection('sanad_document');
            $document->addMedia($request->file('file'))->toMediaCollection('sanad_document');

            try {
                $ocrAgent->analyze($document->fresh());
            } catch (\Throwable $exception) {
                \Log::warning('Sanad document OCR agent failed during vault document update', [
                    'document_id' => $document->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if (!$document->fresh()->expiry_date && $expiryDate) {
            $this->applyVaultExpiry($document, $expiryDate, $reminderDate, $request->boolean('expiry_reminder_enabled'));
        }

        if (!$document->fresh()->expiry_date) {
            return back()
                ->withInput($request->except(['file']))
                ->withErrors([
                    'file' => 'Sanad AI could not detect an expiry date. Please upload a clearer replacement where the expiry date is visible, or enter the expiry date manually.',
                ]);
        }

        $this->audit('customer_vault_document_updated', $document);

        return back()->withSuccess('Document updated.');
    }

    private function vaultDocumentRules(bool $fileRequired): array
    {
        return [
            'document_type' => ['required', 'string', 'max:190'],
            'file' => [
                $fileRequired ? 'required' : 'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf,doc,dox,docx,docs',
                'max:20480',
            ],
            'expiry_date' => ['nullable', 'date'],
            'expiry_reminder_at' => ['nullable', 'date'],
            'expiry_reminder_enabled' => ['nullable', 'boolean'],
        ];
    }

    private function applyVaultExpiry(SanadDocumentVaultItem $document, ?string $expiryDate, ?string $reminderDate, bool $reminderEnabled): void
    {
        if ($expiryDate && !$reminderDate) {
            $reminderDate = \Carbon\Carbon::parse($expiryDate)->subMonthNoOverflow()->toDateString();
        }

        $document->forceFill([
            'expiry_date' => $expiryDate,
            'expiry_reminder_at' => $reminderDate,
            'expiry_reminder_enabled' => $reminderEnabled,
            'expiry_reminder_sent_at' => null,
        ])->save();
    }

    private function vaultDocumentMessages(): array
    {
        return [
            'file.mimes' => 'Please upload a supported document: JPG, JPEG, PNG, PDF, DOC, DOX, DOCX, or DOCS.',
            'file.max' => 'Please upload a document up to 20 MB.',
            'file.uploaded' => 'The file could not be uploaded. Please use JPG, JPEG, PNG, PDF, DOC, DOX, DOCX, or DOCS up to 20 MB.',
        ];
    }

    private function vaultUploadCacheKey(string $token): string
    {
        return 'sanad:vault-upload:' . Auth::id() . ':' . $token;
    }

    private function vaultConfirmError(Request $request, string $message)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => false,
                'message' => $message,
            ], 422);
        }

        return back()->withErrors(['file' => $message]);
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
            'sanadAiInteractions',
        ]);

        if ($request->action_state === 'open_chat') {
            $query->whereHas('sanadChatThreads', fn ($chatQuery) => $chatQuery->where('status', 'open'));
        }
        if ($request->action_state === 'unread_buzz') {
            $query->whereHas('sanadBuzzAlerts', fn ($buzzQuery) => $this->whereVisibleCustomerBuzz($buzzQuery->where('status', 'unread')));
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
                'sanadAiInteractions.user',
            ])->find($request->booking_id);
        }
        $selectedBooking = $selectedBooking ?: $conversations->first();

        $thread = $selectedBooking ? $this->customerThread($selectedBooking) : null;
        $messages = $thread
            ? $thread->messages()->with(['sender', 'buzzAlert.replies.sender', 'documentRequest.document', 'aiInteraction'])->get()
            : collect();
        $visibleMessages = $messages->reject(function ($message) {
            $visibleTo = $message->visible_to ?: [];
            return in_array($message->message_type, ['buzz', 'document_request'], true)
                || $message->buzz_alert_id
                || $message->document_request_id
                || ($visibleTo && empty(array_intersect($visibleTo, ['customer', 'user'])));
        });
        $buzzAlerts = $selectedBooking ? $this->visibleCustomerBuzzQuery($selectedBooking)->with('replies.sender')->latest()->get() : collect();
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

    public function messagesSnapshot(Request $request)
    {
        $booking = $this->customerRequests($this->customer())
            ->with(['customer', 'service', 'provider', 'sanadDocumentRequests.document', 'sanadBuzzAlerts.replies.sender', 'sanadAiInteractions'])
            ->findOrFail($request->booking_id);

        $thread = $this->customerThread($booking);
        $messages = $thread
            ? $thread->messages()->with(['sender', 'buzzAlert.replies.sender', 'documentRequest.document', 'aiInteraction'])->get()
            : collect();
        $visibleMessages = $messages->reject(function ($message) {
            $visibleTo = $message->visible_to ?: [];
            return in_array($message->message_type, ['buzz', 'document_request'], true)
                || $message->buzz_alert_id
                || $message->document_request_id
                || ($visibleTo && empty(array_intersect($visibleTo, ['customer', 'user'])));
        });
        $buzzAlerts = $this->visibleCustomerBuzzQuery($booking)->with('replies.sender')->latest()->get();
        $documentRequests = $booking->sanadDocumentRequests()->with('document')->latest()->get();
        $requiredDocuments = $booking->service
            ? collect($booking->service->required_documents ?: [])->map(function ($doc) {
                $name = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc;
                return [
                    'key' => is_array($doc) ? ($doc['key'] ?? Str::slug($name, '_')) : Str::slug($name, '_'),
                    'name' => $name,
                ];
            })->values()
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
                'message' => $buzz->message,
                'recipient_role' => Str::headline($buzz->recipient_role ?: 'customer'),
                'replies' => $buzz->replies->map(fn ($reply) => [
                    'sender' => optional($reply->sender)->display_name ?: Str::headline($reply->sender_role ?: 'user'),
                    'message' => $reply->message,
                    'created_at' => optional($reply->created_at)->format('Y-m-d H:i'),
                ])->values(),
            ]);
        }
        foreach ($documentRequests as $documentRequest) {
            $timelineData->push([
                'type' => 'document',
                'id' => 'doc-' . $documentRequest->id,
                'timestamp' => optional($documentRequest->created_at)->timestamp ?: 0,
                'created_at' => optional($documentRequest->created_at)->format('Y-m-d H:i'),
                'status' => Str::headline($documentRequest->status),
                'document_name' => $documentRequest->document_name,
                'requested_from' => Str::headline($documentRequest->requested_from ?: 'customer'),
                'instructions' => $documentRequest->instructions ?: $documentRequest->reason,
                'due_at' => optional($documentRequest->due_at)->format('Y-m-d'),
                'due_label' => $documentRequest->due_at ? $documentRequest->due_at->diffForHumans() : null,
                'has_file' => (bool) $documentRequest->document,
                'file_url' => $documentRequest->document ? $documentRequest->document->publicDocumentUrl() : null,
            ]);
        }
        foreach ($visibleMessages as $message) {
            $timelineData->push([
                'type' => 'message',
                'id' => 'msg-' . $message->id,
                'timestamp' => optional($message->created_at)->timestamp ?: 0,
                'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                'sender' => $message->sender_role === 'system' ? 'Sanad AI' : (optional($message->sender)->display_name ?: Str::headline($message->sender_role ?: 'system')),
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
                'reference' => $booking->sanad_reference ?: '#' . $booking->id,
                'customer' => optional($booking->customer)->display_name ?: optional($booking->customer)->email ?: 'Customer',
                'avatar' => Str::upper(Str::substr(optional($booking->customer)->display_name ?: optional($booking->customer)->email ?: 'C', 0, 1)),
                'service' => optional($booking->service)->name ?: 'No service',
                'stage' => Str::headline($booking->sanad_stage ?: $booking->status),
                'priority' => Str::headline($booking->sanad_priority ?: 'normal'),
                'sla' => optional($booking->sla_due_at)->format('Y-m-d H:i') ?: '-',
                'partner' => optional($booking->provider)->display_name ?: '-',
                'request_url' => route('customer-portal.requests.show', $booking->id),
                'updated_at' => optional($booking->updated_at)->toIso8601String(),
                'ai_first_responder_enabled' => $booking->ai_first_responder_enabled !== false,
                'chat_owner_type' => $booking->chat_owner_type ?: 'ai',
                'chat_owner_user_id' => $booking->chat_owner_user_id,
                'chat_owner_team' => null,
                'chat_assignment_label' => $this->chatAssignmentLabel($booking),
            ],
            'composer' => [
                'booking_id' => $booking->id,
                'store_url' => route('customer-portal.requests.messages.store', $booking->id),
                'can_create_buzz' => false,
                'can_request_documents' => false,
                'required_documents' => $requiredDocuments,
                'ai_toggle_url' => null,
                'chat_assignment_url' => null,
                'assignable_chat_targets' => [],
                'direct_message_locked' => false,
                'direct_message_lock_message' => null,
            ],
            'timeline' => $timelineData,
            'messages' => $visibleMessages->map(fn ($message) => [
                'id' => $message->id,
                'sender' => $message->sender_role === 'system' ? 'Sanad AI' : (optional($message->sender)->display_name ?: Str::headline($message->sender_role ?: 'system')),
                'sender_role' => $message->sender_role,
                'message' => $message->message,
                'message_type' => $message->message_type ?: 'text',
                'buzz_alert_id' => $message->buzz_alert_id,
                'document_request_id' => $message->document_request_id,
                'ai_interaction_id' => $message->ai_interaction_id,
                'handover_status' => optional($message->aiInteraction)->status,
                'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                'attachment_url' => $message->getFirstMediaUrl('sanad_chat_attachment') ?: $message->getFirstMediaUrl('attachment'),
                'attachment_name' => optional($message->getFirstMedia('sanad_chat_attachment'))->file_name,
            ])->values(),
            'buzz_alerts' => $buzzAlerts->map(fn ($buzz) => [
                'id' => $buzz->id,
                'priority' => Str::headline($buzz->priority),
                'status' => Str::headline($buzz->status),
                'message' => $buzz->message,
                'recipient_role' => Str::headline($buzz->recipient_role ?: 'customer'),
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
            'ai_escalations' => [],
        ]);
    }

    public function sendMessage(Request $request, $id, SanadAiFirstResponderService $firstResponder)
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
            'visible_to' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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

        if ($request->wantsJson() || $request->ajax()) {
            if (!$buzz && $message->message_type === 'text') {
                app()->terminating(function () use ($firstResponder, $message, $booking) {
                    try {
                        $firstResponder->respondToCustomerMessage($message, $booking);
                    } catch (\Throwable $exception) {
                        \Log::warning('Sanad AI first responder failed after customer message send', [
                            'booking_id' => $booking->id,
                            'message_id' => $message->id,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                });
            }

            return response()->json([
                'status' => true,
                'message' => 'Message sent.',
                'chat_message' => [
                    'id' => $message->id,
                    'message' => $message->message,
                    'sender_role' => $message->sender_role,
                    'sender_name' => optional(Auth::user())->display_name ?: optional(Auth::user())->email ?: 'Customer',
                    'created_at' => optional($message->created_at)->format('Y-m-d H:i'),
                    'buzz_alert_id' => optional($buzz)->id,
                    'message_type' => $message->message_type ?: 'text',
                    'attachment_url' => $message->getFirstMediaUrl('sanad_chat_attachment') ?: $message->getFirstMediaUrl('attachment'),
                    'attachment_name' => optional($message->getFirstMedia('sanad_chat_attachment'))->file_name,
                    'ai_response_pending' => !$buzz && $message->message_type === 'text',
                ],
            ]);
        }

        if (!$buzz && $message->message_type === 'text') {
            $firstResponder->respondToCustomerMessage($message, $booking);
        }

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

    public function askAi(Request $request, SanadAiFirstResponderService $firstResponder)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
            'booking_id' => ['nullable', 'integer'],
        ]);
        $booking = null;
        if (!empty($data['booking_id'])) {
            $booking = $this->customerRequests($this->customer())->with('service')->findOrFail($data['booking_id']);
        }

        if ($booking && !$firstResponder->isEnabled($booking)) {
            $interaction = SanadAiInteraction::create([
                'user_id' => Auth::id(),
                'booking_id' => $booking->id,
                'question' => $data['question'],
                'answer' => 'A Sanad agent is handling this request now. Your message is visible to the assigned team.',
                'confidence' => 0,
                'requires_escalation' => true,
                'status' => 'manual_takeover',
                'metadata' => ['first_responder_disabled' => true],
            ]);
        } else {
            $interaction = $firstResponder->createInteraction($data['question'], $booking, Auth::user(), 'customer');
        }
        $this->audit('customer_ai_question_asked', $interaction, ['booking_id' => optional($booking)->id]);

        return back()->withSuccess('Sanad AI response generated.');
    }

    public function handleAiHandover(Request $request, $id, SanadAiFirstResponderService $firstResponder)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:yes,no'],
        ]);

        $interaction = SanadAiInteraction::where('user_id', Auth::id())->findOrFail($id);
        if ($interaction->booking_id) {
            $this->customerRequests($this->customer())->findOrFail($interaction->booking_id);
        }

        $result = $firstResponder->handleHandoverDecision($interaction, $data['decision'], Auth::user());
        $this->audit('customer_ai_handover_' . $data['decision'], $interaction, $result);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'status' => true,
                'decision' => $data['decision'],
                'message' => $data['decision'] === 'yes'
                    ? 'Sanad team has been notified.'
                    : 'Okay, no handover requested.',
                'result' => $result,
            ]);
        }

        return back()->withSuccess($data['decision'] === 'yes' ? 'Sanad team has been notified.' : 'Okay, no handover requested.');
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
                    ->orWhereHas('sanadBuzzAlerts', fn ($q) => $this->whereVisibleCustomerBuzz($q->where('status', 'unread')));
            })
            ->latest('updated_at');
    }

    private function visibleCustomerBuzzQuery(Booking $booking)
    {
        return $this->whereVisibleCustomerBuzz($booking->sanadBuzzAlerts());
    }

    private function whereVisibleCustomerBuzz($query)
    {
        return $query->where(function ($buzzQuery) {
            $buzzQuery->whereNull('action_type')
                ->orWhere('action_type', '!=', 'chat_assignment_accept');
        });
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
            'participant_roles' => ['user', 'customer', 'admin', 'employee', 'handyman', 'provider'],
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

    private function chatAssignmentLabel(Booking $booking): string
    {
        if (($booking->chat_owner_type ?: 'ai') === 'ai') {
            return 'AI First Responder';
        }

        if ($booking->chat_owner_type === 'sanad_team') {
            return 'Sanad Team';
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
