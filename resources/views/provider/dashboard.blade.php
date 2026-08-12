<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                            <span class="text-muted">Sanad Partner operations workspace</span>
                        </div>
                        <a href="{{ route('provider.order.index') }}" class="btn btn-primary btn-sm"><i class="fas fa-clipboard-list"></i> Assigned Orders</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($dashboard['kpis'] as $label => $value)
                <div class="col-xl-3 col-md-4 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <span class="text-muted small">{{ $label }}</span>
                            <h4 class="mb-0 mt-2">{{ $value }}</h4>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Today's Tasks</h5>
                        <a href="{{ route('provider.kanban.index') }}" class="btn-link">Board</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($dashboard['today_tasks'] as $order)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('provider.order.show', $order->id) }}">{{ $order->sanad_reference ?: 'SANAD-'.$order->id }}</a>
                                        <div class="text-muted small">{{ optional($order->service)->name_en ?: optional($order->service)->name }}</div>
                                    </div>
                                    <span class="badge badge-primary">{{ Str::headline($order->sanad_stage ?: 'submitted') }}</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No tasks updated today.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">SLA Alerts</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($dashboard['sla_alerts'] as $order)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <a href="{{ route('provider.order.show', $order->id) }}">{{ $order->sanad_reference ?: 'SANAD-'.$order->id }}</a>
                                        <div class="text-muted small">{{ optional($order->customer)->display_name }}</div>
                                    </div>
                                    <span class="{{ $order->sla_due_at && $order->sla_due_at->isPast() ? 'text-danger' : 'text-warning' }}">
                                        {{ optional($order->sla_due_at)->format('Y-m-d H:i') ?: '-' }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No SLA alerts.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Recent Orders</h5>
                        <a href="{{ route('provider.order.index') }}" class="btn-link">View all</a>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Stage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($dashboard['recent_orders'] as $order)
                                    <tr>
                                        <td><a href="{{ route('provider.order.show', $order->id) }}">{{ $order->sanad_reference ?: 'SANAD-'.$order->id }}</a></td>
                                        <td>{{ optional($order->customer)->display_name ?: '-' }}</td>
                                        <td>{{ optional($order->service)->name_en ?: optional($order->service)->name ?: '-' }}</td>
                                        <td><span class="badge badge-primary">{{ Str::headline($order->sanad_stage ?: 'submitted') }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No assigned orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="mb-0">Employee Workload</h5>
                        <a href="{{ route('provider.employees.index') }}" class="btn-link">Employees</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($dashboard['employee_workload'] as $employee)
                                <li class="list-group-item d-flex justify-content-between">
                                    <span>{{ $employee->display_name }}</span>
                                    <span>{{ $employee->active_orders_count }} / {{ $employee->sanad_daily_capacity ?: 0 }} active</span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No employees configured.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
