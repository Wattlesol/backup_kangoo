@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'أرباح الموظفين' : 'Employee Earnings';
@endphp

<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>

    <div class="quick-handyman-earning-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    <span>{{ $isAr ? 'المعاملات وأجور الموظفين' : 'Employee Payouts & Commission Tracking' }}</span>
                </div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $isAr ? 'متابعة أرباح وعمولات الموظفين، إجمالي مبالغ الحجوزات المنفذة، ومستحقات الصرف.' : 'Track employee earnings, booking volumes, commissions, and payout histories.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('earning') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                    <span>{{ $isAr ? 'أرباح الشركاء' : 'Partner Earnings' }}</span>
                </a>
                <a href="{{ route('handyman.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>{{ $isAr ? 'دليل الموظفين' : 'Employee Directory' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Table Card -->
        <div class="quick-card">
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'سجل تفاصيل أرباح الموظفين' : 'Employee Earnings Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض وتتبع مبالغ الحجوزات والعمولات المستحقة لكل موظف' : 'View and track completed bookings and commissions earned by staff' }}</div>
                </div>
            </div>

            <div class="quick-table-responsive">
                <table class="quick-table handydata-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.handyman') }}</th>
                            <th>{{ __('messages.commission') }}</th>
                            <th>{{ __('messages.booking') }}</th>
                            <th>{{ __('messages.total_amount') }}</th>
                            <th>{{ __('messages.total_earning') }}</th>
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
        .quick-handyman-earning-page {
            width: 100%;
        }

        table.handydata-table.quick-table {
            width: 100% !important;
            border-collapse: collapse !important;
            margin: 0 !important;
        }
    </style>
    @endonce

    @section('bottom_script')
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            $('.handydata-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: false,
                dom: '<"quick-table-controls" rt><"row align-items-center justify-content-between p-3"<"col-md-6" i><"col-md-6" p>>',
                ajax: "{{ route('handymanEarningData') }}",
                columns: [
                    {data: 'handyman_name', name: 'handyman_name'},
                    {data: 'commission', name: 'commission'},
                    {data: 'total_bookings', name: 'total_bookings'},
                    {data: 'total', name: 'total'},
                    {data: 'total_earning', name: 'total_earning'},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'},
                ],
                language: {
                    search: "",
                    searchPlaceholder: "{{ $isAr ? 'بحث...' : 'Search...' }}",
                    processing: "{{ $isAr ? 'جاري التحميل...' : 'Loading...' }}",
                    info: "{{ $isAr ? 'عرض _START_ إلى _END_ من أصل _TOTAL_ سجل' : 'Showing _START_ to _END_ of _TOTAL_ entries' }}",
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
        });
    </script>
    @endsection
</x-master-layout>

