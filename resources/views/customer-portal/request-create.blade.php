<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">{{ app()->getLocale() === "ar" ? "إنشاء طلب جديد" : "Create New Request" }}</h1><div class="sanad-muted">{{ app()->getLocale() === "ar" ? "اختر الخدمة، وقدم المعلومات المطلوبة، وارفع المستندات، ثم قم بالمراجعة والإرسال." : "Select service, provide information, upload documents, review fees, and submit." }}</div></div></div>
    <form class="sanad-card" method="post" action="{{ route('customer-portal.requests.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="sanad-card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>{{ app()->getLocale() === "ar" ? "اختر الخدمة" : "Select Service" }}</label>
                    <select class="sanad-form-control" name="service_id" onchange="window.location='{{ route('customer-portal.requests.create') }}?service_id='+this.value" required>
                        <option value="">{{ app()->getLocale() === "ar" ? "اختر الخدمة المطلوبة" : "Choose service" }}</option>
                        @foreach($services as $item)
                            @php $serviceLabel = localized_model_name($item, ($isAr ? 'خدمة #' : 'Service #').$item->id); @endphp
                            <option value="{{ $item->id }}" {{ optional($selectedService)->id == $item->id ? 'selected' : '' }}>{{ $serviceLabel }}</option>
                        @endforeach
                    </select>
                    @if($selectedService)
                        <div class="mt-2 sanad-badge">
                            {{ $isAr ? 'الخدمة المختارة:' : 'Selected:' }} {{ localized_model_name($selectedService, ($isAr ? 'خدمة #' : 'Service #').$selectedService->id) }}
                        </div>
                    @endif
                </div>
                <div class="col-md-6 form-group"><label>{{ app()->getLocale() === "ar" ? "الوقت المتوقع للإنجاز" : "Estimated Completion" }}</label><input class="sanad-form-control" value="{{ optional($selectedService)->estimated_completion_time ?? '-' }}" disabled></div>
                <div class="col-md-12 form-group"><label>{{ app()->getLocale() === "ar" ? "المعلومات المطلوبة" : "Required Information" }}</label><textarea class="sanad-form-control" name="description" rows="4" placeholder="{{ app()->getLocale() === "ar" ? "أضف أي تفاصيل أو متطلبات خاصة بهذه الخدمة" : "Add information required for this service" }}"></textarea></div>
            </div>
            <h5 class="mt-3">{{ app()->getLocale() === "ar" ? "رفع المستندات المطلوبة" : "Upload Required Documents" }}</h5>
            <div class="row">
                @forelse($docs as $doc)
                    @php $storedName = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc; $name = localized_service_document_name($doc); $key = is_array($doc) ? ($doc['key'] ?? Str::slug($storedName, '_')) : Str::slug($storedName, '_'); @endphp
                    <div class="col-md-6 form-group"><label>{{ $name }}</label><input class="sanad-form-control" type="file" name="required_documents[{{ $key }}]"></div>
                @empty
                    <div class="col-12"><p class="sanad-muted">{{ $isAr ? 'لا توجد مستندات مطلوبة معدّة للخدمة المختارة.' : 'No required documents configured for the selected service.' }}</p></div>
                @endforelse
            </div>
            @if($vaultDocuments->count())
                <h5 class="mt-3">{{ app()->getLocale() === "ar" ? "إعادة الاستخدام من خزينة المستندات" : "Reuse From Document Vault" }}</h5>
                <div class="row">@foreach($vaultDocuments as $document)<div class="col-md-4"><label><input type="checkbox" name="vault_document_ids[]" value="{{ $document->id }}"> {{ $document->document_type }}</label></div>@endforeach</div>
            @endif
            <h5 class="mt-3">{{ app()->getLocale() === "ar" ? "المراجعة والدفع" : "Review and Payment" }}</h5>
            <div class="sanad-grid mb-3">
                <div><span class="sanad-muted">{{ app()->getLocale() === "ar" ? "الرسوم الحكومية" : "Government Fee" }}</span><strong class="d-block">{{ getPriceFormat(optional($selectedService)->government_fee ?? 0) }}</strong></div>
                <div><span class="sanad-muted">{{ app()->getLocale() === "ar" ? "رسوم الخدمة" : "Service Fee" }}</span><strong class="d-block">{{ getPriceFormat(optional($selectedService)->service_fee ?? optional($selectedService)->price ?? 0) }}</strong></div>
                <div><span class="sanad-muted">{{ app()->getLocale() === "ar" ? "رقم الطلب" : "Request Number" }}</span><strong class="d-block">{{ app()->getLocale() === "ar" ? "يتم التوليد تلقائياً عند الإرسال" : "Generated on submit" }}</strong></div>
            </div>
            <button class="sanad-btn" type="submit">{{ app()->getLocale() === "ar" ? "إرسال الطلب الآن" : "Submit Request" }}</button>
        </div>
    </form>
</div>
</x-master-layout>
