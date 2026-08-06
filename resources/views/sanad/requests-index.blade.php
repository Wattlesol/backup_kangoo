<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="font-weight-bold mb-1">{{ $pageTitle }}</h4>
                                <span class="text-muted">Role-aware request operations queue</span>
                            </div>
                            <a href="{{ route('home') }}" class="btn-link btn-link-hover">Back to dashboard</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row sanad-summary-grid">
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index') }}" class="sanad-summary-card">
                                    <span>Total</span>
                                    <strong>{{ $summary['total'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['action_state' => 'needs_action']) }}" class="sanad-summary-card">
                                    <span>Needs Action</span>
                                    <strong>{{ $summary['needs_action'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}" class="sanad-summary-card">
                                    <span>Unassigned</span>
                                    <strong>{{ $summary['unassigned'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['sla_state' => 'overdue']) }}" class="sanad-summary-card">
                                    <span>Overdue SLA</span>
                                    <strong>{{ $summary['overdue_sla'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['action_state' => 'pending_documents']) }}" class="sanad-summary-card">
                                    <span>Pending Docs</span>
                                    <strong>{{ $summary['pending_documents'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['action_state' => 'unread_buzz']) }}" class="sanad-summary-card">
                                    <span>Unread Buzz</span>
                                    <strong>{{ $summary['unread_buzz'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['payment_state' => 'pending']) }}" class="sanad-summary-card">
                                    <span>Payment Pending</span>
                                    <strong>{{ $summary['payment_pending'] ?? 0 }}</strong>
                                </a>
                            </div>
                            <div class="col-lg-2 col-md-4 col-6 mb-3">
                                <a href="{{ route('sanad.requests.index', ['payment_state' => 'paid']) }}" class="sanad-summary-card">
                                    <span>Paid</span>
                                    <strong>{{ $summary['paid'] ?? 0 }}</strong>
                                </a>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('sanad.requests.index') }}" class="sanad-filter-form mb-4">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <label class="form-control-label">Search</label>
                                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Reference, service, customer">
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">Lifecycle Stage</label>
                                    <select name="sanad_stage" class="form-control">
                                        <option value="">All stages</option>
                                        @foreach(config('sanad.request_lifecycle', []) as $stage)
                                            <option value="{{ $stage }}" {{ request('sanad_stage') === $stage ? 'selected' : '' }}>{{ Str::headline($stage) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">Priority</label>
                                    <select name="sanad_priority" class="form-control">
                                        <option value="">All priorities</option>
                                        @foreach(['urgent', 'high', 'normal', 'low'] as $priority)
                                            <option value="{{ $priority }}" {{ request('sanad_priority') === $priority ? 'selected' : '' }}>{{ Str::headline($priority) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">SLA</label>
                                    <select name="sla_state" class="form-control">
                                        <option value="">Any SLA</option>
                                        <option value="overdue" {{ request('sla_state') === 'overdue' ? 'selected' : '' }}>Overdue</option>
                                        <option value="due_soon" {{ request('sla_state') === 'due_soon' ? 'selected' : '' }}>Due soon</option>
                                        <option value="none" {{ request('sla_state') === 'none' ? 'selected' : '' }}>No SLA</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">Assignment</label>
                                    <select name="assignment_state" class="form-control">
                                        <option value="">Any assignment</option>
                                        <option value="assigned" {{ request('assignment_state') === 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="unassigned" {{ request('assignment_state') === 'unassigned' ? 'selected' : '' }}>Unassigned</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">Action State</label>
                                    <select name="action_state" class="form-control">
                                        <option value="">Any action</option>
                                        <option value="needs_action" {{ request('action_state') === 'needs_action' ? 'selected' : '' }}>Needs action</option>
                                        <option value="pending_documents" {{ request('action_state') === 'pending_documents' ? 'selected' : '' }}>Pending documents</option>
                                        <option value="unread_buzz" {{ request('action_state') === 'unread_buzz' ? 'selected' : '' }}>Unread Buzz</option>
                                        <option value="open_chat" {{ request('action_state') === 'open_chat' ? 'selected' : '' }}>Open chat</option>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-control-label">Payment</label>
                                    <select name="payment_state" class="form-control">
                                        <option value="">Any payment</option>
                                        <option value="paid" {{ request('payment_state') === 'paid' ? 'selected' : '' }}>Paid</option>
                                        <option value="pending" {{ request('payment_state') === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="advanced_paid" {{ request('payment_state') === 'advanced_paid' ? 'selected' : '' }}>Advanced Paid</option>
                                        <option value="pending_by_admin" {{ request('payment_state') === 'pending_by_admin' ? 'selected' : '' }}>Pending By Admin</option>
                                        <option value="failed" {{ request('payment_state') === 'failed' ? 'selected' : '' }}>Failed</option>
                                        <option value="no_payment" {{ request('payment_state') === 'no_payment' ? 'selected' : '' }}>No Payment</option>
                                    </select>
                                </div>
                                <div class="col-lg-1 col-md-6 mb-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                                <div class="col-lg-1 col-md-6 mb-3 d-flex align-items-end">
                                    <a href="{{ route('sanad.requests.index') }}" class="btn btn-light">Reset</a>
                                </div>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-striped border sanad-requests-table">
                                <thead>
                                    <tr>
                                        <th>Request</th>
                                        <th>Service</th>
                                        <th>Customer</th>
                                        <th>Partner</th>
                                        <th>Employees</th>
                                        <th>Stage</th>
                                        <th>Priority</th>
                                        <th>Payment</th>
                                        <th>SLA</th>
                                        <th>Updated</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($requests as $requestItem)
                                        @php
                                            $slaClass = '';
                                            $rowFlags = [];
                                            if ($requestItem->sla_due_at && $requestItem->sla_due_at->isPast()) {
                                                $slaClass = 'text-danger';
                                                $rowFlags[] = 'Overdue';
                                            } elseif ($requestItem->sla_due_at && $requestItem->sla_due_at->lessThanOrEqualTo(now()->addDay())) {
                                                $slaClass = 'text-warning';
                                                $rowFlags[] = 'Due soon';
                                            }
                                            if ($requestItem->handymanAdded->isEmpty()) {
                                                $rowFlags[] = 'Unassigned';
                                            }
                                            if ($requestItem->sanadDocuments->where('verification_status', 'pending')->count() > 0) {
                                                $rowFlags[] = 'Docs';
                                            }
                                            if ($requestItem->sanadBuzzAlerts->where('status', 'unread')->count() > 0) {
                                                $rowFlags[] = 'Buzz';
                                            }
                                            $paymentStatus = optional($requestItem->payment)->payment_status ?: 'no_payment';
                                            if ($paymentStatus !== 'paid') {
                                                $rowFlags[] = 'Payment';
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <a href="{{ route('sanad.requests.show', $requestItem->id) }}" class="btn-link btn-link-hover">
                                                    #{{ $requestItem->sanad_reference ?: str_pad($requestItem->id, 6, '0', STR_PAD_LEFT) }}
                                                </a>
                                                <small>{{ Str::headline($requestItem->status ?: 'pending') }}</small>
                                                @if(!empty($rowFlags))
                                                    <div class="sanad-row-flags">
                                                        @foreach($rowFlags as $flag)
                                                            <span>{{ $flag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td>{{ optional($requestItem->service)->name ?: '-' }}</td>
                                            <td>{{ optional($requestItem->customer)->display_name ?: '-' }}</td>
                                            <td>{{ optional($requestItem->provider)->display_name ?: '-' }}</td>
                                            <td>
                                                @forelse($requestItem->handymanAdded as $mapping)
                                                    <span class="sanad-employee-chip">{{ optional($mapping->handyman)->display_name ?: '-' }}</span>
                                                @empty
                                                    <span class="badge badge-light">Unassigned</span>
                                                @endforelse
                                            </td>
                                            <td><span class="badge badge-primary">{{ Str::headline($requestItem->sanad_stage ?: 'submitted') }}</span></td>
                                            <td><span class="badge badge-light">{{ Str::headline($requestItem->sanad_priority ?: 'normal') }}</span></td>
                                            <td>
                                                <span class="badge {{ $paymentStatus === 'paid' ? 'badge-success' : 'badge-light' }}">{{ Str::headline($paymentStatus) }}</span>
                                                <small>{{ $requestItem->total_amount ? getPriceFormat($requestItem->total_amount) : '-' }}</small>
                                            </td>
                                            <td class="{{ $slaClass }}">
                                                {{ $requestItem->sla_due_at ? $requestItem->sla_due_at->format('Y-m-d H:i') : '-' }}
                                            </td>
                                            <td>{{ optional($requestItem->updated_at)->diffForHumans() }}</td>
                                            <td class="text-right">
                                                <a href="{{ route('sanad.requests.show', $requestItem->id) }}" class="btn btn-sm btn-primary">Open</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11">
                                                <div class="sanad-empty-state">No Sanad requests match the current filters</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mt-3">
                            <span class="text-muted">Showing {{ $requests->firstItem() ?: 0 }}-{{ $requests->lastItem() ?: 0 }} of {{ $requests->total() }}</span>
                            {{ $requests->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .sanad-filter-form {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                padding: 16px;
                background: #fff;
            }

            .sanad-summary-card {
                min-height: 86px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 16px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
                color: inherit;
            }

            .sanad-summary-card:hover {
                color: inherit;
                border-color: rgba(0, 0, 0, 0.18);
            }

            .sanad-summary-card span {
                color: #6c757d;
                font-size: 13px;
            }

            .sanad-summary-card strong {
                font-size: 26px;
            }

            .sanad-filter-form .form-control-label {
                font-weight: 600;
                font-size: 13px;
            }

            .sanad-requests-table td,
            .sanad-requests-table th {
                vertical-align: middle;
            }

            .sanad-requests-table small {
                display: block;
                color: #6c757d;
                margin-top: 4px;
            }

            .sanad-employee-chip {
                display: inline-block;
                max-width: 160px;
                margin: 2px 4px 2px 0;
                padding: 3px 8px;
                border-radius: 999px;
                background: #f4f6f8;
                color: #495057;
                font-size: 12px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                vertical-align: middle;
            }

            .sanad-empty-state {
                padding: 18px;
                color: #6c757d;
                text-align: center;
            }

            .sanad-row-flags {
                display: flex;
                flex-wrap: wrap;
                gap: 4px;
                margin-top: 6px;
            }

            .sanad-row-flags span {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 999px;
                background: #fff4e5;
                color: #9a5b00;
                font-size: 11px;
            }
        </style>
    @endonce
</x-master-layout>
