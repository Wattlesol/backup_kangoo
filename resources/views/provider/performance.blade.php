<x-master-layout>
    <div class="container-fluid">
        <div class="card"><div class="card-body"><h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5><span class="text-muted">Operational performance replaces employee earnings.</span></div></div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Employee</th><th>Completed</th><th>Avg Completion</th><th>Delayed</th><th>Customer Rating</th><th>Quality</th><th>Reopened</th><th>SLA</th><th>Productivity</th></tr></thead>
                    <tbody>
                        @forelse($employees as $employee)
                            @php($m = $employee->sanad_metrics)
                            <tr>
                                <td>{{ $employee->display_name }}</td>
                                <td>{{ $m['completed_orders'] }}</td>
                                <td>{{ $m['average_completion_time'] }} min</td>
                                <td>{{ $m['delayed_orders'] }}</td>
                                <td>{{ $m['customer_rating'] }}</td>
                                <td>{{ $m['quality_score'] }}%</td>
                                <td>{{ $m['reopened_orders'] }}</td>
                                <td>{{ $m['sla_compliance'] }}%</td>
                                <td>{{ $m['productivity'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">No employee metrics available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>

