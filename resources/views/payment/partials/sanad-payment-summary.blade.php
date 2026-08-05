@php
    $summary = $sanadPaymentSummary ?? [];
@endphp

<div class="col-lg-12">
    <div class="card sanad-payment-summary">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">Sanad Payment Center</h4>
                <span class="text-muted">Role-scoped payments, paid revenue, pending balances, cash, and failed transactions</span>
            </div>
            <a href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}" class="btn-link btn-link-hover"><u>Payment queue</u></a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Total Payments</span>
                        <strong>{{ $summary['total_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Paid</span>
                        <strong>{{ $summary['paid_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Pending</span>
                        <strong>{{ $summary['pending_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="sanad-payment-kpi">
                        <span>Failed</span>
                        <strong>{{ $summary['failed_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="sanad-payment-kpi">
                        <span>Cash Payments</span>
                        <strong>{{ $summary['cash_payments'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="sanad-payment-kpi">
                        <span>Paid Amount</span>
                        <strong>{{ getPriceFormat($summary['paid_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="sanad-payment-kpi">
                        <span>Pending Amount</span>
                        <strong>{{ getPriceFormat($summary['pending_amount'] ?? 0) }}</strong>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-0">
                    <div class="sanad-payment-kpi">
                        <span>Total Amount</span>
                        <strong>{{ getPriceFormat($summary['total_amount'] ?? 0) }}</strong>
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

        .sanad-payment-kpi {
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

        .sanad-payment-kpi span {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-payment-kpi strong {
            font-size: 20px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }
    </style>
@endonce
