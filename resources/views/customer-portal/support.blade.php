<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div>
            <h1 class="sanad-title">{{ app()->getLocale() === "ar" ? "الشكاوى والدعم الفني" : "Complaints & Support" }}</h1>
            <div class="sanad-muted">{{ app()->getLocale() === "ar" ? "تقديم ومتابعة الشكاوى وطلبات الدعم المرتبطة بطلباتك." : "Create complaints linked to a specific active or closed request." }}</div>
        </div>
    </div>

    <div class="sanad-card mb-3">
        <div class="sanad-card-header">{{ app()->getLocale() === "ar" ? "تقديم شكوى جديدة" : "Create Complaint" }}</div>
        <div class="sanad-card-body">
            @if($requests->isEmpty())
                <div class="sanad-muted">{{ $isAr ? 'لا توجد طلبات نشطة أو مغلقة متاحة لتقديم شكوى.' : 'No active or closed requests are available for complaints.' }}</div>
            @else
                <form method="post" action="{{ route('customer-portal.support.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>{{ $isAr ? "الطلب" : "Request" }}</label>
                            <select class="sanad-form-control" name="booking_id" required>
                                @foreach($requests as $request)
                                    @php
                                        $serviceName = localized_model_name($request->service, $isAr ? 'خدمة' : 'Service');
                                        $requestStatus = quick_status_label($request->sanad_stage ?? $request->status ?? 'Active');
                                    @endphp
                                    <option value="{{ $request->id }}" {{ (string) old('booking_id') === (string) $request->id ? 'selected' : '' }}>
                                        {{ $request->quick_reference }} - {{ $serviceName }} - {{ $requestStatus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label>{{ app()->getLocale() === "ar" ? "نوع الشكوى" : "Complaint Type" }}</label>
                            <select class="sanad-form-control" name="complaint_type" required>
                                <option value="">{{ $isAr ? "اختر نوع الشكوى / البلاغ" : "Select complaint type" }}</option>
                                @foreach($complaintTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('complaint_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 form-group">
                            <label>{{ $isAr ? "الأولوية" : "Priority" }}</label>
                            <select class="sanad-form-control" name="priority" required>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>{{ $isAr ? "عادية" : "Normal" }}</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>{{ $isAr ? "عالية" : "High" }}</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>{{ $isAr ? "عاجلة" : "Urgent" }}</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label>{{ $isAr ? "المرفقات" : "Attachment" }}</label>
                            <input class="sanad-form-control" type="file" name="attachment">
                        </div>

                        <div class="col-md-12 form-group">
                            <label>{{ app()->getLocale() === "ar" ? "التفاصيل والوصف" : "Description" }}</label>
                            <textarea class="sanad-form-control" name="description" rows="3" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <button class="sanad-btn">{{ app()->getLocale() === "ar" ? "إرسال الشكوى" : "Submit Complaint" }}</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="sanad-card">
        <div class="sanad-card-body table-responsive">
            <table class="sanad-table">
                <thead>
                    <tr>
                        <th>{{ $isAr ? "الطلب" : "Request" }}</th>
                        <th>{{ $isAr ? "النوع" : "Type" }}</th>
                        <th>{{ $isAr ? "الأولوية" : "Priority" }}</th>
                        <th>{{ $isAr ? "الحالة" : "Status" }}</th>
                        <th>{{ $isAr ? "الجدول الزمني للحل" : "Resolution Timeline" }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        @php
                            $booking = $complaint->booking;
                            $serviceName = localized_model_name(optional($booking)->service, $isAr ? 'خدمة' : 'Service');
                            $typeLabel = $complaintTypes[$complaint->complaint_type] ?? \Illuminate\Support\Str::headline($complaint->complaint_type);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ optional($booking)->quick_reference ?? 'QUICK-'.str_pad($complaint->booking_id, 6, '0', STR_PAD_LEFT) }}</strong>
                                <div class="sanad-muted">{{ $serviceName }}</div>
                            </td>
                            <td>{{ $typeLabel }}</td>
                            <td>{{ quick_status_label($complaint->priority) }}</td>
                            <td><span class="sanad-badge">{{ quick_status_label($complaint->status) }}</span></td>
                            <td>{{ $complaint->resolution_notes ?? ($isAr ? 'مفتوحة لدى دعم كويك.' : 'Open with Quick support.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">{{ $isAr ? 'لم يتم تقديم أي شكاوى.' : 'No complaints submitted.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $complaints->links() }}</div>
</div>
</x-master-layout>
