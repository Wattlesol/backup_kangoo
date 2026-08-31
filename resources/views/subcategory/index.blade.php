@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'دليل التصنيفات الفرعية' : 'Subcategory Directory';
    $summary = $subcategorySummary ?? [
        'total' => AppModelsSubCategory::count(),
        'active' => AppModelsSubCategory::where('status', 1)->count(),
        'inactive' => AppModelsSubCategory::where('status', 0)->count(),
        'featured' => AppModelsSubCategory::where('is_featured', 1)->count(),
        'categories' => AppModelsCategory::count(),
        'services' => AppModelsService::count(),
    ];
    $activeRate = ($summary['total'] ?? 0) > 0 ? (int) round((($summary['active'] ?? 0) / $summary['total']) * 100) : 0;
@endphp

<x-master-layout>
    <div class="quick-subcategory-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
                    <span>{{ $isAr ? 'هيكلية وتفرعات الخدمات الحكومية' : 'Branch Taxonomy & Sub-Sectors' }}</span>
                </div>
                <h1>{{ $isAr ? 'دليل التصنيفات الفرعية' : 'Subcategory Directory' }}</h1>
                <p>{{ $isAr ? 'تنظيم الخدمات الفرعية وربطها بالقطاعات الرئيسية، وإدارة حالات الظهور والتمييز والعمليات المجمعة.' : 'Organize subcategories under main government sectors, configure featured visibility, and manage bulk catalog actions.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                @if(isset($auth_user) && $auth_user->can('subcategory add'))
                    <a href="{{ route('subcategory.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>{{ $isAr ? 'إضافة تصنيف فرعي' : 'Add Subcategory' }}</span>
                    </a>
                @endif
                <a href="{{ route('category.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                    <span>{{ $isAr ? 'التصنيفات الرئيسية' : 'Main Categories' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        <div class="quick-kpi-grid">
            <!-- Metric 1: Total -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي التصنيفات الفرعية' : 'Total Subcategories' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
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
                    <span>{{ $isAr ? 'الفرعية النشطة' : 'Active Subcategories' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['active'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['active'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'متاحة للطلب الفوري' : 'live in catalog' }}</span>
                </div>
            </div>

            <!-- Metric 3: Featured -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'المميزة بالبوابة' : 'Featured in Portal' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['featured'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['featured'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'مفعلة بالعروض والبحث' : 'featured cards' }}</span>
                </div>
            </div>

            <!-- Metric 4: Linked Categories & Services -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'القطاعات والخدمات' : 'Sectors & Services' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $summary['categories'] ?? 0 }} <span style="font-size:16px;font-weight:700;color:var(--quick-shell-muted);">/ {{ $summary['services'] ?? 0 }}</span></div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $summary['services'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'خدمة مشمولة بالدليل' : 'catalog services' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. SubCategory Data Table Card -->
        <div class="quick-card quick-subcategory-table-card">
            <!-- Toolbar: Header & Search & Bulk Actions -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل التصنيفات الفرعية' : 'Official Subcategory Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية الفئات الفرعية وتحديث حالات التمييز والنشاط' : 'View, edit, filter, and perform bulk operations on subcategories' }}</div>
                </div>

                <div class="quick-category-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="active" onclick="filterSubCategoryStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" onclick="filterSubCategoryStatus('1', this)">{{ $isAr ? 'النشطة' : 'Active' }}</button>
                        <button type="button" onclick="filterSubCategoryStatus('0', this)">{{ $isAr ? 'غير النشطة' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث في التصنيفات الفرعية...' : 'Search subcategories...' }}" aria-label="Search subcategories">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('sub-bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
                    @csrf
                    <div class="quick-bulk-group">
                        <span class="quick-bulk-label">{{ $isAr ? 'إجراء جماعي:' : 'Bulk action:' }}</span>
                        <select name="action_type" class="quick-bulk-select" id="quick-action-type" disabled>
                            <option value="">{{ __('messages.no_action') }}</option>
                            <option value="change-status">{{ __('messages.status') }}</option>
                            <option value="change-featured">{{ __('messages.featured') }}</option>
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

                    <div class="quick-bulk-target d-none quick-action-featured" id="change-featured-action">
                        <select name="is_featured" class="quick-bulk-select" id="is_featured">
                            <option value="1">{{ __('messages.yes') }}</option>
                            <option value="0">{{ __('messages.no') }}</option>
                        </select>
                    </div>

                    <button id="quick-action-apply" class="quick-bulk-apply-btn" data-ajax="true"
                        data--submit="{{ route('sub-bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('subcategory',['form'=> __('subcategory') ]) }}"
                        title="{{ __('subcategory',['form'=> __('subcategory') ]) }}"
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
                                <input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="subcategory" onclick="selectAllTable(this)">
                            </th>
                            <th>{{ __("messages.english_name") }}</th>
                            <th>{{ __("messages.arabic_name") }}</th>
                            <th>{{ __("messages.category") }}</th>
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
        .quick-subcategory-page {
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
        .quick-subcategory-table-card .quick-table th {
            padding: 14px 16px;
        }

        .quick-subcategory-table-card .quick-table td {
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
        let currentSubCategoryStatus = '';

        document.addEventListener('DOMContentLoaded', (event) => {
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                dom: '<"row align-items-center"><"table-responsive my-2" rt><"row align-items-center py-3" <"col-md-6" l><"col-md-6" p>><"clear">',
                ajax: {
                    type: "GET",
                    url: '{{ route("subcategory.sub-index-data") }}',
                    data: function (d) {
                        d.search = {
                            value: $('.dt-search').val()
                        };
                        d.filter = {
                            column_status: currentSubCategoryStatus
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
                    emptyTable: "{{ $isAr ? 'لا توجد تصنيفات فرعية متاحة.' : 'No subcategories found.' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ تصنيف فرعي' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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

        function filterSubCategoryStatus(status, btn) {
            currentSubCategoryStatus = status;
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
                    $('.quick-action-featured').addClass('d-none');
                } else if (actionValue === 'change-featured') {
                    $('.quick-action-featured').removeClass('d-none');
                    $('.quick-action-field').addClass('d-none');
                } else {
                    $('.quick-action-field').addClass('d-none');
                    $('.quick-action-featured').addClass('d-none');
                }
            } else {
                $('#quick-action-apply').attr('disabled', true);
                $('.quick-action-field').addClass('d-none');
                $('.quick-action-featured').addClass('d-none');
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
