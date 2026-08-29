<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-profile-heading"><div><h1><i class="far fa-building"></i> {{ $isAr ? 'الملف التعريفي وبيانات التوثيق' : 'Office Profile & Verification' }}</h1><p>{{ $isAr ? 'راجع بيانات المكتب والحساب البنكي ومتطلبات التحقق والخدمات وساعات العمل.' : 'Review office details, banking, verification requirements, enabled services, and working hours.' }}</p></div></div>
        <div class="row partner-profile-grid">
            <div class="col-lg-6">
                <div class="card partner-profile-card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'تفاصيل المكتب' : 'Office Details' }}</h5></div>
                    <div class="card-body">
                        <p><strong>{{ $isAr ? 'الاسم' : 'Name' }}:</strong> {{ $provider->display_name }}</p>
                        <p><strong>{{ $isAr ? 'البريد الإلكتروني' : 'Email' }}:</strong> {{ $provider->email }}</p>
                        <p><strong>{{ $isAr ? 'الهاتف' : 'Phone' }}:</strong> {{ $provider->contact_number ?: '-' }}</p>
                        <p><strong>{{ $isAr ? 'معلومات الاتصال' : 'Contact Information' }}:</strong> {{ $provider->description ?: '-' }}</p>
                        <p><strong>{{ $isAr ? 'ساعات العمل' : 'Working Hours' }}:</strong> {{ $provider->sanad_working_hours ?: '-' }}</p>
                    </div>
                </div>
                <div class="card partner-profile-card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'الحساب البنكي / الآيبان' : 'Bank Account / IBAN' }}</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerbank as $bank)
                            <p><strong>{{ $bank->bank_name ?? ($isAr ? 'البنك' : 'Bank') }}:</strong> {{ $bank->account_no ?? '-' }} {{ !empty($bank->ifsc_no) ? ' / IBAN '.$bank->ifsc_no : '' }}</p>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لم يتم إعداد حساب بنكي.' : 'No bank account configured.' }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card partner-profile-card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'متطلبات التحقق من الشريك' : 'Partner Verification Requirements' }}</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerDocument as $document)
                            <div class="border-bottom pb-2 mb-2">
                                @php $hasUpload = getMediaFileExit($document, 'provider_document'); @endphp
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <strong>{{ optional($document->document)->localized_name ?? ($isAr ? 'مستند' : 'Document') }}</strong>
                                        @if($hasUpload)
                                            <span class="badge badge-{{ $document->is_verified ? 'success' : 'warning' }}">{{ $document->is_verified ? ($isAr ? 'معتمد' : 'Approved') : ($isAr ? 'قيد المراجعة' : 'Pending Review') }}</span>
                                        @else
                                            <span class="badge badge-danger">{{ $isAr ? 'مفقود' : 'Missing' }}</span>
                                        @endif
                                        @if($hasUpload)
                                            <a class="d-block mt-1" href="{{ getSingleMedia($document, 'provider_document') }}" target="_blank">{{ $isAr ? 'معاينة / تحميل' : 'Preview / Download' }}</a>
                                        @endif
                                    </div>
                                    @if(!$document->is_verified)
                                        <form method="POST" action="{{ route('provider.profile.documents.upload', $document->id) }}" enctype="multipart/form-data" class="verification-upload-form">
                                            @csrf
                                            <input type="file" name="provider_document" class="form-control form-control-sm mb-1" required>
                                            <button class="btn btn-sm btn-primary">{{ $hasUpload ? ($isAr ? 'استبدال' : 'Replace') : ($isAr ? 'رفع' : 'Upload') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لم تعيّن إدارة كويك متطلبات تحقق للشريك.' : 'No partner verification requirements assigned by Quick admin.' }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="card partner-profile-card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'الخدمات المدعومة' : 'Supported Services' }}</h5></div>
                    <div class="card-body">
                        @forelse($services as $availability)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ localized_model_name($availability->service) }}</strong>
                                <span class="badge badge-{{ $availability->is_enabled ? 'success' : 'secondary' }}">{{ $availability->is_enabled ? ($isAr ? 'مفعلة' : 'Enabled') : ($isAr ? 'معطلة' : 'Disabled') }}</span>
                                <div class="text-muted small">{{ $availability->availability }} | {{ $availability->estimated_execution_time }}</div>
                            </div>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لا توجد خدمات مدعومة معدّة.' : 'No supported services configured.' }}</p>
                        @endforelse
                    </div>
                </div>
                <div class="card partner-profile-card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'معلومات الفرع' : 'Branch Information' }}</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerslotsmapping as $slot)
                            <p>{{ $slot->days ?? ($isAr ? 'يوم عمل' : 'Working day') }}: {{ $slot->start_at ?? '-' }} - {{ $slot->end_at ?? '-' }}</p>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لا توجد سجلات فروع أو ساعات عمل معدّة.' : 'No branch/working-hour records configured.' }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@section('bottom_script')
<style>
.partner-profile-heading{margin:10px 0 24px}.partner-profile-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-profile-heading h1 i{color:#1f6bff;font-size:23px}.partner-profile-heading p{margin:7px 0 0;color:#6d7d94}.partner-profile-grid{margin-inline:-9px}.partner-profile-grid>[class*="col-"]{padding-inline:9px}.partner-profile-card{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-profile-card .card-header{background:#fff;border-color:#e7edf4;padding:17px 20px}.partner-profile-card .card-header h5{color:#17263c;font-weight:800}.partner-profile-card .card-body{padding:20px}.partner-profile-card p{display:flex;justify-content:space-between;gap:14px;border-bottom:1px solid #edf1f6;padding-bottom:10px;margin-bottom:10px;color:#66758a}.partner-profile-card p:last-child{border-bottom:0;margin-bottom:0}.partner-profile-card p strong{color:#25364d}.partner-profile-card .verification-upload-form{min-width:210px}.partner-profile-card .form-control{border-color:#dce5f1;border-radius:8px}
.quick-theme-dark .partner-profile-heading h1,.quick-theme-dark .partner-profile-card .card-header h5,.quick-theme-dark .partner-profile-card p strong{color:#f4f8fb}.quick-theme-dark .partner-profile-card,.quick-theme-dark .partner-profile-card .card-header{background:#102536;border-color:#294154}.quick-theme-dark .partner-profile-card p{border-color:#294154}
@media(max-width:767px){.partner-profile-heading h1{font-size:22px}.partner-profile-card p{align-items:flex-start;flex-direction:column}.partner-profile-card .verification-upload-form{min-width:0;width:100%}}
</style>
@endsection
</x-master-layout>
