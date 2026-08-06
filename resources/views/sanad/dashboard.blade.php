<x-master-layout>
    <div class="container-fluid sanad-role-dashboard">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="font-weight-bold mb-1">{{ $pageTitle }}</h4>
                                <span class="text-muted">Role-based Sanad operations view connected to the shared request workflow</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('sanad.requests.index') }}" class="btn btn-primary">Open Requests</a>
                                <a href="{{ route('sanad.ai.index') }}" class="btn btn-light">AI Console</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @foreach($dashboard['metrics'] as $metric)
                <div class="col-xl-3 col-md-6 mb-3">
                    <a href="{{ route('sanad.requests.index', $metric['filter'] ?? []) }}" class="sanad-dashboard-metric">
                        <span>{{ $metric['label'] }}</span>
                        <strong>{{ $metric['value'] }}</strong>
                    </a>
                </div>
            @endforeach

            <div class="col-xl-7 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 font-weight-bold">{{ $role === 'customer' ? 'My Recent Requests' : 'Recent Orders' }}</h5>
                        <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover">View all</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 sanad-dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Request</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>SLA</th>
                                        <th class="text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($dashboard['recent_orders'] as $order)
                                        <tr>
                                            <td>
                                                <strong>#{{ $order->sanad_reference ?: str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                                <small>{{ optional($order->customer)->display_name ?: '-' }}</small>
                                            </td>
                                            <td>{{ optional($order->service)->name ?: '-' }}</td>
                                            <td><span class="badge badge-primary">{{ Str::headline($order->sanad_stage ?: $order->status ?: 'submitted') }}</span></td>
                                            <td class="{{ $order->sla_due_at && $order->sla_due_at->isPast() ? 'text-danger' : '' }}">
                                                {{ $order->sla_due_at ? $order->sla_due_at->format('Y-m-d H:i') : '-' }}
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ route('sanad.requests.show', $order->id) }}" class="btn btn-sm btn-light">Open</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">No requests available yet</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-5 mb-3">
                <div class="card h-100">
                    <div class="card-header">
                        <h5 class="mb-0 font-weight-bold">{{ $role === 'customer' ? 'Items Needing Attention' : 'Priority Work' }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="sanad-priority-list">
                            @forelse($dashboard['priority_orders'] as $order)
                                <a href="{{ route('sanad.requests.show', $order->id) }}" class="sanad-priority-item">
                                    <div>
                                        <strong>#{{ $order->sanad_reference ?: str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                        <span>{{ optional($order->service)->name ?: '-' }}</span>
                                    </div>
                                    <span class="badge {{ $order->sanad_priority === 'urgent' ? 'badge-danger' : 'badge-warning' }}">
                                        {{ Str::headline($order->sanad_priority ?: 'normal') }}
                                    </span>
                                </a>
                            @empty
                                <div class="text-muted">No urgent or near-deadline work right now</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            @if(in_array($role, ['admin', 'partner', 'employee']))
                <div class="col-lg-12 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0 font-weight-bold">Operations Kanban</h5>
                        </div>
                        <div class="card-body">
                            <div class="sanad-kanban">
                                @foreach($dashboard['kanban'] as $stage => $orders)
                                    <div class="sanad-kanban-column">
                                        <div class="sanad-kanban-title">
                                            <strong>{{ Str::headline($stage) }}</strong>
                                            <span>{{ $orders->count() }}</span>
                                        </div>
                                        @forelse($orders as $order)
                                            <a href="{{ route('sanad.requests.show', $order->id) }}" class="sanad-kanban-card">
                                                <strong>#{{ $order->sanad_reference ?: str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</strong>
                                                <span>{{ optional($order->service)->name ?: '-' }}</span>
                                                <small>{{ optional($order->customer)->display_name ?: '-' }}</small>
                                            </a>
                                        @empty
                                            <div class="sanad-kanban-empty">No orders</div>
                                        @endforelse
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if(in_array($role, ['admin', 'partner']))
                <div class="col-lg-12 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0 font-weight-bold">Employee Workload</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @forelse($dashboard['employee_workload'] as $employee)
                                    <div class="col-xl-3 col-md-6 mb-3">
                                        <div class="sanad-workload-card">
                                            <strong>{{ $employee->display_name }}</strong>
                                            <span>{{ $employee->email }}</span>
                                            <div>
                                                <small>Active orders</small>
                                                <b>{{ $employee->active_orders_count }}</b>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-lg-12 text-muted">No employees available for workload tracking</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @once
        <style>
            .sanad-role-dashboard .gap-2 { gap: 8px; }
            .sanad-role-dashboard .gap-3 { gap: 12px; }
            .sanad-dashboard-metric,
            .sanad-priority-item,
            .sanad-kanban-card {
                color: inherit;
                text-decoration: none;
            }
            .sanad-dashboard-metric {
                min-height: 96px;
                padding: 18px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .sanad-dashboard-metric span,
            .sanad-dashboard-table small,
            .sanad-priority-item span,
            .sanad-kanban-card span,
            .sanad-kanban-card small,
            .sanad-workload-card span,
            .sanad-workload-card small {
                display: block;
                color: #6c757d;
            }
            .sanad-dashboard-metric strong {
                font-size: 24px;
                color: #111827;
            }
            .sanad-priority-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .sanad-priority-item,
            .sanad-workload-card {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                padding: 12px;
                background: #fff;
            }
            .sanad-priority-item {
                display: flex;
                justify-content: space-between;
                gap: 12px;
            }
            .sanad-kanban {
                display: grid;
                grid-auto-flow: column;
                grid-auto-columns: minmax(220px, 1fr);
                gap: 12px;
                overflow-x: auto;
                padding-bottom: 6px;
            }
            .sanad-kanban-column {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #f8f9fb;
                padding: 10px;
                min-height: 220px;
            }
            .sanad-kanban-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }
            .sanad-kanban-title span {
                min-width: 26px;
                height: 26px;
                border-radius: 13px;
                background: #fff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
            }
            .sanad-kanban-card {
                display: block;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
                padding: 10px;
                margin-bottom: 8px;
            }
            .sanad-kanban-empty {
                color: #8a8f98;
                font-size: 13px;
                padding: 12px 4px;
            }
            .sanad-workload-card {
                min-height: 116px;
            }
            .sanad-workload-card div {
                margin-top: 12px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
        </style>
    @endonce
</x-master-layout>
