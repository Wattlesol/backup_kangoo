<x-master-layout>
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
  </head>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            @php $isAr = app()->getLocale() === 'ar'; @endphp<h5 class="font-weight-bold">{{ $isAr ? 'مراقبة الجودة' : 'Quality Control' }}</h5>
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
                                {{ $isAr ? 'إنشاء' : 'Create' }}
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">مراقبه الجوده</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form class="mt-4" action="{{route('complaint.store')}}" method="post" enctype="multipart/form-data">
                                                {{csrf_field()}}
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="complaint_title" class="form-label font-weight-bold">{{ $isAr ? 'عنوان المشكلة' : 'Issue Title' }} <span class="text-danger">*</span></label>
                                                            <input type="text" class="form-control" id="complaint_title" value="{{@old('title')}}" name="title" required placeholder="عنوان الشكوى أو المشكلة">
                                                        </div>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        <label for="issue_type" class="form-control-label font-weight-bold">{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }} <span class="text-danger">*</span></label>
                                                        <select name="issue_type" id="issue_type" class="form-control" required>
                                                            <option value="customer_complaint">Customer Complaint (شكوى عميل)</option>
                                                            <option value="escalation">Escalation (تصعيد إداري)</option>
                                                            <option value="sla_violation">SLA Violation (مخالفة اتفاقية مستوى الخدمة)</option>
                                                            <option value="customer_feedback">Customer Feedback (ملاحظات العميل)</option>
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        <label for="modal_provider_id" class="form-control-label font-weight-bold">Partner (الشريك) <span class="text-danger">*</span></label>
                                                        <select name="provider_id" id="modal_provider_id" class="form-control" required>
                                                            <option value="">Select Partner (اختر الشريك)...</option>
                                                            @foreach($providers ?? [] as $prov)
                                                                <option value="{{ $prov->id }}">{{ $prov->display_name ?: $prov->first_name ?: ('Partner #'.$prov->id) }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        <label for="complaint_details" class="form-control-label font-weight-bold">Complaint Details (تفاصيل الشكوى) <span class="text-danger">*</span></label>
                                                        <textarea name="details" id="complaint_details" class="form-control" rows="4" required placeholder="اكتب تفاصيل الشكوى أو الملاحظة هنا..."></textarea>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        <label for="complaint_file" class="form-control-label font-weight-bold">Attachment (مرفق توثيقي - اختياري)</label>
                                                        <input type="file" name="file" id="complaint_file" class="form-control-file">
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-end gap-2 mt-3">
                                                    <button type="button" class="btn btn-secondary mr-2" data-dismiss="modal">{{ $isAr ? 'إغلاق' : 'Close' }}</button>
                                                    <button type="submit" class="btn btn-success"><i class="fas fa-check-circle mr-1"></i> {{ __('messages.create') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <form class="row">
                    <!-- Provider Field -->
                    <div class="col-md-6">
                        <label for="issue_type_filter">{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}</label>
                        <select name="issue_type" id="issue_type_filter" class="form-control mb-2">
                            <option value="">All {{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}s</option>
                            <option value="customer_complaint">Customer Complaints</option>
                            <option value="escalation">Escalations</option>
                            <option value="sla_violation">SLA Violations</option>
                            <option value="customer_feedback">Customer Feedback</option>
                        </select>
                        {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.provider') ]).' <span class="text-danger">*</span>', ['class'=>'form-control-label'], false) }}
                        {{ Form::select('provider_id', [], "", [
                            'class' => 'select2js form-control',
                            'id' => 'provider_id',
                            'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.provider') ]),
                            'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                        ]) }}
                    </div>

                    <!-- Status Field -->
                    <div class="col-md-6">
                        <label for="status">Satatus</label>
                        <br />
                        <select name="status" class="form-control">
                            <option value="" selected>Select Status</option>

                            @forelse(\App\Enums\ComplaintEnums::all() as $key => $status)
                                <option value="{{$status}}">{{trans('messages.'.$key)}}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <!-- Submit Button -->
                    <div class="col-md-12 mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
        @php
            $sanadComplaintTypeLabels = $isAr ? [
                'document_issue' => 'مشكلة في المستندات',
                'payment_billing' => 'المدفوعات والفوترة',
                'request_delay' => 'تأخر الطلب',
                'status_update' => 'تحديث حالة الطلب',
                'service_quality' => 'جودة الخدمة',
                'communication_issue' => 'مشكلة في التواصل',
                'incorrect_information' => 'معلومات غير صحيحة',
                'other' => 'أخرى',
            ] : [];
            $sanadComplaintPriorityLabels = $isAr ? [
                'low' => 'منخفضة',
                'normal' => 'عادية',
                'high' => 'مرتفعة',
                'urgent' => 'عاجلة',
            ] : [];
            $sanadComplaintStatusLabels = $isAr ? [
                'open' => 'مفتوحة',
                'pending' => 'قيد الانتظار',
                'in_progress' => 'قيد المعالجة',
                'resolved' => 'محلولة',
                'closed' => 'مغلقة',
            ] : [];
        @endphp
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="font-weight-bold mb-1">{{ $isAr ? 'شكاوى عملاء كويك وتذاكر الدعم' : 'Quick Customer Complaints & Support Tickets' }}</h5>
                        <span class="text-muted">{{ $isAr ? 'شكاوى العملاء المرتبطة بطلبات كويك النشطة أو المغلقة.' : 'Customer-created complaints linked to active or closed Quick requests.' }}</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light border p-2">{{ $isAr ? 'الإجمالي' : 'Total' }}: {{ $sanadComplaintStats['total'] ?? 0 }}</span>
                        <span class="badge badge-info p-2">{{ $isAr ? 'المفتوحة' : 'Open' }}: {{ $sanadComplaintStats['open'] ?? 0 }}</span>
                        <span class="badge badge-danger p-2">{{ $isAr ? 'العاجلة' : 'Urgent' }}: {{ $sanadComplaintStats['urgent'] ?? 0 }}</span>
                        <span class="badge badge-success p-2">{{ $isAr ? 'المحلولة' : 'Resolved' }}: {{ $sanadComplaintStats['resolved'] ?? 0 }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ $isAr ? 'الطلب' : 'Request' }}</th>
                                <th>{{ $isAr ? 'العميل' : 'Customer' }}</th>
                                <th>{{ $isAr ? 'النوع' : 'Type' }}</th>
                                <th>{{ $isAr ? 'الأولوية' : 'Priority' }}</th>
                                <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                                <th>{{ $isAr ? 'الوصف' : 'Description' }}</th>
                                <th>{{ $isAr ? 'الجدول الزمني' : 'Timeline' }}</th>
                                <th>{{ $isAr ? 'المرفق' : 'Attachment' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sanadComplaints as $key => $complaint)
                                @php
                                    $booking = $complaint->booking;
                                    $attachmentUrl = $complaint->getFirstMediaUrl('sanad_complaint_attachment');
                                    $complaintTypeLabel = $isAr
                                        ? ($sanadComplaintTypeLabels[$complaint->complaint_type] ?? Str::headline($complaint->complaint_type))
                                        : Str::headline($complaint->complaint_type);
                                    $complaintPriorityLabel = $isAr
                                        ? ($sanadComplaintPriorityLabels[$complaint->priority] ?? Str::headline($complaint->priority))
                                        : Str::headline($complaint->priority);
                                    $complaintStatusLabel = $isAr
                                        ? ($sanadComplaintStatusLabels[$complaint->status] ?? Str::headline($complaint->status))
                                        : Str::headline($complaint->status);
                                @endphp
                                <tr>
                                    <td>{{ method_exists($sanadComplaints, 'firstItem') ? $sanadComplaints->firstItem() + $key : $key + 1 }}</td>
                                    <td>
                                        @if($booking)
                                            <a href="{{ route('sanad.requests.show', $booking->id) }}" class="font-weight-bold">
                                                {{ $booking->quick_reference }}
                                            </a>
                                            <div class="text-muted small">{{ $isAr ? (optional($booking->service)->name_ar ?: optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-') : (optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-') }}</div>
                                            <div class="text-muted small">{{ $isAr ? 'الشريك' : 'Partner' }}: {{ optional($booking->provider)->display_name ?: ($isAr ? 'غير مُسند' : 'Not assigned') }}</div>
                                        @else
                                            <span class="text-muted">{{ $isAr ? 'لا يوجد طلب مرتبط' : 'No linked request' }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ optional($complaint->customer)->display_name ?: optional($complaint->customer)->email ?: '-' }}</strong>
                                        <div class="text-muted small">{{ optional($complaint->customer)->email }}</div>
                                    </td>
                                    <td>{{ $complaintTypeLabel }}</td>
                                    <td>
                                        <span class="badge badge-{{ $complaint->priority === 'urgent' ? 'danger' : ($complaint->priority === 'high' ? 'warning' : 'secondary') }}">
                                            {{ $complaintPriorityLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ in_array($complaint->status, ['resolved', 'closed'], true) ? 'success' : 'info' }}">
                                            {{ $complaintStatusLabel }}
                                        </span>
                                    </td>
                                    <td style="min-width: 260px;">{{ Str::limit($complaint->description, 180) }}</td>
                                    <td>
                                        <div class="small">{{ $isAr ? 'تاريخ الإنشاء' : 'Created' }}: {{ optional($complaint->created_at)->format('Y-m-d H:i') }}</div>
                                        <div class="small text-muted">{{ $isAr ? 'تاريخ الحل' : 'Resolved' }}: {{ optional($complaint->resolved_at)->format('Y-m-d H:i') ?: ($isAr ? 'مفتوحة لدى دعم كويك.' : 'Open with Quick support.') }}</div>
                                    </td>
                                    <td>
                                        @if($attachmentUrl)
                                            <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-paperclip mr-1"></i> {{ $isAr ? 'فتح' : 'Open' }}
                                            </a>
                                        @else
                                            <span class="text-muted small">{{ $isAr ? 'لا يوجد' : 'None' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">{{ $isAr ? 'لم يتم العثور على شكاوى عملاء كويك وفق عوامل التصفية المحددة.' : 'No Quick customer complaints found for the selected filters.' }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($sanadComplaints, 'hasPages') && $sanadComplaints->hasPages())
                    <div class="mt-3">{{ $sanadComplaints->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="table-responsive">


        <br>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Partner</th>
                <th>{{ $isAr ? 'نوع المشكلة' : 'Issue Type' }}</th>
                <th>الحاله</th>
                <th>انشاء بواسطه</th>
                <th>عرض</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $key=>$item)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$item->provider->display_name}}</td>
                    <td>{{ str_replace('_', ' ', ucfirst($item->issue_type ?: 'customer_complaint')) }}</td>
                    <td>{{trans('messages.'.\App\Enums\ComplaintEnums::GetById($item->status))}}</td>
                    <td>{{$item->createdby->display_name}}</td>

                    <td>
                        <a href="{{route('complaint.show',['id'=>$item->id])}}" class="btn btn-info"> Show<i class="ti-pin"></i> </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="200">
                        <h5>{{ __('messages.no_data') }}</h5>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <br>
        @if($data->count()>0)
            <div class="row">
                <div class="col-md-5 col-sm-3 "> Count {{$data->total()}} </div>
                <div class="col-md-7 col-sm-7">{{$data->appends(\Request::except('_token'))->render()}}</div>
            </div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
