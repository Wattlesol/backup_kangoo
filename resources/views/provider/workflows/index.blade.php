<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid quick-role-page quick-partner-page partner-workflows-page">
        <div class="partner-workflows-heading">
            <div>
                <h1><i class="fas fa-project-diagram"></i> {{ $isAr ? 'مسارات العمل وتوزيع مراحل التنفيذ' : 'Workflow Sequencing & Stage Assignments' }}</h1>
                <p>{{ $isAr ? 'أنشئ قوالب تشغيل قابلة لإعادة الاستخدام واربطها بالخدمات وفريق المكتب.' : 'Build reusable execution templates and link them to services and office roles.' }}</p>
            </div>
            <a href="{{ route('provider.workflows.create') }}" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> {{ $isAr ? 'إنشاء مسار جديد' : 'New workflow' }}</a>
        </div>
        {{-- Legacy title card replaced by the approved workflow header. --}}
        <div class="d-none">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                    <p class="text-muted mb-0">{{ $isAr ? 'مسارات عمل الموظفين المرتبطة بخدمات كويك.' : 'Reusable employee workflows linked to Quick services.' }}</p>
                </div>
                <a href="{{ route('provider.workflows.create') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus-circle mr-1"></i> {{ $isAr ? 'إنشاء مسار عمل' : 'Create Workflow' }}</a>
            </div>
        </div>

        <div class="row partner-workflow-grid">
            @forelse($workflows as $workflow)
                <div class="col-lg-6">
                    <div class="card partner-workflow-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-1">{{ $workflow->name }}</h5>
                                <span class="badge badge-{{ $workflow->is_active ? 'success' : 'secondary' }}">{{ $workflow->is_active ? ($isAr ? 'مفعّل' : 'Active') : ($isAr ? 'موقوف' : 'Inactive') }}</span>
                            </div>
                            <div>
                                <a href="{{ route('provider.workflows.edit', $workflow->id) }}" class="btn btn-sm btn-outline-primary">{{ $isAr ? 'تعديل' : 'Edit' }}</a>
                                <form method="POST" action="{{ route('provider.workflows.destroy', $workflow->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this workflow?')">{{ $isAr ? 'حذف' : 'Remove' }}</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">{{ $workflow->description ?: ($isAr ? 'لا يوجد وصف.' : 'No description.') }}</p>
                            <div class="mb-3">
                                <strong>{{ $isAr ? 'الخدمات المرتبطة:' : 'Linked Services:' }}</strong>
                                {{ $workflow->serviceLinks->pluck('service.name')->filter()->implode(', ') ?: '-' }}
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr><th>#</th><th>{{ $isAr ? 'المرحلة' : 'Stage' }}</th><th>{{ $isAr ? 'الدور' : 'Role' }}</th><th>{{ $isAr ? 'المدة' : 'Duration' }}</th></tr>
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
                    <div class="card"><div class="card-body text-muted">{{ $isAr ? 'لا توجد مسارات عمل منشأة بعد.' : 'No employee workflows created yet.' }}</div></div>
                </div>
            @endforelse
        </div>
    </div>
@section('bottom_script')
<style>
.partner-workflows-heading{display:flex;align-items:center;justify-content:space-between;gap:18px;margin:10px 0 24px}.partner-workflows-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-workflows-heading h1 i{color:#1f6bff;font-size:23px}.partner-workflows-heading p{margin:7px 0 0;color:#6d7d94}.partner-workflows-heading .btn{border-radius:11px;padding:10px 16px;font-weight:700}.partner-workflow-grid{margin-inline:-9px}.partner-workflow-grid>[class*="col-"]{padding-inline:9px}.partner-workflow-card{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-workflow-card .card-header{background:#fff;border-color:#e6ecf4;padding:18px 20px}.partner-workflow-card .card-header h5{font-weight:800;color:#17263c}.partner-workflow-card .card-body{padding:20px}.partner-workflow-card .table{border:1px solid #e6ecf4}.partner-workflow-card .table thead th{background:#f5f8fc;color:#65758b;border:0;font-size:11px}.partner-workflow-card .table td{border-color:#edf1f6;font-size:12px}.partner-workflow-card .btn{border-radius:8px}
.quick-theme-dark .partner-workflows-heading h1,.quick-theme-dark .partner-workflow-card .card-header h5{color:#f4f8fb}.quick-theme-dark .partner-workflow-card,.quick-theme-dark .partner-workflow-card .card-header{background:#102536;border-color:#294154}
@media(max-width:767px){.partner-workflows-heading{align-items:stretch;flex-direction:column}.partner-workflows-heading h1{font-size:22px}.partner-workflows-heading .btn{width:100%}}
</style>
@endsection
</x-master-layout>
