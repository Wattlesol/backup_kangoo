@php
    $partner = $data['sanad_partner'] ?? [];
    $recentWorkload = $partner['recent_workload'] ?? collect();
@endphp

<div class="col-md-12">
    <div class="card sanad-partner-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">{{ app()->getLocale() === "ar" ? "نظرة عامة على الشريك" : "Partner Overview" }}</h4>
                <span class="text-muted">{{ app()->getLocale() === "ar" ? "الطلبات المسندة، طاقة الموظفين، الخدمات، وحالة المدفوعات" : "Assigned work, employee capacity, services, and payment visibility" }}</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('sanad.partner-performance') }}" class="btn-link btn-link-hover"><u>Partner performance</u></a>
                <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover"><u>{{ __('messages.view_all') }}</u></a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-partner-kpi" href="{{ route('sanad.requests.index', ['stage' => 'assigned_to_partner']) }}">
                        <span>Assigned Requests</span>
                        <strong>{{ $partner['assigned_requests'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-partner-kpi" href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}">
                        <span>Needs Employee</span>
                        <strong>{{ $partner['unassigned_employee_requests'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-partner-kpi" href="{{ route('handyman.index') }}">
                        <span>Active Employees</span>
                        <strong>{{ $partner['active_employee_count'] ?? 0 }}/{{ $partner['employee_count'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-partner-kpi" href="{{ route('service.index') }}">
                        <span>Active Services</span>
                        <strong>{{ $partner['active_service_count'] ?? 0 }}/{{ $partner['service_count'] ?? 0 }}</strong>
                    </a>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-partner-box">
                        <h5 class="font-weight-bold mb-3">Request Flow</h5>
                        <div class="sanad-partner-line">
                            <span>In Progress</span>
                            <strong>{{ $partner['in_progress_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-partner-line">
                            <span>Awaiting Customer</span>
                            <strong>{{ $partner['awaiting_customer_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-partner-line">
                            <span>Quality Review</span>
                            <strong>{{ $partner['quality_review_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-partner-line">
                            <span>Completed</span>
                            <strong>{{ $partner['completed_requests'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-partner-box">
                        <h5 class="font-weight-bold mb-3">Payment Visibility</h5>
                        <div class="sanad-partner-line">
                            <span>Paid Requests</span>
                            <strong>{{ $partner['paid_requests'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-partner-line">
                            <span>Pending Payment</span>
                            <strong>{{ $partner['pending_payment_requests'] ?? 0 }}</strong>
                        </div>
                        <a href="{{ route('payment.index') }}" class="btn-link btn-link-hover d-inline-block mt-2">Open payments</a>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sanad-partner-box">
                        <h5 class="font-weight-bold mb-3">Recent Workload</h5>
                        @forelse($recentWorkload as $request)
                            <div class="sanad-partner-workload">
                                <div>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}">
                                        <strong>{{ $request->quick_reference }}</strong>
                                    </a>
                                    <span>{{ optional($request->service)->name ?: '-' }}</span>
                                </div>
                                <span>{{ $request->handymanAdded->pluck('handyman.display_name')->filter()->join(', ') ?: 'Unassigned' }}</span>
                            </div>
                        @empty
                            <div class="sanad-partner-empty">No active partner workload yet</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-partner-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-partner-kpi,
        .sanad-partner-box {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-partner-kpi {
            min-height: 88px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-partner-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-partner-kpi span,
        .sanad-partner-line span,
        .sanad-partner-workload span,
        .sanad-partner-empty {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-partner-kpi strong {
            font-size: 24px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .sanad-partner-box {
            min-height: 100%;
            padding: 16px;
        }

        .sanad-partner-line,
        .sanad-partner-workload {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-partner-line:first-of-type,
        .sanad-partner-workload:first-of-type {
            border-top: 0;
        }

        .sanad-partner-workload div {
            min-width: 0;
        }

        .sanad-partner-workload div strong,
        .sanad-partner-workload div span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sanad-partner-empty {
            padding: 18px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
    </style>
@endonce
