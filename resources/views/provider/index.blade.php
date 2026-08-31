@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $summary = $sanadPartnerSummary ?? [];
    $totalPartners = $summary['total_partners'] ?? 0;
    $activePartners = $summary['active_partners'] ?? 0;
    $pendingPartners = $summary['pending_partners'] ?? 0;
    $activeRate = $totalPartners > 0 ? (int) round(($activePartners / $totalPartners) * 100) : 0;
@endphp

<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="quick-provider-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>{{ $isAr ? 'إدارة واعتماد شركاء كويك' : 'Partner Network & Service Provider Management' }}</span>
                </div>
                <h1>{{ $pageTitle ?? ($isAr ? 'دليل وسجل شركاء كويك' : 'Partner Directory') }}</h1>
                <p>{{ $isAr ? 'متابعة وتدقيق حسابات الشركاء، التراخيص، مؤشرات الأداء وجودة التنفيذ، وإدارة عمليات الإسناد والصلاحيات.' : 'Manage partner onboarding, monitor performance scores, track daily capacity, and configure operational permissions.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                @if($list_status != 'pending')
                    @if($auth_user->can('provider add'))
                        <a href="{{ route('provider.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span>{{ $isAr ? 'إضافة شريك جديد' : 'Add Partner' }}</span>
                        </a>
                    @endif
                @endif
                <a href="{{ route('sanad.partner-performance') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    <span>{{ $isAr ? 'مؤشرات أداء الشركاء' : 'Partner Performance' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
            <div class="quick-kpi-grid">
                <!-- Metric 1: Total Partners -->
                <div class="quick-kpi-card">
                    <div class="quick-kpi-header">
                        <span>{{ $isAr ? 'إجمالي الشركاء' : 'Total Partners' }}</span>
                        <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        </div>
                    </div>
                    <div class="quick-kpi-value">{{ $totalPartners }}</div>
                    <div class="quick-kpi-sub">
                        <b class="quick-trend-up">{{ $activeRate }}%</b>
                        <span>{{ $isAr ? 'نسبة الشركاء النشطين' : 'active readiness' }}</span>
                    </div>
                </div>

                <!-- Metric 2: Active Partners -->
                <div class="quick-kpi-card">
                    <div class="quick-kpi-header">
                        <span>{{ $isAr ? 'الشركاء النشطون' : 'Active Partners' }}</span>
                        <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        </div>
                    </div>
                    <div class="quick-kpi-value">{{ $activePartners }}</div>
                    <div class="quick-kpi-sub">
                        <b class="quick-trend-up">{{ $activePartners }}</b>
                        <span>{{ $isAr ? 'جاهزون لاستقبال الطلبات' : 'ready for dispatch' }}</span>
                    </div>
                </div>

                <!-- Metric 3: Pending Approval -->
                <div class="quick-kpi-card">
                    <div class="quick-kpi-header">
                        <span>{{ $isAr ? 'بانتظار الاعتماد' : 'Pending Approval' }}</span>
                        <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                    </div>
                    <div class="quick-kpi-value">{{ $pendingPartners }}</div>
                    <div class="quick-kpi-sub">
                        <b style="color: #f59e0b;">{{ $pendingPartners }}</b>
                        <span>{{ $isAr ? 'تحت مراجعة الوثائق' : 'under document review' }}</span>
                    </div>
                </div>

                <!-- Metric 4: Assigned Requests -->
                <div class="quick-kpi-card">
                    <div class="quick-kpi-header">
                        <span>{{ $isAr ? 'الطلبات المُسندة' : 'Assigned Requests' }}</span>
                        <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                        </div>
                    </div>
                    <div class="quick-kpi-value">{{ $summary['assigned_requests'] ?? 0 }} <span style="font-size:15px;font-weight:700;color:var(--quick-shell-muted);">/ {{ ($summary['assigned_requests'] ?? 0) + ($summary['unassigned_requests'] ?? 0) }}</span></div>
                    <div class="quick-kpi-sub">
                        <b class="quick-trend-up">{{ $summary['assigned_requests'] ?? 0 }}</b>
                        <span>{{ $isAr ? 'طلب قيد المعالجة' : 'active partner orders' }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- 3. Partner Directory Table Card -->
        <div class="quick-card quick-provider-table-card">
            <!-- Toolbar: Header & Search & Filter Pills -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل ودليل الشركاء' : 'Official Partner Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية الشركاء وإجراء العمليات الجماعية ومتابعة السعة والتقييم' : 'View, edit, filter, and perform bulk operations on service providers' }}</div>
                </div>

                <div class="quick-provider-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="{{ $filter['status'] === '' || $filter['status'] === null ? 'active' : '' }}" onclick="filterProviderStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '1' ? 'active' : '' }}" onclick="filterProviderStatus('1', this)">{{ $isAr ? 'النشطون' : 'Active' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '0' ? 'active' : '' }}" onclick="filterProviderStatus('0', this)">{{ $isAr ? 'غير النشطين' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث بالاسم أو البريد أو الهاتف...' : 'Search partners by name, email, phone...' }}" aria-label="Search partners">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('provider.bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
                    @csrf
                    <div class="quick-bulk-group">
                        <span class="quick-bulk-label">{{ $isAr ? 'إجراء جماعي:' : 'Bulk action:' }}</span>
                        <select name="action_type" class="quick-bulk-select" id="quick-action-type" disabled>
                            <option value="">{{ __('messages.no_action') }}</option>
                            <option value="change-status">{{ __('messages.status') }}</option>
                            <option value="delete">{{ __('messages.delete') }}</option>
                            <option value="restore">{{ __('messages.restore') }}</option>
                            <option value="permanently-delete">{{ __('messages.permanent_dlt') }}</option>
                        </select>
                    </div>

                    <div class="quick-bulk-target d-none quick-action-field" id="change-status-action">
                        <select name="status" class="quick-bulk-select" id="status">
                            @if($list_status == 'pending')
                                <option value="1">{{ __('messages.approve') }}</option>
                            @else
                                <option value="1">{{ __('messages.active') }}</option>
                                <option value="0">{{ __('messages.inactive') }}</option>
                            @endif
                        </select>
                    </div>

                    <button id="quick-action-apply" class="quick-bulk-apply-btn" data-ajax="true"
                        data--submit="{{ route('provider.bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('provider',['form'=> __('provider') ]) }}"
                        title="{{ __('provider',['form'=> __('provider') ]) }}"
                        data-message='{{ __("Do you want to perform this action?") }}' disabled>
                        {{ __('messages.apply') }}
                    </button>
                </form>
            </div>

            <!-- Hidden input for column status filter -->
            <input type="hidden" id="column_status" value="{{ $filter['status'] ?? '' }}">

            <!-- Responsive Data Table -->
            <div class="quick-table-responsive">
                <table id="datatable" class="quick-table">
                </table>
            </div>
        </div>
    </div>

    @once
    <style>
        .quick-provider-page {
            width: 100%;
        }

        .quick-provider-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quick-search-box {
            position: relative;
            min-width: 260px;
        }

        .quick-search-icon {
            position: absolute;
            inset-inline-start: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            color: var(--quick-shell-muted);
            pointer-events: none;
        }

        .quick-search-input {
            width: 100%;
            height: 38px;
            border-radius: 11px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding-inline-start: 36px;
            padding-inline-end: 14px;
            font-size: 13px;
            outline: none;
            transition: all .15s ease;
        }

        .quick-search-input:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.15);
        }

        /* Bulk Action Bar */
        .quick-bulk-bar {
            padding: 12px 16px;
            margin-bottom: 16px;
            border-radius: 14px;
            background: color-mix(in srgb, var(--quick-shell-bg) 75%, var(--quick-shell-surface));
            border: 1px solid var(--quick-shell-line);
        }

        .quick-bulk-form {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .quick-bulk-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-bulk-label {
            font-size: 12px;
            font-weight: 800;
            color: var(--quick-shell-muted);
            white-space: nowrap;
        }

        .quick-bulk-select {
            height: 36px;
            border-radius: 9px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding: 0 10px;
            font-size: 12px;
            font-weight: 700;
            outline: none;
            cursor: pointer;
        }

        .quick-bulk-select:focus {
            border-color: var(--quick-blue);
        }

        .quick-bulk-apply-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 16px;
            border-radius: 9px;
            border: 0;
            background: var(--quick-blue);
            color: #ffffff;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all .15s ease;
        }

        .quick-bulk-apply-btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .quick-bulk-apply-btn:not(:disabled):hover {
            background: #1455d9;
        }

        /* Responsive Table Container */
        .quick-table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 14px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
        }

        /* DataTable General Styling */
        table#datatable.quick-table {
            width: 100% !important;
            min-width: 1400px;
            border-collapse: collapse !important;
            margin: 0 !important;
        }

        table#datatable.quick-table thead th {
            background: color-mix(in srgb, var(--quick-shell-bg) 65%, var(--quick-shell-surface)) !important;
            color: var(--quick-shell-muted) !important;
            font-size: 11px !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: .4px !important;
            padding: 14px 14px !important;
            border-bottom: 1px solid var(--quick-shell-line) !important;
            border-top: none !important;
            white-space: nowrap !important;
            vertical-align: middle !important;
        }

        table#datatable.quick-table tbody td {
            padding: 13px 14px !important;
            vertical-align: middle !important;
            border-bottom: 1px solid var(--quick-shell-line) !important;
            border-top: none !important;
            color: var(--quick-shell-ink) !important;
            font-size: 13px !important;
            background: var(--quick-shell-surface) !important;
        }

        table#datatable.quick-table tbody tr:hover td {
            background: color-mix(in srgb, var(--quick-shell-bg) 40%, transparent) !important;
        }

        /* Checkbox Column Fix: Prevent Squeezing */
        table#datatable.quick-table th:first-child,
        table#datatable.quick-table td:first-child {
            width: 52px !important;
            min-width: 52px !important;
            max-width: 52px !important;
            text-align: center !important;
            vertical-align: middle !important;
            padding: 12px 6px !important;
        }

        table#datatable.quick-table .form-check-input {
            width: 18px !important;
            height: 18px !important;
            margin: 0 auto !important;
            display: block !important;
            position: static !important;
            cursor: pointer;
            accent-color: var(--quick-blue);
        }

        /* Partner Avatar in Cell */
        table#datatable.quick-table .avatar {
            width: 38px !important;
            height: 38px !important;
            border-radius: 10px !important;
            object-fit: cover;
        }

        /* Badges */
        .badge-active {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            background: rgba(16,185,129,.12);
            color: #059669;
            border: 1px solid rgba(16,185,129,.25);
        }

        /* DataTables Controls & Pagination */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_info {
            font-size: 12px;
            color: var(--quick-shell-muted);
            padding: 16px 12px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--quick-shell-line);
            border-radius: 8px;
            padding: 4px 8px;
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            outline: none;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding: 16px 12px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 4px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid var(--quick-shell-line) !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            font-size: 12px !important;
            font-weight: 700 !important;
            color: var(--quick-shell-ink) !important;
            background: var(--quick-shell-surface) !important;
            cursor: pointer;
            transition: all .15s ease;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current,
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            border-color: var(--quick-blue) !important;
            background: var(--quick-blue) !important;
            color: #ffffff !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
            opacity: .4;
            cursor: not-allowed;
        }
    </style>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                scrollX: true,
                dom: '<"quick-table-controls" rt><"row align-items-center justify-content-between p-3"<"col-md-6" i><"col-md-6" p>>',
                ajax: {
                    type: "GET",
                    url: '{{ route("provider.index_data", ["list_status" => $list_status]) }}',
                    data: function (d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: $('#column_status').val()
                        };
                    }
                },
                columns: [
                    {
                        name: 'check',
                        data: 'check',
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="user" onclick="selectAllTable(this)">',
                        searchable: false,
                        exportable: false,
                        orderable: false,
                        width: '52px',
                        className: 'text-center'
                    },
                    {
                        data: 'display_name',
                        name: 'display_name',
                        title: "{{ __('messages.name') }}",
                        orderable: false,
                        width: '240px'
                    },
                    {
                        data: 'created_at',
                        name: 'created_at',
                        title: "{{ __('messages.joining_date') }}",
                        width: '140px'
                    },
                    {
                        data: 'providertype_id',
                        name: 'providertype_id',
                        title: "{{ $isAr ? 'نوع الشريك' : 'Partner Type' }}",
                        width: '110px'
                    },
                    {
                        data: 'contact_number',
                        name: 'contact_number',
                        title: "{{ __('messages.contact_number') }}",
                        width: '130px'
                    },
                    {
                        data: 'partner_score',
                        name: 'sanad_quality_score',
                        title: "{{ $isAr ? 'التقييم' : 'Score' }}",
                        width: '80px',
                        className: 'text-center',
                        render: function(data) {
                            if (data === null || data === '-' || data === '') return '-';
                            return '<span class="badge badge-light border font-weight-bold" style="padding:4px 8px;border-radius:8px;">⭐ ' + data + '</span>';
                        }
                    },
                    {
                        data: 'active_orders',
                        name: 'active_orders',
                        title: "{{ $isAr ? 'الطلبات النشطة' : 'Active Orders' }}",
                        width: '90px',
                        className: 'text-center',
                        render: function(data) {
                            return '<span class="badge badge-info" style="padding:4px 8px;border-radius:8px;">' + (data || 0) + '</span>';
                        }
                    },
                    {
                        data: 'capacity',
                        name: 'capacity',
                        title: "{{ $isAr ? 'السعة' : 'Capacity' }}",
                        width: '80px',
                        className: 'text-center'
                    },
                    {
                        data: 'sla_compliance',
                        name: 'sla_compliance',
                        title: "{{ $isAr ? 'الالتزام' : 'SLA' }}",
                        width: '80px',
                        className: 'text-center'
                    },
                    {
                        data: 'acceptance_rate',
                        name: 'sanad_acceptance_rate',
                        title: "{{ $isAr ? 'القبول' : 'Acceptance' }}",
                        width: '85px',
                        className: 'text-center',
                        render: function(data) {
                            return data === null || data === '' ? '-' : data + '%';
                        }
                    },
                    {
                        data: 'cancellation_rate',
                        name: 'sanad_cancellation_rate',
                        title: "{{ $isAr ? 'الإلغاء' : 'Cancellation' }}",
                        width: '85px',
                        className: 'text-center',
                        render: function(data) {
                            return data === null || data === '' ? '-' : data + '%';
                        }
                    },
                    {
                        data: 'average_completion',
                        name: 'sanad_average_completion_minutes',
                        title: "{{ $isAr ? 'متوسط الوقت' : 'Avg Time' }}",
                        width: '95px',
                        className: 'text-center',
                        render: function(data) {
                            return data === null || data === '' ? '-' : data + ' min';
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}",
                        width: '90px',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}",
                        width: '100px',
                        className: 'text-center'
                    }
                ],
                language: {
                    search: "",
                    searchPlaceholder: "{{ $isAr ? 'بحث...' : 'Search...' }}",
                    processing: "{{ $isAr ? 'جاري التحميل...' : 'Loading...' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ شريك' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
                    infoEmpty: "{{ $isAr ? 'عرض 0 إلى 0 من أصل 0' : 'Showing 0 to 0 of 0 entries' }}",
                    infoFiltered: "{{ $isAr ? '(تمت التصفية من أصل _MAX_ إجمالي)' : '(filtered from _MAX_ total entries)' }}",
                    lengthMenu: "{{ $isAr ? 'عرض _MENU_ سجلات' : 'Show _MENU_ entries' }}",
                    zeroRecords: "{{ $isAr ? 'لم يتم العثور على نتائج مطابقة' : 'No matching records found' }}",
                    paginate: {
                        first: "{{ $isAr ? 'الأول' : 'First' }}",
                        last: "{{ $isAr ? 'الأخير' : 'Last' }}",
                        next: "{{ $isAr ? 'التالي' : 'Next' }}",
                        previous: "{{ $isAr ? 'السابق' : 'Previous' }}"
                    }
                }
            });

            // Live search with debounce
            let searchTimeout;
            $('.dt-search').on('keyup input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () {
                    window.renderedDataTable.draw();
                }, 250);
            });
        });

        function filterProviderStatus(status, btn) {
            $('#column_status').val(status);
            document.querySelectorAll('.quick-filter-pills button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            window.renderedDataTable.draw();
        }

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue !== '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue === 'change-status') {
                    $('.quick-action-field').addClass('d-none');
                    $('#change-status-action').removeClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
            }
        }

        $('#quick-action-type').change(function () {
            resetQuickAction();
        });

        $(document).on('click', '[data-ajax="true"]', function (e) {
            e.preventDefault();
            const button = $(this);
            const confirmation = button.data('confirmation');

            if (confirmation === 'true') {
                const message = button.data('message') || '{{ $isAr ? "هل أنت متأكد من تنفيذ هذا الإجراء؟" : "Do you want to perform this action?" }}';
                if (confirm(message)) {
                    const submitUrl = button.data('submit');
                    const form = button.closest('form');
                    form.attr('action', submitUrl);
                    form.submit();
                }
            } else {
                const submitUrl = button.data('submit');
                const form = button.closest('form');
                form.attr('action', submitUrl);
                form.submit();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>

