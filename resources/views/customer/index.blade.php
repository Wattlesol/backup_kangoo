@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $pageTitle ?? ($isAr ? 'دليل العملاء والمستفيدين' : 'Customer Directory');
@endphp

<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="quick-customer-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>{{ $isAr ? 'إدارة المستفيدين والعملاء' : 'Beneficiary & Client Management' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'متابعة وتدقيق حسابات المستفيدين، توثيق الهويات والبريد، والطلبات المرتبطة، والعمليات الجماعية.' : 'Manage client profiles, account verification states, contact information, and order activity.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                @if($list_status !== 'unverified')
                    @if($auth_user->can('user add'))
                        <a href="{{ route('user.create') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span>{{ $isAr ? 'إضافة عميل جديد' : 'Add Customer' }}</span>
                        </a>
                    @endif
                @endif
                <a href="{{ route('user.export_data_to_excel') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>{{ $isAr ? 'تصدير Excel' : 'Export Excel' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Table Card -->
        <div class="quick-card">
            <!-- Header with Title, Pills, Search -->
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل ودليل حسابات العملاء' : 'Official Customer Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتعديل وتصفية المستفيدين وإدارة حالات التفعيل والتوثيق' : 'View, edit, filter, and perform bulk operations on customer accounts' }}</div>
                </div>

                <div class="quick-provider-toolbar-actions">
                    <!-- Status Filter Pills -->
                    <div class="quick-filter-pills" role="tablist">
                        <button type="button" class="{{ $filter['status'] === '' || $filter['status'] === null ? 'active' : '' }}" onclick="filterCustomerStatus('', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '1' ? 'active' : '' }}" onclick="filterCustomerStatus('1', this)">{{ $isAr ? 'النشطون' : 'Active' }}</button>
                        <button type="button" class="{{ (string) $filter['status'] === '0' ? 'active' : '' }}" onclick="filterCustomerStatus('0', this)">{{ $isAr ? 'غير النشطين' : 'Inactive' }}</button>
                    </div>

                    <!-- Search Input -->
                    <div class="quick-search-box">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="quick-search-icon"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" class="quick-search-input dt-search" placeholder="{{ $isAr ? 'بحث في العملاء...' : 'Search customers...' }}" aria-label="Search customers">
                    </div>
                </div>
            </div>

            <!-- Bulk Action Form Bar -->
            <div class="quick-bulk-bar">
                <form action="{{ route('user.bulk-action') }}" id="quick-action-form" class="quick-bulk-form form-disabled">
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
                        data--submit="{{ route('user.bulk-action') }}"
                        data-datatable="reload" data-confirmation="true"
                        data-title="{{ __('user',['form'=> __('user') ]) }}"
                        title="{{ __('user',['form'=> __('user') ]) }}"
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
        .quick-customer-page {
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
                scrollX: true,
                dom: '<"quick-table-controls" rt><"row align-items-center justify-content-between p-3"<"col-md-6" i><"col-md-6" p>>',
                ajax: {
                    type: "GET",
                    url: '{{ route("user.index_data", ["list_status" => $list_status]) }}',
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
                        exportable: false,
                        orderable: false,
                        searchable: false,
                        width: '52px',
                        className: 'text-center'
                    },
                    {
                        data: 'display_name',
                        name: 'display_name',
                        title: "{{ __('messages.name') }}",
                        width: '260px'
                    },
                    {
                        data: 'contact_number',
                        name: 'contact_number',
                        title: "{{ __('messages.contact_number') }}",
                        width: '140px'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}",
                        width: '100px',
                        className: 'text-center'
                    },
                    @if($list_status == 'unverified')
                    {
                        data: 'is_email_verified',
                        name: 'is_email_verified',
                        title: "{{ __('messages.is_email_verified') }}",
                        width: '120px',
                        className: 'text-center'
                    },
                    @endif
                    @if($list_status !== 'unverified')
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}",
                        width: '100px',
                        className: 'text-center'
                    }
                    @endif
                ],
                language: {
                    search: "",
                    searchPlaceholder: "{{ $isAr ? 'بحث...' : 'Search...' }}",
                    processing: "{{ $isAr ? 'جاري التحميل...' : 'Loading...' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ عميل' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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

        function filterCustomerStatus(status, btn) {
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

