<x-master-layout>
<div class="container-fluid sanad-assignment-page">
    <div class="card card-block card-stretch">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="font-weight-bold mb-1">Assignment</h4>
                <span class="text-muted">Assign orders to Partners, then track acceptance and delivery commitment.</span>
            </div>
            <a href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}" class="btn btn-sm btn-outline-primary">View Unassigned Orders</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="btn-group mb-3">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('sanad.assignments.index') }}">All</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('sanad.assignments.index', ['assignment_state'=>'unassigned']) }}">Unassigned</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('sanad.assignments.index', ['assignment_state'=>'assigned']) }}">Assigned</a>
            </div>

            @forelse($orders as $order)
                @php
                    $decision = $latestDecisions[$order->id] ?? null;
                    $acceptedAt = data_get(optional($decision)->score_snapshot, 'accepted_at');
                    $accepted = in_array($order->status, ['accept', 'accepted', 'in_progress'], true) || !empty($acceptedAt);
                    $progressLabel = $accepted ? Str::headline($order->sanad_stage ?: $order->status) : ($order->provider_id ? 'Waiting Partner Acceptance' : 'Unassigned');
                @endphp
                <div class="sanad-assignment-item">
                    <div class="sanad-assignment-head">
                        <div>
                            <strong>{{ $order->sanad_reference ?: 'Order #'.$order->id }}</strong>
                            <span>· {{ optional($order->service)->name_en ?: optional($order->service)->name }}</span>
                            <div class="text-muted small mt-1">
                                Customer: {{ optional($order->customer)->display_name ?: '-' }} · Priority: {{ $order->sanad_priority ?: 'normal' }}
                            </div>
                        </div>
                        <span class="badge {{ $order->provider_id ? 'badge-primary' : 'badge-light' }}">{{ $progressLabel }}</span>
                    </div>

                    <div class="sanad-assignment-status">
                        <div>
                            <span>Partner</span>
                            <strong>{{ optional($order->provider)->display_name ?: 'Not assigned' }}</strong>
                        </div>
                        <div>
                            <span>Assigned On</span>
                            <strong>{{ optional($order->assigned_at)->format('M d, Y h:i A') ?: '-' }}</strong>
                        </div>
                        <div>
                            <span>Accepted On</span>
                            <strong>{{ $acceptedAt ? \Carbon\Carbon::parse($acceptedAt)->format('M d, Y h:i A') : '-' }}</strong>
                        </div>
                        <div>
                            <span>Expected Delivery</span>
                            <strong>{{ optional($order->expected_completion_at)->format('M d, Y h:i A') ?: '-' }}</strong>
                        </div>
                    </div>

                    <div class="row mt-3">
                        @forelse($recommendations[$order->id] ?? [] as $candidate)
                            <div class="col-lg-4 mb-2">
                                <div class="sanad-candidate-card">
                                    <strong>{{ $candidate->display_name }}</strong>
                                    <div class="small">Score: {{ $candidate->assignment_score }} · Active: {{ $candidate->assignment_metrics['active'] }} · Capacity: {{ $candidate->assignment_metrics['capacity'] ?: 'open' }}</div>
                                    <div class="small">SLA: {{ $candidate->assignment_metrics['sla'] }}% · Acceptance: {{ $candidate->assignment_metrics['acceptance'] }}% · Avg: {{ $candidate->assignment_metrics['avg'] ?: '-' }} min</div>
                                    <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="provider_id" value="{{ $candidate->id }}">
                                        <input type="hidden" name="mode" value="suggested">
                                        <button class="btn btn-sm btn-primary">Assign Suggested Partner</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">No suggested Partner is currently available. You can assign manually below.</div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}" class="sanad-manual-assignment">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="col-md-4 mb-2">
                                <label class="form-control-label">{{ $order->provider_id ? 'Reassign Partner' : 'Assign Partner' }}</label>
                                <select name="provider_id" class="form-control" required>
                                    <option value="">{{ $order->provider_id ? 'Reassign to Partner...' : 'Assign to Partner...' }}</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ (int) $order->provider_id === (int) $partner->id ? 'selected' : '' }}>{{ $partner->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="form-control-label">Assignment Note</label>
                                <input name="reason" class="form-control" placeholder="{{ $order->provider_id ? 'Reason required for reassignment' : 'Optional note for Partner assignment' }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="hidden" name="mode" value="manual">
                                <button class="btn btn-outline-primary btn-block">{{ $order->provider_id ? 'Reassign' : 'Assign' }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @empty
                <div class="text-muted py-4">No orders found.</div>
            @endforelse

            {{ $orders->links() }}
        </div>
    </div>
</div>

@push('after-styles')
    <style>
        .sanad-assignment-item { border: 1px solid #edf1f7; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff; }
        .sanad-assignment-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .sanad-assignment-status { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 10px; margin-top: 14px; }
        .sanad-assignment-status > div, .sanad-candidate-card { border: 1px solid #edf1f7; border-radius: 8px; padding: 12px; background: #fbfcfe; }
        .sanad-assignment-status span { display: block; color: #64748b; font-size: 12px; margin-bottom: 4px; }
        .sanad-assignment-status strong { color: #111827; }
        .sanad-manual-assignment { border-top: 1px solid #edf1f7; margin-top: 14px; padding-top: 14px; }
        @media (max-width: 991px) { .sanad-assignment-status { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 576px) {
            .sanad-assignment-head { flex-direction: column; }
            .sanad-assignment-status { grid-template-columns: 1fr; }
        }
    </style>
@endpush
</x-master-layout>
