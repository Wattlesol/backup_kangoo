<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $rawInstructions = $service->service_instructions ?? [];
    if (is_string($rawInstructions)) {
        $decodedInstructions = json_decode($rawInstructions, true);
        $instructionSteps = is_array($decodedInstructions)
            ? collect($decodedInstructions)
            : collect(array_filter([['title' => $isAr ? 'تعليمات الخدمة' : 'Service instructions', 'instruction' => $rawInstructions]]));
    } else {
        $instructionSteps = collect($rawInstructions ?: []);
    }
@endphp
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">{{ localized_model_name($service) }}</h1><div class="sanad-muted">{{ $service->government_entity ?? ($isAr ? 'خدمة كويك' : 'Quick Service') }}</div></div>
        <a class="sanad-btn" href="{{ route('customer-portal.requests.create', ['service_id' => $service->id]) }}">{{ $isAr ? 'بدء طلب الخدمة' : 'Start Service' }}</a>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="sanad-card mb-3"><div class="sanad-card-header">{{ $isAr ? 'وصف الخدمة' : 'Service Description' }}</div><div class="sanad-card-body">{!! $service->description ?: '<span class="sanad-muted">'.($isAr ? 'لا يوجد وصف معدّ.' : 'No description configured.').'</span>' !!}</div></div>
            <div class="sanad-card mb-3"><div class="sanad-card-header">{{ $isAr ? "المتطلبات" : "Requirements" }}</div><div class="sanad-card-body">@forelse($docs as $doc)<span class="sanad-badge mr-2 mb-2">{{ localized_service_document_name($doc) }}</span>@empty<span class="sanad-muted">{{ $isAr ? 'لا توجد مستندات مطلوبة معدّة.' : 'No required documents configured.' }}</span>@endforelse</div></div>
            <div class="sanad-card"><div class="sanad-card-header">{{ $isAr ? "التعليمات والشروط" : "Instructions and Terms" }}</div><div class="sanad-card-body"><h6>{{ $isAr ? 'إرشادات وتعليمات تنفيذ الخدمة' : 'Service Instructions' }}</h6>@forelse($instructionSteps as $step)<div class="mb-2"><strong>{{ is_array($step) ? ($step['title'] ?? ($isAr ? 'خطوة' : 'Step')) : ($isAr ? 'تعليمات الخدمة' : 'Service instructions') }}</strong><div class="sanad-muted">{{ is_array($step) ? ($step['instruction'] ?? '') : $step }}</div></div>@empty<p>-</p>@endforelse<h6>{{ $isAr ? 'الشروط والأحكام' : 'Terms & Conditions' }}</h6><p>{{ $service->terms_and_conditions ?? '-' }}</p></div></div>
        </div>
        <div class="col-lg-4">
            <div class="sanad-card mb-3"><div class="sanad-card-header">{{ $isAr ? "المعالجة والرسوم" : "Processing and Fees" }}</div><div class="sanad-card-body"><p><strong>{{ $isAr ? 'المدة المتوقعة للمعالجة:' : 'Estimated Processing Time:' }}</strong> {{ $service->estimated_completion_time ?? '-' }}</p><p><strong>{{ $isAr ? 'الرسوم الحكومية' : 'Government Fee' }}:</strong> {{ getPriceFormat($service->government_fee ?? 0) }}</p><p><strong>{{ $isAr ? 'رسوم الخدمة' : 'Service Fee' }}:</strong> {{ getPriceFormat($service->service_fee ?? $service->price ?? 0) }}</p></div></div>
            <div class="sanad-card"><div class="sanad-card-header">{{ $isAr ? "خدمات ذات صلة" : "Related Services" }}</div><div class="sanad-card-body">@forelse($relatedServices as $related)<div class="mb-2"><a href="{{ route('customer-portal.catalog.show', $related->id) }}">{{ localized_model_name($related) }}</a></div>@empty<span class="sanad-muted">{{ $isAr ? 'لا توجد خدمات ذات صلة.' : 'No related services.' }}</span>@endforelse</div></div>
        </div>
    </div>
</div>
</x-master-layout>
