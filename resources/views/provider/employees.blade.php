<x-master-layout>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                    <span class="text-muted">Partner employees replacing legacy handyman wording.</span>
                </div>
                <a href="{{ route('handyman.create') }}" class="btn btn-primary btn-sm">Add Employee</a>
            </div>
        </div>
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Name</th><th>Job Title</th><th>Department</th><th>Skills</th><th>Status</th><th>Capacity</th><th>Assigned Orders</th><th>Score</th></tr></thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr>
                                <td>{{ $employee->display_name }}</td>
                                <td>{{ $employee->sanad_job_title ?: $employee->designation ?: '-' }}</td>
                                <td>{{ $employee->sanad_department ?: '-' }}</td>
                                <td>{{ is_array($employee->skills) ? implode(', ', $employee->skills) : ($employee->skills ?: '-') }}</td>
                                <td><span class="badge badge-light">{{ Str::headline($employee->sanad_employee_status ?: ($employee->is_available ? 'available' : 'offline')) }}</span></td>
                                <td>{{ $employee->sanad_daily_capacity ?: 0 }}</td>
                                <td>{{ $employee->assigned_orders_count }}</td>
                                <td>{{ $employee->sanad_quality_score ?: 0 }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">No employees found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>

