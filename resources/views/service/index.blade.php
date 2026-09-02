@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'دليل الخدمات الحكومية' : 'Government Service Catalog';
    $summary = $sanadServiceSummary ?? [];
    $totalServices = (int) ($summary['total_services'] ?? AppModelsService::where('service_type', 'service')->count());
    $activeServices = (int) ($summary['active_services'] ?? AppModelsService::where('service_type', 'service')->where('status', 1)->count());
    $inactiveServices = (int) ($summary['inactive_services'] ?? AppModelsService::where('service_type', 'service')->where('status', 0)->count());
    $packagesCount = (int) ($summary['packages'] ?? AppModelsServicePackage::count());
    $addonsCount = (int) ($summary['addons'] ?? AppModelsServiceAddon::count());
    $activeRate = $totalServices > 0 ? (int) round(($activeServices / $totalServices) * 100) : 0;
@endphp

<x-master-layout>
    <div class="quick-service-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span>{{ $isAr ? 'دليل المعاملات والخدمات الحكومية' : 'Government Service Catalog' }}</span>
                </div>
                <h1>{{ $isAr ? 'دليل الخدمات الحكومية' : 'Government Service Catalog' }}</h1>
                <p>{{ $isAr ? 'إدارة وتخصيص الخدمات والرسوم الحكومية وتعيين التصنيفات وتحديث التسعير وحالات التمكين للبوابة والتطبيق.' : 'Manage government transactions, official fees, category mappings, pricing, and live catalog availability.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                @if(isset($auth_user) && $auth_user->can('service add') && Route::currentRouteName() !== 'servicepackage.service')
                    <a href="{{ route('service.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>{{ $isAr ? 'إضافة خدمة جديدة' : 'Add Service' }}</span>
                    </a>
                @endif
                <a href="{{ route('servicepackage.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span>{{ $isAr ? 'باقات الخدمات' : 'Service Bundles' }}</span>
                </a>
                <a href="{{ route('serviceaddon.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>{{ $isAr ? 'الخدمات الإضافية' : 'Add-on Services' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        <div class="quick-kpi-grid">
            <!-- Metric 1: Total Services -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي الخدمات' : 'Total Services' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $totalServices }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $activeRate }}%</b>
                    <span>{{ $isAr ? 'نسبة الجاهزية والنشاط' : 'active readiness' }}</span>
                </div>
            </div>

            <!-- Metric 2: Active Services -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الخدمات النشطة' : 'Active Services' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $activeServices }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $activeServices }}</b>
                    <span>{{ $isAr ? 'متاحة للطلب عبر البوابة' : 'live in portal' }}</span>
                </div>
            </div>

            <!-- Metric 3: Service Bundles -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'باقات الخدمات' : 'Service Bundles' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $packagesCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $packagesCount }}</b>
                    <span>{{ $isAr ? 'باقة مجمعة ومخفضة' : 'bundled packages' }}</span>
                </div>
            </div>

            <!-- Metric 4: Additional Services -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الخدمات الإضافية' : 'Add-on Services' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $addonsCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $addonsCount }}</b>
                    <span>{{ $isAr ? 'إضافات وخيارات ملحقة' : 'service add-ons' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Services Data Table Card -->
        <div class="quick-card quick-service-table-card">
            <!-- Toolbar: Header & Search & Bulk Actions -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'قائمة الخدمات الحكومية الرسمية' : 'Official Services Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية الخدمات والرسوم الحكومية وإجراء العمليات الجماعية' : 'View, edit, filter, and manage government services, fees, and statuses' }}</div>
                </div>

                <div class="quick-category-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="active" onclick="filterServiceStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" onclick="filterServiceStatus('1', this)">{{ $isAr ? 'النشطة' : 'Active' }}</button>
                        <button type="button" onclick="filterServiceStatus('0', this)">{{ $isAr ? 'غير النشطة' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث في الخدمات...' : 'Search services...' }}" aria-label="Search services">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('service.bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
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
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>

                    <button id="quick-action-apply" class="quick-bulk-apply-btn" data-ajax="true"
                        data--submit="{{ route('service.bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('service',['form'=> __('service') ]) }}"
                        title="{{ __('service',['form'=> __('service') ]) }}"
                        data-message='{{ __("Do you want to perform this action?") }}' disabled>
                        {{ __('messages.apply') }}
                    </button>
                </form>
            </div>

            <!-- Responsive Data Table -->
            <div class="quick-table-responsive">
                <table id="datatable" class="quick-table">
                    <thead>
                        <tr>
                            <th style="width: 44px; text-align: center;">
                                <input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="service" onclick="selectAllTable(this)">
                            </th>
                            <th>{{ __("messages.english_name") }}</th>
                            <th>{{ __("messages.arabic_name") }}</th>
                            <th>{{ __("messages.category") }}</th>
                            <th>{{ __("messages.price") }}</th>
                            <th style="text-align: center;">{{ __('messages.featured') }}</th>
                            <th style="text-align: center;">{{ __('messages.status') }}</th>
                            <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @once
    <style>
        .quick-service-page {
            width: 100%;
        }

        .quick-category-toolbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .quick-search-box {
            position: relative;
            min-width: 240px;
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

        /* Modern Table Adjustments */
        .quick-service-table-card .quick-table th {
            padding: 14px 16px;
        }

        .quick-service-table-card .quick-table td {
            padding: 14px 16px;
        }

        .quick-category-avatar {
            box-shadow: 0 2px 8px rgba(10,22,38,.06);
            transition: transform .2s ease;
        }

        .quick-category-avatar:hover {
            transform: scale(1.08);
        }

        .quick-category-title-link:hover {
            color: var(--quick-blue) !important;
        }

        /* Responsive Mobile Layout */
        @media (max-width: 860px) {
            .quick-category-toolbar-actions {
                width: 100%;
                justify-content: space-between;
            }
            .quick-search-box {
                width: 100%;
                min-width: 0;
            }
            .quick-bulk-form {
                flex-direction: column;
                align-items: stretch;
            }
            .quick-bulk-select,
            .quick-bulk-apply-btn {
                width: 100%;
            }
        }
    </style>
    @endonce

    <script>
        let currentServiceStatus = '';

        document.addEventListener('DOMContentLoaded', (event) => {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                dom: '<"row align-items-center"><"table-responsive my-2" rt><"row align-items-center py-3" <"col-md-6" l><"col-md-6" p>><"clear">',
                ajax: {
                    type: "GET",
                    url: '{{ route("service.service-index-data", ["postrequestid" => $postrequestid, "servicepackage" => $servicepackage]) }}',
                    data: function (d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: currentServiceStatus
                        };
                    }
                },
                columns: [
                    {
                        name: 'check',
                        data: 'check',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'name_ar',
                        name: 'name_ar'
                    },
                    {
                        data: 'category_id',
                        name: 'category_id'
                    },
                    {
                        data: 'price',
                        name: 'price'
                    },
                    {
                        data: 'is_featured',
                        name: 'is_featured',
                        className: 'text-center'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'text-center'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: '{{ $isAr ? "text-left" : "text-right" }}'
                    }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                    emptyTable: "{{ $isAr ? 'لا توجد خدمات متاحة.' : 'No services found.' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ خدمة' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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

        function filterServiceStatus(status, btn) {
            currentServiceStatus = status;
            document.querySelectorAll('.quick-filter-pills button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            window.renderedDataTable.draw();
        }

        function resetQuickAction() {
            const actionValue = $('#quick-action-type').val();
            if (actionValue !== '') {
                $('#quick-action-apply').removeAttr('disabled');

                if (actionValue === 'change-status') {
                    $('.quick-action-field').removeClass('d-none');
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
