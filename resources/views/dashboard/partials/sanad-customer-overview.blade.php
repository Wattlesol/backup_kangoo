@php
    $customer = $data['sanad_customer'] ?? [];
    $nextRequests = $customer['next_requests'] ?? collect();
@endphp

<div class="col-md-12">
    <div class="card sanad-customer-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">{{ app()->getLocale() === "ar" ? "نظرة عامة على طلبات العميل" : "Customer Request Overview" }}</h4>
                <span class="text-muted">{{ app()->getLocale() === "ar" ? "الطلبات والإجراءات والمستندات والرسائل وحالة الدفع" : "Requests, actions, documents, messages, and payment status" }}</span>
            </div>
            <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover"><u>{{ __('messages.view_all') }}</u></a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-customer-kpi" href="{{ route('sanad.requests.index') }}">
                        <span>My Requests</span>
                        <strong>{{ $customer['total_requests'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-customer-kpi" href="{{ route('sanad.requests.index', ['action_state' => 'needs_action']) }}">
                        <span>Needs Action</span>
                        <strong>{{ $customer['awaiting_customer_action'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-customer-kpi" href="{{ route('sanad.requests.index', ['action_state' => 'pending_documents']) }}">
                        <span>Pending Documents</span>
                        <strong>{{ $customer['pending_documents'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-customer-kpi" href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}">
                        <span>Payment Pending</span>
                        <strong>{{ $customer['pending_payment_requests'] ?? 0 }}</strong>
                    </a>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-customer-box">
                        <h5 class="font-weight-bold mb-3">Request Status</h5>
                        <div class="sanad-customer-line">
                            <span>Active</span>
                            <strong>{{ $customer['active_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-customer-line">
                            <span>Completed</span>
                            <strong>{{ $customer['completed_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-customer-line">
                            <span>Paid</span>
                            <strong>{{ $customer['paid_requests'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-customer-box">
                        <h5 class="font-weight-bold mb-3">Communication</h5>
                        <div class="sanad-customer-line">
                            <span>Unread Buzz</span>
                            <strong>{{ $customer['unread_buzz'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-customer-line">
                            <span>Open Chats</span>
                            <strong>{{ $customer['open_chats'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-customer-line">
                            <span>Approved Documents</span>
                            <strong>{{ $customer['approved_documents'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sanad-customer-box">
                        <h5 class="font-weight-bold mb-3">Recent Requests</h5>
                        @forelse($nextRequests as $request)
                            <div class="sanad-customer-request">
                                <div>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}">
                                        <strong>{{ $request->quick_reference }}</strong>
                                    </a>
                                    <span>{{ optional($request->service)->name ?: '-' }}</span>
                                </div>
                                <span>{{ Str::headline($request->sanad_stage ?: 'submitted') }}</span>
                            </div>
                        @empty
                            <div class="sanad-customer-empty">No Quick requests yet</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-customer-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-customer-kpi,
        .sanad-customer-box {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-customer-kpi {
            min-height: 86px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-customer-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-customer-kpi span,
        .sanad-customer-line span,
        .sanad-customer-request span,
        .sanad-customer-empty {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-customer-kpi strong {
            font-size: 24px;
            line-height: 1.1;
            text-align: right;
        }

        .sanad-customer-box {
            min-height: 100%;
            padding: 16px;
        }

        .sanad-customer-line,
        .sanad-customer-request {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-customer-line:first-of-type,
        .sanad-customer-request:first-of-type {
            border-top: 0;
        }

        .sanad-customer-request div {
            min-width: 0;
        }

        .sanad-customer-request div strong,
        .sanad-customer-request div span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sanad-customer-empty {
            padding: 18px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
    </style>
@endonce
