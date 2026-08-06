@php
    $employee = $data['sanad_employee'] ?? [];
    $nextTasks = $employee['next_tasks'] ?? collect();
@endphp

<div class="col-md-12">
    <div class="card sanad-employee-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="font-weight-bold mb-1">Employee Task Overview</h4>
                <span class="text-muted">Assigned work, status progress, evidence, communication, and payment visibility</span>
            </div>
            <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover"><u>{{ __('messages.view_all') }}</u></a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-employee-kpi" href="{{ route('sanad.requests.index') }}">
                        <span>Assigned Tasks</span>
                        <strong>{{ $employee['assigned_tasks'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-employee-kpi" href="{{ route('sanad.requests.index', ['sanad_stage' => 'in_progress']) }}">
                        <span>In Progress</span>
                        <strong>{{ $employee['in_progress_tasks'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-employee-kpi" href="{{ route('sanad.requests.index', ['sanad_stage' => 'awaiting_quality_review']) }}">
                        <span>Awaiting Review</span>
                        <strong>{{ $employee['awaiting_review_tasks'] ?? 0 }}</strong>
                    </a>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <a class="sanad-employee-kpi" href="{{ route('sanad.requests.index', ['payment_state' => 'paid']) }}">
                        <span>Paid Tasks</span>
                        <strong>{{ $employee['paid_tasks'] ?? 0 }}</strong>
                    </a>
                </div>
            </div>

            <div class="row align-items-stretch">
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-employee-box">
                        <h5 class="font-weight-bold mb-3">Task Signals</h5>
                        <div class="sanad-employee-line">
                            <span>Today</span>
                            <strong>{{ $employee['today_tasks'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-employee-line">
                            <span>Active</span>
                            <strong>{{ $employee['active_tasks'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-employee-line">
                            <span>Completed</span>
                            <strong>{{ $employee['completed_tasks'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3 mb-lg-0">
                    <div class="sanad-employee-box">
                        <h5 class="font-weight-bold mb-3">Action Items</h5>
                        <div class="sanad-employee-line">
                            <span>Pending Evidence</span>
                            <strong>{{ $employee['pending_evidence'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-employee-line">
                            <span>Unread Buzz</span>
                            <strong>{{ $employee['unread_buzz'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-employee-line">
                            <span>Open Chats</span>
                            <strong>{{ $employee['open_chats'] ?? 0 }}</strong>
                        </div>
                        <div class="sanad-employee-line">
                            <span>Pending Payment</span>
                            <strong>{{ $employee['pending_payment_tasks'] ?? 0 }}</strong>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="sanad-employee-box">
                        <h5 class="font-weight-bold mb-3">Next Tasks</h5>
                        @forelse($nextTasks as $task)
                            <div class="sanad-employee-task">
                                <div>
                                    <a href="{{ route('sanad.requests.show', $task->id) }}">
                                        <strong>#{{ $task->sanad_reference ?: $task->id }}</strong>
                                    </a>
                                    <span>{{ optional($task->service)->name ?: '-' }}</span>
                                </div>
                                <span>{{ Str::headline($task->sanad_stage ?: 'submitted') }}</span>
                            </div>
                        @empty
                            <div class="sanad-employee-empty">No active assigned tasks</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <style>
        .sanad-employee-card .card-header {
            border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-employee-kpi,
        .sanad-employee-box {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
        }

        .sanad-employee-kpi {
            min-height: 86px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            color: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .sanad-employee-kpi:hover {
            color: inherit;
            border-color: rgba(255, 111, 0, 0.35);
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
            text-decoration: none;
        }

        .sanad-employee-kpi span,
        .sanad-employee-line span,
        .sanad-employee-task span,
        .sanad-employee-empty {
            color: #6c757d;
            font-size: 13px;
        }

        .sanad-employee-kpi strong {
            font-size: 24px;
            line-height: 1.1;
            text-align: right;
        }

        .sanad-employee-box {
            min-height: 100%;
            padding: 16px;
        }

        .sanad-employee-line,
        .sanad-employee-task {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }

        .sanad-employee-line:first-of-type,
        .sanad-employee-task:first-of-type {
            border-top: 0;
        }

        .sanad-employee-task div {
            min-width: 0;
        }

        .sanad-employee-task div strong,
        .sanad-employee-task div span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sanad-employee-empty {
            padding: 18px 0;
            border-top: 1px solid rgba(0, 0, 0, 0.06);
        }
    </style>
@endonce
