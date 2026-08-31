@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'دليل الخدمات الإضافية' : 'Additional Services Directory';
    $summary = $addonSummary ?? [
        'total' => AppModelsServiceAddon::count(),
        'active' => AppModelsServiceAddon::where('status', 1)->count(),
        'inactive' => AppModelsServiceAddon::where('status', 0)->count(),
        'categories' => AppModelsCategory::count(),
        'services' => AppModelsService::count(),
    ];
    $activeRate = ($summary['total'] ?? 0) > 0 ? (int) round((($summary['active'] ?? 0) / $summary['total']) * 100) : 0;
@endphp

<x-master-layout>
    <div class="quick-addon-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>{{ $isAr ? 'الخيارات والإضافات التكميلية للمعاملات' : 'Service Add-on Options' }}</span>
                </div>
                <h1>{{ $isAr ? 'دليل الخدمات الإضافية' : 'Additional Services Directory' }}</h1>
                <p>{{ $isAr ? 'تحديد الخدمات والإضافات الملحقة التي يمكن للمستفيد اختيارها مع المعاملات الرئيسية وضبط التسعير ونطاق التطبيق.' : 'Configure optional add-on services, premium processing add-ons, pricing, and sector/service target assignments.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('serviceaddon.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>{{ $isAr ? 'إضافة خدمة تكميلية' : 'Add Service Add-on' }}</span>
                </a>
                <a href="{{ route('service.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    <span>{{ $isAr ? 'قائمة الخدمات' : 'All Services' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        <div class="quick-kpi-grid">
            <!-- Metric 1: Total Addons -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي الخدمات الإضافية' : 'Total Add-ons' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['total'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $activeRate }}%</b>
                    <span>{{ $isAr ? 'نسبة الجاهزية والنشاط' : 'active readiness' }}</span>
                </div>
            </div>

            <!-- Metric 2: Active -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الإضافات النشطة' : 'Active Add-ons' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['active'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['active'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'متاحة للطلب الفوري' : 'live in checkout' }}</span>
                </div>
            </div>

            <!-- Metric 3: Target Categories -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'القطاعات المشمولة' : 'Target Sectors' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['categories'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['categories'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'قطاع حكومي مرتبط' : 'linked sectors' }}</span>
                </div>
            </div>

            <!-- Metric 4: Target Services -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الخدمات المتاحة' : 'Linked Services' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['services'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['services'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'خدمة تقبل الإضافات' : 'eligible services' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Addon Data Table Card -->
        <div class="quick-card quick-addon-table-card">
            <!-- Toolbar: Header & Search & Bulk Actions -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'قائمة الخدمات الإضافية الرسمية' : 'Official Additional Services Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية الخدمات التكميلية وإدارتها حسب القطاع أو الخدمة' : 'View, edit, filter, and manage service add-ons and their targets' }}</div>
                </div>

                <div class="quick-category-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="active" onclick="filterAddonStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" onclick="filterAddonStatus('1', this)">{{ $isAr ? 'النشطة' : 'Active' }}</button>
                        <button type="button" onclick="filterAddonStatus('0', this)">{{ $isAr ? 'غير النشطة' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث في الخدمات الإضافية...' : 'Search add-ons...' }}" aria-label="Search add-ons">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('serviceaddon.bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
                    @csrf
                    <div class="quick-bulk-group">
                        <span class="quick-bulk-label">{{ $isAr ? 'إجراء جماعي:' : 'Bulk action:' }}</span>
                        <select name="action_type" class="quick-bulk-select" id="quick-action-type" disabled>
                            <option value="">{{ __('messages.no_action') }}</option>
                            <option value="change-status">{{ __('messages.status') }}</option>
                            <option value="delete">{{ __('messages.delete') }}</option>
                        </select>
                    </div>

                    <div class="quick-bulk-target d-none quick-action-field" id="change-status-action">
                        <select name="status" class="quick-bulk-select" id="status">
                            <option value="1">{{ __('messages.active') }}</option>
                            <option value="0">{{ __('messages.inactive') }}</option>
                        </select>
                    </div>

                    <button id="quick-action-apply" class="quick-bulk-apply-btn" data-ajax="true"
                        data--submit="{{ route('serviceaddon.bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('serviceaddon',['form'=> __('serviceaddon') ]) }}"
                        title="{{ __('serviceaddon',['form'=> __('serviceaddon') ]) }}"
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
                                <input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">
                            </th>
                            <th>{{ __("messages.english_name") }}</th>
                            <th>{{ __("messages.arabic_name") }}</th>
                            <th>{{ $isAr ? 'نطاق التطبيق' : 'Applies To' }}</th>
                            <th>{{ __("messages.price") }}</th>
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
        .quick-addon-page {
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
        .quick-addon-table-card .quick-table th {
            padding: 14px 16px;
        }

        .quick-addon-table-card .quick-table td {
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
        let currentAddonStatus = '';

        document.addEventListener('DOMContentLoaded', (event) => {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                dom: '<"row align-items-center"><"table-responsive my-2" rt><"row align-items-center py-3" <"col-md-6" l><"col-md-6" p>><"clear">',
                ajax: {
                    type: "GET",
                    url: '{{ route("serviceaddon.index-data") }}',
                    data: function (d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: currentAddonStatus
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
                        data: 'targets',
                        name: 'targets',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'price',
                        name: 'price'
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
                    emptyTable: "{{ $isAr ? 'لا توجد خدمات إضافية متاحة.' : 'No additional services found.' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ خدمة إضافية' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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

        function filterAddonStatus(status, btn) {
            currentAddonStatus = status;
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
