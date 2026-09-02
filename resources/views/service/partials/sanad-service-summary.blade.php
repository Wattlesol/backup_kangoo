@php
    $isAr = app()->getLocale() === 'ar';
    $summary = $sanadServiceSummary ?? [];
    $total = (int) ($summary['total_services'] ?? 0);
    $active = (int) ($summary['active_services'] ?? 0);
    $readiness = $total > 0 ? (int) round(($active / $total) * 100) : 0;
@endphp
<div class="col-lg-12">
    <div class="card card-block card-stretch mb-3 quick-service-catalog-summary">
        <div class="card-body quick-service-catalog-hero">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                <div>
                    <h4 class="font-weight-bold mb-1">{{ $isAr ? 'دليل خدمات كويك' : 'Quick Service Catalog' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'إدارة الخدمات والباقات والإضافات المتاحة للشركاء والعملاء.' : 'Manage the services, packages, and add-ons available to partners and customers.' }}</span>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge badge-primary px-3 py-2">{{ $isAr ? 'جاهزية الدليل' : 'Catalog Readiness' }}: {{ $readiness }}%</span>
                    @if(isset($auth_user) && $auth_user->can('service add') && Route::currentRouteName() !== 'servicepackage.service')
                        <a href="{{ route('service.create') }}" class="btn btn-primary"><x-quick-icon name="check" /> {{ $isAr ? 'إضافة خدمة' : 'Add Service' }}</a>
                    @endif
                </div>
            </div>
            <div class="row">
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'إجمالي الخدمات' : 'Total Services' }}</small><strong class="h4 mb-0">{{ $total }}</strong></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'الخدمات النشطة' : 'Active Services' }}</small><strong class="h4 mb-0 text-success">{{ $active }}</strong></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'غير النشطة' : 'Inactive Services' }}</small><strong class="h4 mb-0">{{ (int) ($summary['inactive_services'] ?? 0) }}</strong></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'باقات الخدمات' : 'Service Bundles' }}</small><strong class="h4 mb-0">{{ (int) ($summary['packages'] ?? 0) }}</strong></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'الخدمات الإضافية' : 'Additional Services' }}</small><strong class="h4 mb-0">{{ (int) ($summary['addons'] ?? 0) }}</strong></div></div>
                <div class="col-6 col-md-4 col-xl-2 mb-2"><div class="border rounded p-3 h-100"><small class="text-muted d-block">{{ $isAr ? 'نطاق الشريك' : 'Partner Scope' }}</small><strong class="h6 mb-0">{{ auth()->user()->hasAnyRole(['admin','demo_admin']) ? ($isAr ? 'كامل' : 'Full') : ($isAr ? 'مخصص' : 'Assigned') }}</strong></div></div>
            </div>
        </div>
    </div>
</div>
