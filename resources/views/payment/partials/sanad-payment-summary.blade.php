@php
    $summary = $sanadPaymentSummary ?? [];
    $roleScope = $summary['role_scope'] ?? [
        'label' => 'Sanad finance scope',
        'description' => 'Financial information is scoped by the signed-in user role.',
        'can_bulk_manage' => false,
    ];
@endphp

<div class="col-lg-12">
    <div class="card sanad-payment-summary">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">Sanad Financial Center</h4>
                <span class="text-muted">Customer payments, settlements, commission, VAT, refunds, invoices, transactions, and wallet balances</span>
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <a href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}" class="btn-link btn-link-hover"><u>Pending payments</u></a>
                @if(Route::has('providerpayout.index'))
                    <a href="{{ route('providerpayout.index') }}" class="btn-link btn-link-hover"><u>Settlements</u></a>
                @endif
                @if(Route::has('wallet.index'))
                    <a href="{{ route('wallet.index') }}" class="btn-link btn-link-hover"><u>Wallet</u></a>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="sanad-finance-scope mb-3">
                <div>
                    <strong>{{ $roleScope['label'] }}</strong>
                    <span>{{ $roleScope['description'] }}</span>
                </div>
                <span class="badge {{ ($roleScope['can_bulk_manage'] ?? false) ? 'badge-success' : 'badge-light' }}">
                    {{ ($roleScope['can_bulk_manage'] ?? false) ? 'Bulk management enabled' : 'Scoped view only' }}
                </span>
            </div>

            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Customer Payments</span>
                        <strong>{{ $summary['customer_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Pending Payments</span>
                        <strong>{{ getPriceFormat($summary['pending_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Settlements</span>
                        <strong>{{ getPriceFormat($summary['settled_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Platform Commission</span>
                        <strong>{{ getPriceFormat($summary['platform_commission'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Invoices</span>
                        <strong>{{ $summary['paid_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>VAT</span>
                        <strong>{{ getPriceFormat($summary['vat_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Refunds</span>
                        <strong>{{ getPriceFormat($summary['refund_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Transaction History</span>
                        <strong>{{ getPriceFormat($summary['total_amount'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-wallet-kpi">
                        <span>Current Balance</span>
                        <strong>{{ getPriceFormat($summary['wallet_current_balance'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-wallet-kpi">
                        <span>Pending Balance</span>
                        <strong>{{ getPriceFormat($summary['wallet_pending_balance'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-wallet-kpi">
                        <span>Released Balance</span>
                        <strong>{{ getPriceFormat($summary['wallet_released_balance'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-wallet-kpi">
                        <span>Upcoming Settlement</span>
                        <strong>{{ getPriceFormat($summary['upcoming_settlement'] ?? 0) }}</strong>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-6 mb-3 mb-xl-0">
                    <div class="sanad-finance-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Settlement History</h5>
                            <span class="text-muted">{{ $summary['settlements_count'] ?? 0 }} records</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 sanad-finance-table">
                                <thead>
                                    <tr>
                                        <th>Settlement No.</th>
                                        <th>Transfer Date</th>
                                        <th>Amount</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($summary['recent_settlements'] ?? []) as $settlement)
                                        <tr>
                                            <td>#{{ $settlement->id }}</td>
                                            <td>{{ $settlement->paid_date ? date('Y-m-d', strtotime($settlement->paid_date)) : '-' }}</td>
                                            <td>{{ getPriceFormat($settlement->amount ?? 0) }}</td>
                                            <td>{{ $settlement->payment_method ?? '-' }}</td>
                                            <td><span class="badge badge-primary1">{{ ucfirst(str_replace('_', ' ', $settlement->status ?? 'pending')) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted text-center py-3">No settlement history available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-xl-6">
                    <div class="sanad-finance-panel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Transaction History</h5>
                            <span class="text-muted">{{ $summary['total_payments'] ?? 0 }} payments</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 sanad-finance-table">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse(($summary['recent_transactions'] ?? []) as $transaction)
                                        <tr>
                                            <td>
                                                @if($transaction->booking_id && Route::has('invoice_pdf'))
                                                    <a href="{{ route('invoice_pdf', $transaction->booking_id) }}" class="btn-link btn-link-hover">#{{ $transaction->booking_id }}</a>
                                                @else
                                                    #{{ $transaction->booking_id ?? $transaction->id }}
                                                @endif
                                            </td>
                                            <td>{{ optional($transaction->customer)->display_name ?? '-' }}</td>
                                            <td>{{ optional(optional($transaction->booking)->service)->name ?? '-' }}</td>
                                            <td>{{ getPriceFormat($transaction->total_amount ?? 0) }}</td>
                                            <td><span class="badge badge-primary1">{{ ucfirst(str_replace('_', ' ', $transaction->payment_status ?? 'pending')) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-muted text-center py-3">No transaction history available.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-payment-summary .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-payment-kpi,
        .sanad-wallet-kpi {
            min-height: 84px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-wallet-kpi {
            background: #f8fafc;
        }

        .sanad-payment-kpi span,
        .sanad-wallet-kpi span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-payment-kpi strong,
        .sanad-wallet-kpi strong {
            font-size: 20px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .sanad-finance-panel {
            height: 100%;
            padding: 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-finance-scope {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #f8fafc;
        }

        .sanad-finance-scope strong,
        .sanad-finance-scope span {
            display: block;
        }

        .sanad-finance-scope div span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-finance-table th {
            white-space: nowrap;
            font-size: 12px;
            color: #6c757d;
        }

        .sanad-finance-table td {
            vertical-align: middle;
            white-space: nowrap;
        }
    </style>
@endonce
