<x-master-layout>
@include('customer-portal.partials.styles')
@php
    $stage = $booking->sanad_stage ?? $booking->status;
    $progress = in_array($stage, ['completed','closed']) ? 100 : (['submitted'=>15,'pending_review'=>25,'assigned_to_partner'=>40,'assigned_to_employee'=>55,'in_progress'=>70,'awaiting_customer_action'=>65,'awaiting_quality_review'=>85,'escalated'=>60][$stage] ?? 20);
    $docs = is_array(optional($booking->service)->required_documents) ? $booking->service->required_documents : [];
@endphp
<script>
    window.syncCustomerPortalDocumentChoice = function (selectElem) {
        var form = selectElem ? selectElem.closest('.customer-document-submit') : null;
        if (!form) return;

        var hasVault = !!selectElem.value;
        var uploadRow = form.querySelector('.customer-upload-row');
        var fileInput = form.querySelector('input[type="file"]');

        if (uploadRow) uploadRow.classList.toggle('is-disabled', hasVault);
        if (fileInput) {
            if (hasVault) fileInput.value = '';
            fileInput.disabled = hasVault;
        }
    };

    window.validateCustomerPortalDocumentSubmit = function (form) {
        var vaultSelect = form.querySelector('select[name="vault_document_id"]');
        var fileInput = form.querySelector('input[type="file"]');
        var hasVault = vaultSelect && vaultSelect.value;
        var hasFile = fileInput && !fileInput.disabled && fileInput.files && fileInput.files.length > 0;

        if (!hasVault && !hasFile) {
            alert('Choose a vault document or attach a file first.');
            return false;
        }

        return true;
    };

    window.syncCustomerPortalDocumentFileName = function (input) {
        var form = input ? input.closest('.customer-document-submit') : null;
        var fileName = form ? form.querySelector('.customer-file-name') : null;
        if (!fileName) return;

        fileName.textContent = input.files && input.files[0] ? input.files[0].name : 'No file selected';
    };
</script>
<style>
    .customer-upload-row.is-disabled { opacity: .48; pointer-events: none; }
    .customer-upload-icon { width: 42px; height: 38px; display: inline-flex; align-items: center; justify-content: center; }
    .customer-attach-icon { width: 42px; height: 38px; display: inline-flex; align-items: center; justify-content: center; }
    .customer-file-input { display: none !important; }
    .customer-file-name { min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">Request {{ $booking->sanad_reference ?? '#'.$booking->id }}</h1><div class="sanad-muted">{{ optional($booking->service)->name_en ?? optional($booking->service)->name }}</div></div>
        <a class="sanad-btn secondary" href="{{ route('customer-portal.requests.index') }}">Back</a>
    </div>
    @foreach($booking->sanadBuzzAlerts->where('status', 'unread') as $buzz)
        <div class="alert alert-danger" id="buzz-{{ $buzz->id }}">
            <strong>Buzz:</strong> {{ $buzz->message }}
            <form class="mt-2" method="post" action="{{ route('customer-portal.requests.buzz.reply', [$booking->id, $buzz->id]) }}">
                @csrf
                <div class="row">
                    <div class="col-md-9"><input class="sanad-form-control" name="message" placeholder="Reply to this urgent request" required></div>
                    <div class="col-md-3"><button class="sanad-btn w-100">Reply</button></div>
                </div>
            </form>
        </div>
    @endforeach
    <div class="row">
        <div class="col-xl-8">
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Request Information</div>
                <div class="sanad-card-body">
                    <div class="sanad-grid">
                        <div><span class="sanad-muted">Service</span><strong class="d-block">{{ optional($booking->service)->name_en ?? optional($booking->service)->name }}</strong></div>
                        <div><span class="sanad-muted">Service Provider</span><strong class="d-block">Sanad Solutions</strong></div>
                        <div><span class="sanad-muted">Assigned Employee</span><strong class="d-block">{{ optional(optional($booking->handymanAdded)->first())->handyman->display_name ?? '-' }}</strong></div>
                        <div><span class="sanad-muted">SLA</span><strong class="d-block">{{ optional($booking->sla_due_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                        <div><span class="sanad-muted">Created</span><strong class="d-block">{{ optional($booking->created_at)->format('Y-m-d H:i') }}</strong></div>
                        <div><span class="sanad-muted">Estimated Completion</span><strong class="d-block">{{ optional($booking->expected_completion_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                    </div>
                    <div class="mt-4"><div class="d-flex justify-content-between"><strong>Progress</strong><span>{{ $progress }}%</span></div><div class="sanad-progress"><span style="width:{{ $progress }}%"></span></div></div>
                    <div class="mt-3"><span class="sanad-badge">{{ Str::headline($stage) }}</span> <span class="sanad-muted ml-2">Next expected step is managed by the Sanad team.</span></div>
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Timeline</div>
                <div class="sanad-card-body sanad-timeline">
                    @forelse($booking->sanadRequestActions as $action)
                        <div class="sanad-timeline-item"><strong>{{ Str::headline($action->action ?? 'Update') }}</strong><div class="sanad-muted">{{ $action->note ?? $action->reason ?? 'Request updated.' }}</div><small>{{ optional($action->created_at)->format('Y-m-d H:i') }}</small></div>
                    @empty
                        <div class="sanad-timeline-item"><strong>Request Created</strong><div class="sanad-muted">Your request has been submitted.</div><small>{{ optional($booking->created_at)->format('Y-m-d H:i') }}</small></div>
                    @endforelse
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Secure Chat</div>
                <div class="sanad-card-body">
                    <div class="sanad-chat-box mb-3">
                        @forelse($thread->messages as $message)
                            <div class="sanad-chat-message"><strong>{{ optional($message->sender)->display_name ?? Str::headline($message->sender_role) }}</strong> <small class="sanad-muted">{{ optional($message->created_at)->format('Y-m-d H:i') }}</small><div>{{ $message->message }}</div>@if($message->getFirstMediaUrl('sanad_chat_attachment'))<a href="{{ $message->getFirstMediaUrl('sanad_chat_attachment') }}" target="_blank">Attachment</a>@endif</div>
                        @empty
                            <p class="sanad-muted mb-0">No messages yet.</p>
                        @endforelse
                    </div>
                    <form method="post" action="{{ route('customer-portal.requests.messages.store', $booking->id) }}" enctype="multipart/form-data">@csrf@if(request('buzz_id'))<input type="hidden" name="buzz_alert_id" value="{{ request('buzz_id') }}">@endif<div class="row"><div class="col-md-8"><input class="sanad-form-control" name="message" placeholder="{{ request('buzz_id') ? 'Reply to selected Buzz' : 'Message Sanad team' }}"></div><div class="col-md-2"><input class="sanad-form-control" type="file" name="attachment"></div><div class="col-md-2"><button class="sanad-btn w-100">Send</button></div></div><small class="sanad-muted">Phone numbers, emails, URLs, and external contact details are removed automatically.</small></form>
                </div>
            </div>
            <div class="sanad-card">
                <div class="sanad-card-header">Customer Rating</div>
                <div class="sanad-card-body">
                    @if(in_array($stage, ['completed','closed']))
                        <form method="post" action="{{ route('save-booking-rating') }}">@csrf<input type="hidden" name="booking_id" value="{{ $booking->id }}"><input type="hidden" name="service_id" value="{{ $booking->service_id }}"><div class="row"><div class="col-md-3"><input class="sanad-form-control" name="rating" type="number" min="1" max="5" placeholder="Overall"></div><div class="col-md-7"><input class="sanad-form-control" name="review" placeholder="Comments"></div><div class="col-md-2"><button class="sanad-btn w-100">Rate</button></div></div></form>
                    @else
                        <p class="sanad-muted mb-0">Rating is available after completion.</p>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Required Documents</div>
                <div class="sanad-card-body">
                    @forelse($docs as $doc)
                        @php $name = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc; @endphp
                        <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $name }}</span><span class="sanad-badge">{{ $booking->sanadDocuments->contains('document_type', $name) ? 'Submitted' : 'Required' }}</span></div>
                    @empty
                        <p class="sanad-muted">No required document list configured.</p>
                    @endforelse
                    <form class="mt-3" method="post" action="{{ route('customer-portal.requests.documents.store', $booking->id) }}" enctype="multipart/form-data">@csrf<input class="sanad-form-control mb-2" name="document_type" placeholder="Custom document type" required><input class="sanad-form-control mb-2" name="file" type="file" required><button class="sanad-btn">Upload Document</button></form>
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Document Requests</div>
                <div class="sanad-card-body">
                    @forelse($booking->sanadDocumentRequests->where('requested_from', 'customer') as $docRequest)
                        <div class="border-bottom py-2">
                            <strong>{{ $docRequest->document_name }}</strong>
                            <div class="sanad-muted">{{ $docRequest->instructions ?? $docRequest->reason }}</div>
                            <span class="sanad-badge">{{ Str::headline($docRequest->status) }}</span>
                            @if(in_array($docRequest->status, ['pending','rejected','replacement_requested']))
                                <form class="customer-document-submit mt-2" method="post" action="{{ route('customer-portal.requests.document-requests.upload', [$booking->id, $docRequest->id]) }}" enctype="multipart/form-data" onsubmit="return window.validateCustomerPortalDocumentSubmit(this)">
                                    @csrf
                                    @if(!empty($vaultDocuments) && $vaultDocuments->isNotEmpty())
                                        <div class="d-flex gap-2 align-items-center mb-2">
                                            <select name="vault_document_id" class="sanad-form-control" onchange="window.syncCustomerPortalDocumentChoice(this)">
                                                <option value="">-- Attach from Document Vault --</option>
                                                @foreach($vaultDocuments as $vDoc)
                                                    <option value="{{ $vDoc->id }}">📁 {{ $vDoc->document_type }} ({{ $vDoc->file_name }})</option>
                                                @endforeach
                                            </select>
                                            <button class="sanad-btn" type="submit"><i class="fas fa-check mr-1"></i> Submit Vault Doc</button>
                                        </div>
                                        <div class="sanad-muted small text-center mb-2">- OR Upload New File -</div>
                                    @endif
                                    <div class="customer-upload-row d-flex gap-2 align-items-center">
                                        <label class="sanad-btn secondary customer-attach-icon mb-0" title="Choose file" aria-label="Choose file">
                                            <i class="fas fa-paperclip"></i>
                                            <input class="customer-file-input" type="file" name="file" onchange="window.syncCustomerPortalDocumentFileName(this)">
                                        </label>
                                        <span class="customer-file-name sanad-muted small">No file selected</span>
                                        <button class="sanad-btn customer-upload-icon" type="submit" title="Upload file" aria-label="Upload file"><i class="fas fa-upload"></i></button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="sanad-muted">No open document requests.</p>
                    @endforelse
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">Submitted Documents</div>
                <div class="sanad-card-body">
                    @forelse($booking->sanadDocuments as $document)
                        @php($documentUrl = $document->publicDocumentUrl())
                        <div class="border-bottom py-2"><strong>{{ $document->document_type }}</strong><div><span class="sanad-badge">{{ Str::headline($document->verification_status) }}</span></div>@if($documentUrl)<a href="{{ $documentUrl }}" target="_blank">Preview / Download</a>@endif</div>
                    @empty
                        <p class="sanad-muted">No submitted documents.</p>
                    @endforelse
                </div>
            </div>
            <div class="sanad-card">
                <div class="sanad-card-header">Billing</div>
                <div class="sanad-card-body"><p><strong>Invoice:</strong> {{ $booking->payment ? 'Available' : '-' }}</p><p><strong>Service Fee:</strong> {{ getPriceFormat(optional($booking->service)->service_fee ?? $booking->amount ?? 0) }}</p><p><strong>VAT:</strong> {{ getPriceFormat($booking->final_total_tax ?? 0) }}</p><p><strong>Payment Status:</strong> {{ optional($booking->payment)->payment_status ?? 'pending' }}</p>@if($booking->payment)<a class="sanad-btn secondary" href="{{ route('invoice_pdf', $booking->id) }}">Export Invoice</a>@endif</div>
            </div>
        </div>
    </div>
</div>
</x-master-layout>
