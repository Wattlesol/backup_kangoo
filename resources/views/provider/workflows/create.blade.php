<x-master-layout>
    <div class="container-fluid">
        <div class="card card-block card-stretch">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="font-weight-bold mb-0">{{ $pageTitle }}</h5>
                <a href="{{ route('provider.workflows.index') }}" class="btn btn-sm btn-secondary">Back</a>
            </div>
        </div>

        <form method="POST" action="{{ $workflow->exists ? route('provider.workflows.update', $workflow->id) : route('provider.workflows.store') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Workflow Name</label>
                            <input name="name" class="form-control" value="{{ old('name', $workflow->name) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Linked Services</label>
                            <div class="service-checklist">
                                <label class="service-option service-option-all">
                                    <input type="checkbox" id="select-all-services">
                                    <span>Select all services</span>
                                </label>
                                @foreach($services as $service)
                                    <label class="service-option">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="service-checkbox" {{ in_array($service->id, old('service_ids', $linkedServiceIds), true) ? 'checked' : '' }}>
                                        <span>{{ $service->name_en ?: $service->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $workflow->description) }}</textarea>
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $workflow->is_active) ? 'checked' : '' }}>
                                Active
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Workflow Stages</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-workflow-stage">Add Stage</button>
                </div>
                <div class="card-body" id="workflow-stage-list">
                    @php($steps = old('stage_name') ? collect(old('stage_name'))->map(fn($name, $i) => (object)['stage_name' => $name, 'role' => old('role.'.$i), 'estimated_duration_minutes' => old('estimated_duration_minutes.'.$i), 'stage_mode' => old('stage_mode.'.$i, 'sequential')]) : ($workflow->steps->isNotEmpty() ? $workflow->steps : collect([(object)['stage_name' => '', 'role' => '', 'estimated_duration_minutes' => '', 'parallel_group' => null]])))
                    @foreach($steps as $step)
                        <div class="row workflow-stage-row">
                            <div class="col-md-4 form-group"><input name="stage_name[]" class="form-control" placeholder="Stage name" value="{{ $step->stage_name }}" required></div>
                            <div class="col-md-3 form-group"><input name="role[]" class="form-control" placeholder="Employee role" value="{{ $step->role }}"></div>
                            <div class="col-md-2 form-group"><input name="estimated_duration_minutes[]" type="number" min="1" class="form-control" placeholder="Minutes" value="{{ $step->estimated_duration_minutes }}"></div>
                            <div class="col-md-2 form-group">
                                @php($stageMode = old('stage_mode.'.$loop->index, !empty($step->parallel_group) ? 'parallel' : ($step->stage_mode ?? 'sequential')))
                                <select name="stage_mode[]" class="form-control">
                                    <option value="sequential" {{ $stageMode === 'sequential' ? 'selected' : '' }}>Sequential</option>
                                    <option value="parallel" {{ $stageMode === 'parallel' ? 'selected' : '' }}>Parallel</option>
                                </select>
                            </div>
                            <div class="col-md-1 form-group"><button type="button" class="btn btn-outline-danger btn-block remove-stage">X</button></div>
                        </div>
                    @endforeach
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save Workflow</button>
                </div>
            </div>
        </form>
    </div>

@section('bottom_script')
<style>
.service-checklist { max-height: 150px; overflow-y: auto; border: 1px solid #e3e7ee; border-radius: 4px; padding: 8px 10px; background: #fff; }
.service-option { display: flex; align-items: center; gap: 8px; padding: 6px 2px; margin: 0; font-weight: 400; cursor: pointer; }
.service-option input { margin: 0; }
.service-option-all { border-bottom: 1px solid #eef1f5; margin-bottom: 4px; padding-bottom: 8px; font-weight: 600; }
</style>
<script>
$(document).on('click', '#add-workflow-stage', function () {
    $('#workflow-stage-list').append('<div class="row workflow-stage-row"><div class="col-md-4 form-group"><input name="stage_name[]" class="form-control" placeholder="Stage name" required></div><div class="col-md-3 form-group"><input name="role[]" class="form-control" placeholder="Employee role"></div><div class="col-md-2 form-group"><input name="estimated_duration_minutes[]" type="number" min="1" class="form-control" placeholder="Minutes"></div><div class="col-md-2 form-group"><select name="stage_mode[]" class="form-control"><option value="sequential">Sequential</option><option value="parallel">Parallel</option></select></div><div class="col-md-1 form-group"><button type="button" class="btn btn-outline-danger btn-block remove-stage">X</button></div></div>');
});
$(document).on('click', '.remove-stage', function () {
    if ($('.workflow-stage-row').length > 1) {
        $(this).closest('.workflow-stage-row').remove();
    }
});
$(document).on('change', '#select-all-services', function () {
    $('.service-checkbox').prop('checked', this.checked);
});
$(document).on('change', '.service-checkbox', function () {
    $('#select-all-services').prop('checked', $('.service-checkbox').length === $('.service-checkbox:checked').length);
});
$('#select-all-services').prop('checked', $('.service-checkbox').length > 0 && $('.service-checkbox').length === $('.service-checkbox:checked').length);
</script>
@endsection
</x-master-layout>
