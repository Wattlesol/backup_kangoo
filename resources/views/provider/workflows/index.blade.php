<x-master-layout>
    <div class="container-fluid">
        <div class="card card-block card-stretch">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                    <p class="text-muted mb-0">Reusable employee workflows linked to Sanad services.</p>
                </div>
                <a href="{{ route('provider.workflows.create') }}" class="btn btn-primary btn-sm">Create Workflow</a>
            </div>
        </div>

        <div class="row">
            @forelse($workflows as $workflow)
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">{{ $workflow->name }}</h5>
                                <span class="badge badge-{{ $workflow->is_active ? 'success' : 'secondary' }}">{{ $workflow->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                            <div>
                                <a href="{{ route('provider.workflows.edit', $workflow->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('provider.workflows.destroy', $workflow->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this workflow?')">Remove</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ $workflow->description ?: 'No description.' }}</p>
                            <div class="mb-3">
                                <strong>Linked Services:</strong>
                                {{ $workflow->serviceLinks->pluck('service.name')->filter()->implode(', ') ?: '-' }}
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr><th>#</th><th>Stage</th><th>Role</th><th>Duration</th></tr>
                                    </thead>
                                    <tbody>
                                        @foreach($workflow->steps as $step)
                                            <tr>
                                                <td>{{ $step->execution_order }}</td>
                                                <td>{{ $step->stage_name }}</td>
                                                <td>{{ $step->role ?: '-' }}</td>
                                                <td>{{ $step->estimated_duration_minutes ? $step->estimated_duration_minutes.' min' : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card"><div class="card-body text-muted">No employee workflows created yet.</div></div>
                </div>
            @endforelse
        </div>
    </div>
</x-master-layout>
