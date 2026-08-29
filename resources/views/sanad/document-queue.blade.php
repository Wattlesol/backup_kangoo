<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $partnerDocumentName = function ($document, $fallback = null) use ($isAr) {
        return optional($document)->localized_name
            ?: ($fallback ?: ($isAr ? 'مستند مطلوب' : 'Required document'));
    };
@endphp
<div class="sanad-document-queue quick-document-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="quick-admin-hero quick-documents-hero">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="quick-documents-kicker"><x-quick-icon name="shield" /> {{ $isAr ? 'الامتثال وتدقيق الوثائق الرسمية' : 'Compliance & document intake' }}</div>
                    <h4 class="font-weight-bold mb-1">{{ $isAr ? 'مراجعة المستندات وتوثيق الشركاء' : 'Document Verification & Vault' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'بوابة موحدة لاعتماد ملفات الشركاء وتدقيق مستندات معاملات العملاء.' : 'Unified partner onboarding verification and customer request document review.' }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn">
                        <x-quick-icon name="briefcase" /> {{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}
                    </a>
                </div>
            </div>
        </div>

        <div class="row sanad-summary-grid">
            <div class="col-lg-3 col-md-6">
                <div class="quick-kpi-card sanad-dashboard-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-2 booking-text font-weight-bold">{{ $partnerSummary['partners'] }}</h4>
                            <p class="mb-0 booking-text">{{ $isAr ? "الشركاء" : "Partners" }}</p>
                        </div>
                        <div class="quick-kpi-icon">
                            <x-quick-icon name="briefcase" size="22" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-kpi-card sanad-dashboard-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-2 booking-text font-weight-bold">{{ $partnerSummary['pending'] }}</h4>
                            <p class="mb-0 booking-text">{{ $isAr ? "مستندات الشركاء المعلقة" : "Partner Docs Pending" }}</p>
                        </div>
                        <div class="quick-kpi-icon">
                            <x-quick-icon name="shield" size="22" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-kpi-card sanad-dashboard-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-2 booking-text font-weight-bold">{{ $orderSummary['orders'] }}</h4>
                            <p class="mb-0 booking-text">{{ $isAr ? "طلبات تحتوي مستندات" : "Requests With Docs" }}</p>
                        </div>
                        <div class="quick-kpi-icon">
                            <x-quick-icon name="briefcase" size="22" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="quick-kpi-card sanad-dashboard-stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-2 booking-text font-weight-bold">{{ $orderSummary['pending'] }}</h4>
                            <p class="mb-0 booking-text">{{ $isAr ? "مستندات الطلبات المعلقة" : "Request Docs Pending" }}</p>
                        </div>
                        <div class="quick-kpi-icon">
                            <x-quick-icon name="check" size="22" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="quick-card quick-document-panel">
                <ul class="nav nav-tabs nav-fill tabslink sanad-tabs mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') !== 'orders' ? 'active' : '' }}" data-toggle="tab" href="#partner-documents" role="tab">
                            <i class="fas fa-building mr-2"></i> {{ $isAr ? "توثيق الشركاء" : "Partner Onboarding" }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request('tab') === 'orders' ? 'active' : '' }}" data-toggle="tab" href="#request-documents" role="tab">
                            <i class="fas fa-id-card mr-2"></i> {{ $isAr ? "مستندات الخدمات والطلبات" : "Service Documents" }}
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade {{ request('tab') !== 'orders' ? 'show active' : '' }}" id="partner-documents" role="tabpanel">
                        <form class="sanad-filter-bar mb-3">
                            <input type="hidden" name="tab" value="partners">
                            <div class="form-row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label class="form-control-label">{{ $isAr ? "حالة المراجعة" : "Review Status" }}</label>
                                    <select name="partner_status" class="form-control">
                                        <option value="">{{ $isAr ? "جميع مستندات الشركاء" : "All partner documents" }}</option>
                                        <option value="pending" {{ request('partner_status') === 'pending' || request('partner_status') === '0' ? 'selected' : '' }}>{{ $isAr ? 'قيد المراجعة' : 'Pending Review' }}</option>
                                        <option value="approved" {{ request('partner_status') === 'approved' || request('partner_status') === '1' ? 'selected' : '' }}>{{ $isAr ? 'معتمد' : 'Approved' }}</option>
                                        <option value="rejected" {{ request('partner_status') === 'rejected' ? 'selected' : '' }}>{{ $isAr ? 'مرفوض' : 'Rejected' }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button class="btn btn-primary btn-block">
                                        <i class="fas fa-filter mr-1"></i> {{ $isAr ? "تصفية" : "Filter" }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="row">
                            @forelse($partnerCards as $index => $card)
                                @php $partner = $card['partner']; @endphp
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
                                    <div class="sanad-entity-card">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <h5 class="font-weight-bold mb-1">{{ optional($partner)->display_name ?: 'Partner' }}</h5>
                                                <div class="text-muted small">{{ optional($partner)->email ?: 'No email' }}</div>
                                                <div class="text-muted small">{{ optional($partner)->contact_number ?: 'No phone' }}</div>
                                            </div>
                                            <span class="badge badge-{{ $card['status'] === 'verified' ? 'success' : ($card['status'] === 'in_review' ? 'warning' : 'light') }}">
                                                {{ $card['status'] === 'verified' ? ($isAr ? 'موثق ومعتمد' : 'Verified') : ($card['status'] === 'in_review' ? ($isAr ? 'قيد المراجعة' : 'In Review') : ($isAr ? 'غير مكتمل' : Str::headline($card['status']))) }}
                                            </span>
                                        </div>

                                        <div class="sanad-metric-grid mb-3">
                                            <div><span>{{ $isAr ? 'معتمد' : 'Approved' }}</span><strong>{{ $card['approved'] }}/{{ $card['total'] }}</strong></div>
                                            <div><span>{{ $isAr ? 'تم الرفع' : 'Uploaded' }}</span><strong>{{ $card['uploaded'] }}</strong></div>
                                            <div><span>{{ $isAr ? 'معلق' : 'Pending' }}</span><strong>{{ $card['pending'] }}</strong></div>
                                        </div>
                                        <div class="progress sanad-progress mb-3">
                                            <div class="progress-bar" style="width: {{ $card['progress'] }}%"></div>
                                        </div>

                                        <div class="sanad-mini-list mb-3">
                                            @foreach($card['documents']->take(3) as $document)
                                                @php $hasUpload = getMediaFileExit($document, 'provider_document'); @endphp
                                                <div class="sanad-mini-row">
                                                    <span>{{ $partnerDocumentName($document->document) }}</span>
                                                    <span class="text-{{ $document->verification_status === 'rejected' ? 'danger' : ($document->is_verified ? 'success' : ($hasUpload ? 'warning' : 'muted')) }}">
                                                        {{ $document->verification_status === 'rejected' ? ($isAr ? 'مرفوض' : 'Rejected') : ($document->is_verified ? ($isAr ? 'معتمد' : 'Approved') : ($hasUpload ? ($isAr ? 'قيد المراجعة' : 'Review') : ($isAr ? 'مفقود' : 'Missing'))) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                            @if($card['documents']->count() > 3)
                                                <div class="small text-muted mt-1">+{{ $card['documents']->count() - 3 }} {{ $isAr ? 'متطلبات إضافية' : 'more requirements' }}</div>
                                            @endif
                                        </div>

                                        <div class="sanad-card-actions">
                                            <a href="{{ route('provider.show', ['provider' => optional($partner)->id]) }}" class="btn btn-sm sanad-action-button sanad-profile-link">{{ $isAr ? "فتح ملف الشريك" : "Open Partner Profile" }}</a>
                                            <button class="btn btn-sm sanad-action-button sanad-collapse-toggle collapsed" type="button" data-toggle="collapse" data-target="#partner-drawer-{{ $index }}" aria-expanded="false" aria-controls="partner-drawer-{{ $index }}">
                                                <span class="sanad-collapse-label"><i class="fas fa-list-alt mr-1"></i> {{ $isAr ? "قائمة المستندات المطلوبة" : "Document checklist" }}</span>
                                                <span class="sanad-collapse-count">{{ $card['documents']->count() }}</span>
                                            </button>
                                        </div>

                                        <div class="collapse sanad-inline-drawer mt-3" id="partner-drawer-{{ $index }}">
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ $isAr ? "المستند" : "Document" }}</th>
                                                            <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                                                            <th class="text-right">{{ $isAr ? "الإجراء" : "Action" }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($card['documents'] as $document)
                                                            @php $hasUpload = getMediaFileExit($document, 'provider_document'); @endphp
                                                            @php $media = $hasUpload ? $document->getFirstMedia('provider_document') : null; @endphp
                                                            @php $mediaUrl = $media ? $media->getFullUrl() : null; @endphp
                                                            @php $mimeType = optional($media)->mime_type ?: ''; @endphp
                                                            @php $isImage = Str::startsWith($mimeType, 'image/'); @endphp
                                                            @php $isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) optional($media)->file_name), '.pdf'); @endphp
                                                            <tr>
                                                                <td>
                                                                    <strong>{{ $partnerDocumentName($document->document) }}</strong>
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
                                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#partnerDocumentModal{{ $document->id }}">{{ $isAr ? "عرض" : "View" }}</button>
                                                                    @else
                                                                        <span class="text-muted small">{{ $isAr ? "بانتظار الرفع" : "Waiting upload" }}</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            @foreach($card['documents'] as $document)
                                                @php $hasUpload = getMediaFileExit($document, 'provider_document'); @endphp
                                                @php $media = $hasUpload ? $document->getFirstMedia('provider_document') : null; @endphp
                                                @php $mediaUrl = $media ? $media->getFullUrl() : null; @endphp
                                                @php $mimeType = optional($media)->mime_type ?: ''; @endphp
                                                @php $isImage = Str::startsWith($mimeType, 'image/'); @endphp
                                                @php $isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) optional($media)->file_name), '.pdf'); @endphp
                                                @if($hasUpload)
                                                    <div class="modal fade sanad-document-modal" id="partnerDocumentModal{{ $document->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <div>
                                                                        <h5 class="modal-title">{{ $partnerDocumentName($document->document, $isAr ? 'مستند الشريك' : 'Partner document') }}</h5>
                                                                        <div class="small text-muted">{{ optional($partner)->display_name ?: 'Partner' }} · {{ optional($media)->file_name }}</div>
                                                                    </div>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="sanad-modal-preview">
                                                                        @if($isImage)
                                                                            <img src="{{ $mediaUrl }}" alt="{{ $partnerDocumentName($document->document, $isAr ? 'مستند الشريك' : 'Partner document') }}">
                                                                        @elseif($isPdf)
                                                                            <iframe src="{{ $mediaUrl }}" title="{{ $partnerDocumentName($document->document, $isAr ? 'مستند الشريك' : 'Partner document') }}"></iframe>
                                                                        @else
                                                                            <div class="sanad-modal-file"><i class="fas fa-file-alt"></i><span>{{ $isAr ? "المعاينة غير متاحة لهذا النوع من الملفات." : "Preview is not available for this file type." }}</span><a target="_blank" href="{{ $mediaUrl }}">{{ $isAr ? "تحميل الملف" : "Download file" }}</a></div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <div class="sanad-partner-review-actions">
                                                                        <form method="POST" action="{{ route('sanad.partner-documents.review', $document->id) }}" class="mb-0">
                                                                            @csrf
                                                                            <input type="hidden" name="verification_status" value="approved">
                                                                            <button class="btn btn-primary">{{ $isAr ? "اعتماد المستند" : "Approve Document" }}</button>
                                                                        </form>
                                                                        <form method="POST" action="{{ route('sanad.partner-documents.review', $document->id) }}" class="sanad-reject-form mb-0">
                                                                            @csrf
                                                                            <input type="hidden" name="verification_status" value="rejected">
                                                                            <input name="review_reason" class="form-control" placeholder="{{ $isAr ? 'سبب الرفض' : 'Reason for rejection' }}" required>
                                                                            <button class="btn btn-outline-danger">{{ $isAr ? "رفض" : "Reject" }}</button>
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
                                    <label class="form-control-label">{{ $isAr ? "حالة المراجعة" : "Review Status" }}</label>
                                    <select name="order_status" class="form-control">
                                        <option value="">{{ $isAr ? "جميع مستندات الطلبات" : "All service documents" }}</option>
                                        @foreach(['pending','approved','rejected','replacement_requested'] as $status)
                                            <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label class="form-control-label">{{ $isAr ? "مرسل بواسطة" : "Submitted By" }}</label>
                                    <select name="source" class="form-control">
                                        <option value="">{{ $isAr ? "العميل والشريك" : "Customer and Partner" }}</option>
                                        <option value="customer" {{ request('source') === 'customer' ? 'selected' : '' }}>{{ $isAr ? "العميل" : "Customer" }}</option>
                                        <option value="partner" {{ request('source') === 'partner' ? 'selected' : '' }}>{{ $isAr ? "الشريك" : "Partner" }}</option>
                                        <option value="request" {{ request('source') === 'request' ? 'selected' : '' }}>{{ $isAr ? "رفع من تفاصيل الطلب" : "Request Upload" }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-2">
                                    <button class="btn btn-primary btn-block">
                                        <i class="fas fa-filter mr-1"></i> {{ $isAr ? "تصفية" : "Filter" }}
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="row">
                            @forelse($requestCards as $requestIndex => $card)
                                @php $booking = $card['booking']; @endphp
                                @php $service = $card['service']; @endphp
                                <div class="col-xl-4 col-lg-6 col-md-6 mb-3">
                                    <div class="sanad-entity-card h-100">
                                        <div class="sanad-request-header mb-3">
                                            <div>
                                                <div class="small text-muted">{{ optional($booking)->quick_reference ?: 'QUICK-'.str_pad((string) optional($booking)->id, 6, '0', STR_PAD_LEFT) }}</div>
                                                <h5 class="font-weight-bold mb-1">{{ optional(optional($booking)->customer)->display_name ?: 'Customer' }}</h5>
                                                <div class="text-muted">{{ optional($service)->name_en ?: optional($service)->name ?: 'Service' }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="badge badge-light">{{ Str::headline(optional($booking)->sanad_stage ?: optional($booking)->status ?: 'pending') }}</span>
                                            </div>
                                        </div>

                                        <div class="sanad-request-meta mb-3">
                                            <div><span>{{ $isAr ? "الشريك المسند" : "Assigned Partner" }}</span><strong>{{ optional(optional($booking)->provider)->display_name ?: 'Unassigned' }}</strong></div>
                                            <div><span>{{ $isAr ? "مطلوب" : "Required" }}</span><strong>{{ $card['required_count'] }}</strong></div>
                                            <div><span>{{ $isAr ? "تم الإرسال" : "Submitted" }}</span><strong>{{ $card['submitted_count'] }}</strong></div>
                                            <div><span>{{ $isAr ? "معتمد" : "Approved" }}</span><strong>{{ $card['approved_count'] }}</strong></div>
                                        </div>

                                        <div class="progress sanad-progress mb-3">
                                            <div class="progress-bar" style="width: {{ $card['progress'] }}%"></div>
                                        </div>

                                        @if($card['missing_required']->isNotEmpty())
                                            <div class="sanad-section-note mb-3">
                                                <strong>{{ $isAr ? "مستندات مطلوبة مفقودة:" : "Missing required:" }}</strong>
                                                {{ $card['missing_required']->map(fn ($document) => localized_service_document_name($document))->filter()->join(', ') ?: ($isAr ? 'مستند مطلوب قيد الانتظار' : 'Required document pending') }}
                                            </div>
                                        @endif

                                        <div class="sanad-card-actions">
                                            @if($booking)
                                                <a href="{{ route('sanad.requests.show', $booking->id) }}" class="btn btn-sm sanad-action-button sanad-profile-link">{{ $isAr ? "فتح تفاصيل الطلب" : "Open Request Details" }}</a>
                                            @endif
                                            <button class="btn btn-sm sanad-action-button sanad-collapse-toggle collapsed" type="button" data-toggle="collapse" data-target="#request-documents-drawer-{{ $requestIndex }}" aria-expanded="false" aria-controls="request-documents-drawer-{{ $requestIndex }}">
                                                <span class="sanad-collapse-label"><i class="fas fa-list-alt mr-1"></i> {{ $isAr ? "طلبات المستندات" : "Document requests" }}</span>
                                                <span class="sanad-collapse-count">{{ $card['documents']->count() + $card['document_requests']->count() }}</span>
                                            </button>
                                        </div>

                                        <div class="collapse sanad-inline-drawer mt-3" id="request-documents-drawer-{{ $requestIndex }}">
                                            <div class="sanad-drawer-heading">{{ $isAr ? "المستندات المرسلة" : "Submitted documents" }}</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ $isAr ? "المستند" : "Document" }}</th>
                                                            <th>{{ $isAr ? "مرسل بواسطة" : "Submitted By" }}</th>
                                                            <th>{{ $isAr ? "تاريخ الرفع" : "Uploaded" }}</th>
                                                            <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                                                            <th class="text-right">{{ $isAr ? "الإجراء" : "Action" }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            @forelse($card['documents'] as $document)
                                                @php $media = $document->getFirstMedia('document') ?: $document->getFirstMedia('sanad_document'); @endphp
                                                @php $documentUrl = $media ? $media->getFullUrl() : $document->file_path; @endphp
                                                @php $fileName = optional($media)->file_name ?: $document->file_name ?: 'Uploaded document'; @endphp
                                                @php $mimeType = optional($media)->mime_type ?: ''; @endphp
                                                @php $isImage = Str::startsWith($mimeType, 'image/'); @endphp
                                                @php $isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) $fileName), '.pdf') || Str::endsWith(Str::lower((string) $documentUrl), '.pdf'); @endphp
                                                <tr>
                                                    <td>
                                                        <strong>{{ Str::headline($document->document_type) }}</strong>
                                                        <div class="small text-muted">{{ $fileName }}</div>
                                                        @if($document->review_reason)
                                                            <div class="small text-danger">{{ $document->review_reason }}</div>
                                                        @endif
                                                    </td>
                                                    <td>{{ $isAr ? ($document->source === 'customer' ? 'العميل' : ($document->source === 'partner' ? 'الشريك' : 'الطلب')) : Str::headline($document->source ?: 'request') }}</td>
                                                    <td>{{ optional($document->created_at)->format('M d, Y') ?: '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $document->verification_status === 'approved' ? 'success' : ($document->verification_status === 'rejected' ? 'danger' : 'warning') }}">{{ $isAr ? ($document->verification_status === 'approved' ? 'معتمد' : ($document->verification_status === 'rejected' ? 'مرفوض' : ($document->verification_status === 'uploaded' ? 'تم الرفع' : 'قيد المراجعة'))) : Str::headline($document->verification_status ?: 'pending') }}</span>
                                                    </td>
                                                    <td class="text-right">
                                                        @if($documentUrl)
                                                            <button type="button" class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#requestDocumentModal{{ $document->id }}">{{ $isAr ? "عرض" : "View" }}</button>
                                                        @else
                                                            <span class="text-muted small">{{ $isAr ? "لا يوجد ملف" : "No file" }}</span>
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
                                                                            <div class="sanad-modal-file"><i class="fas fa-file-alt"></i><span>{{ $isAr ? "المعاينة غير متاحة لهذا النوع من الملفات." : "Preview is not available for this file type." }}</span><a target="_blank" href="{{ $documentUrl }}">{{ $isAr ? "تحميل الملف" : "Download file" }}</a></div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    @if($booking)
                                                                        <form method="POST" action="{{ route('sanad.requests.documents.review', [$booking->id, $document->id]) }}" class="sanad-modal-review-form mb-0">
                                                                            @csrf
                                                                            <select name="verification_status" class="form-control">
                                                                                <option value="approved">{{ $isAr ? "اعتماد" : "Approve" }}</option>
                                                                                <option value="rejected">{{ $isAr ? "رفض" : "Reject" }}</option>
                                                                                <option value="replacement_requested">{{ $isAr ? "طلب استبدال" : "Request replacement" }}</option>
                                                                            </select>
                                                                            <input name="reason" class="form-control" placeholder="{{ $isAr ? 'سبب الرفض أو طلب الاستبدال' : 'Reason for rejection/replacement' }}">
                                                                            <button class="btn btn-primary">{{ $isAr ? "حفظ المراجعة" : "Save Review" }}</button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif
                                            @empty
                                                <tr><td colspan="5" class="text-muted text-center">{{ $isAr ? "لا توجد مستندات مرسلة حتى الآن." : "No submitted documents yet." }}</td></tr>
                                            @endforelse
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="sanad-drawer-heading mt-3">{{ $isAr ? "طلبات مستندات إضافية" : "Additional document requests" }}</div>
                                            <div class="table-responsive">
                                                <table class="table table-sm table-hover mb-0 sanad-document-table">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ $isAr ? "المستند" : "Document" }}</th>
                                                            <th>{{ $isAr ? "مطلوب من" : "Requested From" }}</th>
                                                            <th>{{ $isAr ? "تاريخ الاستحقاق" : "Due" }}</th>
                                                            <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                                                            <th>{{ $isAr ? "الملف" : "File" }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                            @forelse($card['document_requests'] as $documentRequest)
                                                <tr>
                                                    <td>
                                                        <strong>{{ $documentRequest->document_name }}</strong>
                                                        <div class="small text-muted">{{ $documentRequest->reason }}</div>
                                                        <div class="small text-muted">By {{ optional($documentRequest->requester)->display_name ?: 'Quick Team' }}</div>
                                                    </td>
                                                    <td>{{ $isAr ? ($documentRequest->requested_from === 'customer' ? 'العميل' : ($documentRequest->requested_from === 'partner' ? 'الشريك' : 'الفريق')) : Str::headline($documentRequest->requested_from) }}</td>
                                                    <td>{{ $documentRequest->due_at ? $documentRequest->due_at->format('M d, Y') : '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $documentRequest->status === 'approved' ? 'success' : ($documentRequest->status === 'rejected' ? 'danger' : 'warning') }}">
                                                            {{ $isAr ? ($documentRequest->status === 'approved' ? 'معتمد' : ($documentRequest->status === 'rejected' ? 'مرفوض' : ($documentRequest->status === 'submitted' ? 'تم الإرسال' : 'قيد الانتظار'))) : Str::headline($documentRequest->status ?: 'pending') }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        @php $requestDocumentUrl = $documentRequest->document ? ($documentRequest->document->getFirstMediaUrl('document') ?: $documentRequest->document->getFirstMediaUrl('sanad_document') ?: $documentRequest->document->file_path) : null; @endphp
                                                        @if($requestDocumentUrl)
                                                            <a target="_blank" href="{{ $requestDocumentUrl }}">{{ $isAr ? "الملف المرسل" : "Submitted file" }}</a>
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

    <style>
            .quick-document-page {
                max-width: 1180px;
                margin: 0 auto;
            }

            .quick-document-page .text-muted,
            .quick-document-page .small {
                color: var(--quick-shell-muted) !important;
            }

            .quick-documents-hero h4 {
                color: var(--quick-shell-ink);
                font-size: clamp(22px, 2.5vw, 32px);
                font-weight: 900;
            }

            .quick-documents-kicker {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border-radius: 999px;
                padding: 6px 14px;
                margin-bottom: 12px;
                color: var(--quick-blue);
                background: rgba(31,107,255,.1);
                font-size: 12px;
                font-weight: 900;
            }

            .quick-document-panel {
                overflow: hidden;
            }

            .quick-document-page .quick-kpi-card {
                min-height: 118px;
                margin-bottom: 20px;
            }

            .quick-document-page .quick-kpi-icon {
                color: var(--quick-blue);
                background: rgba(31,107,255,.1);
            }

            .quick-document-page .booking-text {
                color: var(--quick-shell-ink) !important;
            }

            .quick-document-page .form-control {
                min-height: 42px;
                border-color: var(--quick-shell-line);
                border-radius: 12px;
                color: var(--quick-shell-ink);
                background: var(--quick-shell-surface);
                box-shadow: none;
            }

            .quick-document-page .btn-primary {
                border-color: var(--quick-blue);
                border-radius: 12px;
                background: var(--quick-blue);
                color: #fff;
                font-size: 12px;
                font-weight: 900;
                min-height: 38px;
                padding: 8px 14px;
            }

            .quick-document-page .nav-tabs {
                display: inline-flex;
                gap: 6px;
                width: auto;
                border: 0;
                border-radius: 14px;
                padding: 5px;
                background: color-mix(in srgb, var(--quick-shell-bg) 82%, transparent);
            }

            .quick-document-page .nav-tabs .nav-link {
                border: 0;
                border-radius: 10px;
                color: var(--quick-shell-muted);
                font-size: 12px;
                font-weight: 900;
                padding: 10px 14px;
            }

            .quick-document-page .nav-tabs .nav-link.active {
                color: #fff;
                background: var(--quick-blue);
                box-shadow: 0 8px 18px rgba(31,107,255,.16);
            }

            .sanad-document-queue .card,
            .sanad-entity-card {
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                box-shadow: 0 10px 28px rgba(31, 41, 55, .04);
            }
            .sanad-entity-card {
                background: var(--quick-shell-surface);
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
                background: color-mix(in srgb, var(--quick-shell-surface) 90%, var(--quick-shell-bg));
                border-color: var(--quick-shell-line);
                box-shadow: 0 14px 34px rgba(63, 63, 85, .10);
            }
            #partner-documents .sanad-entity-card:hover {
                border-color: #b9c2ef;
                box-shadow: 0 18px 42px rgba(95, 88, 201, .16);
            }
            .sanad-metric-grid span,
            .sanad-request-meta span {
                color: var(--quick-shell-muted);
                display: block;
                font-size: 12px;
            }
            .sanad-filter-bar {
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
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
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
                border: 1px solid var(--quick-shell-line);
                border-radius: 12px;
                padding: 10px;
            }
            .sanad-metric-grid strong,
            .sanad-request-meta strong {
                color: var(--quick-shell-ink);
                display: block;
                font-size: 16px;
                line-height: 1.3;
                margin-top: 3px;
            }
            .sanad-progress {
                background: color-mix(in srgb, var(--quick-shell-bg) 80%, transparent);
                height: 7px;
                border-radius: 999px;
            }
            .sanad-progress .progress-bar {
                background: var(--quick-blue);
            }
            .sanad-mini-list,
            .sanad-review-list {
                border-top: 1px solid var(--quick-shell-line);
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
                border-bottom: 1px solid var(--quick-shell-line);
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
                border-top: 1px solid var(--quick-shell-line);
                display: flex;
                flex-wrap: nowrap;
                gap: 8px;
                justify-content: space-between;
                padding-top: 12px;
            }
            .sanad-action-button {
                align-items: center;
                border: 1px solid transparent;
                border-radius: 10px;
                color: var(--quick-blue);
                display: inline-flex;
                font-size: 11px;
                font-weight: 700;
                justify-content: center;
                letter-spacing: .04em;
                line-height: 1.2;
                min-height: 36px;
                padding: 8px 9px;
                text-transform: uppercase;
                transition: background-color .18s ease, border-color .18s ease, color .18s ease;
            }
            .sanad-action-button:hover,
            .sanad-action-button:focus {
                background: var(--quick-blue);
                border-color: var(--quick-blue);
                color: #fff;
                text-decoration: none;
            }
            .sanad-card-actions .sanad-profile-link {
                flex: 1 1 0;
                min-width: 0;
                text-align: center;
                white-space: nowrap;
            }
            .sanad-collapse-toggle {
                align-items: center;
                display: inline-grid;
                flex: 1 1 0;
                gap: 8px;
                grid-template-columns: minmax(0, 1fr) auto;
                justify-content: initial;
                max-width: 100%;
                min-width: 0;
            }
            .sanad-collapse-label {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .sanad-collapse-count {
                align-items: center;
                background: rgba(31,107,255,.1);
                border-radius: 999px;
                color: var(--quick-blue);
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
                background: var(--quick-shell-surface);
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                overflow: hidden;
                padding-top: 12px;
            }
            .sanad-drawer-heading {
                color: var(--quick-shell-ink);
                font-size: 12px;
                font-weight: 700;
                letter-spacing: .04em;
                padding: 0 12px 8px;
                text-transform: uppercase;
            }
            .sanad-document-grid {
                display: grid;
                gap: 16px;
                grid-template-columns: repeat(auto-fill, minmax(168px, 1fr));
                padding-top: 14px;
            }
            .sanad-document-tile {
                background: var(--quick-shell-surface);
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
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
                background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
                border-style: dashed;
            }
            .sanad-document-preview {
                align-items: center;
                background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
                border: 1px solid var(--quick-shell-line);
                border-radius: 12px;
                color: var(--quick-blue);
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
                background: rgba(31,107,255,.1);
                border-radius: 999px;
                color: var(--quick-blue);
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
                color: var(--quick-shell-ink);
                font-size: 14px;
                line-height: 1.3;
                margin-bottom: 6px;
            }
            .sanad-document-tile-body span {
                color: var(--quick-shell-muted);
                font-size: 12px;
                line-height: 1.45;
                overflow-wrap: anywhere;
            }
            .sanad-document-tile-footer {
                align-items: center;
                border-top: 1px solid var(--quick-shell-line);
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
                border-radius: 16px;
                display: flex;
                flex-direction: column;
                max-height: calc(100vh - 48px);
                max-width: 100%;
                overflow: hidden;
                width: 100%;
            }
            .sanad-document-modal .modal-dialog {
                margin-left: auto;
                margin-right: auto;
                max-width: 960px;
                width: min(960px, calc(100vw - 48px));
            }
            .sanad-document-modal .modal-header,
            .sanad-document-modal .modal-footer {
                border-color: var(--quick-shell-line);
            }
            .sanad-document-modal .modal-body {
                flex: 1 1 auto;
                min-height: 0;
                min-width: 0;
                overflow: hidden;
                padding: 16px;
            }
            .sanad-document-modal .modal-footer {
                justify-content: flex-end;
            }
            .sanad-modal-preview {
                align-items: center;
                background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                display: flex;
                justify-content: center;
                height: 62vh;
                min-height: 360px;
                max-width: 100%;
                overflow: hidden;
                position: relative;
                width: 100%;
            }
            .sanad-modal-preview img {
                bottom: 0;
                display: block;
                height: 100%;
                left: 0;
                max-height: none;
                max-width: none;
                object-fit: contain;
                position: absolute;
                right: 0;
                top: 0;
                width: 100%;
            }
            .sanad-modal-preview iframe {
                border: 0;
                height: 100%;
                width: 100%;
            }
            .sanad-modal-file {
                align-items: center;
                color: var(--quick-shell-muted);
                display: flex;
                flex-direction: column;
                gap: 12px;
                padding: 40px;
                text-align: center;
            }
            .sanad-modal-file i {
                color: var(--quick-blue);
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
                border-radius: 12px;
                color: #66510d;
                padding: 10px 12px;
            }
            .sanad-empty-state,
            .sanad-empty-inline {
                color: var(--quick-shell-muted);
                text-align: center;
            }
            .sanad-empty-state {
                background: var(--quick-shell-surface);
                border: 1px dashed var(--quick-shell-line);
                border-radius: 14px;
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
                .quick-document-page {
                    max-width: none;
                }

                .quick-document-page .nav-tabs {
                    display: grid;
                    width: 100%;
                }

                .quick-documents-hero,
                .quick-document-panel {
                    padding: 16px;
                    border-radius: 18px;
                }

                .quick-document-page .quick-table-btn,
                .quick-document-page .btn-primary,
                .quick-document-page .sanad-action-button {
                    width: 100%;
                }

                .sanad-card-actions {
                    display: grid;
                }

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

</x-master-layout>
