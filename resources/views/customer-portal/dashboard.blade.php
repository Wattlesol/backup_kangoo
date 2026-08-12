<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div>
            <h1 class="sanad-title">Customer Dashboard</h1>
            <div class="sanad-muted">Welcome {{ $user->display_name ?? $user->first_name ?? $user->email }}. Manage your Sanad requests from one workspace.</div>
        </div>
        <div class="sanad-actions">
            <a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}"><i class="fas fa-plus-circle"></i> Create New Request</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.ai') }}"><i class="fas fa-robot"></i> Ask Sanad AI</a>
        </div>
    </div>

    @if($pendingActions->count())
        <div class="sanad-card mb-3">
            <div class="sanad-card-header">Pending Customer Actions</div>
            <div class="sanad-card-body">
                <div class="sanad-grid">
                    @foreach($pendingActions as $request)
                        <a class="sanad-card p-3 text-decoration-none" href="{{ route('customer-portal.requests.show', $request->id) }}">
                            <strong>{{ $request->sanad_reference ?? '#'.$request->id }}</strong>
                            <div>{{ optional($request->service)->name_en ?? optional($request->service)->name ?? 'Service' }}</div>
                            <span class="sanad-badge warn">{{ Str::headline($request->sanad_stage ?? $request->status) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="sanad-grid mb-3">
        <div class="sanad-card"><div class="sanad-card-body"><span class="sanad-muted">Active Requests</span><div class="sanad-kpi">{{ $stats['active'] }}</div></div></div>
        <div class="sanad-card"><div class="sanad-card-body"><span class="sanad-muted">Completed Requests</span><div class="sanad-kpi">{{ $stats['completed'] }}</div></div></div>
        <div class="sanad-card"><div class="sanad-card-body"><span class="sanad-muted">Pending Actions</span><div class="sanad-kpi">{{ $stats['pending_actions'] }}</div></div></div>
        <div class="sanad-card"><div class="sanad-card-body"><span class="sanad-muted">Latest Activity</span><div class="h6 mt-2">{{ optional($stats['latest_activity'])->format('Y-m-d H:i') ?? '-' }}</div></div></div>
    </div>

    <div class="sanad-card mb-3">
        <div class="sanad-card-header">Quick Actions</div>
        <div class="sanad-card-body sanad-actions">
            <a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}">Create New Request</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.requests.index') }}">Track Existing Request</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.vault') }}">Upload Documents</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.ai') }}">Ask Sanad AI</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="sanad-card">
                <div class="sanad-card-header">Active Requests</div>
                <div class="sanad-card-body table-responsive">
                    <table class="sanad-table">
                        <thead><tr><th>Request</th><th>Service</th><th>Stage</th><th>Progress</th><th>Assigned Employee</th><th>ETA</th></tr></thead>
                        <tbody>
                        @forelse($activeRequests as $request)
                            @php $progress = in_array($request->sanad_stage, ['completed','closed']) ? 100 : (['submitted'=>15,'pending_review'=>25,'assigned_to_partner'=>40,'assigned_to_employee'=>55,'in_progress'=>70,'awaiting_customer_action'=>65,'awaiting_quality_review'=>85,'escalated'=>60][$request->sanad_stage] ?? 20); @endphp
                            <tr>
                                <td><a href="{{ route('customer-portal.requests.show', $request->id) }}">{{ $request->sanad_reference ?? '#'.$request->id }}</a></td>
                                <td>{{ optional($request->service)->name_en ?? optional($request->service)->name }}</td>
                                <td><span class="sanad-badge">{{ Str::headline($request->sanad_stage ?? $request->status) }}</span></td>
                                <td><div class="sanad-progress"><span style="width:{{ $progress }}%"></span></div></td>
                                <td>{{ optional(optional($request->handymanAdded)->first())->handyman->display_name ?? '-' }}</td>
                                <td>{{ optional($request->expected_completion_at)->format('Y-m-d') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No active requests.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="sanad-card">
                <div class="sanad-card-header">Recent Activity</div>
                <div class="sanad-card-body">
                    @forelse($activities as $activity)
                        <div class="mb-3">
                            <strong>{{ Str::headline($activity->activity_type ?? $activity->type ?? 'Activity') }}</strong>
                            <div class="sanad-muted">{{ $activity->activity_message ?? $activity->description ?? 'Request updated.' }}</div>
                            <small>{{ optional($activity->created_at)->format('Y-m-d H:i') }}</small>
                        </div>
                    @empty
                        <p class="sanad-muted mb-0">No recent activity.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
</x-master-layout>
