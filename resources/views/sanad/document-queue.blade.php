<x-master-layout>
@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $partnerDocumentName = function ($document, $fallback = null) use ($isAr) {
        return optional($document)->localized_name
            ?: ($fallback ?: ($isAr ? 'مستند مطلوب' : 'Required document'));
    };
@endphp
<div class="sanad-document-queue quick-document-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <!-- 1. Hero Header Banner -->
    <div class="quick-admin-hero quick-documents-hero">
        <div class="quick-admin-hero-content">
            <div class="quick-admin-hero-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>{{ $isAr ? 'الامتثال وتدقيق الوثائق الرسمية' : 'Compliance & Document Intake' }}</span>
            </div>
            <h1>{{ $isAr ? 'مراجعة المستندات وتوثيق الشركاء' : 'Document Verification & Vault' }}</h1>
            <p>{{ $isAr ? 'بوابة موحدة لاعتماد ملفات الشركاء وتدقيق مستندات معاملات العملاء ومتابعة النواقص والمتطلبات.' : 'Unified partner onboarding verification, customer request document review, and missing requirement tracking.' }}</p>
        </div>

        <div class="quick-admin-hero-actions">
            <a href="{{ route('sanad.requests.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>{{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}</span>
            </a>
        </div>
    </div>

    <!-- 2. KPI Summary Grid -->
    <div class="quick-kpi-grid">
        <!-- Metric 1: Partners -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? "إجمالي الشركاء" : "Total Partners" }}</span>
                <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $partnerSummary['partners'] ?? 0 }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $partnerSummary['partners'] ?? 0 }}</b>
                <span>{{ $isAr ? 'شريك مسجل بالنظام' : 'registered partners' }}</span>
            </div>
        </div>

        <!-- Metric 2: Partner Docs Pending -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? "مستندات الشركاء المعلقة" : "Partner Docs Pending" }}</span>
                <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value" style="color: #f59e0b;">{{ $partnerSummary['pending'] ?? 0 }}</div>
            <div class="quick-kpi-sub">
                <b style="color: #f59e0b;">{{ $partnerSummary['pending'] ?? 0 }}</b>
                <span>{{ $isAr ? 'ملف بانتظار الاعتماد' : 'files pending review' }}</span>
            </div>
        </div>

        <!-- Metric 3: Requests With Docs -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? "طلبات تحتوي مستندات" : "Requests With Docs" }}</span>
                <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $orderSummary['orders'] ?? 0 }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $orderSummary['orders'] ?? 0 }}</b>
                <span>{{ $isAr ? 'طلب بخدمات حكومية' : 'active orders' }}</span>
            </div>
        </div>

        <!-- Metric 4: Request Docs Pending -->
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span>{{ $isAr ? "مستندات الطلبات المعلقة" : "Request Docs Pending" }}</span>
                <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value" style="color: #10b981;">{{ $orderSummary['pending'] ?? 0 }}</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up">{{ $orderSummary['pending'] ?? 0 }}</b>
                <span>{{ $isAr ? 'مستند يحتاج مراجعة' : 'awaiting verification' }}</span>
            </div>
        </div>
    </div>

    <!-- 3. Document Tabs Panel -->
    <div class="quick-card quick-document-panel">
        <div class="quick-card-header" style="border-bottom: 1px solid var(--quick-shell-line); padding-bottom: 16px;">
            <ul class="nav quick-filter-pills" role="tablist" style="list-style:none;padding:4px;margin:0;display:inline-flex;gap:4px;">
                <li class="nav-item" role="presentation" style="margin:0;">
                    <a class="nav-link quick-tab-pill {{ request('tab') !== 'orders' ? 'active' : '' }}" data-toggle="tab" href="#partner-documents" role="tab" aria-selected="{{ request('tab') !== 'orders' ? 'true' : 'false' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        <span>{{ $isAr ? "توثيق الشركاء" : "Partner Onboarding" }}</span>
                    </a>
                </li>
                <li class="nav-item" role="presentation" style="margin:0;">
                    <a class="nav-link quick-tab-pill {{ request('tab') === 'orders' ? 'active' : '' }}" data-toggle="tab" href="#request-documents" role="tab" aria-selected="{{ request('tab') === 'orders' ? 'true' : 'false' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                        <span>{{ $isAr ? "مستندات الخدمات والطلبات" : "Service Documents" }}</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="tab-content" style="padding-top: 20px;">
            <!-- Tab 1: Partner Onboarding -->
            <div class="tab-pane fade {{ request('tab') !== 'orders' ? 'show active' : '' }}" id="partner-documents" role="tabpanel">
                <form class="sanad-filter-bar mb-4">
                    <input type="hidden" name="tab" value="partners">
                    <div style="display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;">
                        <div style="flex: 1 1 240px; min-width: 200px;">
                            <label class="quick-filter-label">{{ $isAr ? "حالة المراجعة" : "Review Status" }}</label>
                            <select name="partner_status" class="form-control quick-select">
                                <option value="">{{ $isAr ? "جميع مستندات الشركاء" : "All partner documents" }}</option>
                                <option value="pending" {{ request('partner_status') === 'pending' || request('partner_status') === '0' ? 'selected' : '' }}>{{ $isAr ? 'قيد المراجعة' : 'Pending Review' }}</option>
                                <option value="approved" {{ request('partner_status') === 'approved' || request('partner_status') === '1' ? 'selected' : '' }}>{{ $isAr ? 'معتمد' : 'Approved' }}</option>
                                <option value="rejected" {{ request('partner_status') === 'rejected' ? 'selected' : '' }}>{{ $isAr ? 'مرفوض' : 'Rejected' }}</option>
                            </select>
                        </div>
                        <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height: 42px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span>{{ $isAr ? "تصفية" : "Filter" }}</span>
                        </button>
                    </div>
                </form>

                <div class="row">
                    @forelse($partnerCards as $index => $card)
                        @php $partner = $card['partner']; @endphp
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="sanad-entity-card">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                    <div style="min-width: 0;">
                                        <h5 class="sanad-card-title">{{ optional($partner)->display_name ?: 'Partner' }}</h5>
                                        <div class="sanad-card-subtitle">{{ optional($partner)->email ?: 'No email' }}</div>
                                        <div class="sanad-card-subtitle">{{ optional($partner)->contact_number ?: 'No phone' }}</div>
                                    </div>
                                    <span class="quick-badge quick-badge-{{ $card['status'] === 'verified' ? 'success' : ($card['status'] === 'in_review' ? 'warning' : 'neutral') }}">
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
                                            <span class="text-truncate">{{ $partnerDocumentName($document->document) }}</span>
                                            <span class="quick-text-{{ $document->verification_status === 'rejected' ? 'danger' : ($document->is_verified ? 'success' : ($hasUpload ? 'warning' : 'muted')) }}">
                                                {{ $document->verification_status === 'rejected' ? ($isAr ? 'مرفوض' : 'Rejected') : ($document->is_verified ? ($isAr ? 'معتمد' : 'Approved') : ($hasUpload ? ($isAr ? 'قيد المراجعة' : 'Review') : ($isAr ? 'مفقود' : 'Missing'))) }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @if($card['documents']->count() > 3)
                                        <div class="small text-muted mt-1">+{{ $card['documents']->count() - 3 }} {{ $isAr ? 'متطلبات إضافية' : 'more requirements' }}</div>
                                    @endif
                                </div>

                                <div class="sanad-card-actions">
                                    <a href="{{ route('provider.show', ['provider' => optional($partner)->id]) }}" class="quick-card-btn quick-card-btn-outline">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        <span>{{ $isAr ? "فتح الملف" : "Profile" }}</span>
                                    </a>
                                    <button class="quick-card-btn quick-card-btn-checklist collapsed" type="button" data-toggle="collapse" data-target="#partner-drawer-{{ $index }}" aria-expanded="false" aria-controls="partner-drawer-{{ $index }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        <span>{{ $isAr ? "المستندات" : "Checklist" }}</span>
                                        <span class="quick-count-pill">{{ $card['documents']->count() }}</span>
                                    </button>
                                </div>

                                <div class="collapse sanad-inline-drawer" id="partner-drawer-{{ $index }}">
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 sanad-document-table">
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
                                                            <strong style="color:var(--quick-shell-ink);font-size:12px;">{{ $partnerDocumentName($document->document) }}</strong>
                                                            <div class="small text-muted" style="margin-top:2px;">{{ optional($document->document)->is_required ? ($isAr ? 'إلزامي' : 'Required') : ($isAr ? 'اختياري' : 'Optional') }} · {{ $hasUpload ? (optional($media)->file_name ?: 'Uploaded') : ($isAr ? 'لم يُرفع بعد' : 'Not uploaded yet') }}</div>
                                                            @if($document->review_reason)
                                                                <div class="small text-danger" style="margin-top:2px;">{{ $document->review_reason }}</div>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @php
                                                                $vStatus = $document->verification_status ?: ($document->is_verified ? 'approved' : 'pending');
                                                            @endphp
                                                            <span class="quick-badge quick-badge-{{ $vStatus === 'approved' ? 'success' : ($vStatus === 'rejected' ? 'danger' : 'warning') }}">
                                                                {{ $vStatus === 'approved' ? ($isAr ? 'معتمد' : 'Approved') : ($vStatus === 'rejected' ? ($isAr ? 'مرفوض' : 'Rejected') : ($isAr ? 'قيد المراجعة' : 'Pending')) }}
                                                            </span>
                                                        </td>
                                                        <td class="text-right">
                                                            @if($hasUpload)
                                                                <button type="button" class="quick-drawer-action-btn" data-toggle="modal" data-target="#partnerDocumentModal{{ $document->id }}">
                                                                    {{ $isAr ? "معاينة" : "View" }}
                                                                </button>
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
                                                                <h5 class="modal-title font-weight-bold">{{ $partnerDocumentName($document->document, $isAr ? 'مستند الشريك' : 'Partner document') }}</h5>
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
                                                            <form method="POST" action="{{ route('sanad.partner-documents.review', $document->id) }}" class="sanad-modal-review-form mb-0">
                                                                @csrf
                                                                <select name="verification_status" class="form-control quick-select">
                                                                    <option value="approved">{{ $isAr ? "اعتماد" : "Approve" }}</option>
                                                                    <option value="rejected">{{ $isAr ? "رفض" : "Reject" }}</option>
                                                                </select>
                                                                <input name="reason" class="form-control quick-input-text" placeholder="{{ $isAr ? 'سبب الرفض (اختياري)' : 'Rejection reason (optional)' }}">
                                                                <button class="quick-admin-hero-btn quick-admin-hero-btn-primary">{{ $isAr ? "حفظ المراجعة" : "Save Review" }}</button>
                                                            </form>
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
                            <div class="sanad-empty-state">{{ $isAr ? "لا توجد مستندات شركاء مطابقة للبحث." : "No partner documents found." }}</div>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Tab 2: Service / Request Documents -->
            <div class="tab-pane fade {{ request('tab') === 'orders' ? 'show active' : '' }}" id="request-documents" role="tabpanel">
                <form class="sanad-filter-bar mb-4">
                    <input type="hidden" name="tab" value="orders">
                    <div style="display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap;">
                        <div style="flex: 1 1 200px; min-width: 180px;">
                            <label class="quick-filter-label">{{ $isAr ? "حالة المراجعة" : "Review Status" }}</label>
                            <select name="order_status" class="form-control quick-select">
                                <option value="">{{ $isAr ? "جميع مستندات الطلبات" : "All service documents" }}</option>
                                @foreach(['pending','approved','rejected','replacement_requested'] as $status)
                                    <option value="{{ $status }}" {{ request('order_status') === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div style="flex: 1 1 200px; min-width: 180px;">
                            <label class="quick-filter-label">{{ $isAr ? "مرسل بواسطة" : "Submitted By" }}</label>
                            <select name="source" class="form-control quick-select">
                                <option value="">{{ $isAr ? "العميل والشريك" : "Customer and Partner" }}</option>
                                <option value="customer" {{ request('source') === 'customer' ? 'selected' : '' }}>{{ $isAr ? "العميل" : "Customer" }}</option>
                                <option value="partner" {{ request('source') === 'partner' ? 'selected' : '' }}>{{ $isAr ? "الشريك" : "Partner" }}</option>
                                <option value="request" {{ request('source') === 'request' ? 'selected' : '' }}>{{ $isAr ? "رفع من تفاصيل الطلب" : "Request Upload" }}</option>
                            </select>
                        </div>
                        <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height: 42px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span>{{ $isAr ? "تصفية" : "Filter" }}</span>
                        </button>
                    </div>
                </form>

                <div class="row">
                    @forelse($requestCards as $requestIndex => $card)
                        @php $booking = $card['booking']; @endphp
                        @php $service = $card['service']; @endphp
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="sanad-entity-card h-100">
                                <div class="sanad-request-header mb-3">
                                    <div style="min-width: 0; flex: 1;">
                                        <div class="sanad-card-ref">{{ optional($booking)->quick_reference ?: 'QUICK-'.str_pad((string) optional($booking)->id, 6, '0', STR_PAD_LEFT) }}</div>
                                        <h5 class="sanad-card-title">{{ optional(optional($booking)->customer)->display_name ?: 'Customer' }}</h5>
                                        <div class="sanad-card-subtitle">{{ optional($service)->name_en ?: optional($service)->name ?: 'Service' }}</div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <span class="quick-badge quick-badge-neutral">{{ Str::headline(optional($booking)->sanad_stage ?: optional($booking)->status ?: 'pending') }}</span>
                                    </div>
                                </div>

                                <div class="sanad-request-meta mb-3">
                                    <div><span>{{ $isAr ? "الشريك" : "Partner" }}</span><strong class="text-truncate" title="{{ optional(optional($booking)->provider)->display_name ?: 'Unassigned' }}">{{ optional(optional($booking)->provider)->display_name ?: ($isAr ? 'غير مسند' : 'Unassigned') }}</strong></div>
                                    <div><span>{{ $isAr ? "مطلوب" : "Required" }}</span><strong>{{ $card['required_count'] }}</strong></div>
                                    <div><span>{{ $isAr ? "مرسل" : "Sent" }}</span><strong>{{ $card['submitted_count'] }}</strong></div>
                                    <div><span>{{ $isAr ? "معتمد" : "Approved" }}</span><strong style="color:#10b981;">{{ $card['approved_count'] }}</strong></div>
                                </div>

                                <div class="progress sanad-progress mb-3">
                                    <div class="progress-bar" style="width: {{ $card['progress'] }}%"></div>
                                </div>

                                @if($card['missing_required']->isNotEmpty())
                                    <div class="sanad-section-note mb-3">
                                        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                                            <div style="display: flex; align-items: flex-start; gap: 6px; flex: 1; min-width: 180px;">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:#d97706;flex-shrink:0;margin-top:2px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                                <div>
                                                    <strong>{{ $isAr ? "مستندات مطلوبة مفقودة:" : "Missing required:" }}</strong>
                                                    <span>{{ $card['missing_required']->map(fn ($document) => localized_service_document_name($document))->filter()->join(', ') ?: ($isAr ? 'مستند مطلوب قيد الانتظار' : 'Required document pending') }}</span>
                                                </div>
                                            </div>
                                            @if($booking)
                                                <button type="button" class="quick-buzz-pill-btn quick-send-reminder-btn"
                                                    data-booking-id="{{ $booking->id }}"
                                                    data-document-name="{{ $card['missing_required']->map(fn ($document) => localized_service_document_name($document))->filter()->join('، ') }}"
                                                    data-recipient="customer"
                                                    data-url="{{ route('sanad.requests.buzz.store', $booking->id) }}"
                                                    title="{{ $isAr ? 'إرسال تنبيه ذكي بالنواقص للعميل عبر الدردشة' : 'Send AI buzz reminder to customer' }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                                    <span>{{ $isAr ? "تنبيه بالنواقص" : "Buzz Customer" }}</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <div class="sanad-card-actions">
                                    @if($booking)
                                        <a href="{{ route('sanad.requests.show', $booking->id) }}" class="quick-card-btn quick-card-btn-outline">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                            <span>{{ $isAr ? "تفاصيل الطلب" : "Details" }}</span>
                                        </a>
                                    @endif
                                    <button class="quick-card-btn quick-card-btn-checklist collapsed" type="button" data-toggle="collapse" data-target="#request-documents-drawer-{{ $requestIndex }}" aria-expanded="false" aria-controls="request-documents-drawer-{{ $requestIndex }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                        <span>{{ $isAr ? "المستندات" : "Documents" }}</span>
                                        <span class="quick-count-pill">{{ $card['documents']->count() + $card['document_requests']->count() }}</span>
                                    </button>
                                </div>

                                <div class="collapse sanad-inline-drawer" id="request-documents-drawer-{{ $requestIndex }}">
                                    <div class="sanad-drawer-heading">{{ $isAr ? "المستندات المرسلة" : "Submitted documents" }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 sanad-document-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ $isAr ? "المستند" : "Document" }}</th>
                                                    <th>{{ $isAr ? "مرسل بواسطة" : "Source" }}</th>
                                                    <th>{{ $isAr ? "التاريخ" : "Date" }}</th>
                                                    <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                                                    <th class="text-right">{{ $isAr ? "الإجراء" : "Action" }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                    @forelse($card['documents'] as $document)
                                        @php
                                            $documentUrl = $document->publicDocumentUrl();
                                            $media = $document->getFirstMedia('document') ?: $document->getFirstMedia('sanad_document');
                                            $fileName = optional($media)->file_name ?: $document->file_name ?: ($documentUrl ? basename($documentUrl) : null);
                                            $mimeType = optional($media)->mime_type ?: '';
                                            $isImage = Str::startsWith($mimeType, 'image/') || ($documentUrl && preg_match('/\.(png|jpe?g|webp|gif)$/i', $documentUrl));
                                            $isPdf = $mimeType === 'application/pdf' || Str::endsWith(Str::lower((string) $fileName), '.pdf') || ($documentUrl && Str::endsWith(Str::lower((string) $documentUrl), '.pdf'));
                                            $docDisplayName = Str::headline($document->document_type);
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong style="color:var(--quick-shell-ink);font-size:12px;">{{ $docDisplayName }}</strong>
                                                <div class="small text-muted text-truncate" style="max-width:180px;">
                                                    {{ $documentUrl ? ($fileName ?: ($isAr ? 'مستند مرفق' : 'Uploaded document')) : ($isAr ? 'لم يُرفع ملف بعد' : 'No file uploaded yet') }}
                                                </div>
                                                @if($document->review_reason)
                                                    <div class="small text-danger">{{ $document->review_reason }}</div>
                                                @endif
                                            </td>
                                            <td><span class="small">{{ $isAr ? ($document->source === 'customer' ? 'العميل' : ($document->source === 'partner' ? 'الشريك' : 'الطلب')) : Str::headline($document->source ?: 'request') }}</span></td>
                                            <td><span class="small text-muted">{{ optional($document->created_at)->format('M d') ?: '-' }}</span></td>
                                            <td>
                                                @php $dStatus = $document->verification_status ?: ($documentUrl ? 'pending' : 'missing'); @endphp
                                                <span class="quick-badge quick-badge-{{ $dStatus === 'approved' ? 'success' : ($dStatus === 'rejected' ? 'danger' : ($dStatus === 'missing' ? 'danger' : 'warning')) }}">
                                                    {{ $dStatus === 'approved' ? ($isAr ? 'معتمد' : 'Approved') : ($dStatus === 'rejected' ? ($isAr ? 'مرفوض' : 'Rejected') : ($dStatus === 'missing' ? ($isAr ? 'غير مرفق' : 'Not Uploaded') : ($isAr ? 'قيد المراجعة' : 'Pending Review'))) }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                @if($documentUrl)
                                                    <button type="button" class="quick-drawer-action-btn" data-toggle="modal" data-target="#requestDocumentModal{{ $document->id }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-inline-end:3px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        {{ $isAr ? "معاينة" : "View" }}
                                                    </button>
                                                @elseif($booking)
                                                    <button type="button" class="quick-drawer-buzz-btn quick-send-reminder-btn"
                                                        data-booking-id="{{ $booking->id }}"
                                                        data-document-name="{{ $document->document_type }}"
                                                        data-recipient="customer"
                                                        data-url="{{ route('sanad.requests.buzz.store', $booking->id) }}"
                                                        title="{{ $isAr ? 'إرسال تنبيه عاجل للعميل عبر الدردشة' : 'Send Buzz reminder to customer' }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-inline-end:3px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                                        <span>{{ $isAr ? "تنبيه" : "Remind" }}</span>
                                                    </button>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($documentUrl)
                                            <div class="modal fade sanad-document-modal" id="requestDocumentModal{{ $document->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                                <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <div>
                                                                <h5 class="modal-title font-weight-bold">{{ Str::headline($document->document_type) }}</h5>
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
                                                                    <select name="verification_status" class="form-control quick-select">
                                                                        <option value="approved">{{ $isAr ? "اعتماد" : "Approve" }}</option>
                                                                        <option value="rejected">{{ $isAr ? "رفض" : "Reject" }}</option>
                                                                        <option value="replacement_requested">{{ $isAr ? "طلب استبدال" : "Request replacement" }}</option>
                                                                    </select>
                                                                    <input name="reason" class="form-control quick-input-text" placeholder="{{ $isAr ? 'سبب الرفض أو طلب الاستبدال' : 'Reason for rejection/replacement' }}">
                                                                    <button class="quick-admin-hero-btn quick-admin-hero-btn-primary">{{ $isAr ? "حفظ المراجعة" : "Save Review" }}</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    @empty
                                        <tr><td colspan="5" class="text-muted text-center py-3">{{ $isAr ? "لا توجد مستندات مرسلة حتى الآن." : "No submitted documents yet." }}</td></tr>
                                    @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="sanad-drawer-heading mt-2">{{ $isAr ? "طلبات مستندات إضافية" : "Additional document requests" }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0 sanad-document-table">
                                            <thead>
                                                <tr>
                                                    <th>{{ $isAr ? "المستند" : "Document" }}</th>
                                                    <th>{{ $isAr ? "مطلوب من" : "Target" }}</th>
                                                    <th>{{ $isAr ? "الاستحقاق" : "Due" }}</th>
                                                    <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                                                    <th>{{ $isAr ? "الملف" : "File" }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                    @forelse($card['document_requests'] as $documentRequest)
                                        @php
                                            $requestDoc = $documentRequest->document;
                                            $requestDocumentUrl = $requestDoc ? $requestDoc->publicDocumentUrl() : null;
                                            $reqTarget = $documentRequest->requested_from === 'partner' ? 'partner' : 'customer';
                                        @endphp
                                        <tr>
                                            <td>
                                                <strong style="color:var(--quick-shell-ink);font-size:12px;">{{ $documentRequest->document_name }}</strong>
                                                <div class="small text-muted">{{ $documentRequest->reason }}</div>
                                            </td>
                                            <td><span class="small">{{ $isAr ? ($documentRequest->requested_from === 'customer' ? 'العميل' : ($documentRequest->requested_from === 'partner' ? 'الشريك' : 'الفريق')) : Str::headline($documentRequest->requested_from) }}</span></td>
                                            <td><span class="small text-muted">{{ $documentRequest->due_at ? $documentRequest->due_at->format('M d') : '-' }}</span></td>
                                            <td>
                                                @php $rStatus = $documentRequest->status ?: 'pending'; @endphp
                                                <span class="quick-badge quick-badge-{{ $rStatus === 'approved' ? 'success' : ($rStatus === 'rejected' ? 'danger' : 'warning') }}">
                                                    {{ $isAr ? ($rStatus === 'approved' ? 'معتمد' : ($rStatus === 'rejected' ? 'مرفوض' : ($rStatus === 'submitted' ? 'تم الإرسال' : 'معلق'))) : Str::headline($rStatus) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($requestDocumentUrl)
                                                    <a target="_blank" href="{{ $requestDocumentUrl }}" class="quick-drawer-action-btn">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-inline-end:3px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                        {{ $isAr ? "معاينة" : "View" }}
                                                    </a>
                                                @elseif($booking)
                                                    <button type="button" class="quick-drawer-buzz-btn quick-send-reminder-btn"
                                                        data-booking-id="{{ $booking->id }}"
                                                        data-document-name="{{ $documentRequest->document_name }}"
                                                        data-recipient="{{ $reqTarget }}"
                                                        data-url="{{ route('sanad.requests.buzz.store', $booking->id) }}"
                                                        title="{{ $isAr ? 'إرسال تنبيه عاجل عبر الدردشة' : 'Send Buzz reminder' }}">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-inline-end:3px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                                        <span>{{ $isAr ? "تنبيه" : "Remind" }}</span>
                                                    </button>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted text-center py-3">{{ $isAr ? "لا توجد طلبات إضافية." : "No additional document requests." }}</td></tr>
                                    @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="sanad-empty-state">{{ $isAr ? "لا توجد مستندات طلبات حالياً." : "No service request documents found." }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@once
<style>
    .quick-document-page {
        width: 100%;
    }

    .quick-tab-pill {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 16px !important;
        border-radius: 10px !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        color: var(--quick-shell-muted) !important;
        background: transparent !important;
        border: 0 !important;
        text-decoration: none !important;
        cursor: pointer !important;
        transition: all .15s ease !important;
    }

    .quick-tab-pill:hover {
        color: var(--quick-shell-ink) !important;
        background: color-mix(in srgb, var(--quick-shell-bg) 60%, transparent) !important;
    }

    .quick-tab-pill.active {
        background: var(--quick-blue) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(31,107,255,.3) !important;
    }

    .sanad-filter-bar {
        padding: 14px 18px;
        border-radius: 14px;
        background: color-mix(in srgb, var(--quick-shell-bg) 75%, var(--quick-shell-surface));
        border: 1px solid var(--quick-shell-line);
    }

    .quick-filter-label {
        display: block;
        font-size: 11px;
        font-weight: 800;
        color: var(--quick-shell-muted);
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 6px;
    }

    .quick-select {
        height: 42px !important;
        border-radius: 10px !important;
        border: 1px solid var(--quick-shell-line) !important;
        background: var(--quick-shell-surface) !important;
        color: var(--quick-shell-ink) !important;
        font-size: 13px !important;
        font-weight: 700 !important;
    }

    .quick-input-text {
        height: 42px !important;
        border-radius: 10px !important;
        border: 1px solid var(--quick-shell-line) !important;
        background: var(--quick-shell-surface) !important;
        color: var(--quick-shell-ink) !important;
        font-size: 13px !important;
    }

    /* Entity Cards */
    .sanad-entity-card {
        padding: 20px;
        border-radius: 20px;
        border: 1px solid var(--quick-shell-line);
        background: var(--quick-shell-surface);
        box-shadow: 0 4px 18px rgba(10,22,38,.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .sanad-entity-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(10,22,38,.06);
    }

    .sanad-card-ref {
        font-size: 11px;
        font-weight: 800;
        color: var(--quick-blue);
        letter-spacing: .04em;
        margin-bottom: 2px;
    }

    .sanad-card-title {
        font-size: 15px;
        font-weight: 900;
        color: var(--quick-shell-ink);
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sanad-card-subtitle {
        font-size: 12px;
        color: var(--quick-shell-muted);
        line-height: 1.4;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sanad-request-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }

    /* Badges */
    .quick-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
    }

    .quick-badge-success {
        background: rgba(16,185,129,.1);
        color: #10b981;
        border: 1px solid rgba(16,185,129,.2);
    }

    .quick-badge-warning {
        background: rgba(245,158,11,.1);
        color: #d97706;
        border: 1px solid rgba(245,158,11,.2);
    }

    .quick-badge-danger {
        background: rgba(239,68,68,.1);
        color: #ef4444;
        border: 1px solid rgba(239,68,68,.2);
    }

    .quick-badge-neutral {
        background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
        color: var(--quick-shell-muted);
        border: 1px solid var(--quick-shell-line);
    }

    .quick-text-success { color: #10b981; font-weight: 800; font-size: 12px; }
    .quick-text-warning { color: #d97706; font-weight: 800; font-size: 12px; }
    .quick-text-danger { color: #ef4444; font-weight: 800; font-size: 12px; }
    .quick-text-muted { color: var(--quick-shell-muted); font-weight: 700; font-size: 12px; }

    /* Metric Grid */
    .sanad-metric-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    .sanad-request-meta {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
        gap: 8px;
    }

    .sanad-metric-grid > div,
    .sanad-request-meta > div {
        background: color-mix(in srgb, var(--quick-shell-bg) 65%, var(--quick-shell-surface));
        border: 1px solid var(--quick-shell-line);
        border-radius: 12px;
        padding: 10px 8px;
        text-align: center;
        min-width: 0;
    }

    .sanad-metric-grid span,
    .sanad-request-meta span {
        display: block;
        font-size: 10px;
        font-weight: 800;
        color: var(--quick-shell-muted);
        text-transform: uppercase;
        letter-spacing: .02em;
    }

    .sanad-metric-grid strong,
    .sanad-request-meta strong {
        display: block;
        font-size: 14px;
        font-weight: 900;
        color: var(--quick-shell-ink);
        margin-top: 3px;
    }

    .sanad-progress {
        background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
        height: 6px;
        border-radius: 99px;
        overflow: hidden;
    }

    .sanad-progress .progress-bar {
        background: var(--quick-blue);
    }

    .sanad-mini-list {
        border-top: 1px solid var(--quick-shell-line);
        padding-top: 10px;
    }

    .sanad-mini-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        padding: 4px 0;
        color: var(--quick-shell-ink);
    }

    .sanad-section-note {
        background: rgba(245,158,11,.08);
        border: 1px solid rgba(245,158,11,.2);
        border-radius: 12px;
        color: #b45309;
        padding: 10px 12px;
        font-size: 12px;
        line-height: 1.5;
    }

    /* Card Action Buttons */
    .sanad-card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        border-top: 1px solid var(--quick-shell-line);
        padding-top: 14px;
        margin-top: auto;
    }

    .quick-card-btn {
        flex: 1;
        min-height: 38px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-decoration: none;
        cursor: pointer;
        transition: all .15s ease;
        padding: 0 10px;
        white-space: nowrap;
    }

    .quick-card-btn-outline {
        border: 1px solid var(--quick-shell-line);
        background: var(--quick-shell-surface);
        color: var(--quick-shell-ink) !important;
    }

    .quick-card-btn-outline:hover {
        border-color: var(--quick-blue);
        color: var(--quick-blue) !important;
    }

    .quick-card-btn-checklist {
        border: 1px solid rgba(31,107,255,.2);
        background: rgba(31,107,255,.08);
        color: var(--quick-blue) !important;
    }

    .quick-card-btn-checklist:hover {
        background: var(--quick-blue);
        color: #ffffff !important;
    }

    .quick-card-btn-checklist:hover .quick-count-pill {
        background: #ffffff;
        color: var(--quick-blue);
    }

    .quick-count-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 20px;
        height: 20px;
        border-radius: 99px;
        background: var(--quick-blue);
        color: #ffffff;
        font-size: 11px;
        font-weight: 900;
        padding: 0 6px;
        transition: all .15s ease;
    }

    /* Inline Drawer & Table */
    .sanad-inline-drawer {
        border: 1px solid var(--quick-shell-line);
        border-radius: 14px;
        background: var(--quick-shell-surface);
        margin-top: 14px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(10,22,38,.02);
    }

    .sanad-drawer-heading {
        padding: 10px 14px;
        font-size: 11px;
        font-weight: 800;
        color: var(--quick-shell-muted);
        text-transform: uppercase;
        letter-spacing: .04em;
        border-bottom: 1px solid var(--quick-shell-line);
        background: color-mix(in srgb, var(--quick-shell-bg) 50%, var(--quick-shell-surface));
    }

    .sanad-document-table {
        margin: 0 !important;
        border-collapse: collapse;
        width: 100%;
    }

    .sanad-document-table thead th {
        background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface)) !important;
        color: var(--quick-shell-muted) !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        text-transform: uppercase !important;
        letter-spacing: .04em !important;
        padding: 10px 14px !important;
        border-top: 0 !important;
        border-bottom: 1px solid var(--quick-shell-line) !important;
    }

    .sanad-document-table tbody td {
        padding: 12px 14px !important;
        font-size: 12px !important;
        color: var(--quick-shell-ink) !important;
        vertical-align: middle !important;
        border-bottom: 1px solid var(--quick-shell-line) !important;
        background: transparent !important;
    }

    .sanad-document-table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .quick-drawer-action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid var(--quick-shell-line);
        background: var(--quick-shell-surface);
        color: var(--quick-blue);
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
    }

    .quick-drawer-action-btn:hover {
        background: var(--quick-blue);
        border-color: var(--quick-blue);
        color: #ffffff !important;
    }

    .sanad-empty-state {
        padding: 36px;
        text-align: center;
        border-radius: 16px;
        border: 1px dashed var(--quick-shell-line);
        color: var(--quick-shell-muted);
        font-size: 13px;
        font-weight: 700;
        background: var(--quick-shell-surface);
    }

    /* Modal Styling */
    .sanad-document-modal .modal-content {
        border-radius: 20px;
        border: 1px solid var(--quick-shell-line);
        background: var(--quick-shell-surface);
        box-shadow: 0 20px 60px rgba(10,22,38,.12);
        overflow: hidden;
    }

    .sanad-document-modal .modal-header,
    .sanad-document-modal .modal-footer {
        border-color: var(--quick-shell-line);
        padding: 16px 20px;
    }

    .sanad-modal-preview {
        height: 60vh;
        min-height: 360px;
        background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
        border-radius: 14px;
        border: 1px solid var(--quick-shell-line);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }

    .sanad-modal-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .sanad-modal-preview iframe {
        width: 100%;
        height: 100%;
        border: 0;
    }

    .sanad-modal-review-form {
        display: flex;
        align-items: center;
        gap: 10px;
        width: 100%;
        flex-wrap: wrap;
    }

    @media (max-width: 768px) {
        .sanad-request-meta {
            grid-template-columns: repeat(2, 1fr);
        }
        .sanad-card-actions {
            flex-direction: column;
        }
        .quick-card-btn {
            width: 100%;
        }
    }

    /* Quick Drawer Buzz & Reminder Buttons */
    .quick-drawer-buzz-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 12px;
        border-radius: 8px;
        border: 1px solid rgba(245, 158, 11, 0.35);
        background: rgba(245, 158, 11, 0.08);
        color: #d97706 !important;
        font-size: 12px;
        font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
        text-decoration: none !important;
        white-space: nowrap;
    }

    .quick-drawer-buzz-btn:hover {
        background: #f59e0b;
        border-color: #f59e0b;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }

    .quick-drawer-buzz-btn.is-sent {
        background: rgba(16, 185, 129, 0.12) !important;
        border-color: rgba(16, 185, 129, 0.35) !important;
        color: #10b981 !important;
        cursor: default;
    }

    .quick-buzz-pill-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 8px;
        border: 1px solid rgba(245, 158, 11, 0.4);
        background: #ffffff;
        color: #b45309;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: all .15s ease;
        flex-shrink: 0;
    }

    .quick-buzz-pill-btn:hover {
        background: #f59e0b;
        color: #ffffff;
        border-color: #f59e0b;
    }

    .quick-buzz-pill-btn.is-sent {
        background: #ecfdf5;
        border-color: #10b981;
        color: #047857;
        cursor: default;
    }

    /* Floating Toast Notification */
    .quick-buzz-toast-container {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 10px;
        pointer-events: none;
    }

    [dir="rtl"] .quick-buzz-toast-container {
        right: auto;
        left: 24px;
    }

    .quick-buzz-toast {
        pointer-events: auto;
        display: flex;
        align-items: center;
        gap: 12px;
        background: #0a1626;
        color: #ffffff;
        padding: 12px 18px;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(10,22,38,0.25);
        border: 1px solid rgba(255,255,255,0.1);
        animation: quickToastSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        max-width: 420px;
    }

    .quick-buzz-toast-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(245, 158, 11, 0.2);
        color: #f59e0b;
        display: grid;
        place-items: center;
        flex-shrink: 0;
    }

    .quick-buzz-toast-title {
        font-size: 13px;
        font-weight: 800;
        line-height: 1.3;
    }

    .quick-buzz-toast-desc {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 2px;
    }

    .quick-buzz-toast-action {
        margin-inline-start: auto;
        padding: 4px 10px;
        border-radius: 6px;
        background: var(--quick-blue, #1f6bff);
        color: #ffffff;
        font-size: 11px;
        font-weight: 800;
        text-decoration: none;
        white-space: nowrap;
    }

    .quick-buzz-toast-action:hover {
        background: #1557d6;
        color: #ffffff;
    }

    @keyframes quickToastSlideUp {
        from {
            opacity: 0;
            transform: translateY(16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endonce

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.jQuery) {
            window.jQuery(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
                window.jQuery(e.target).closest('.nav').find('.quick-tab-pill').removeClass('active');
                window.jQuery(e.target).addClass('active');
            });
        }

        function showBuzzToast(message, chatUrl) {
            let container = document.querySelector('.quick-buzz-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'quick-buzz-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = 'quick-buzz-toast';
            const isAr = document.documentElement.lang === 'ar' || document.dir === 'rtl';

            toast.innerHTML = `
                <div class="quick-buzz-toast-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                </div>
                <div style="min-width:0;flex:1;">
                    <div class="quick-buzz-toast-title">${isAr ? 'تم إرسال التنبيه وتذكير العميل' : 'Buzz Reminder Sent'}</div>
                    <div class="quick-buzz-toast-desc">${message}</div>
                </div>
                ${chatUrl ? `<a href="${chatUrl}" class="quick-buzz-toast-action">${isAr ? 'فتح المحادثة' : 'View Chat'}</a>` : ''}
            `;

            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(() => toast.remove(), 350);
            }, 5000);
        }

        document.querySelectorAll('.quick-send-reminder-btn').forEach(button => {
            button.addEventListener('click', async function(e) {
                e.preventDefault();
                e.stopPropagation();

                const btn = this;
                if (btn.classList.contains('is-sent') || btn.disabled) return;

                const url = btn.getAttribute('data-url');
                const docName = btn.getAttribute('data-document-name');
                const recipient = btn.getAttribute('data-recipient') || 'customer';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '{{ csrf_token() }}';
                const isAr = document.documentElement.lang === 'ar' || document.dir === 'rtl';

                const originalHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = `<span>⟳</span> <span>${isAr ? 'جاري الإرسال...' : 'Sending...'}</span>`;

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            document_name: docName,
                            recipient_role: recipient,
                            action_type: 'document_reminder'
                        })
                    });

                    const data = await response.json();

                    if (response.ok && data.status) {
                        btn.classList.add('is-sent');
                        btn.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline-block;vertical-align:middle;margin-inline-end:3px;"><polyline points="20 6 9 17 4 12"/></svg> <span>${isAr ? 'تم التنبيه' : 'Sent'}</span>`;
                        showBuzzToast(data.message || (isAr ? 'تم إنشاء رسالة تذكير ذكية وإرسالها للعميل في محادثة الطلب.' : 'AI reminder buzz sent to customer in chat.'), data.chat_url);
                    } else {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                        alert(data.message || (isAr ? 'تعذر إرسال التنبيه' : 'Failed to send reminder'));
                    }
                } catch (err) {
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                    console.error(err);
                }
            });
        });
    });
</script>

</x-master-layout>
