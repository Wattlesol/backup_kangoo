<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div>
            <h1 class="sanad-title">{{ app()->getLocale() === "ar" ? "دليل الخدمات" : "Service Catalog" }}</h1>
            <div class="sanad-muted">{{ app()->getLocale() === "ar" ? "تصفح خدمات كويك الحكومية والتجارية والباقات المجمعة." : "Browse Quick government, business, and bundled package services." }}</div>
        </div>
        <div class="sanad-actions">
            <a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}"><i class="fas fa-plus-circle"></i> {{ app()->getLocale() === "ar" ? "بدء الخدمة" : "Start Service" }}</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.ai') }}"><i class="fas fa-robot"></i> {{ app()->getLocale() === "ar" ? "اسأل المساعد الذكي" : "Ask AI Assistant" }}</a>
        </div>
    </div>

    <div class="quick-catalog-tabs mb-3">
        <a href="{{ route('customer-portal.catalog', ['type' => 'all']) }}" class="quick-catalog-tab {{ request('type', 'all') === 'all' ? 'active' : '' }}">{{ $isAr ? 'كل الخدمات' : 'All Services' }}</a>
        <a href="{{ route('customer-portal.catalog', ['type' => 'single']) }}" class="quick-catalog-tab {{ request('type') === 'single' ? 'active' : '' }}">{{ $isAr ? 'الخدمات الفردية' : 'Single Services' }}</a>
        <a href="{{ route('customer-portal.catalog', ['type' => 'bundle']) }}" class="quick-catalog-tab {{ request('type') === 'bundle' ? 'active' : '' }}">{{ $isAr ? 'الباقات المجمعة' : 'Bundles & Packages' }}</a>
    </div>

    <form class="sanad-card mb-3" method="get">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        <div class="sanad-card-body row">
            <div class="col-md-5 mb-2 mb-md-0"><input class="sanad-form-control" name="search" value="{{ request('search') }}" placeholder="{{ app()->getLocale() === "ar" ? "ابحث عن الخدمات، المتطلبات، الجهة..." : "Search services, requirements, authority..." }}"></div>
            <div class="col-md-5 mb-2 mb-md-0">
                <select class="sanad-form-control" name="category_id">
                    <option value="">{{ $isAr ? 'جميع التصنيفات' : 'All Categories' }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) request('category_id') === (string) $category->id ? 'selected' : '' }}>{{ localized_model_name($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="sanad-btn w-100" type="submit"><i class="fas fa-search mr-1"></i> {{ app()->getLocale() === "ar" ? "تصفية" : "Filter" }}</button></div>
        </div>
    </form>

    <div class="sanad-grid">
        @foreach($services as $service)
            <div class="sanad-card">
                <div class="sanad-card-body d-flex flex-column justify-content-between h-100">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="sanad-badge">{{ localized_model_name($service->category, 'Quick') }}</span>
                        </div>
                        <h5 class="mt-2 font-weight-bold">{{ localized_model_name($service) }}</h5>
                        <p class="sanad-muted">{{ Str::limit(strip_tags($service->description), 120) }}</p>
                        <div class="mb-2 small"><strong>{{ $isAr ? 'الجهة:' : 'Entity:' }}</strong> {{ $service->government_entity ?? ($isAr ? 'جهة حكومية' : 'Ministry / Government') }}</div>
                        <div class="mb-2 small"><strong>{{ $isAr ? 'المدة المتوقعة:' : 'ETA:' }}</strong> {{ $service->estimated_completion_time ?? ($isAr ? '1-3 أيام عمل' : '1-3 Business Days') }}</div>
                        <div class="mb-3"><strong>{{ $isAr ? 'الرسوم:' : 'Fee:' }}</strong> <span class="text-primary font-weight-bold">{{ getPriceFormat(($service->service_fee ?? $service->price ?? 0) + ($service->government_fee ?? 0)) }}</span></div>
                    </div>
                    <div class="sanad-actions mt-3">
                        <a class="sanad-btn" href="{{ route('customer-portal.requests.create', ['service_id' => $service->id]) }}">
                            {{ $isAr ? 'بدء الخدمة' : 'Start Service' }}
                        </a>
                        <a class="sanad-btn secondary" href="{{ route('customer-portal.catalog.show', $service->id) }}">{{ app()->getLocale() === "ar" ? "التفاصيل" : "Details" }}</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $services->links() }}</div>

    <!-- Floating Customer AI Assistant Widget -->
    <a href="{{ route('customer-portal.ai') }}" class="sanad-floating-ai-btn" title="{{ $isAr ? 'اسأل مساعد كويك الذكي' : 'Ask Quick AI Assistant' }}">
        <div class="sanad-floating-ai-icon"><i class="fas fa-robot"></i></div>
        <span>{{ $isAr ? "مساعد كويك الذكي" : "Quick AI Assistant" }}</span>
    </a>
</div>
</x-master-layout>
