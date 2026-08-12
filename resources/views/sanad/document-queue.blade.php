<x-master-layout>
    <div class="container-fluid sanad-document-queue">
        <div class="card card-block card-stretch">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="font-weight-bold mb-1">Document Review</h4>
                    <span class="text-muted">Partner onboarding verification and request-specific service documents.</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('provider.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user-tie mr-1"></i> Partners
                    </a>
                    <a href="{{ route('sanad.requests.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-folder-open mr-1"></i> Orders
                    </a>
                </div>
            </div>
        </div>

        <div class="row sanad-summary-grid">
            <div class="col-lg-3 col-md-6">
                <div class="card total-provider-card sanad-dashboard-stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-2 booking-text font-weight-bold">{{ $partnerSummary['partners'] }}</h4>
                                <p class="mb-0 booking-text">Partners</p>
                            </div>
                            <div class="col-auto d-flex flex-column">
                                <div class="iq-card-icon iq-card-icon-provider icon-shape text-white rounded-circle shadow">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card total-service-card sanad-dashboard-stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-2 booking-text font-weight-bold">{{ $partnerSummary['pending'] }}</h4>
                                <p class="mb-0 booking-text">Partner Docs Pending</p>
                            </div>
                            <div class="col-auto d-flex flex-column">
                                <div class="iq-card-icon iq-card-icon-service icon-shape text-white rounded-circle shadow">
                                    <i class="fas fa-file-signature"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card total-booking-card sanad-dashboard-stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-2 booking-text font-weight-bold">{{ $orderSummary['orders'] }}</h4>
                                <p class="mb-0 booking-text">Requests With Docs</p>
                            </div>
                            <div class="col-auto d-flex flex-column">
                                <div class="iq-card-icon iq-card-icon-booking icon-shape text-white rounded-circle shadow">
                                    <i class="fas fa-folder-open"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card total-revenue sanad-dashboard-stat-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <h4 class="mb-2 booking-text font-weight-bold">{{ $orderSummary['pending'] }}</h4>
                                <p class="mb-0 booking-text">Request Docs Pending</p>
                            </div>
                            <div class="col-auto d-flex flex-column">
                                <div class="iq-card-icon iq-card-icon-revenue icon-shape text-white rounded-circle shadow">
                                    <i class="fas fa-clipboard-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-fill tabslink sanad-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') !== 'orders' ? 'active' : '' }}" data-toggle="tab" href="#partner-documents" role="tab">
                            <i class="fas fa-building mr-2"></i> Partner Onboarding
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'orders' ? 'active' : '' }}" data-toggle="tab" href="#request-documents" role="tab">
                            <i class="fas fa-id-card mr-2"></i> Service Documents
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ request('tab') !== 'orders' ? 'show active' : '' }}" id="partner-documents" role="tabpanel">
                        <form class="sanad-filter-bar mb-3">
                            <input type="hidden" name="tab" value="partners">
                            <div class="form-row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label class="form-control-label">Review Status</label>
                                    <select name="partner_status" class="form-control">
                                        <option value="">All partner documents</option>
                                        <option value="pending" {{ request('partner_status') === 'pending' || request('partner_status') === '0' ? 'selected' : '' }}>Pending Review</option>
                                        <option value="approved" {{ request('partner_status') === 'approved' || request('partner_status') === '1' ? 'selected' : '' }}>Approved</option>
                                        <option value="rejected" {{ request('partner_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button class="btn btn-primary btn-block">
                                        <i class="fas fa-filter mr-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="row">
                            @forelse($partnerCards as $index => $card)
                                @php($partner = $card['partner'])
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
                                    <div class="sanad-entity-card">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <h5 class="font-weight-bold mb-1">{{ optional($partner)->display_name ?: 'Partner' }}</h5>
                                                <div class="text-muted small">{{ optional($partner)->email ?: 'No email' }}</div>
                                                <div class="text-muted small">{{ optional($partner)->contact_number ?: 'No phone' }}</div>
                                            </div>
                                            <span class="badge badge-{{ $card['status'] === 'verified' ? 'success' : ($card['status'] === 'in_review' ? 'warning' : 'light') }}">
                                                {{ Str::headline($card['status']) }}
                                            </span>
                                        </div>

                                        <div class="sanad-metric-grid mb-3">
                                            <div><span>Approved</span><strong>{{ $card['approved'] }}/{{ $card['total'] }}</strong></div>
                                            <div><span>Uploaded</span><strong>{{ $card['uploaded'] }}</strong></div>
                                            <div><span>Pending</span><strong>{{ $card['pending'] }}</strong></div>
                                        </div>
                                        <div class="progress sanad-progress mb-3">
                                            <div class="progress-bar" style="width: {{ $card['progress'] }}%"></div>
                                        </div>

                                        <div class="sanad-mini-list mb-3">
                                            @foreach($card['documents']->take(3) as $document)
                                                @php($hasUpload = getMediaFileExit($document, 'provider_document'))
                                                <div class="sanad-mini-row">
                                                    <span>{{ optional($document->document)->name ?: 'Required document' }}</span>
                                                    <span class="text-{{ $document->verification_status === 'rejected' ? 'danger' : ($document->is_verified ? 'success' : ($hasUpload ? 'warning' : 'muted')) }}">
                                                        {{ $document->verification_status === 'rejected' ? 'Rejected' : ($document->is_verified ? 'Approved' : ($hasUpload ? 'Review' : 'Missing')) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($card['documents']->count() > 3)
                                                <div class="small text-muted mt-1">+{{ $card['documents']->count() - 3 }} more requirements</div>
                                            @endif
                                        </div>

                                        <div class="sanad-card-actions">
                                            <a href="{{ route('providerdocument.show', ['providerdocument' => optional($partner)->id]) }}" class="btn btn-link btn-sm px-0">Open Partner Profile</a>
                                            <button class="btn btn-sm btn-outline-primary sanad-collapse-toggle collapsed" type="button" data-toggle="collapse" data-target="#partner-drawer-{{ $index }}" aria-expanded="false" aria-controls="partner-drawer-{{ $index }}">
                                                <span><i class="fas fa-list-alt mr-1"></i> Document checklist</span>
                                                <span class="sanad-collapse-count">{{ $card['documents']->count() }}</span>
                                                <i class="fas fa-chevron-down sanad-collapse-caret ml-2"></i>
                                            </button>
                                        </div>

                                        <div class="collapse sanad-inline-drawer mt-3" id="partner-drawer-{{ $index }}">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Status</th>
                                                            <th class="text-right">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($card['documents'] as $document)
                                                            @php($hasUpload = getMediaFileExit($document, 'provider_document'))
                                                            @php($media = $hasUpload ? $document->getFirstMedia('provider_document') : null)
                                                            @php($mediaUrl = $media ? $media->getFullUrl() : null)
                                                            @php($mimeType = optional($media)->mime_type ?: '')
                                                            @php($isImage = Str::startsWith($mimeType, 'image/'))
                                                            @php($isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) optional($media)->file_name), '.pdf'))
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ optional($document->document)->name ?: 'Required document' }}</strong>
                                                                    <div class="small text-muted">{{ optional($document->document)->is_required ? 'Required' : 'Optional' }} · {{ $hasUpload ? (optional($media)->file_name ?: 'Uploaded') : 'Not uploaded yet' }}</div>
                                                                    @if($document->review_reason)
                                                                        <div class="small text-danger">{{ $document->review_reason }}</div>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <span class="badge badge-{{ $document->verification_status === 'rejected' ? 'danger' : ($document->is_verified ? 'success' : 'warning') }}">{{ Str::headline($document->verification_status ?: ($document->is_verified ? 'approved' : 'pending')) }}</span>
                                                                </td>
                                                                <td class="text-right">
                                                                    @if($hasUpload)
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#partnerDocumentModal{{ $document->id }}">View</button>
                                                                    @else
                                                                        <span class="text-muted small">Waiting upload</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @foreach($card['documents'] as $document)
                                                @php($hasUpload = getMediaFileExit($document, 'provider_document'))
                                                @php($media = $hasUpload ? $document->getFirstMedia('provider_document') : null)
                                                @php($mediaUrl = $media ? $media->getFullUrl() : null)
                                                @php($mimeType = optional($media)->mime_type ?: '')
                                                @php($isImage = Str::startsWith($mimeType, 'image/'))
                                                @php($isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) optional($media)->file_name), '.pdf'))
                                                @if($hasUpload)
                                                    <div class="modal fade sanad-document-modal" id="partnerDocumentModal{{ $document->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <div>
                                                                        <h5 class="modal-title">{{ optional($document->document)->name ?: 'Partner document' }}</h5>
                                                                        <div class="small text-muted">{{ optional($partner)->display_name ?: 'Partner' }} · {{ optional($media)->file_name }}</div>
                                                                    </div>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="sanad-modal-preview">
                                                                        @if($isImage)
                                                                            <img src="{{ $mediaUrl }}" alt="{{ optional($document->document)->name ?: 'Partner document' }}">
                                                                        @elseif($isPdf)
                                                                            <iframe src="{{ $mediaUrl }}" title="{{ optional($document->document)->name ?: 'Partner document' }}"></iframe>
                                                                        @else
                                                                            <div class="sanad-modal-file"><i class="fas fa-file-alt"></i><span>Preview is not available for this file type.</span><a target="_blank" href="{{ $mediaUrl }}">Download file</a></div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <div class="sanad-partner-review-actions">
                                                                        <form method="POST" action="{{ route('sanad.partner-documents.review', $document->id) }}" class="mb-0">
                                                                            @csrf
                                                                            <input type="hidden" name="verification_status" value="approved">
                                                                            <button class="btn btn-primary">Approve Document</button>
                                                                        </form>
                                                                        <form method="POST" action="{{ route('sanad.partner-documents.review', $document->id) }}" class="sanad-reject-form mb-0">
                                                                            @csrf
                                                                            <input type="hidden" name="verification_status" value="rejected">
                                                                            <input name="review_reason" class="form-control" placeholder="Reason for rejection" required>
                                                                            <button class="btn btn-outline-danger">Reject</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="sanad-empty-state">No partner onboarding documents found.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="tab-pane fade {{ request('tab') === 'orders' ? 'show active' : '' }}" id="request-documents" role="tabpanel">
                        <form class="sanad-filter-bar mb-3">
                            <input type="hidden" name="tab" value="orders">
                            <div class="form-row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label class="form-control-label">Review Status</label>
                                    <select name="order_status" class="form-control">
                                        <option value="">All service documents</option>
                                        @foreach(['pending','approved','rejected','replacement_requested'] as $status)
                                            <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-control-label">Submitted By</label>
                                    <select name="source" class="form-control">
                                        <option value="">Customer and Partner</option>
                                        <option value="customer" {{ request('source') === 'customer' ? 'selected' : '' }}>Customer</option>
                                        <option value="partner" {{ request('source') === 'partner' ? 'selected' : '' }}>Partner</option>
                                        <option value="request" {{ request('source') === 'request' ? 'selected' : '' }}>Request Upload</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button class="btn btn-primary btn-block">
                                        <i class="fas fa-filter mr-1"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="row">
                            @forelse($requestCards as $requestIndex => $card)
                                @php($booking = $card['booking'])
                                @php($service = $card['service'])
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
                                    <div class="sanad-entity-card h-100">
                                        <div class="sanad-request-header mb-3">
                                            <div>
                                                <div class="small text-muted">{{ optional($booking)->sanad_reference ?: '#'.optional($booking)->id }}</div>
                                                <h5 class="font-weight-bold mb-1">{{ optional(optional($booking)->customer)->display_name ?: 'Customer' }}</h5>
                                                <div class="text-muted">{{ optional($service)->name_en ?: optional($service)->name ?: 'Service' }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge badge-light">{{ Str::headline(optional($booking)->sanad_stage ?: optional($booking)->status ?: 'pending') }}</span>
                                                @if($booking)
                                                    <a href="{{ route('sanad.requests.show', $booking->id) }}" class="btn btn-sm btn-outline-primary d-block mt-2">Open Request</a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="sanad-request-meta mb-3">
                                            <div><span>Assigned Partner</span><strong>{{ optional(optional($booking)->provider)->display_name ?: 'Unassigned' }}</strong></div>
                                            <div><span>Required</span><strong>{{ $card['required_count'] }}</strong></div>
                                            <div><span>Submitted</span><strong>{{ $card['submitted_count'] }}</strong></div>
                                            <div><span>Approved</span><strong>{{ $card['approved_count'] }}</strong></div>
                                        </div>

                                        <div class="progress sanad-progress mb-3">
                                            <div class="progress-bar" style="width: {{ $card['progress'] }}%"></div>
                                        </div>

                                        @if($card['missing_required']->isNotEmpty())
                                            <div class="sanad-section-note mb-3">
                                                <strong>Missing required:</strong>
                                                {{ $card['missing_required']->pluck('name')->filter()->join(', ') ?: 'Required document pending' }}
                                            </div>
                                        @endif

                                        <div class="sanad-card-actions">
                                            <button class="btn btn-sm btn-outline-primary sanad-collapse-toggle collapsed" type="button" data-toggle="collapse" data-target="#request-documents-drawer-{{ $requestIndex }}" aria-expanded="false" aria-controls="request-documents-drawer-{{ $requestIndex }}">
                                                <span><i class="fas fa-list-alt mr-1"></i> Submitted documents</span>
                                                <span class="sanad-collapse-count">{{ $card['documents']->count() }}</span>
                                                <i class="fas fa-chevron-down sanad-collapse-caret ml-2"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-primary sanad-collapse-toggle collapsed" type="button" data-toggle="collapse" data-target="#request-team-drawer-{{ $requestIndex }}" aria-expanded="false" aria-controls="request-team-drawer-{{ $requestIndex }}">
                                                <span><i class="fas fa-tasks mr-1"></i> Document requests</span>
                                                <span class="sanad-collapse-count">{{ $card['document_requests']->count() }}</span>
                                                <i class="fas fa-chevron-down sanad-collapse-caret ml-2"></i>
                                            </button>
                                        </div>

                                        <div class="collapse sanad-inline-drawer mt-3" id="request-documents-drawer-{{ $requestIndex }}">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Submitted By</th>
                                                            <th>Uploaded</th>
                                                            <th>Status</th>
                                                            <th class="text-right">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            @forelse($card['documents'] as $document)
                                                @php($media = $document->getFirstMedia('document') ?: $document->getFirstMedia('sanad_document'))
                                                @php($documentUrl = $media ? $media->getFullUrl() : $document->file_path)
                                                @php($fileName = optional($media)->file_name ?: $document->file_name ?: 'Uploaded document')
                                                @php($mimeType = optional($media)->mime_type ?: '')
                                                @php($isImage = Str::startsWith($mimeType, 'image/'))
                                                @php($isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) $fileName), '.pdf') || Str::endsWith(Str::lower((string) $documentUrl), '.pdf'))
                                                <tr>
                                                    <td>
                                                        <strong>{{ Str::headline($document->document_type) }}</strong>
                                                        <div class="small text-muted">{{ $fileName }}</div>
                                                        @if($document->review_reason)
                                                            <div class="small text-danger">{{ $document->review_reason }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ Str::headline($document->source ?: 'request') }}</td>
                                                    <td>{{ optional($document->created_at)->format('M d, Y') ?: '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $document->verification_status === 'approved' ? 'success' : ($document->verification_status === 'rejected' ? 'danger' : 'warning') }}">{{ Str::headline($document->verification_status ?: 'pending') }}</span>
                                                    </td>
                                                    <td class="text-right">
                                                        @if($documentUrl)
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#requestDocumentModal{{ $document->id }}">View</button>
                                                        @else
                                                            <span class="text-muted small">No file</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                @if($documentUrl)
                                                    <div class="modal fade sanad-document-modal" id="requestDocumentModal{{ $document->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <div>
                                                                        <h5 class="modal-title">{{ Str::headline($document->document_type) }}</h5>
                                                                        <div class="small text-muted">
                                                                            {{ optional(optional($booking)->customer)->display_name ?: 'Customer' }}
                                                                            · {{ optional($service)->name_en ?: optional($service)->name ?: 'Service' }}
                                                                            · {{ $fileName }}
                                                                        </div>
                                                                    </div>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="sanad-modal-preview">
                                                                        @if($isImage)
                                                                            <img src="{{ $documentUrl }}" alt="{{ Str::headline($document->document_type) }}">
                                                                        @elseif($isPdf)
                                                                            <iframe src="{{ $documentUrl }}" title="{{ Str::headline($document->document_type) }}"></iframe>
                                                                        @else
                                                                            <div class="sanad-modal-file"><i class="fas fa-file-alt"></i><span>Preview is not available for this file type.</span><a target="_blank" href="{{ $documentUrl }}">Download file</a></div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    @if($booking)
                                                                        <form method="POST" action="{{ route('sanad.requests.documents.review', [$booking->id, $document->id]) }}" class="sanad-modal-review-form mb-0">
                                                                            @csrf
                                                                            <select name="verification_status" class="form-control">
                                                                                <option value="approved">Approve</option>
                                                                                <option value="rejected">Reject</option>
                                                                                <option value="replacement_requested">Request replacement</option>
                                                                            </select>
                                                                            <input name="reason" class="form-control" placeholder="Reason for rejection/replacement">
                                                                            <button class="btn btn-primary">Save Review</button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @empty
                                                <tr><td colspan="5" class="text-muted text-center">No submitted documents yet.</td></tr>
                                            @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="collapse sanad-inline-drawer mt-3" id="request-team-drawer-{{ $requestIndex }}">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Requested From</th>
                                                            <th>Due</th>
                                                            <th>Status</th>
                                                            <th>File</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            @forelse($card['document_requests'] as $documentRequest)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $documentRequest->document_name }}</strong>
                                                        <div class="small text-muted">{{ $documentRequest->reason }}</div>
                                                        <div class="small text-muted">By {{ optional($documentRequest->requester)->display_name ?: 'Sanad Team' }}</div>
                                                    </td>
                                                    <td>{{ Str::headline($documentRequest->requested_from) }}</td>
                                                    <td>{{ $documentRequest->due_at ? $documentRequest->due_at->format('M d, Y') : '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $documentRequest->status === 'approved' ? 'success' : ($documentRequest->status === 'rejected' ? 'danger' : 'warning') }}">
                                                            {{ Str::headline($documentRequest->status ?: 'pending') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php($requestDocumentUrl = $documentRequest->document ? ($documentRequest->document->getFirstMediaUrl('document') ?: $documentRequest->document->getFirstMediaUrl('sanad_document') ?: $documentRequest->document->file_path) : null)
                                                        @if($requestDocumentUrl)
                                                            <a target="_blank" href="{{ $requestDocumentUrl }}">Submitted file</a>
                                                        @else
                                                            <span class="text-muted small">Pending</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-muted text-center">No additional document requests.</td></tr>
                                            @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="sanad-empty-state">No service request documents found.</div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('after-styles')
        <style>
            .sanad-document-queue .card,
            .sanad-entity-card {
                border: 1px solid #edf1f7;
                border-radius: 8px;
                box-shadow: 0 10px 28px rgba(31, 41, 55, .04);
            }
            .sanad-entity-card {
                background: #fff;
            }
            .sanad-summary-grid .sanad-dashboard-stat-card {
                margin-bottom: 24px;
                min-height: 118px;
            }
            .sanad-summary-grid .iq-card-icon {
                align-items: center;
                display: flex;
                height: 48px;
                justify-content: center;
                width: 48px;
            }
            .sanad-summary-grid .iq-card-icon i {
                color: #fff;
                font-size: 18px;
            }
            #partner-documents .sanad-entity-card {
                background: linear-gradient(180deg, #fbfcff 0%, #f3f6ff 100%);
                border-color: #dce3f7;
                box-shadow: 0 14px 34px rgba(63, 63, 85, .10);
            }
            #partner-documents .sanad-entity-card:hover {
                border-color: #b9c2ef;
                box-shadow: 0 18px 42px rgba(95, 88, 201, .16);
            }
            .sanad-metric-grid span,
            .sanad-request-meta span {
                color: #6b7280;
                display: block;
                font-size: 12px;
            }
            .sanad-filter-bar {
                background: #fafbff;
                border: 1px solid #edf1f7;
                border-radius: 8px;
                padding: 14px 16px;
            }
            .sanad-entity-card {
                padding: 18px;
            }
            .sanad-metric-grid,
            .sanad-request-meta {
                display: grid;
                gap: 10px;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .sanad-request-meta {
                grid-template-columns: 1.7fr repeat(3, minmax(0, .75fr));
            }
            .sanad-metric-grid > div,
            .sanad-request-meta > div {
                background: #f8f9ff;
                border: 1px solid #edf1f7;
                border-radius: 6px;
                padding: 10px;
            }
            .sanad-metric-grid strong,
            .sanad-request-meta strong {
                color: #1f2937;
                display: block;
                font-size: 16px;
                line-height: 1.3;
                margin-top: 3px;
            }
            .sanad-progress {
                background: #eef1f7;
                height: 7px;
                border-radius: 999px;
            }
            .sanad-progress .progress-bar {
                background: #5f58c9;
            }
            .sanad-mini-list,
            .sanad-review-list {
                border-top: 1px solid #edf1f7;
            }
            .sanad-mini-row,
            .sanad-review-row,
            .sanad-request-header {
                display: flex;
                justify-content: space-between;
                gap: 16px;
            }
            .sanad-mini-row {
                font-size: 13px;
                padding: 8px 0;
            }
            .sanad-review-row {
                align-items: flex-start;
                border-bottom: 1px solid #edf1f7;
                padding: 13px 0;
            }
            .sanad-review-row:last-child {
                border-bottom: 0;
                padding-bottom: 0;
            }
            .sanad-row-actions {
                align-items: flex-end;
                display: flex;
                flex-direction: column;
                flex-shrink: 0;
                gap: 8px;
            }
            .sanad-row-actions-wide {
                min-width: 330px;
            }
            .sanad-card-actions {
                align-items: center;
                border-top: 1px solid #edf1f7;
                display: flex;
                gap: 10px;
                justify-content: space-between;
                padding-top: 12px;
            }
            .sanad-collapse-toggle {
                align-items: center;
                display: inline-flex;
                gap: 8px;
                justify-content: space-between;
                min-width: 190px;
            }
            .sanad-collapse-count {
                align-items: center;
                background: #eeecff;
                border-radius: 999px;
                color: #5f58c9;
                display: inline-flex;
                font-size: 11px;
                font-weight: 700;
                height: 22px;
                justify-content: center;
                min-width: 22px;
                padding: 0 7px;
            }
            .sanad-collapse-caret {
                font-size: 11px;
                transition: transform .18s ease;
            }
            .sanad-collapse-toggle[aria-expanded="true"] .sanad-collapse-caret {
                transform: rotate(180deg);
            }
            .sanad-inline-drawer {
                background: #fff;
                border: 1px solid #e5e9f5;
                border-radius: 8px;
                overflow: hidden;
                padding-top: 12px;
            }
            .sanad-document-grid {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
                padding-top: 14px;
            }
            .sanad-document-tile {
                background: #fff;
                border: 1px solid #edf1f7;
                border-radius: 8px;
                overflow: hidden;
                padding: 12px;
                transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
            }
            .sanad-document-tile:hover {
                border-color: #c8c4f3;
                box-shadow: 0 10px 24px rgba(95, 88, 201, .12);
                transform: translateY(-1px);
            }
            .sanad-document-tile.is-missing {
                background: #fafbff;
                border-style: dashed;
            }
            .sanad-document-preview {
                align-items: center;
                background: #f6f7fb;
                border: 1px solid #edf1f7;
                border-radius: 8px;
                color: #5f58c9;
                cursor: pointer;
                display: flex;
                height: 120px;
                justify-content: center;
                margin: 0 auto 10px;
                overflow: hidden;
                padding: 0;
                position: relative;
                width: 120px;
            }
            .sanad-document-preview img,
            .sanad-document-preview embed {
                border: 0;
                height: 100%;
                object-fit: cover;
                pointer-events: none;
                width: 100%;
            }
            .sanad-file-placeholder {
                align-items: center;
                background: #eeecff;
                border-radius: 999px;
                color: #5f58c9;
                display: flex;
                font-size: 28px;
                height: 64px;
                justify-content: center;
                width: 64px;
            }
            .sanad-pdf-badge {
                background: rgba(31, 41, 55, .84);
                border-radius: 999px;
                bottom: 10px;
                color: #fff;
                font-size: 11px;
                font-weight: 700;
                left: 10px;
                padding: 5px 9px;
                position: absolute;
            }
            .sanad-document-tile-body {
                min-height: 78px;
                padding: 0;
                text-align: center;
            }
            .sanad-document-tile-body strong,
            .sanad-document-tile-body span {
                display: block;
            }
            .sanad-document-tile-body strong {
                color: #1f2937;
                font-size: 14px;
                line-height: 1.3;
                margin-bottom: 6px;
            }
            .sanad-document-tile-body span {
                color: #6b7280;
                font-size: 12px;
                line-height: 1.45;
                overflow-wrap: anywhere;
            }
            .sanad-document-tile-footer {
                align-items: center;
                border-top: 1px solid #edf1f7;
                display: flex;
                flex-direction: column;
                gap: 8px;
                justify-content: space-between;
                padding: 10px 0 0;
            }
            .sanad-tile-actions {
                display: flex;
                gap: 8px;
                justify-content: center;
                width: 100%;
            }
            .sanad-tile-actions .btn {
                min-width: 72px;
            }
            .sanad-document-modal .modal-content {
                border: 0;
                border-radius: 8px;
                display: flex;
                flex-direction: column;
                max-height: calc(100vh - 48px);
                overflow: hidden;
            }
            .sanad-document-modal .modal-dialog {
                max-width: 1100px;
                width: calc(100vw - 48px);
            }
            .sanad-document-modal .modal-header,
            .sanad-document-modal .modal-footer {
                border-color: #edf1f7;
            }
            .sanad-document-modal .modal-body {
                max-width: 100%;
                overflow: auto;
                overflow-x: hidden;
                padding: 16px;
            }
            .sanad-document-modal .modal-footer {
                justify-content: flex-end;
            }
            .sanad-modal-preview {
                align-items: center;
                background: #f5f6fb;
                border: 1px solid #edf1f7;
                border-radius: 8px;
                display: flex;
                justify-content: center;
                max-height: calc(100vh - 230px);
                min-height: 360px;
                overflow: hidden;
                width: 100%;
            }
            .sanad-modal-preview img {
                display: block;
                height: 100%;
                max-height: calc(100vh - 260px);
                max-width: 100%;
                object-fit: contain;
                width: 100%;
            }
            .sanad-modal-preview iframe {
                border: 0;
                height: calc(100vh - 260px);
                min-height: 420px;
                width: 100%;
            }
            .sanad-modal-file {
                align-items: center;
                color: #6b7280;
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 40px;
                text-align: center;
            }
            .sanad-modal-file i {
                color: #5f58c9;
                font-size: 46px;
            }
            .sanad-modal-review-form {
                display: grid;
                gap: 8px;
                grid-template-columns: 160px minmax(220px, 1fr) auto;
                max-width: 680px;
                width: 100%;
            }
            .sanad-partner-review-actions {
                align-items: center;
                display: flex;
                flex-wrap: nowrap;
                gap: 10px;
                justify-content: flex-end;
                width: 100%;
            }
            .sanad-reject-form {
                align-items: center;
                display: flex;
                gap: 8px;
                width: auto;
            }
            .sanad-reject-form .form-control {
                min-width: 260px;
            }
            .sanad-section-note {
                background: #fff8e5;
                border: 1px solid #f4d47c;
                border-radius: 6px;
                color: #66510d;
                padding: 10px 12px;
            }
            .sanad-empty-state,
            .sanad-empty-inline {
                color: #6b7280;
                text-align: center;
            }
            .sanad-empty-state {
                background: #fff;
                border: 1px dashed #d8ddec;
                border-radius: 8px;
                padding: 32px;
            }
            .sanad-empty-inline {
                padding: 16px 0 4px;
            }
            @media (max-width: 991.98px) {
                .sanad-request-meta {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }
            @media (max-width: 767.98px) {
                .sanad-review-row,
                .sanad-request-header {
                    flex-direction: column;
                }
                .sanad-row-actions,
                .sanad-row-actions-wide {
                    align-items: stretch;
                    min-width: 100%;
                    width: 100%;
                }
                .sanad-metric-grid,
                .sanad-request-meta {
                    grid-template-columns: 1fr;
                }
                .sanad-modal-review-form {
                    grid-template-columns: 1fr;
                }
                .sanad-reject-form {
                    flex-wrap: wrap;
                    width: 100%;
                }
                .sanad-partner-review-actions {
                    flex-wrap: wrap;
                }
                .sanad-partner-review-actions .btn,
                .sanad-reject-form .form-control {
                    width: 100%;
                }
            }
        </style>
    @endpush

</x-master-layout>
