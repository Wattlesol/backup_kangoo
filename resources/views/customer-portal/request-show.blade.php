<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $stageLabelsAr = ['submitted'=>'تم التقديم','pending_review'=>'قيد المراجعة','assigned_to_partner'=>'مُسند إلى الشريك','assigned_to_employee'=>'مُسند إلى الموظف','in_progress'=>'قيد التنفيذ','awaiting_customer_action'=>'بانتظار إجراء العميل','awaiting_quality_review'=>'بانتظار مراجعة الجودة','ready_for_delivery'=>'جاهز للتسليم','completed'=>'مكتمل','closed'=>'مغلق','escalated'=>'تم التصعيد','cancelled'=>'ملغي'];
    $localizedStage = fn ($value) => $isAr ? ($stageLabelsAr[Str::snake((string) $value)] ?? Str::headline($value)) : Str::headline($value);
    $pendingDocumentRequests = $booking->sanadDocumentRequests
        ->whereIn('requested_from', ['customer', 'user'])
        ->whereIn('status', ['pending', 'rejected', 'replacement_requested']);
@endphp
<style>
    .quick-inline-buzz { display:flex; align-items:center; justify-content:space-between; gap:16px; }
    .quick-inline-buzz-message { min-width:0; line-height:1.55; }
    .quick-chat-cta { display:flex; align-items:center; justify-content:space-between; gap:18px; }
    @media (max-width: 575px) { .quick-inline-buzz,.quick-chat-cta { align-items:stretch; flex-direction:column; } }
</style>
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">{{ $isAr ? 'الطلب' : 'Request' }} {{ $booking->quick_reference }}</h1><div class="sanad-muted">{{ localized_model_name($booking->service) }}</div></div>
        <a class="sanad-btn secondary" href="{{ route('customer-portal.requests.index') }}">{{ $isAr ? 'رجوع' : 'Back' }}</a>
    </div>
    @foreach($openBuzzAlerts as $buzz)
        <div class="alert alert-danger" id="buzz-{{ $buzz->id }}">
            <div class="quick-inline-buzz">
                <div class="quick-inline-buzz-message"><strong>{{ $isAr ? 'تنبيه عاجل:' : 'Urgent Buzz:' }}</strong> {{ $buzz->message }}</div>
                <a class="sanad-btn text-nowrap" href="{{ route('customer-portal.messages', ['booking_id' => $booking->id, 'buzz_id' => $buzz->id]) }}#buzz-{{ $buzz->id }}">{{ $isAr ? 'تحدث إلى كويك' : 'Talk to Quick' }}</a>
            </div>
        </div>
    @endforeach
    <div class="row">
        <div class="col-xl-8">
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? 'معلومات الطلب' : 'Request Information' }}</div>
                <div class="sanad-card-body">
                    <div class="sanad-grid">
                        <div><span class="sanad-muted">{{ $isAr ? "الخدمة" : "Service" }}</span><strong class="d-block">{{ localized_model_name($booking->service) }}</strong></div>
                        <div><span class="sanad-muted">{{ $isAr ? "شريك الخدمة" : "Service Provider" }}</span><strong class="d-block">Quick</strong></div>
                        <div><span class="sanad-muted">{{ $isAr ? "الدعم" : "Support" }}</span><strong class="d-block">{{ $isAr ? 'فريق كويك' : 'Quick team' }}</strong></div>
                        <div><span class="sanad-muted">SLA</span><strong class="d-block">{{ optional($booking->sla_due_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                        <div><span class="sanad-muted">{{ $isAr ? "تاريخ الإنشاء" : "Created" }}</span><strong class="d-block">{{ optional($booking->created_at)->format('Y-m-d H:i') }}</strong></div>
                        <div><span class="sanad-muted">{{ $isAr ? "الإنجاز المتوقع" : "Estimated Completion" }}</span><strong class="d-block">{{ optional($booking->expected_completion_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                    </div>
                    <div class="mt-4"><div class="d-flex justify-content-between"><strong>{{ $isAr ? "نسبة التقدم" : "Progress" }}</strong><span>{{ $progress }}%</span></div><div class="sanad-progress"><span style="width:{{ $progress }}%"></span></div></div>
                    <div class="mt-3"><span class="sanad-badge">{{ $localizedStage($stage) }}</span> <span class="sanad-muted ml-2">{{ $isAr ? 'يتولى فريق كويك إدارة الخطوة التالية المتوقعة.' : 'The next expected step is managed by the Quick team.' }}</span></div>
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? "المراحل والجدول الزمني" : "Timeline" }}</div>
                <div class="sanad-card-body sanad-timeline">
                    @forelse($booking->sanadRequestActions as $action)
                        <div class="sanad-timeline-item"><strong>{{ Str::headline($action->action ?? 'Update') }}</strong><div class="sanad-muted">{{ $action->note ?? $action->reason ?? 'Request updated.' }}</div><small>{{ optional($action->created_at)->format('Y-m-d H:i') }}</small></div>
                    @empty
                        <div class="sanad-timeline-item"><strong>{{ $isAr ? "تم إنشاء الطلب" : "Request Created" }}</strong><div class="sanad-muted">{{ $isAr ? 'تم تقديم طلبك بنجاح.' : 'Your request has been submitted.' }}</div><small>{{ optional($booking->created_at)->format('Y-m-d H:i') }}</small></div>
                    @endforelse
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? 'تواصل مع كويك' : 'Quick Support' }}</div>
                <div class="sanad-card-body">
                    <div class="quick-chat-cta">
                        <div><strong>{{ $isAr ? 'هل تحتاج إلى مساعدة بخصوص هذا الطلب؟' : 'Need help with this request?' }}</strong><div class="sanad-muted">{{ $isAr ? 'افتح المحادثة المخصصة لهذا الطلب مع فريق كويك.' : 'Open this request’s dedicated conversation with the Quick team.' }}</div></div>
                        <a class="sanad-btn text-nowrap" href="{{ route('customer-portal.messages', ['booking_id' => $booking->id]) }}">{{ $isAr ? 'تحدث إلى كويك' : 'Talk to Quick' }}</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? 'المستندات المطلوبة' : 'Required Documents' }}</div>
                <div class="sanad-card-body">
                    @forelse($docs as $doc)
                        @php $storedName = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc; $name = localized_service_document_name($doc); @endphp
                        <div class="d-flex justify-content-between border-bottom py-2"><span>{{ $name }}</span><span class="sanad-badge">{{ $booking->sanadDocuments->contains('document_type', $storedName) ? ($isAr ? 'تم الإرسال' : 'Submitted') : ($isAr ? 'مطلوب' : 'Required') }}</span></div>
                    @empty
                        <p class="sanad-muted">{{ $isAr ? 'لم يتم إعداد قائمة المستندات المطلوبة.' : 'No required document list configured.' }}</p>
                    @endforelse
                    @if($documentChoices->isNotEmpty())
                        <form class="mt-3" method="post" action="{{ route('customer-portal.requests.documents.store', $booking->id) }}" enctype="multipart/form-data">
                            @csrf
                            <label class="d-block mb-1 font-weight-bold" for="request-document-selection">{{ $isAr ? 'نوع المستند المطلوب' : 'Required document type' }}</label>
                            <select id="request-document-selection" class="sanad-form-control mb-2" name="document_selection" required>
                                <option value="">{{ $isAr ? 'اختر نوع المستند' : 'Select document type' }}</option>
                                @foreach($documentChoices as $choiceKey => $choice)
                                    <option value="{{ $choiceKey }}">{{ $choice['label'] }}</option>
                                @endforeach
                            </select>
                            <label class="d-block mb-1 font-weight-bold" for="request-document-file">{{ $isAr ? 'اختر الملف' : 'Select file' }}</label>
                            <input id="request-document-file" class="sanad-form-control mb-2" name="file" type="file" required>
                            <button class="sanad-btn">{{ $isAr ? "رفع مستند" : "Upload Document" }}</button>
                        </form>
                    @else
                        <p class="sanad-muted mt-3 mb-0">{{ $isAr ? 'لا توجد مستندات مطلوبة للرفع حالياً.' : 'There are no requested documents to upload right now.' }}</p>
                    @endif
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? "طلبات المستندات" : "Document Requests" }}</div>
                <div class="sanad-card-body">
                    @foreach($pendingDocumentRequests as $docRequest)
                        <a class="d-block border-bottom py-2 text-decoration-none text-reset" href="{{ route('customer-portal.messages', ['booking_id' => $booking->id]) }}">
                            <strong>{{ $docRequest->document_name }}</strong>
                            <div class="sanad-muted">{{ $docRequest->instructions ?? $docRequest->reason }}</div>
                            <span class="sanad-badge">{{ quick_status_label($docRequest->status) }}</span>
                        </a>
                    @endforeach
                    @foreach($openBuzzAlerts as $buzz)
                        <a class="d-block border-bottom py-2 text-decoration-none text-reset" href="{{ route('customer-portal.messages', ['booking_id' => $booking->id, 'buzz_id' => $buzz->id]) }}#buzz-{{ $buzz->id }}">
                            <strong>{{ $isAr ? 'تنبيه كويك عاجل' : 'Pending Quick Buzz' }}</strong>
                            <div class="sanad-muted">{{ Str::limit($buzz->message, 120) }}</div>
                            <span class="sanad-badge">{{ $isAr ? 'فتح المحادثة' : 'Open conversation' }}</span>
                        </a>
                    @endforeach
                    @if($pendingDocumentRequests->isEmpty() && $openBuzzAlerts->isEmpty())
                        <p class="sanad-muted">{{ $isAr ? 'لا توجد طلبات مستندات مفتوحة.' : 'No open document requests.' }}</p>
                    @endif
                </div>
            </div>
            <div class="sanad-card mb-3">
                <div class="sanad-card-header">{{ $isAr ? 'المستندات الموثقة' : 'Verified Documents' }}</div>
                <div class="sanad-card-body">
                    @forelse($verifiedDocuments as $document)
                        @php $documentUrl = $document->publicDocumentUrl(); @endphp
                        <div class="border-bottom py-2"><strong>{{ $document->document_type }}</strong><div><span class="sanad-badge">{{ $isAr ? 'موثق' : 'Verified' }}</span>@if($document->approved_at)<small class="sanad-muted ml-2">{{ $document->approved_at->format('Y-m-d H:i') }}</small>@endif</div>@if($documentUrl)<a href="{{ $documentUrl }}" target="_blank">{{ $isAr ? 'معاينة / تحميل' : 'Preview / Download' }}</a>@endif</div>
                    @empty
                        <p class="sanad-muted">{{ $isAr ? 'لا توجد مستندات موثقة حتى الآن.' : 'No verified documents yet.' }}</p>
                    @endforelse
                </div>
            </div>
            <div class="sanad-card">
                <div class="sanad-card-header">{{ $isAr ? "الفواتير والرسوم" : "Billing" }}</div>
                <div class="sanad-card-body"><p><strong>{{ $isAr ? 'الفاتورة:' : 'Invoice:' }}</strong> {{ $booking->payment ? ($isAr ? 'متاحة' : 'Available') : '-' }}</p><p><strong>{{ $isAr ? 'رسوم الخدمة:' : 'Service Fee:' }}</strong> {{ getPriceFormat(optional($booking->service)->service_fee ?? $booking->amount ?? 0) }}</p><p><strong>{{ $isAr ? 'ضريبة القيمة المضافة:' : 'VAT:' }}</strong> {{ getPriceFormat($booking->final_total_tax ?? 0) }}</p><p><strong>{{ $isAr ? 'حالة الدفع:' : 'Payment Status:' }}</strong> {{ quick_status_label(optional($booking->payment)->payment_status ?? 'pending') }}</p>@if($booking->payment)<a class="sanad-btn secondary" href="{{ route('invoice_pdf', $booking->id) }}">{{ $isAr ? "تصدير الفاتورة" : "Export Invoice" }}</a>@endif</div>
            </div>
        </div>
    </div>
</div>
</x-master-layout>
