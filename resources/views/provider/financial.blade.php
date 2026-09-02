<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-finance-heading">
            <div><h1><i class="fas fa-wallet"></i> {{ $isAr ? 'المركز المالي ومحفظة مستحقات المكتب' : 'Financial Center & Wallet Balance' }}</h1><p>{{ $isAr ? 'تابع المدفوعات والتسويات والعمولة وضريبة القيمة المضافة من سجل مالي موحد.' : 'Track payments, settlements, commission, VAT, and wallet activity in one ledger.' }}</p></div>
        </div>
        <div class="partner-finance-kpis">
            <div><span><i class="fas fa-coins text-primary"></i>{{ $isAr ? 'مدفوعات العملاء' : 'Customer payments' }}</span><h4>{{ getPriceFormat($paid) }}</h4><small>{{ $isAr ? 'إجمالي المحصل' : 'Total collected' }}</small></div>
            <div><span><i class="far fa-clock text-warning"></i>{{ $isAr ? 'المدفوعات المعلقة' : 'Pending payments' }}</span><h4>{{ getPriceFormat($pending) }}</h4><small>{{ $isAr ? 'بانتظار التحصيل' : 'Awaiting collection' }}</small></div>
            <div><span><i class="fas fa-exchange-alt text-info"></i>{{ $isAr ? 'تسويات معلقة' : 'Pending settlement' }}</span><h4>{{ getPriceFormat(max($paid - $settlements->sum('amount'), 0)) }}</h4><small>{{ $isAr ? 'بانتظار التحويل' : 'Awaiting transfer' }}</small></div>
            <div><span><i class="fas fa-wallet text-success"></i>{{ $isAr ? 'رصيد المحفظة' : 'Wallet balance' }}</span><h4>{{ getPriceFormat(optional($wallet)->amount ?: 0) }}</h4><small>{{ $isAr ? 'الرصيد الحالي' : 'Current balance' }}</small></div>
        </div>
        <div class="card partner-finance-card">
            <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'سجل التسويات' : 'Settlement History' }}</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr>
        <th>{{ $isAr ? 'رقم التسوية' : 'Settlement' }}</th>
        <th>{{ $isAr ? 'تاريخ التحويل' : 'Transfer Date' }}</th>
        <th>{{ $isAr ? 'العمولة' : 'Commission' }}</th>
        <th>{{ $isAr ? 'ضريبة القيمة المضافة' : 'VAT' }}</th>
        <th>{{ $isAr ? 'المرجع البنكي' : 'Transfer Reference' }}</th>
        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
    </tr></thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td>#{{ $settlement->id }}</td>
                                <td>{{ optional($settlement->created_at)->format('Y-m-d') }}</td>
                                <td>{{ getPriceFormat($commission) }}</td>
                                <td>{{ getPriceFormat(0) }}</td>
                                <td>{{ $settlement->bank_id ? 'BANK-'.$settlement->bank_id : '-' }}</td>
                                <td><span class="badge badge-light">{{ quick_status_label($settlement->status ?? 'released') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ $isAr ? 'لا توجد تسويات مسجلة.' : 'No settlements found.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card partner-finance-card">
            <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'سجل المعاملات' : 'Transaction History' }}</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr>
        <th>{{ $isAr ? 'رقم الدفعة' : 'Payment' }}</th>
        <th>{{ $isAr ? 'الطلب' : 'Order' }}</th>
        <th>{{ $isAr ? 'المبلغ' : 'Amount' }}</th>
        <th>{{ $isAr ? 'طريقة الدفع' : 'Method' }}</th>
        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
        <th>{{ $isAr ? 'التاريخ' : 'Date' }}</th>
    </tr></thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td>#{{ $payment->booking_id }}</td>
                                <td>{{ getPriceFormat($payment->total_amount) }}</td>
                                <td>{{ $payment->payment_type ?: '-' }}</td>
                                <td><span class="badge badge-light">{{ quick_status_label($payment->payment_status ?: 'pending') }}</span></td>
                                <td>{{ optional($payment->created_at)->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">{{ $isAr ? 'لا توجد سجلات دفع مسجلة.' : 'No payment records found.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@section('bottom_script')
<style>
.partner-finance-heading{margin:10px 0 24px}.partner-finance-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-finance-heading h1 i{color:#1f6bff;font-size:23px}.partner-finance-heading p{margin:7px 0 0;color:#6d7d94}.partner-finance-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:20px}.partner-finance-kpis>div{background:#fff;border:1px solid #dce5f1;border-radius:16px;padding:19px;box-shadow:0 7px 18px rgba(15,41,51,.035)}.partner-finance-kpis span{display:flex;align-items:center;justify-content:space-between;gap:10px;color:#74849a;font-size:11px;font-weight:700}.partner-finance-kpis h4{margin:12px 0 5px;color:#17263c;font-size:24px;font-weight:800}.partner-finance-kpis small{color:#8b98aa}.partner-finance-card{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-finance-card .card-header{background:#fff;border-color:#e6ecf4;padding:18px 20px}.partner-finance-card .card-header h5{font-weight:800}.partner-finance-card .card-body{padding:0}.partner-finance-card thead th{background:#f6f8fc;color:#617188;border:0;padding:14px;font-size:11px;text-transform:uppercase;white-space:nowrap}.partner-finance-card tbody td{padding:14px;border-color:#edf1f6;font-size:12px;vertical-align:middle}
.quick-theme-dark .partner-finance-heading h1,.quick-theme-dark .partner-finance-kpis h4{color:#f4f8fb}.quick-theme-dark .partner-finance-kpis>div,.quick-theme-dark .partner-finance-card,.quick-theme-dark .partner-finance-card .card-header{background:#102536;border-color:#294154}
@media(max-width:991px){.partner-finance-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.partner-finance-heading h1{font-size:22px}}@media(max-width:480px){.partner-finance-kpis{grid-template-columns:1fr}}
</style>
@endsection
</x-master-layout>
