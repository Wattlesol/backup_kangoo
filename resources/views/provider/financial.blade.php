<x-master-layout>
    <div class="container-fluid">
        <div class="card"><div class="card-body"><h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5><span class="text-muted">Payments, settlements, commission, VAT, refunds, and wallet.</span></div></div>
        <div class="row">
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Customer Payments</span><h4>{{ getPriceFormat($paid) }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Pending Payments</span><h4>{{ getPriceFormat($pending) }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Pending Settlement</span><h4>{{ getPriceFormat(max($paid - $settlements->sum('amount'), 0)) }}</h4></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Wallet Balance</span><h4>{{ getPriceFormat(optional($wallet)->amount ?: 0) }}</h4></div></div></div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Settlement History</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Settlement</th><th>Transfer Date</th><th>Commission</th><th>VAT</th><th>Transfer Reference</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td>#{{ $settlement->id }}</td>
                                <td>{{ optional($settlement->created_at)->format('Y-m-d') }}</td>
                                <td>{{ getPriceFormat($commission) }}</td>
                                <td>{{ getPriceFormat(0) }}</td>
                                <td>{{ $settlement->bank_id ? 'BANK-'.$settlement->bank_id : '-' }}</td>
                                <td><span class="badge badge-light">{{ Str::headline($settlement->status ?? 'released') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No settlements found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Transaction History</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Payment</th><th>Booking</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse($payments as $payment)
                            <tr>
                                <td>#{{ $payment->id }}</td>
                                <td>#{{ $payment->booking_id }}</td>
                                <td>{{ getPriceFormat($payment->total_amount) }}</td>
                                <td>{{ $payment->payment_type ?: '-' }}</td>
                                <td><span class="badge badge-light">{{ Str::headline($payment->payment_status ?: 'pending') }}</span></td>
                                <td>{{ optional($payment->created_at)->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-muted">No payment records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>
