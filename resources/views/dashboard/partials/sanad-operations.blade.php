@php
    $sanad = $data['sanad'] ?? [];
    $stageCounts = $sanad['stage_counts'] ?? [];
    $recentRequests = $sanad['recent_requests'] ?? collect();
    $attentionRequests = $sanad['attention_requests'] ?? collect();
    $requestLabel = ucfirst($sanad['terminology']['request'] ?? 'Request');
    $paidRevenue = getPriceFormat($sanad['paid_revenue'] ?? 0);
@endphp

<div class="col-md-12">
    <div class="card sanad-operations-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">Sanad Operations</h4>
                <span class="text-muted">{{ $requestLabel }} lifecycle and alerts</span>
            </div>
            <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover"><u>{{ __('messages.view_all') }}</u></a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="sanad-metric">
                        <span>Active {{ Str::plural($requestLabel) }}</span>
                        <strong>{{ $sanad['active_requests'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="sanad-metric">
                        <span>Buzz Alerts</span>
                        <strong>{{ $sanad['unread_buzz'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="sanad-metric">
                        <span>Pending Documents</span>
                        <strong>{{ $sanad['pending_documents'] ?? 0 }}</strong>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="sanad-metric">
                        <span>AI Escalations</span>
                        <strong>{{ $sanad['ai_escalations'] ?? 0 }}</strong>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['action_state' => 'needs_action']) }}">
                        <span>Needs Action</span>
                        <strong>{{ $sanad['needs_action'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}">
                        <span>Unassigned</span>
                        <strong>{{ $sanad['unassigned_requests'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['sla_state' => 'overdue']) }}">
                        <span>Overdue SLA</span>
                        <strong>{{ $sanad['overdue_sla'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['sla_state' => 'due_soon']) }}">
                        <span>Due Soon</span>
                        <strong>{{ $sanad['due_soon_sla'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}">
                        <span>Payment Pending</span>
                        <strong>{{ $sanad['payment_pending'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-2 col-lg-4 col-md-6 mb-3">
                    <a class="sanad-action-metric" href="{{ route('sanad.requests.index', ['payment_state' => 'paid']) }}">
                        <span>Paid Revenue</span>
                        <strong>{{ $paidRevenue }}</strong>
                    </a>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-xl-4 col-lg-6 mb-3 mb-xl-0">
                    <div class="sanad-stage-grid">
                        @foreach($stageCounts as $stage => $count)
                            <div class="sanad-stage-item">
                                <span>{{ Str::headline($stage) }}</span>
                                <strong>{{ $count }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-xl-4 col-lg-6 mb-3 mb-xl-0">
                    <div class="sanad-recent-box">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0">Attention Queue</h5>
                            <span class="badge badge-light">{{ ($sanad['overdue_sla'] ?? 0) }} overdue</span>
                        </div>
                        @forelse($attentionRequests as $request)
                            <div class="sanad-request-row">
                                <div>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}">
                                        <strong>#{{ $request->sanad_reference ?: $request->id }}</strong>
                                    </a>
                                    <span>{{ optional($request->customer)->display_name ?: optional($request->customer)->first_name ?: '-' }}</span>
                                </div>
                                <div class="sanad-request-actions">
                                    <span class="badge badge-warning">{{ Str::headline($request->sanad_priority ?: 'normal') }}</span>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}" class="btn-link btn-link-hover">Open</a>
                                </div>
                            </div>
                        @empty
                            <div class="sanad-empty-state">
                                No urgent Sanad {{ Str::plural(strtolower($requestLabel)) }}
                            </div>
                        @endforelse
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="sanad-recent-box">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="font-weight-bold mb-0">Recent {{ Str::plural($requestLabel) }}</h5>
                            <span class="badge badge-light">{{ $sanad['open_chats'] ?? 0 }} open chats</span>
                        </div>
                        @forelse($recentRequests as $request)
                            <div class="sanad-request-row">
                                <div>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}">
                                        <strong>#{{ $request->sanad_reference ?: $request->id }}</strong>
                                    </a>
                                    <span>{{ optional($request->service)->name ?: '-' }}</span>
                                </div>
                                <div class="sanad-request-actions">
                                    <span class="badge badge-primary">{{ Str::headline($request->sanad_stage) }}</span>
                                    <a href="{{ route('sanad.requests.show', $request->id) }}" class="btn-link btn-link-hover">Open</a>
                                </div>
                            </div>
                        @empty
                            <div class="sanad-empty-state">
                                No Sanad {{ Str::plural(strtolower($requestLabel)) }} yet
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-operations-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-metric,
        .sanad-action-metric,
        .sanad-stage-item,
        .sanad-recent-box {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-metric {
            min-height: 96px;
            padding: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .sanad-action-metric {
            min-height: 78px;
            padding: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-action-metric:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-metric span,
        .sanad-action-metric span,
        .sanad-stage-item span,
        .sanad-request-row span,
        .sanad-empty-state {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-metric strong {
            font-size: 28px;
            line-height: 1;
        }

        .sanad-action-metric strong {
            font-size: 20px;
            line-height: 1.1;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .sanad-stage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            height: 100%;
        }

        .sanad-stage-item {
            min-height: 72px;
            padding: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .sanad-stage-item strong {
            font-size: 20px;
        }

        .sanad-recent-box {
            min-height: 100%;
            padding: 16px;
        }

        .sanad-request-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-request-row:first-of-type {
            border-top: 0;
        }

        .sanad-request-row div {
            min-width: 0;
        }

        .sanad-request-row div strong,
        .sanad-request-row div span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sanad-request-actions {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sanad-empty-state {
            padding: 18px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
    </style>
@endonce
