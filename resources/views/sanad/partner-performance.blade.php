<x-master-layout>
    <div class="container-fluid">
        <div class="card card-block card-stretch">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="font-weight-bold mb-1">Partner Performance</h4>
                    <span class="text-muted">Service-specific quality, SLA, acceptance, cancellation, speed, and completed-order metrics</span>
                </div>
                <a href="{{ route('sanad.dashboard') }}" class="btn btn-sm btn-light">Back to Sanad dashboard</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Service</th>
                                <th>Quality Score</th>
                                <th>SLA Compliance</th>
                                <th>Acceptance</th>
                                <th>Cancellation</th>
                                <th>Avg. Completion</th>
                                <th>Completed Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($performances as $performance)
                                <tr>
                                    <td>{{ optional($performance->provider)->display_name ?: optional($performance->provider)->name ?: '-' }}</td>
                                    <td>{{ optional($performance->service)->name_en ?: optional($performance->service)->name ?: '-' }}</td>
                                    <td>{{ $performance->quality_score !== null ? number_format($performance->quality_score, 2) : '-' }}</td>
                                    <td>{{ $performance->sla_compliance_rate !== null ? number_format($performance->sla_compliance_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->acceptance_rate !== null ? number_format($performance->acceptance_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->cancellation_rate !== null ? number_format($performance->cancellation_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->average_completion_minutes !== null ? number_format($performance->average_completion_minutes, 0).' min' : '-' }}</td>
                                    <td>{{ $performance->completed_orders ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-4">No partner performance records yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($performances->hasPages())
                    <div class="p-3">{{ $performances->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-master-layout>
