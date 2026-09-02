@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'مركز مراقبة الجودة والشكاوى' : 'Quality Control & Support Hub';

    $sanadTotal = $sanadComplaintStats['total'] ?? 0;
    $sanadOpen = $sanadComplaintStats['open'] ?? 0;
    $sanadUrgent = $sanadComplaintStats['urgent'] ?? 0;
    $sanadResolved = $sanadComplaintStats['resolved'] ?? 0;

    $qcTotal = $data->total();
    $combinedTotal = $sanadTotal + $qcTotal;

    $sanadComplaintTypeLabels = [
        'document_issue' => $isAr ? 'مشكلة في المستندات' : 'Document Issue',
        'payment_billing' => $isAr ? 'المدفوعات والفوترة' : 'Payment & Billing',
        'request_delay' => $isAr ? 'تأخر الطلب' : 'Request Delay',
        'status_update' => $isAr ? 'تحديث حالة الطلب' : 'Status Update',
        'service_quality' => $isAr ? 'جودة الخدمة' : 'Service Quality',
        'communication_issue' => $isAr ? 'مشكلة في التواصل' : 'Communication Issue',
        'incorrect_information' => $isAr ? 'معلومات غير صحيحة' : 'Incorrect Information',
        'customer_complaint' => $isAr ? 'شكوى عميل' : 'Customer Complaint',
        'escalation' => $isAr ? 'تصعيد إداري' : 'Escalation',
        'sla_violation' => $isAr ? 'مخالفة اتفاقية مستوى الخدمة' : 'SLA Violation',
        'customer_feedback' => $isAr ? 'ملاحظات العميل' : 'Customer Feedback',
        'other' => $isAr ? 'أخرى' : 'Other',
    ];

    $sanadComplaintPriorityLabels = [
        'low' => $isAr ? 'منخفضة' : 'Low',
        'normal' => $isAr ? 'عادية' : 'Normal',
        'high' => $isAr ? 'مرتفعة' : 'High',
        'urgent' => $isAr ? 'عاجلة' : 'Urgent',
    ];

    $sanadComplaintStatusLabels = [
        'open' => $isAr ? 'مفتوحة' : 'Open',
        'pending' => $isAr ? 'قيد الانتظار' : 'Pending',
        'in_progress' => $isAr ? 'قيد المعالجة' : 'In Progress',
        'resolved' => $isAr ? 'محلولة' : 'Resolved',
        'closed' => $isAr ? 'مغلقة' : 'Closed',
    ];
@endphp

<x-master-layout>
    <div class="quick-qc-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    <span>{{ $isAr ? 'إدارة الجودة ومعالجة الشكاوى' : 'Quality Assurance & Escalation Center' }}</span>
                </div>
                <h1>{{ $isAr ? 'مركز مراقبة الجودة والشكاوى والدعم' : 'Quality Control & Support Center' }}</h1>
                <p>{{ $isAr ? 'متابعة شكاوى وتذاكر المستفيدين، وتدقيق التزام الشركاء بمعايير الخدمة، ومعالجة الملاحظات والتصعيدات بشكل مباشر.' : 'Monitor customer support tickets, track partner service compliance, resolve escalations, and log quality audits.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <button type="button" class="quick-admin-hero-btn quick-admin-hero-btn-primary" data-toggle="modal" data-target="#createQualityModal">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>{{ $isAr ? 'تسجيل حالة جودة جديدة' : 'New Quality Case' }}</span>
                </button>
                @if(Route::has('sanad.requests.index'))
                    <a href="{{ route('sanad.requests.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>{{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}</span>
                    </a>
                @elseif(Route::has('booking.index'))
                    <a href="{{ route('booking.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>{{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        <div class="quick-kpi-grid">
            <!-- Metric 1: Total Cases & Tickets -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي الحالات والشكاوى' : 'Total Cases & Tickets' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $combinedTotal }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $sanadTotal }}</b>
                    <span>{{ $isAr ? 'تذكرة عملاء مسجلة' : 'customer tickets' }}</span>
                </div>
            </div>

            <!-- Metric 2: Open Tickets -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'التذاكر المفتوحة' : 'Open Support Tickets' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $sanadOpen }}</div>
                <div class="quick-kpi-sub">
                    <b style="color: #f59e0b;">{{ $sanadOpen }}</b>
                    <span>{{ $isAr ? 'تتطلب متابعة فورية' : 'require active response' }}</span>
                </div>
            </div>

            <!-- Metric 3: Urgent & Escalated -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الحالات العاجلة' : 'Urgent & Escalated' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(239,51,64,.1); color: #ef3340;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $sanadUrgent }}</div>
                <div class="quick-kpi-sub">
                    <b style="color: #ef3340;">{{ $sanadUrgent }}</b>
                    <span>{{ $isAr ? 'أولوية قصوى للمعالجة' : 'critical priority' }}</span>
                </div>
            </div>

            <!-- Metric 4: Resolved -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الحالات المحلولة' : 'Resolved & Closed' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $sanadResolved }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $sanadResolved }}</b>
                    <span>{{ $isAr ? 'تم إغلاقها بنجاح' : 'resolved successfully' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Modern Filter Card -->
        <div class="quick-card mb-4">
            <div class="quick-card-header mb-3">
                <div class="quick-card-title-group">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--quick-blue);"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <h3 class="quick-card-title">{{ $isAr ? 'تصفية وبحث السجلات' : 'Filter & Search Records' }}</h3>
                </div>
                <div class="quick-card-sub">{{ $isAr ? 'تحديد نوع المشكلة أو الشريك أو حالة المعالجة للوصول السريع' : 'Filter cases by issue category, partner assignment, and current resolution status' }}</div>
            </div>

            <form action="{{ route('complaint.index_data') }}" method="GET" class="quick-filter-form">
                <div class="quick-filter-grid">
                    <!-- Issue Type -->
                    <div class="quick-filter-field">
                        <label for="issue_type_filter" class="quick-filter-label">{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}</label>
                        <select name="issue_type" id="issue_type_filter" class="quick-filter-select">
                            <option value="">{{ $isAr ? 'جميع أنواع المشكلات' : 'All Issue Types' }}</option>
                            <option value="customer_complaint" {{ request('issue_type') == 'customer_complaint' ? 'selected' : '' }}>{{ $isAr ? 'شكاوى العملاء (Customer Complaints)' : 'Customer Complaints' }}</option>
                            <option value="escalation" {{ request('issue_type') == 'escalation' ? 'selected' : '' }}>{{ $isAr ? 'تصعيد إداري (Escalations)' : 'Escalations' }}</option>
                            <option value="sla_violation" {{ request('issue_type') == 'sla_violation' ? 'selected' : '' }}>{{ $isAr ? 'مخالفة SLA (SLA Violations)' : 'SLA Violations' }}</option>
                            <option value="customer_feedback" {{ request('issue_type') == 'customer_feedback' ? 'selected' : '' }}>{{ $isAr ? 'ملاحظات العملاء (Customer Feedback)' : 'Customer Feedback' }}</option>
                            <option value="document_issue" {{ request('issue_type') == 'document_issue' ? 'selected' : '' }}>{{ $isAr ? 'مشكلات المستندات (Document Issues)' : 'Document Issues' }}</option>
                            <option value="payment_billing" {{ request('issue_type') == 'payment_billing' ? 'selected' : '' }}>{{ $isAr ? 'المدفوعات والفوترة (Payment & Billing)' : 'Payment & Billing' }}</option>
                            <option value="request_delay" {{ request('issue_type') == 'request_delay' ? 'selected' : '' }}>{{ $isAr ? 'تأخر الطلب (Request Delays)' : 'Request Delays' }}</option>
                            <option value="service_quality" {{ request('issue_type') == 'service_quality' ? 'selected' : '' }}>{{ $isAr ? 'جودة الخدمة (Service Quality)' : 'Service Quality' }}</option>
                        </select>
                    </div>

                    <!-- Partner Field -->
                    <div class="quick-filter-field">
                        <label for="provider_id_filter" class="quick-filter-label">{{ $isAr ? 'مزود الخدمة / الشريك' : 'Service Provider / Partner' }}</label>
                        <select name="provider_id" id="provider_id_filter" class="quick-filter-select">
                            <option value="">{{ $isAr ? 'جميع الشركاء' : 'All Partners' }}</option>
                            @foreach($providers ?? [] as $prov)
                                <option value="{{ $prov->id }}" {{ request('provider_id') == $prov->id ? 'selected' : '' }}>
                                    {{ $prov->display_name ?: $prov->first_name ?: ('Partner #' . $prov->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status Field -->
                    <div class="quick-filter-field">
                        <label for="status_filter" class="quick-filter-label">{{ $isAr ? 'الحالة' : 'Status' }}</label>
                        <select name="status" id="status_filter" class="quick-filter-select">
                            <option value="">{{ $isAr ? 'جميع الحالات' : 'All Statuses' }}</option>
                            @foreach(AppEnumsComplaintEnums::all() as $key => $statusVal)
                                <option value="{{ $statusVal }}" {{ (string) request('status') === (string) $statusVal ? 'selected' : '' }}>
                                    {{ trans('messages.'.$key) }}
                                </option>
                            @endforeach
                            <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>{{ $isAr ? 'مفتوحة (Open)' : 'Open' }}</option>
                            <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>{{ $isAr ? 'قيد المعالجة (In Progress)' : 'In Progress' }}</option>
                            <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>{{ $isAr ? 'محلولة (Resolved)' : 'Resolved' }}</option>
                            <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>{{ $isAr ? 'مغلقة (Closed)' : 'Closed' }}</option>
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="quick-filter-actions-col">
                        <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span>{{ __('messages.filter') }}</span>
                        </button>
                        <a href="{{ route('complaint.index_data') }}" class="quick-filter-btn quick-filter-btn-secondary" title="{{ $isAr ? 'إعادة ضبط' : 'Reset' }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                            <span>{{ $isAr ? 'إعادة ضبط' : 'Reset' }}</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- 4. Quick Customer Complaints & Support Tickets Card -->
        @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
            <div class="quick-card mb-4">
                <div class="quick-card-header">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="quick-card-title">{{ $isAr ? 'شكاوى عملاء كويك وتذاكر الدعم' : 'Quick Customer Complaints & Support Tickets' }}</h3>
                            <span class="quick-badge quick-badge-blue">{{ $sanadTotal }}</span>
                        </div>
                        <div class="quick-card-sub">{{ $isAr ? 'شكاوى وتذاكر المستفيدين المرتبطة بطلبات منصة كويك النشطة والمغلقة' : 'Customer-created complaints linked to active or closed Quick requests.' }}</div>
                    </div>

                    <div class="quick-kpi-pills-row">
                        <span class="quick-pill quick-pill-neutral">
                            {{ $isAr ? 'الإجمالي' : 'Total' }}: <strong>{{ $sanadTotal }}</strong>
                        </span>
                        <span class="quick-pill quick-pill-warning">
                            {{ $isAr ? 'المفتوحة' : 'Open' }}: <strong>{{ $sanadOpen }}</strong>
                        </span>
                        <span class="quick-pill quick-pill-danger">
                            {{ $isAr ? 'العاجلة' : 'Urgent' }}: <strong>{{ $sanadUrgent }}</strong>
                        </span>
                        <span class="quick-pill quick-pill-success">
                            {{ $isAr ? 'المحلولة' : 'Resolved' }}: <strong>{{ $sanadResolved }}</strong>
                        </span>
                    </div>
                </div>

                <div class="quick-table-responsive">
                    <table class="quick-table">
                        <thead>
                            <tr>
                                <th style="width: 45px;">#</th>
                                <th>{{ $isAr ? 'الطلب' : 'Request' }}</th>
                                <th>{{ $isAr ? 'العميل' : 'Customer' }}</th>
                                <th>{{ $isAr ? 'نوع المشكلة' : 'Type' }}</th>
                                <th style="text-align: center;">{{ $isAr ? 'الأولوية' : 'Priority' }}</th>
                                <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                                <th>{{ $isAr ? 'الوصف' : 'Description' }}</th>
                                <th>{{ $isAr ? 'الجدول الزمني' : 'Timeline' }}</th>
                                <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'المرفق' : 'Attachment' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sanadComplaints as $key => $complaint)
                                @php
                                    $booking = $complaint->booking;
                                    $attachmentUrl = $complaint->getFirstMediaUrl('sanad_complaint_attachment');
                                    $complaintTypeLabel = $sanadComplaintTypeLabels[$complaint->complaint_type] ?? Str::headline($complaint->complaint_type);
                                    $complaintPriorityLabel = $sanadComplaintPriorityLabels[$complaint->priority] ?? Str::headline($complaint->priority);
                                    $complaintStatusLabel = $sanadComplaintStatusLabels[$complaint->status] ?? Str::headline($complaint->status);
                                @endphp
                                <tr>
                                    <td class="font-weight-bold text-muted" style="font-size: 12px;">
                                        {{ method_exists($sanadComplaints, 'firstItem') ? $sanadComplaints->firstItem() + $key : $key + 1 }}
                                    </td>
                                    <td>
                                        @if($booking)
                                            <a href="{{ route('sanad.requests.show', $booking->id) }}" class="quick-table-ref-badge">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                                <span>{{ $booking->quick_reference }}</span>
                                            </a>
                                            <div class="quick-table-service-name">
                                                {{ $isAr ? (optional($booking->service)->name_ar ?: optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-') : (optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-') }}
                                            </div>
                                            <div class="quick-table-partner-hint">
                                                <span>{{ $isAr ? 'الشريك:' : 'Partner:' }}</span>
                                                <strong>{{ optional($booking->provider)->display_name ?: ($isAr ? 'غير مُسند' : 'Unassigned') }}</strong>
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size: 12px;">{{ $isAr ? 'لا يوجد طلب مرتبط' : 'No linked request' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="quick-customer-cell">
                                            <div class="quick-customer-avatar">
                                                {{ mb_substr(optional($complaint->customer)->first_name ?: optional($complaint->customer)->display_name ?: 'C', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong class="quick-customer-name">{{ optional($complaint->customer)->display_name ?: optional($complaint->customer)->email ?: '-' }}</strong>
                                                <div class="quick-customer-email">{{ optional($complaint->customer)->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="quick-badge quick-badge-neutral">{{ $complaintTypeLabel }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        @if($complaint->priority === 'urgent')
                                            <span class="quick-badge quick-badge-danger">
                                                <span class="quick-dot-pulse"></span>
                                                {{ $complaintPriorityLabel }}
                                            </span>
                                        @elseif($complaint->priority === 'high')
                                            <span class="quick-badge quick-badge-warning">{{ $complaintPriorityLabel }}</span>
                                        @else
                                            <span class="quick-badge quick-badge-muted">{{ $complaintPriorityLabel }}</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        @if(in_array($complaint->status, ['resolved', 'closed'], true))
                                            <span class="quick-badge quick-badge-success">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:11px;height:11px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                {{ $complaintStatusLabel }}
                                            </span>
                                        @elseif($complaint->status === 'in_progress')
                                            <span class="quick-badge quick-badge-blue">{{ $complaintStatusLabel }}</span>
                                        @else
                                            <span class="quick-badge quick-badge-warning">{{ $complaintStatusLabel }}</span>
                                        @endif
                                    </td>
                                    <td style="max-width: 280px; min-width: 180px;">
                                        <div class="quick-table-desc" title="{{ $complaint->description }}">
                                            {{ Str::limit($complaint->description, 160) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="quick-timeline-info">
                                            <div class="quick-timeline-row">
                                                <span class="text-muted">{{ $isAr ? 'الإنشاء:' : 'Created:' }}</span>
                                                <span>{{ optional($complaint->created_at)->format('Y-m-d H:i') }}</span>
                                            </div>
                                            @if($complaint->resolved_at)
                                                <div class="quick-timeline-row quick-timeline-resolved">
                                                    <span class="text-muted">{{ $isAr ? 'الحل:' : 'Resolved:' }}</span>
                                                    <span>{{ optional($complaint->resolved_at)->format('Y-m-d H:i') }}</span>
                                                </div>
                                            @else
                                                <div class="quick-timeline-row quick-timeline-open">
                                                    <span class="text-muted">{{ $isAr ? 'الحالة:' : 'Status:' }}</span>
                                                    <span style="color: #d97706;">{{ $isAr ? 'مفتوحة لدى الدعم' : 'Open in Support' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        @if($attachmentUrl)
                                            <a href="{{ $attachmentUrl }}" target="_blank" class="quick-table-btn quick-table-btn-outline" title="{{ $isAr ? 'فتح المرفق' : 'Open Attachment' }}">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                                <span>{{ $isAr ? 'عرض المرفق' : 'Attachment' }}</span>
                                            </a>
                                        @else
                                            <span class="quick-table-empty-dash">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="quick-table-empty">
                                        <div class="quick-table-empty-state">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--quick-shell-muted);"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                            <p>{{ $isAr ? 'لم يتم العثور على شكاوى عملاء كويك مطابقة للخيارات المحددة.' : 'No Quick customer complaints found for the selected filters.' }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($sanadComplaints, 'hasPages') && $sanadComplaints->hasPages())
                    <div class="quick-table-pagination-row">
                        {{ $sanadComplaints->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- 5. Partner Quality Control Cases Card -->
        <div class="quick-card mb-4">
            <div class="quick-card-header">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="quick-card-title">{{ $isAr ? 'سجل تدقيق جودة أداء الشركاء' : 'Partner Quality Control & Audit Logs' }}</h3>
                        <span class="quick-badge quick-badge-neutral">{{ $qcTotal }}</span>
                    </div>
                    <div class="quick-card-sub">{{ $isAr ? 'متابعة ملاحظات الجودة والشكاوى المسجلة على مزودي الخدمة والشركاء وإجراءات التدقيق' : 'Internal quality audits and corrective action logs for service providers and partners.' }}</div>
                </div>

                <div class="quick-card-header-actions">
                    <button type="button" class="quick-filter-btn quick-filter-btn-primary" data-toggle="modal" data-target="#createQualityModal">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>{{ $isAr ? 'إضافة سجل جودة' : 'Add Audit Record' }}</span>
                    </button>
                </div>
            </div>

            <div class="quick-table-responsive">
                <table class="quick-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>{{ $isAr ? 'الشريك' : 'Partner' }}</th>
                            <th>{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                            <th>{{ $isAr ? 'أنشئ بواسطة' : 'Created By' }}</th>
                            <th>{{ $isAr ? 'تاريخ التسجيل' : 'Date Created' }}</th>
                            <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'الإجراءات' : 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $key => $item)
                            <tr>
                                <td class="font-weight-bold text-muted" style="font-size: 12px;">{{ $key + 1 }}</td>
                                <td>
                                    <div class="quick-customer-cell">
                                        <div class="quick-customer-avatar" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                                            {{ mb_substr(optional($item->provider)->display_name ?: optional($item->provider)->first_name ?: 'P', 0, 1) }}
                                        </div>
                                        <div>
                                            <strong class="quick-customer-name">{{ optional($item->provider)->display_name ?: optional($item->provider)->first_name ?: ('Partner #' . $item->provider_id) }}</strong>
                                            <div class="quick-customer-email">{{ optional($item->provider)->email ?: '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="quick-badge quick-badge-neutral">
                                        {{ $sanadComplaintTypeLabels[$item->issue_type] ?? str_replace('_', ' ', ucfirst($item->issue_type ?: 'customer_complaint')) }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @php
                                        $enumKey = AppEnumsComplaintEnums::GetById($item->status);
                                        $isFinished = ($item->status == AppEnumsComplaintEnums::finished);
                                    @endphp
                                    <span class="quick-badge {{ $isFinished ? 'quick-badge-success' : 'quick-badge-blue' }}">
                                        {{ trans('messages.'.$enumKey) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="quick-creator-info">
                                        <strong>{{ optional($item->createdby)->display_name ?: optional($item->createdby)->first_name ?: '-' }}</strong>
                                        <div class="quick-customer-email">{{ optional($item->createdby)->email }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="quick-timeline-info">
                                        <span>{{ optional($item->created_at)->format('Y-m-d H:i') }}</span>
                                    </div>
                                </td>
                                <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                    <a href="{{ route('complaint.show', ['id' => $item->id]) }}" class="quick-table-btn quick-table-btn-primary">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <span>{{ $isAr ? 'عرض التفاصيل والردود' : 'View Details' }}</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="quick-table-empty">
                                    <div class="quick-table-empty-state">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--quick-shell-muted);"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                        <p>{{ __('messages.no_data') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($data->total() > 0)
                <div class="quick-table-pagination-row">
                    <div class="quick-pagination-count">
                        {{ $isAr ? 'إجمالي السجلات: ' : 'Total Records: ' }} <strong>{{ $data->total() }}</strong>
                    </div>
                    <div>
                        {{ $data->appends(Request::except('_token'))->render() }}
                    </div>
                </div>
            @endif
        </div>

        <!-- 6. Modern Create Quality Modal -->
        <div class="modal fade quick-modal" id="createQualityModal" tabindex="-1" role="dialog" aria-labelledby="createQualityModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content quick-modal-content">
                    <div class="modal-header quick-modal-header">
                        <div class="d-flex align-items-center gap-2">
                            <div class="quick-modal-icon-badge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                            </div>
                            <div>
                                <h5 class="modal-title quick-modal-title" id="createQualityModalLabel">
                                    {{ $isAr ? 'تسجيل حالة وملاحظة جودة جديدة' : 'Create Quality Control Case' }}
                                </h5>
                                <div class="quick-modal-sub">
                                    {{ $isAr ? 'توثيق شكوى أو مخالفة أو تقييم جودة على أحد الشركاء' : 'Log a partner quality audit, SLA violation, or support issue' }}
                                </div>
                            </div>
                        </div>
                        <button type="button" class="close quick-modal-close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <form action="{{ route('complaint.store') }}" method="POST" enctype="multipart/form-data" class="quick-modal-form">
                        @csrf
                        <div class="modal-body quick-modal-body">
                            <div class="row">
                                <!-- Issue Title -->
                                <div class="col-md-12 mb-3">
                                    <div class="form-group mb-0">
                                        <label for="complaint_title" class="quick-form-label">
                                            {{ $isAr ? 'عنوان المشكلة / الحالة' : 'Issue / Case Title' }} <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="quick-form-input" id="complaint_title" value="{{ @old('title') }}" name="title" required placeholder="{{ $isAr ? 'مثال: تأخر في استكمال متطلبات تجديد الرخصة' : 'e.g. Delay in completing license requirements' }}">
                                    </div>
                                </div>

                                <!-- Issue Type -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group mb-0">
                                        <label for="issue_type" class="quick-form-label">
                                            {{ $isAr ? 'نوع المشكلة' : 'Issue Type' }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="issue_type" id="issue_type" class="quick-form-select" required>
                                            <option value="customer_complaint">{{ $isAr ? 'شكوى عميل (Customer Complaint)' : 'Customer Complaint' }}</option>
                                            <option value="escalation">{{ $isAr ? 'تصعيد إداري (Escalation)' : 'Escalation' }}</option>
                                            <option value="sla_violation">{{ $isAr ? 'مخالفة اتفاقية مستوى الخدمة (SLA Violation)' : 'SLA Violation' }}</option>
                                            <option value="customer_feedback">{{ $isAr ? 'ملاحظات العميل (Customer Feedback)' : 'Customer Feedback' }}</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Partner -->
                                <div class="col-md-6 mb-3">
                                    <div class="form-group mb-0">
                                        <label for="modal_provider_id" class="quick-form-label">
                                            {{ $isAr ? 'الشريك / مزود الخدمة' : 'Partner / Provider' }} <span class="text-danger">*</span>
                                        </label>
                                        <select name="provider_id" id="modal_provider_id" class="quick-form-select" required>
                                            <option value="">{{ $isAr ? 'اختر الشريك...' : 'Select Partner...' }}</option>
                                            @foreach($providers ?? [] as $prov)
                                                <option value="{{ $prov->id }}">
                                                    {{ $prov->display_name ?: $prov->first_name ?: ('Partner #' . $prov->id) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Complaint Details -->
                                <div class="col-md-12 mb-3">
                                    <div class="form-group mb-0">
                                        <label for="complaint_details" class="quick-form-label">
                                            {{ $isAr ? 'تفاصيل الحالة والشكوى' : 'Case Details & Notes' }} <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="details" id="complaint_details" class="quick-form-textarea" rows="4" required placeholder="{{ $isAr ? 'اكتب تفاصيل الشكوى أو الملاحظة الرقابية هنا بالتفصيل...' : 'Provide full details, audit findings, or customer complaint context...' }}"></textarea>
                                    </div>
                                </div>

                                <!-- File Attachment -->
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="complaint_file" class="quick-form-label">
                                            {{ $isAr ? 'مرفق توثيقي أو تقرير (اختياري)' : 'Supporting Attachment (Optional)' }}
                                        </label>
                                        <div class="quick-file-upload-box">
                                            <input type="file" name="file" id="complaint_file" class="quick-file-input">
                                            <div class="quick-file-upload-content">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:24px;height:24px;color:var(--quick-blue);"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                                <span>{{ $isAr ? 'انقر أو اسحب ملفاً هنا لإرفاقه بالحالة' : 'Click or drop a file here to attach to this case' }}</span>
                                                <small class="text-muted">{{ $isAr ? 'الحد الأقصى: 10 ميجابايت (PDF, PNG, JPG, DOCX)' : 'Max: 10MB (PDF, PNG, JPG, DOCX)' }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer quick-modal-footer">
                            <button type="button" class="quick-filter-btn quick-filter-btn-secondary" data-dismiss="modal">
                                {{ $isAr ? 'إلغاء' : 'Cancel' }}
                            </button>
                            <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>{{ $isAr ? 'حفظ وتسجيل الحالة' : 'Save & Register Case' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @once
    <style>
        .quick-qc-page {
            width: 100%;
        }

        /* Filter Section */
        .quick-filter-form {
            width: 100%;
        }

        .quick-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) auto;
            gap: 16px;
            align-items: flex-end;
        }

        .quick-filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .quick-filter-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--quick-shell-ink);
            margin: 0;
        }

        .quick-filter-select {
            width: 100%;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: all .2s ease;
        }

        .quick-filter-select:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.12);
        }

        .quick-filter-actions-col {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-filter-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .quick-filter-btn-primary {
            background: var(--quick-blue);
            color: #ffffff;
            border-color: var(--quick-blue);
        }

        .quick-filter-btn-primary:hover {
            background: #1455d9;
            border-color: #1455d9;
            color: #ffffff;
        }

        .quick-filter-btn-secondary {
            background: var(--quick-shell-surface);
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        .quick-filter-btn-secondary:hover {
            background: color-mix(in srgb, var(--quick-shell-bg) 70%, var(--quick-shell-surface));
            border-color: var(--quick-shell-muted);
            color: var(--quick-shell-ink);
        }

        /* KPI Pills in Header */
        .quick-kpi-pills-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .quick-pill-neutral {
            background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        .quick-pill-warning {
            background: rgba(245,158,11,.12);
            color: #d97706;
            border-color: rgba(245,158,11,.25);
        }

        .quick-pill-danger {
            background: rgba(239,51,64,.12);
            color: #ef3340;
            border-color: rgba(239,51,64,.25);
        }

        .quick-pill-success {
            background: rgba(16,185,129,.12);
            color: #059669;
            border-color: rgba(16,185,129,.25);
        }

        /* Modern Table Styles */
        .quick-table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
        }

        .quick-table {
            width: 100%;
            border-collapse: collapse;
            text-align: start;
            margin-bottom: 0;
        }

        .quick-table th {
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
            color: var(--quick-shell-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--quick-shell-line);
            white-space: nowrap;
        }

        .quick-table td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--quick-shell-line);
            color: var(--quick-shell-ink);
            font-size: 13px;
        }

        .quick-table tr:last-child td {
            border-bottom: none;
        }

        .quick-table tr:hover td {
            background: color-mix(in srgb, var(--quick-shell-bg) 40%, transparent);
        }

        .quick-table-ref-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-weight: 800;
            color: var(--quick-blue);
            text-decoration: none;
            background: rgba(31,107,255,.08);
            border: 1px solid rgba(31,107,255,.18);
            padding: 3px 9px;
            border-radius: 8px;
            font-size: 12px;
            transition: all .15s ease;
        }

        .quick-table-ref-badge:hover {
            background: var(--quick-blue);
            color: #ffffff;
            border-color: var(--quick-blue);
        }

        .quick-table-service-name {
            font-size: 12px;
            font-weight: 700;
            color: var(--quick-shell-ink);
            margin-top: 4px;
        }

        .quick-table-partner-hint {
            font-size: 11px;
            color: var(--quick-shell-muted);
            margin-top: 2px;
        }

        .quick-customer-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-customer-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: rgba(16,185,129,.12);
            color: #059669;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .quick-customer-name {
            font-size: 13px;
            color: var(--quick-shell-ink);
            display: block;
        }

        .quick-customer-email {
            font-size: 11px;
            color: var(--quick-shell-muted);
            line-height: 1.2;
        }

        .quick-creator-info strong {
            font-size: 13px;
            color: var(--quick-shell-ink);
            display: block;
        }

        .quick-table-desc {
            font-size: 12px;
            color: var(--quick-shell-ink);
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .quick-timeline-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
            font-size: 11px;
        }

        .quick-timeline-row {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .quick-table-empty-dash {
            color: var(--quick-shell-muted);
            font-weight: 700;
            padding: 0 10px;
        }

        /* Badges */
        .quick-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .quick-badge-blue {
            background: rgba(31,107,255,.1);
            color: #1f6bff;
            border: 1px solid rgba(31,107,255,.2);
        }

        .quick-badge-success {
            background: rgba(16,185,129,.12);
            color: #059669;
            border: 1px solid rgba(16,185,129,.25);
        }

        .quick-badge-warning {
            background: rgba(245,158,11,.12);
            color: #d97706;
            border: 1px solid rgba(245,158,11,.25);
        }

        .quick-badge-danger {
            background: rgba(239,51,64,.12);
            color: #ef3340;
            border: 1px solid rgba(239,51,64,.25);
        }

        .quick-badge-neutral {
            background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
            color: var(--quick-shell-ink);
            border: 1px solid var(--quick-shell-line);
        }

        .quick-badge-muted {
            background: color-mix(in srgb, var(--quick-shell-bg) 50%, var(--quick-shell-surface));
            color: var(--quick-shell-muted);
            border: 1px solid var(--quick-shell-line);
        }

        .quick-dot-pulse {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #ef3340;
            box-shadow: 0 0 0 2px rgba(239,51,64,.3);
            display: inline-block;
        }

        /* Action Buttons in Table */
        .quick-table-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 9px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all .15s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .quick-table-btn-primary {
            background: rgba(31,107,255,.08);
            color: var(--quick-blue);
            border-color: rgba(31,107,255,.2);
        }

        .quick-table-btn-primary:hover {
            background: var(--quick-blue);
            color: #ffffff;
            border-color: var(--quick-blue);
        }

        .quick-table-btn-outline {
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            border-color: var(--quick-shell-line);
        }

        .quick-table-btn-outline:hover {
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
            border-color: var(--quick-shell-muted);
        }

        /* Empty State */
        .quick-table-empty {
            padding: 48px 16px !important;
            text-align: center;
        }

        .quick-table-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .quick-table-empty-state p {
            color: var(--quick-shell-muted);
            font-size: 13px;
            font-weight: 600;
            margin: 0;
        }

        /* Pagination Row */
        .quick-table-pagination-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 16px 4px 0 4px;
        }

        .quick-pagination-count {
            font-size: 12px;
            color: var(--quick-shell-muted);
        }

        /* Modern Modal Styling */
        .quick-modal .modal-content {
            border-radius: 20px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            box-shadow: 0 20px 60px rgba(10,22,38,.18);
            overflow: hidden;
        }

        .quick-modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--quick-shell-line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: color-mix(in srgb, var(--quick-shell-bg) 40%, var(--quick-shell-surface));
        }

        .quick-modal-icon-badge {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            background: rgba(31,107,255,.1);
            color: var(--quick-blue);
            display: grid;
            place-items: center;
        }

        .quick-modal-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--quick-shell-ink);
            margin: 0;
        }

        .quick-modal-sub {
            font-size: 12px;
            color: var(--quick-shell-muted);
            margin-top: 2px;
        }

        .quick-modal-close {
            font-size: 24px;
            color: var(--quick-shell-muted);
            opacity: .7;
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
        }

        .quick-modal-close:hover {
            opacity: 1;
            color: var(--quick-shell-ink);
        }

        .quick-modal-body {
            padding: 24px;
        }

        .quick-form-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--quick-shell-ink);
            margin-bottom: 6px;
            display: block;
        }

        .quick-form-input,
        .quick-form-select,
        .quick-form-textarea {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding: 10px 14px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            transition: all .15s ease;
        }

        .quick-form-input:focus,
        .quick-form-select:focus,
        .quick-form-textarea:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.12);
        }

        .quick-file-upload-box {
            position: relative;
            border: 2px dashed var(--quick-shell-line);
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            background: color-mix(in srgb, var(--quick-shell-bg) 40%, var(--quick-shell-surface));
            cursor: pointer;
            transition: all .2s ease;
        }

        .quick-file-upload-box:hover {
            border-color: var(--quick-blue);
            background: rgba(31,107,255,.04);
        }

        .quick-file-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .quick-file-upload-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            pointer-events: none;
        }

        .quick-file-upload-content span {
            font-size: 12px;
            font-weight: 700;
            color: var(--quick-shell-ink);
        }

        .quick-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid var(--quick-shell-line);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            background: color-mix(in srgb, var(--quick-shell-bg) 30%, var(--quick-shell-surface));
        }

        @media (max-width: 768px) {
            .quick-filter-grid {
                grid-template-columns: 1fr;
            }
            .quick-filter-actions-col {
                width: 100%;
            }
            .quick-filter-actions-col .quick-filter-btn {
                flex: 1;
            }
        }
    </style>
    @endonce
</x-master-layout>

