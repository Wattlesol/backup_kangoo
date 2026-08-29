<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page partner-workflow-editor">
        <div class="partner-editor-heading">
            <div>
                <span>{{ $isAr ? 'إدارة الفريق' : 'Team management' }}</span>
                <h1><i class="fas fa-project-diagram"></i> {{ $workflow->exists ? ($isAr ? 'تعديل مسار العمل' : 'Edit workflow') : ($isAr ? 'إنشاء مسار عمل جديد' : 'Create a new workflow') }}</h1>
                <p>{{ $isAr ? 'حدد الخدمات ومراحل التنفيذ والأدوار والمدة المتوقعة لكل مرحلة.' : 'Define linked services, execution stages, role ownership, and time estimates.' }}</p>
            </div>
            <a href="{{ route('provider.workflows.index') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-{{ $isAr ? 'right' : 'left' }} mr-1"></i> {{ $isAr ? 'رجوع' : 'Back' }}</a>
        </div>

        <form method="POST" action="{{ $workflow->exists ? route('provider.workflows.update', $workflow->id) : route('provider.workflows.store') }}">
            @csrf
            <div class="card partner-editor-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ $isAr ? 'اسم مسار العمل' : 'Workflow Name' }}</label>
                            <input name="name" class="form-control" value="{{ old('name', $workflow->name) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>{{ $isAr ? 'الخدمات المرتبطة' : 'Linked Services' }}</label>
                            <div class="service-checklist">
                                <label class="service-option service-option-all">
                                    <input type="checkbox" id="select-all-services">
                                    <span>{{ $isAr ? 'تحديد كل الخدمات' : 'Select all services' }}</span>
                                </label>
                                @foreach($services as $service)
                                    <label class="service-option">
                                        <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" class="service-checkbox" {{ in_array($service->id, old('service_ids', $linkedServiceIds), true) ? 'checked' : '' }}>
                                        <span>{{ localized_model_name($service) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-12 form-group">
                            <label>{{ $isAr ? 'الوصف' : 'Description' }}</label>
                            <textarea name="description" class="form-control" rows="2">{{ old('description', $workflow->description) }}</textarea>
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="d-flex align-items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $workflow->is_active) ? 'checked' : '' }}>
                                {{ $isAr ? 'مسار مفعّل' : 'Active workflow' }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card partner-editor-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ $isAr ? 'مراحل مسار العمل' : 'Workflow stages' }}</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-workflow-stage"><i class="fas fa-plus"></i> {{ $isAr ? 'إضافة مرحلة' : 'Add stage' }}</button>
                </div>
                <div class="card-body" id="workflow-stage-list">
                    @php
                        $steps = old('stage_name')
                            ? collect(old('stage_name'))->map(fn($name, $i) => (object) [
                                'stage_name' => $name,
                                'role' => old('role.'.$i),
                                'estimated_duration_minutes' => old('estimated_duration_minutes.'.$i),
                                'stage_mode' => old('stage_mode.'.$i, 'sequential'),
                                'parallel_group' => old('stage_mode.'.$i, 'sequential') === 'parallel' ? 1 : null,
                            ])
                            : ($workflow->steps->isNotEmpty()
                                ? $workflow->steps
                                : collect([(object) ['stage_name' => '', 'role' => '', 'estimated_duration_minutes' => '', 'stage_mode' => 'sequential', 'parallel_group' => null]]));
                    @endphp
                    @foreach($steps as $step)
                        <div class="row workflow-stage-row">
                            <div class="col-md-4 form-group"><input name="stage_name[]" class="form-control" placeholder="{{ $isAr ? 'اسم المرحلة' : 'Stage name' }}" value="{{ $step->stage_name }}" required></div>
                            <div class="col-md-3 form-group"><input name="role[]" class="form-control" placeholder="{{ $isAr ? 'دور الموظف' : 'Employee role' }}" value="{{ $step->role }}"></div>
                            <div class="col-md-2 form-group"><input name="estimated_duration_minutes[]" type="number" min="1" class="form-control" placeholder="{{ $isAr ? 'بالدقائق' : 'Minutes' }}" value="{{ $step->estimated_duration_minutes }}"></div>
                            <div class="col-md-2 form-group">
                                @php $stageMode = old('stage_mode.'.$loop->index, !empty($step->parallel_group) ? 'parallel' : ($step->stage_mode ?? 'sequential')); @endphp
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
                    <button class="btn btn-primary">{{ $isAr ? 'حفظ مسار العمل' : 'Save Workflow' }}</button>
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
.partner-editor-heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:10px 0 24px}.partner-editor-heading>div>span{color:#1f6bff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.partner-editor-heading h1{display:flex;align-items:center;gap:10px;margin:4px 0 0;color:#0f1d33;font-size:28px;font-weight:800}.partner-editor-heading h1 i{color:#1f6bff;font-size:23px}.partner-editor-heading p{margin:7px 0 0;color:#6d7d94}.partner-editor-heading .btn{border-radius:10px}.partner-editor-card{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-editor-card .card-header{padding:17px 20px;background:#fff;border-color:#e7edf4}.partner-editor-card .card-body{padding:22px}.partner-editor-card .card-footer{display:flex;justify-content:flex-end;background:#fff;border-color:#e7edf4;padding:16px 20px}.partner-editor-card .form-control,.service-checklist{border-color:#dce5f1;border-radius:9px}.workflow-stage-row{background:#f8fafc;border:1px solid #e4ebf3;border-radius:12px;margin:0 0 12px;padding:14px 6px 0}.partner-editor-card .btn{border-radius:9px}
.quick-theme-dark .partner-editor-heading h1{color:#f4f8fb}.quick-theme-dark .partner-editor-card,.quick-theme-dark .partner-editor-card .card-header,.quick-theme-dark .partner-editor-card .card-footer,.quick-theme-dark .service-checklist{background:#102536;border-color:#294154}.quick-theme-dark .workflow-stage-row{background:#0d2030;border-color:#294154}
@media(max-width:767px){.partner-editor-heading{align-items:stretch;flex-direction:column}.partner-editor-heading h1{font-size:22px}.partner-editor-heading .btn{width:100%}}
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
