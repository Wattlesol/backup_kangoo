<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-employees-heading">
            <div>
                <h1><i class="fas fa-users"></i> {{ $isAr ? 'دليل موظفي المكتب الشريك' : 'Partner Staff Directory & Capacity' }}</h1>
                <p>{{ $isAr ? 'إدارة حسابات الموظفين ومراقبة الطاقة الاستيعابية وجودة الإنجاز.' : 'Manage office staff profiles, current capacity, assigned work, and delivery quality.' }}</p>
            </div>
            <a href="{{ route('handyman.create') }}" class="btn btn-primary"><i class="fa fa-plus-circle mr-1"></i> {{ $isAr ? 'إضافة موظف للمكتب' : 'Add staff member' }}</a>
        </div>
        <div class="partner-employees-search"><i class="fas fa-search"></i><input id="partner-employee-search" type="search" placeholder="{{ $isAr ? 'بحث بالاسم أو الدور أو القسم...' : 'Search by name, role, department, or skill...' }}"></div>
        <div class="card partner-employees-card">
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead><tr>
        <th>{{ $isAr ? 'الاسم' : 'Name' }}</th>
        <th>{{ $isAr ? 'المسمى الوظيفي' : 'Job Title' }}</th>
        <th>{{ $isAr ? 'القسم' : 'Department' }}</th>
        <th>{{ $isAr ? 'المهارات' : 'Skills' }}</th>
        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
        <th>{{ $isAr ? 'الطاقة الاستيعابية' : 'Capacity' }}</th>
        <th>{{ $isAr ? 'الطلبات المسندة' : 'Assigned Orders' }}</th>
        <th>{{ $isAr ? 'التقييم' : 'Score' }}</th>
        <th class="text-right">{{ $isAr ? 'الإجراءات' : 'Actions' }}</th>
    </tr></thead>
                    <tbody>
                        @forelse($employees as $employee)
                            <tr data-employee-row data-search="{{ Str::lower($employee->display_name.' '.$employee->sanad_job_title.' '.$employee->designation.' '.$employee->sanad_department.' '.(is_array($employee->skills) ? implode(' ', $employee->skills) : $employee->skills)) }}">
                                <td><div class="partner-employee-identity"><span>{{ Str::upper(Str::substr($employee->display_name ?: 'E', 0, 1)) }}</span><strong>{{ $employee->display_name }}</strong></div></td>
                                <td><strong>{{ $employee->sanad_job_title ?: $employee->designation ?: '-' }}</strong></td>
                                <td>{{ $employee->sanad_department ?: '-' }}</td>
                                <td>{{ is_array($employee->skills) ? implode(', ', $employee->skills) : ($employee->skills ?: '-') }}</td>
                                <td><span class="badge badge-{{ $employee->is_available ? 'success' : 'light' }}">{{ quick_status_label($employee->sanad_employee_status ?: ($employee->is_available ? 'available' : 'offline')) }}</span></td>
                                <td><strong>{{ $employee->sanad_daily_capacity ?: 0 }}</strong></td>
                                <td><span class="partner-capacity-pill">{{ $employee->assigned_orders_count }} / {{ $employee->sanad_daily_capacity ?: 0 }}</span></td>
                                <td><span class="partner-quality-score"><i class="fas fa-star"></i> {{ $employee->sanad_quality_score ?: 0 }}</span></td>
                                <td>
                                    <div class="partner-employee-actions">
                                        <a href="{{ route('handyman.create', ['id' => $employee->id]) }}" class="quick-table-btn" title="{{ $isAr ? 'تعديل الملف والصلاحيات' : 'Edit profile and permissions' }}">
                                            <i class="fas fa-pen"></i><span>{{ $isAr ? 'تعديل' : 'Edit' }}</span>
                                        </a>
                                        <form method="POST" action="{{ route('provider.employees.destroy', $employee->id) }}" onsubmit="return confirm('{{ $isAr ? 'هل تريد إزالة هذا الموظف؟' : 'Remove this staff member?' }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="quick-table-btn partner-employee-delete" title="{{ $isAr ? 'إزالة الموظف' : 'Remove staff member' }}">
                                                <i class="fas fa-trash"></i><span>{{ $isAr ? 'إزالة' : 'Remove' }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted">{{ $isAr ? 'لم يتم العثور على موظفين.' : 'No employees found.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@section('bottom_script')
<style>
.partner-employees-heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:10px 0 24px}.partner-employees-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-employees-heading h1 i{color:#1f6bff;font-size:23px}.partner-employees-heading p{margin:7px 0 0;color:#6d7d94}.partner-employees-heading .btn{border-radius:11px;padding:10px 16px;font-weight:700}.partner-employees-search{position:relative;background:#fff;border:1px solid #dce5f1;border-radius:14px;padding:14px 16px;margin-bottom:18px}.partner-employees-search i{position:absolute;inset-inline-start:29px;top:50%;transform:translateY(-50%);color:#8b99ad}.partner-employees-search input{width:100%;height:42px;border:1px solid #dce5f1;border-radius:10px;padding-inline:38px 14px;outline:0}.partner-employees-card{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-employees-card .card-body{padding:0}.partner-employees-card thead th{background:#f6f8fc;color:#617188;border:0;padding:15px 14px;font-size:11px;text-transform:uppercase;white-space:nowrap}.partner-employees-card tbody td{padding:15px 14px;border-color:#edf1f6;vertical-align:middle;font-size:12px}.partner-employee-identity{display:flex;align-items:center;gap:10px;min-width:170px}.partner-employee-identity>span{display:inline-flex;width:34px;height:34px;border-radius:50%;align-items:center;justify-content:center;background:rgba(31,107,255,.1);color:#1f6bff;font-weight:800}.partner-employee-identity strong{color:#17263c}.partner-capacity-pill{display:inline-block;color:#1f6bff;background:rgba(31,107,255,.09);padding:5px 9px;border-radius:7px;font-weight:800}.partner-quality-score{color:#d99a00;font-weight:800;white-space:nowrap}.employee-row-hidden{display:none}
.quick-theme-dark .partner-employees-heading h1,.quick-theme-dark .partner-employee-identity strong{color:#f4f8fb}.quick-theme-dark .partner-employees-search,.quick-theme-dark .partner-employees-card{background:#102536;border-color:#294154}.quick-theme-dark .partner-employees-search input{background:#0b1c2a;border-color:#294154;color:#eaf1f7}
.partner-employee-actions{display:flex;align-items:center;justify-content:flex-end;gap:7px;white-space:nowrap}.partner-employee-actions form{margin:0}.partner-employee-actions .quick-table-btn{display:inline-flex;align-items:center;gap:6px;min-height:34px}.partner-employee-delete{color:#dc3545!important;background:transparent}.partner-employee-delete:hover{border-color:#dc3545!important;background:rgba(220,53,69,.08)!important}
@media(max-width:767px){.partner-employees-heading{align-items:stretch;flex-direction:column}.partner-employees-heading h1{font-size:22px}.partner-employees-heading .btn{width:100%}}
</style>
<script>$(document).on('input','#partner-employee-search',function(){const q=String($(this).val()||'').toLowerCase().trim();$('[data-employee-row]').each(function(){$(this).toggleClass('employee-row-hidden',q&&!String($(this).data('search')||'').includes(q));});});</script>
@endsection
</x-master-layout>
