<x-master-layout>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                    <span class="text-muted">Enable only Sanad-provided services. Pricing is controlled by Sanad.</span>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('provider.services.update') }}">
            @csrf
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Enabled</th>
                                <th>Service</th>
                                <th>Availability</th>
                                <th>Estimated Execution Time</th>
                                <th>Required Skills</th>
                                <th>Internal Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($services as $service)
                                @php($row = $availability->get($service->id))
                                <tr>
                                    <td><input type="checkbox" name="services[{{ $service->id }}][is_enabled]" value="1" {{ optional($row)->is_enabled ? 'checked' : '' }}></td>
                                    <td>
                                        <strong>{{ $service->name_en ?: $service->name }}</strong>
                                        <div class="text-muted small">{{ $service->government_entity }}</div>
                                    </td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][availability]" value="{{ optional($row)->availability }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][estimated_execution_time]" value="{{ optional($row)->estimated_execution_time }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][required_employee_skills]" value="{{ implode(', ', optional($row)->required_employee_skills ?: []) }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][internal_notes]" value="{{ optional($row)->internal_notes }}"></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-primary">Save Availability</button>
                </div>
            </div>
        </form>
    </div>
</x-master-layout>

