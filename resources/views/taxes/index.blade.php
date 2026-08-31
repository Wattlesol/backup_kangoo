@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $pageTitle ?? ($isAr ? 'الضرائب والرسوم' : 'Taxes & Levies Directory');
@endphp

<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="quick-tax-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                    <span>{{ $isAr ? 'المعاملات وضبط الرسوم والضرائب' : 'Tax Rules & Service Levies' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'إدارة نسب ضريبة القيمة المضافة، الرسوم الإدارية، وتحديد آليات التطبيق على المعاملات الحكومية.' : 'Configure value added tax rates, service levies, calculation formulas, and active states.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('tax.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    <span>{{ $isAr ? 'إضافة ضريبة جديدة' : 'Add Tax' }}</span>
                </a>
                <a href="{{ route('payment.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>{{ $isAr ? 'المركز المالي' : 'Financial Center' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Table Card -->
        <div class="quick-card">
            <!-- Header with Title, Pills, Search -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل الضرائب والرسوم المعتمدة' : 'Official Taxes Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية قواعد الضرائب ونسب الخصم والتحصيل' : 'View, edit, filter, and perform bulk operations on taxes' }}</div>
                </div>

                <div class="quick-provider-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="{{ $filter['status'] === '' || $filter['status'] === null ? 'active' : '' }}" onclick="filterTaxStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '1' ? 'active' : '' }}" onclick="filterTaxStatus('1', this)">{{ $isAr ? 'النشطة' : 'Active' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '0' ? 'active' : '' }}" onclick="filterTaxStatus('0', this)">{{ $isAr ? 'غير النشطة' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث في الضرائب...' : 'Search taxes...' }}" aria-label="Search taxes">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('tax.bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
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
                        data--submit="{{ route('tax.bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('tax',['form'=> __('tax') ]) }}"
                        title="{{ __('tax',['form'=> __('tax') ]) }}"
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
        .quick-tax-page {
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

        table#datatable.quick-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 !important;
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
                dom: '<"quick-table-controls" rt><"row align-items-center justify-content-between p-3"<"col-md-6" i><"col-md-6" p>>',
                ajax: {
                    type: "GET",
                    url: '{{ route("tax.index_data") }}',
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
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">',
                        exportable: false,
                        orderable: false,
                        searchable: false,
                        width: '52px',
                        className: 'text-center'
                    },
                    {
                        data: 'title',
                        name: 'title',
                        title: "{{ __('messages.title') }}"
                    },
                    {
                        data: 'value',
                        name: 'value',
                        title: "{{ __('messages.value') }}"
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}",
                        width: '100px',
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
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ ضريبة' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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

        function filterTaxStatus(status, btn) {
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
                const message = button.data('message');
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

