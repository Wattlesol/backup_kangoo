<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page partner-services-page">
        <div class="partner-page-heading">
            <div>
                <h1><i class="fas fa-tags"></i> {{ $isAr ? 'الخدمات الحكومية المعتمدة للمكتب' : 'Authorized Services & Team Readiness' }}</h1>
                <p>{{ $isAr ? 'تحكم في استقبال المعاملات وحدد جاهزية الفريق لكل خدمة معتمدة. تدير كويك التسعير.' : 'Control intake and define team readiness for each authorized service. Quick manages customer pricing.' }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('provider.services.update') }}">
            @csrf
            <div class="partner-service-search">
                <i class="fas fa-search"></i>
                <input id="partner-service-search" type="search" placeholder="{{ $isAr ? 'بحث في الخدمات أو الجهات الحكومية...' : 'Search services or government authorities...' }}">
            </div>

            <div class="partner-service-table-card">
                <div class="table-responsive">
                    <table class="table partner-service-table mb-0">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'الخدمة والجهة' : 'Service & authority' }}</th>
                                <th>{{ $isAr ? 'حالة الاستقبال' : 'Intake status' }}</th>
                                <th>{{ $isAr ? 'التوفر' : 'Availability' }}</th>
                                <th>{{ $isAr ? 'وقت التنفيذ المتوقع' : 'Execution estimate' }}</th>
                                <th>{{ $isAr ? 'مهارات الفريق المطلوبة' : 'Required team skills' }}</th>
                                <th>{{ $isAr ? 'ملاحظات داخلية' : 'Internal notes' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $service)
                                @php $row = $availability->get($service->id); @endphp
                                <tr data-service-row data-search="{{ Str::lower(localized_model_name($service).' '.$service->government_entity) }}">
                                    <td class="partner-service-name">
                                        <strong>{{ localized_model_name($service) }}</strong>
                                        <span><i class="far fa-building"></i> {{ $service->government_entity ?: ($isAr ? 'جهة حكومية' : 'Government authority') }}</span>
                                    </td>
                                    <td>
                                        <label class="partner-service-toggle">
                                            <input type="checkbox" name="services[{{ $service->id }}][is_enabled]" value="1" {{ optional($row)->is_enabled ? 'checked' : '' }}>
                                            <span></span>
                                            <b data-active-label>{{ optional($row)->is_enabled ? ($isAr ? 'مفعلة' : 'Active') : ($isAr ? 'موقوفة' : 'Disabled') }}</b>
                                        </label>
                                    </td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][availability]" value="{{ optional($row)->availability }}" placeholder="{{ $isAr ? 'مثال: أيام العمل' : 'e.g. Business days' }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][estimated_execution_time]" value="{{ optional($row)->estimated_execution_time }}" placeholder="{{ $isAr ? 'مثال: 8 ساعات' : 'e.g. 8 hours' }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][required_employee_skills]" value="{{ implode(', ', optional($row)->required_employee_skills ?: []) }}" placeholder="{{ $isAr ? 'المهارات مفصولة بفواصل' : 'Comma-separated skills' }}"></td>
                                    <td><input class="form-control" name="services[{{ $service->id }}][internal_notes]" value="{{ optional($row)->internal_notes }}" placeholder="{{ $isAr ? 'للفريق فقط' : 'Visible to your team' }}"></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="partner-service-empty">{{ $isAr ? 'لا توجد خدمات معتمدة لهذا المكتب.' : 'No services are authorized for this office.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="partner-service-save">
                    <span>{{ $isAr ? 'تُطبّق التغييرات على الطلبات الجديدة فقط.' : 'Changes apply to new incoming orders.' }}</span>
                    <button class="btn btn-primary"><i class="far fa-save"></i> {{ $isAr ? 'حفظ جاهزية الخدمات' : 'Save service readiness' }}</button>
                </div>
            </div>
        </form>
    </div>

@section('bottom_script')
<style>
.partner-page-heading{margin:10px 0 24px}.partner-page-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-page-heading h1 i{color:#1f6bff;font-size:24px}.partner-page-heading p{margin:7px 0 0;color:#6d7d94}.partner-service-search{position:relative;background:#fff;border:1px solid #dce5f1;border-radius:15px;padding:14px 16px;margin-bottom:18px}.partner-service-search i{position:absolute;inset-inline-start:28px;top:50%;transform:translateY(-50%);color:#8c9aaf}.partner-service-search input{width:100%;height:42px;border:1px solid #dce5f1;border-radius:11px;padding-inline:38px 14px;outline:0}.partner-service-search input:focus{border-color:#1f6bff;box-shadow:0 0 0 3px rgba(31,107,255,.1)}.partner-service-table-card{background:#fff;border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-service-table thead th{background:#f7f9fc;color:#617189;border:0;padding:15px 13px;font-size:11px;text-transform:uppercase;white-space:nowrap}.partner-service-table tbody td{padding:14px 13px;vertical-align:middle;border-color:#edf1f6}.partner-service-table .form-control{min-width:150px;border-radius:9px;border-color:#dce5f1;font-size:12px}.partner-service-name{min-width:220px}.partner-service-name strong{display:block;color:#17263c;font-size:13px;margin-bottom:5px}.partner-service-name span{display:block;color:#8a98ac;font-size:11px}.partner-service-toggle{display:flex;align-items:center;gap:8px;margin:0;cursor:pointer;white-space:nowrap}.partner-service-toggle input{position:absolute;opacity:0}.partner-service-toggle span{position:relative;width:38px;height:22px;border-radius:12px;background:#cbd5e1;transition:.2s}.partner-service-toggle span:after{content:"";position:absolute;top:3px;inset-inline-start:3px;width:16px;height:16px;border-radius:50%;background:#fff;transition:.2s;box-shadow:0 1px 4px rgba(0,0,0,.18)}.partner-service-toggle input:checked+span{background:#16a36a}.partner-service-toggle input:checked+span:after{transform:translateX(16px)}html[dir="rtl"] .partner-service-toggle input:checked+span:after{transform:translateX(-16px)}.partner-service-toggle b{color:#53637a;font-size:11px}.partner-service-save{display:flex;justify-content:space-between;align-items:center;gap:20px;padding:16px 18px;border-top:1px solid #e8edf4}.partner-service-save span{color:#8190a5;font-size:12px}.partner-service-save .btn{border-radius:10px;font-weight:700}.partner-service-empty{text-align:center;color:#8190a5;padding:48px!important}.partner-service-row-hidden{display:none}
.quick-theme-dark .partner-page-heading h1,.quick-theme-dark .partner-service-name strong{color:#f4f8fb}.quick-theme-dark .partner-service-search,.quick-theme-dark .partner-service-table-card{background:#102536;border-color:#294154}.quick-theme-dark .partner-service-search input,.quick-theme-dark .partner-service-table .form-control{background:#0b1c2a;border-color:#294154;color:#eaf1f7}.quick-theme-dark .partner-service-table thead th{background:#0d2030}.quick-theme-dark .partner-service-save{border-color:#294154}
@media(max-width:767px){.partner-page-heading h1{font-size:22px}.partner-service-save{align-items:stretch;flex-direction:column}.partner-service-save .btn{width:100%}}
</style>
<script>
$(document).on('input', '#partner-service-search', function () {
    const query = String($(this).val() || '').toLowerCase().trim();
    $('[data-service-row]').each(function () {
        $(this).toggleClass('partner-service-row-hidden', query && !String($(this).data('search') || '').includes(query));
    });
});
$(document).on('change', '.partner-service-toggle input', function () {
    $(this).siblings('[data-active-label]').text(this.checked ? @json($isAr ? 'مفعلة' : 'Active') : @json($isAr ? 'موقوفة' : 'Disabled'));
});
</script>
@endsection
</x-master-layout>
