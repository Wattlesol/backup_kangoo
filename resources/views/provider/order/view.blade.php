<x-master-layout>
    @php
        $noteActions = $booking->sanadRequestActions->filter(fn($action) => !empty($action->internal_note));
        $canRespondToOrder = in_array($booking->sanad_stage ?: $booking->status, ['submitted', 'pending', 'pending_review', 'assigned_to_partner'], true);
        $isAr = app()->getLocale() === 'ar';
    @endphp
    <div class="container-fluid provider-order-detail quick-role-page quick-partner-page">
        <div class="partner-detail-toolbar">
            <div class="partner-detail-breadcrumb">
                <a href="{{ route('provider.order.index') }}">{{ $isAr ? 'الطلبات المسندة' : 'Assigned orders' }}</a>
                <i class="fas fa-chevron-{{ $isAr ? 'left' : 'right' }}"></i>
                <strong>{{ $booking->quick_reference }}</strong>
            </div>
            <div class="d-flex flex-wrap gap-2 partner-detail-actions">
                    @if($canRespondToOrder)
                        <form method="POST" action="{{ route('provider.order.update-status') }}">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="accept_order">
                            <button class="btn btn-sm btn-success"><i class="fa fa-check mr-1"></i> {{ $isAr ? 'قبول الطلب' : 'Accept Order' }}</button>
                        </form>
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="reject_order">
                            <input name="reason" class="form-control form-control-sm" placeholder="{{ $isAr ? 'سبب الرفض' : 'Reject reason' }}" required>
                            <button class="btn btn-sm btn-outline-danger">{{ $isAr ? 'رفض الطلب' : 'Reject Order' }}</button>
                        </form>
                    @endif
                    <a href="{{ route('provider.order.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fa fa-arrow-{{ $isAr ? 'right' : 'left' }}"></i> {{ $isAr ? 'رجوع' : 'Back' }}</a>
            </div>
        </div>

        <section class="partner-order-summary">
            <div class="partner-order-summary__heading">
                <div>
                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                        <span class="partner-order-reference">{{ $booking->quick_reference }}</span>
                        <span class="badge badge-primary">{{ quick_status_label($booking->sanad_stage ?: 'submitted') }}</span>
                        <span class="badge badge-light">{{ quick_status_label($booking->sanad_priority ?: 'normal') }}</span>
                    </div>
                    <h1>{{ $isAr ? (optional($booking->service)->name_ar ?: optional($booking->service)->name_en) : (optional($booking->service)->name_en ?: optional($booking->service)->name) }}</h1>
                    <p>{{ $isAr ? 'مساحة تنفيذ موحدة لمتابعة مراحل المعاملة والمستندات والفريق.' : 'A unified execution workspace for stages, documents, customer actions, and team ownership.' }}</p>
                </div>
            </div>
            <div class="partner-order-facts">
                <div><span>{{ $isAr ? 'العميل' : 'Customer' }}</span><strong>{{ optional($booking->customer)->display_name ?: '-' }}</strong></div>
                <div><span>{{ $isAr ? 'الجهة / التصنيف' : 'Authority / category' }}</span><strong>{{ optional(optional($booking->service)->category)->name ?: '-' }}</strong></div>
                <div><span>{{ $isAr ? 'موعد SLA' : 'SLA deadline' }}</span><strong class="{{ optional($booking->sla_due_at)->isPast() ? 'text-danger' : '' }}">{{ optional($booking->sla_due_at)->format('Y-m-d H:i') ?: '-' }}</strong></div>
                <div><span>{{ $isAr ? 'الموظف المسؤول' : 'Assigned owner' }}</span><strong>{{ $booking->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: ($isAr ? 'غير مسند' : 'Unassigned') }}</strong></div>
            </div>
        </section>

        <div class="row">
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'الملاحظات الداخلية' : 'Internal Notes' }}</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="mb-3">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="add_internal_note">
                            <textarea name="internal_note" class="form-control" rows="3" placeholder="{{ $isAr ? 'أضف ملاحظة داخلية' : 'Add internal note' }}" required></textarea>
                            <button class="btn btn-primary btn-sm mt-2">{{ $isAr ? 'إضافة ملاحظة' : 'Add Note' }}</button>
                        </form>
                        <div class="note-timeline">
                            @forelse($noteActions as $action)
                                <div class="note-entry">
                                    <strong>{{ optional($action->actor)->display_name ?: ($isAr ? 'فريق كويك' : 'Quick Team') }}</strong>
                                    <span class="text-muted small d-block">{{ $action->created_at->format('Y-m-d H:i') }}</span>
                                    <p class="mb-0">{{ $action->internal_note }}</p>
                                </div>
                            @empty
                                <p class="text-muted mb-0">{{ $isAr ? 'لا توجد ملاحظات بعد.' : 'No notes yet.' }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'سجل التنفيذ' : 'Execution History' }}</h5></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($booking->sanadRequestActions as $action)
                                <li class="list-group-item">
                                    <strong>{{ Str::headline($action->action) }}</strong>
                                    <div class="text-muted small">{{ optional($action->actor)->display_name }} | {{ $action->created_at->format('Y-m-d H:i') }}</div>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">{{ $isAr ? 'لم يتم تسجيل أي إجراءات.' : 'No actions recorded.' }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $isAr ? 'مساحة عمل الطلب' : 'Order Workspace' }}</h5>
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <select name="action" class="form-control form-control-sm">
                                <option value="complete_current_stage">{{ $isAr ? 'إكمال المرحلة الحالية' : 'Complete Current Stage' }}</option>
                                <option value="mark_completed">{{ $isAr ? 'تحديد الطلب كمكتمل' : 'Mark Order Completed' }}</option>
                            </select>
                            <button class="btn btn-sm btn-primary">{{ $isAr ? 'تحديث الحالة' : 'Update Status' }}</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>{{ $isAr ? 'العميل' : 'Customer' }}:</strong> {{ optional($booking->customer)->display_name ?: '-' }}</p>
                                <p><strong>{{ $isAr ? 'الخدمة' : 'Service' }}:</strong> {{ optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-' }}</p>
                                <p><strong>{{ $isAr ? 'التصنيف' : 'Category' }}:</strong> {{ optional(optional($booking->service)->category)->name ?: '-' }}</p>
                                <p><strong>{{ $isAr ? 'الأولوية' : 'Priority' }}:</strong> {{ ucfirst($booking->sanad_priority ?: 'normal') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>{{ $isAr ? 'موعد SLA' : 'SLA Timer' }}:</strong> {{ optional($booking->sla_due_at)->format('Y-m-d H:i') ?: '-' }}</p>
                                <p><strong>{{ $isAr ? 'الموعد المتوقع للإنجاز' : 'Expected Completion' }}:</strong> {{ optional($booking->expected_completion_at)->format('Y-m-d H:i') ?: '-' }}</p>
                                <p><strong>{{ $isAr ? 'المرحلة الحالية' : 'Current Stage' }}:</strong> {{ Str::headline($booking->sanad_stage ?: 'submitted') }}</p>
                                <p><strong>{{ $isAr ? 'الموظفون المسندون' : 'Assigned Employees' }}:</strong> {{ $booking->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $isAr ? 'إسناد الموظفين' : 'Employee Assignment' }}</h5>
                        <a href="{{ route('provider.workflows.create') }}" class="btn btn-sm btn-outline-primary">{{ $isAr ? 'إنشاء مسار عمل' : 'Create Workflow' }}</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('provider.order.employees.assign', $booking->id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>{{ $isAr ? 'نمط الإسناد' : 'Assignment Mode' }}</label>
                                    <select name="assignment_mode" class="form-control">
                                        <option value="manual">{{ $isAr ? 'يدوي' : 'Manual' }}</option>
                                        <option value="sequential">{{ $isAr ? 'تسلسلي' : 'Sequential' }}</option>
                                        <option value="parallel">{{ $isAr ? 'متوازي' : 'Parallel' }}</option>
                                        <option value="automatic_next_stage">{{ $isAr ? 'انتقال تلقائي للمرحلة التالية' : 'Automatic Next Stage' }}</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ $isAr ? 'مسار العمل' : 'Workflow' }}</label>
                                    <select name="workflow_template_id" class="form-control">
                                        <option value="">{{ $isAr ? 'بدون قالب' : 'No template' }}</option>
                                        @foreach($workflowTemplates as $workflow)
                                            <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>{{ $isAr ? 'الموظفون' : 'Employees' }}</label>
                                    <select name="handyman_id[]" class="form-control select2" multiple>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ $booking->handymanAdded->pluck('handyman_id')->contains($employee->id) ? 'selected' : '' }}>{{ $employee->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm">{{ $isAr ? 'إسناد المهمة' : 'Assign Job' }}</button>
                        </form>

                        <div class="table-responsive mt-4">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr>
        <th>{{ $isAr ? 'المرحلة' : 'Stage' }}</th>
        <th>{{ $isAr ? 'الموظف' : 'Employee' }}</th>
        <th>{{ $isAr ? 'النمط' : 'Mode' }}</th>
        <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
        <th>{{ $isAr ? 'المدة' : 'Duration' }}</th>
        <th></th>
    </tr></thead>
                                <tbody>
                                    @forelse($booking->sanadWorkflowStages as $stage)
                                        <tr>
                                            <td>{{ $stage->stage_name }}</td>
                                            <td>{{ optional($stage->employee)->display_name ?: '-' }}</td>
                                            <td>{{ Str::headline($stage->assignment_mode) }}</td>
                                            <td><span class="badge badge-light">{{ Str::headline($stage->status) }}</span></td>
                                            <td>{{ $stage->estimated_duration_minutes ?: '-' }}</td>
                                            <td>
                                                @if($stage->status !== 'completed')
                                                    <form method="POST" action="{{ route('provider.order.workflow.complete', [$booking->id, $stage->id]) }}">
                                                        @csrf
                                                        <button class="btn btn-sm btn-success">{{ $isAr ? 'إكمال' : 'Complete' }}</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">{{ $isAr ? 'لم يتم إسناد مسار عمل لهذا الطلب.' : 'No workflow assigned to this order.' }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('provider.order.update-status') }}" class="mb-4">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <input type="hidden" name="action" value="request_admin_review">
                    <div class="input-group">
                        <input name="reason" class="form-control" placeholder="{{ $isAr ? 'سبب طلب مراجعة إدارة كويك' : 'Reason for Quick admin review' }}" required>
                        <div class="input-group-append">
                            <button class="btn btn-warning">{{ $isAr ? 'طلب مراجعة الإدارة' : 'Request Admin Review' }}</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-xl-3 col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'الموظفون المقترحون' : 'Recommended Employees' }}</h5></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($recommendations as $employee)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $employee->display_name }}</strong>
                                        <span>{{ $employee->recommendation_score }}</span>
                                    </div>
                                    <small class="text-muted">{{ $isAr ? 'نشط' : 'Active' }} {{ $employee->active_orders_count }} | SLA {{ $employee->sanad_sla_compliance_rate ?: 0 }}%</small>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">{{ $isAr ? 'لا يوجد موظفون متاحون.' : 'No employees available.' }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'طلبات المستندات' : 'Document Requests' }}</h5></div>
                    <div class="card-body">
                        @forelse($booking->sanadDocumentRequests as $documentRequest)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $documentRequest->document_name }}</strong>
                                <div><span class="badge badge-light">{{ Str::headline($documentRequest->status) }}</span></div>
                                <small>{{ $documentRequest->reason }}</small>
                            </div>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لا توجد طلبات مستندات.' : 'No document requests.' }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">{{ $isAr ? 'المستندات المرفوعة' : 'Uploaded Documents' }}</h5></div>
                    <div class="card-body">
                        @forelse($booking->sanadDocuments as $document)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $document->document_type }}</strong>
                                <div><span class="badge badge-light">{{ Str::headline($document->verification_status ?: 'pending') }}</span></div>
                                @if($document->getFirstMediaUrl('document'))
                                    <a href="{{ $document->getFirstMediaUrl('document') }}" target="_blank">{{ $isAr ? 'معاينة / تحميل' : 'Preview / Download' }}</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">{{ $isAr ? 'لا توجد مستندات مرفوعة.' : 'No uploaded documents.' }}</p>
                        @endforelse
                        <form method="POST" action="{{ route('sanad.requests.documents.store', $booking->id) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="provider_id" value="{{ auth()->id() }}">
                            <input type="hidden" name="source" value="partner">
                            <input type="hidden" name="visible_to[]" value="admin">
                            <input type="hidden" name="visible_to[]" value="provider">
                            <input type="hidden" name="visible_to[]" value="employee">
                            @if($serviceDocumentOptions->isNotEmpty())
                                <select class="form-control mb-2 document-type-select" required>
                                    <option value="">{{ $isAr ? 'اختر نوع المستند' : 'Select document type' }}</option>
                                    @foreach($serviceDocumentOptions as $documentOption)
                                        <option value="{{ $documentOption['name'] }}" data-document-key="{{ $documentOption['key'] }}">{{ $documentOption['name'] }}</option>
                                    @endforeach
                                    <option value="__custom__" data-document-key="">{{ $isAr ? 'مستند مخصص' : 'Custom document' }}</option>
                                </select>
                                <input type="hidden" name="document_type" class="document-type-input">
                                <input type="hidden" name="document_key" class="document-key-input">
                                <input name="custom_document_type" class="form-control mb-2 custom-document-type-input d-none" placeholder="{{ $isAr ? 'نوع مستند مخصص' : 'Custom document type' }}">
                            @else
                                <input name="document_type" class="form-control mb-2" placeholder="{{ $isAr ? 'نوع مستند مخصص' : 'Custom document type' }}" required>
                                <small class="d-block text-muted mb-2">{{ $isAr ? 'لا توجد متطلبات مستندات معدّة للخدمة؛ سيُحفظ الملف كمستند داعم مخصص.' : 'No service document requirements are configured, so this upload will be stored as a custom supporting document.' }}</small>
                            @endif
                            <input type="file" name="document" class="form-control mb-2" required>
                            <button class="btn btn-primary btn-sm">{{ $isAr ? 'رفع المستند' : 'Upload Document' }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <a class="request-widget-toggle" href="{{ route('sanad.chat.workspace', ['booking_id' => $booking->id]) }}">
        <i class="fas fa-comments"></i> {{ $isAr ? 'فتح المحادثة' : 'Go to Chat' }}
    </a>

@section('bottom_script')
<style>
.provider-order-detail .gap-2 { gap: .5rem; }
.provider-order-detail .gap-3 { gap: 1rem; }
.provider-order-detail { max-width: 1440px; margin-inline: auto; }
.partner-detail-toolbar { display:flex; align-items:center; justify-content:space-between; gap:16px; margin: 4px 0 18px; }
.partner-detail-breadcrumb { display:flex; align-items:center; gap:9px; color:#8290a6; font-size:13px; }
.partner-detail-breadcrumb a { color:#66758c; font-weight:700; }
.partner-detail-breadcrumb strong, .partner-order-reference { color:#1f6bff; font-weight:800; letter-spacing:.02em; }
.partner-order-summary { background:#fff; border:1px solid #dce5f1; border-radius:18px; padding:24px; margin-bottom:22px; box-shadow:0 10px 24px rgba(15,41,51,.04); }
.partner-order-summary__heading { display:flex; justify-content:space-between; gap:20px; padding-bottom:20px; border-bottom:1px solid #e5ebf3; }
.partner-order-summary h1 { margin:0; color:#0f1d33; font-size:24px; font-weight:800; }
.partner-order-summary p { margin:7px 0 0; color:#6b7b93; }
.partner-order-facts { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; padding-top:18px; }
.partner-order-facts span { display:block; color:#8997ab; font-size:12px; margin-bottom:5px; }
.partner-order-facts strong { display:block; color:#26364d; font-size:13px; overflow-wrap:anywhere; }
.provider-order-detail > .row { display:grid; grid-template-columns:minmax(0,2fr) minmax(300px,1fr); gap:22px; margin:0; align-items:start; }
.provider-order-detail > .row > [class*="col-"] { max-width:none; width:auto; padding:0; }
.provider-order-detail > .row > .col-xl-6 { grid-column:1; grid-row:1; }
.provider-order-detail > .row > .col-xl-3:last-child { grid-column:2; grid-row:1; }
.provider-order-detail > .row > .col-xl-3:first-child { grid-column:1 / -1; grid-row:2; display:grid; grid-template-columns:1fr 1fr; gap:22px; }
.provider-order-detail .card { border:1px solid #dce5f1; border-radius:16px; box-shadow:0 8px 20px rgba(15,41,51,.035); overflow:hidden; }
.provider-order-detail .card-header { background:#fff; border-color:#e5ebf3; padding:18px 20px; }
.provider-order-detail .card-body { padding:20px; }
.provider-order-detail .table thead th { background:#f4f7fb; color:#66758c; border:0; }
.note-timeline { border-left: 2px solid #eef1f5; padding-left: 14px; }
.note-entry { position: relative; padding-bottom: 16px; }
.note-entry:before { content: ""; position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #f45135; }
.request-widget-toggle { position: fixed; right: 24px; bottom: 24px; z-index: 1050; display:inline-flex; align-items:center; gap:8px; border: 0; border-radius: 24px; background: #1f6bff; color: #fff; padding: 12px 18px; box-shadow: 0 12px 30px rgba(31,107,255,.22); font-weight:800; text-decoration:none; }
.request-widget-toggle:hover { color:#fff; text-decoration:none; background:#0d57e8; }
html[dir="rtl"] .note-timeline { border-left:0; border-right:2px solid #eef1f5; padding-left:0; padding-right:14px; }
html[dir="rtl"] .note-entry:before { left:auto; right:-20px; }
.quick-theme-dark .partner-order-summary,
.quick-theme-dark .provider-order-detail .card,
.quick-theme-dark .provider-order-detail .card-header { background:#102536; border-color:#294154; color:#e8f0f8; }
.quick-theme-dark .partner-order-summary h1,
.quick-theme-dark .partner-order-facts strong { color:#f5f8fc; }
.quick-theme-dark .partner-order-summary__heading { border-color:#294154; }
@media (max-width: 1199px) {
    .provider-order-detail > .row { grid-template-columns:1fr; }
    .provider-order-detail > .row > .col-xl-6,
    .provider-order-detail > .row > .col-xl-3:last-child,
    .provider-order-detail > .row > .col-xl-3:first-child { grid-column:1; grid-row:auto; }
}
@media (max-width: 767px) {
    .partner-detail-toolbar { align-items:flex-start; flex-direction:column; }
    .partner-detail-actions, .partner-detail-actions form { width:100%; }
    .partner-detail-actions .btn, .partner-detail-actions .form-control { width:100%; }
    .partner-order-summary { padding:18px; border-radius:15px; }
    .partner-order-summary h1 { font-size:20px; }
    .partner-order-facts { grid-template-columns:1fr 1fr; }
    .provider-order-detail > .row > .col-xl-3:first-child { display:block; }
}
@media (max-width: 480px) { .partner-order-facts { grid-template-columns:1fr; } }
</style>
<script>
$(document).on('change', '.document-type-select', function () {
    const selected = $(this).val();
    const isCustom = selected === '__custom__';
    $('.document-key-input').val(isCustom ? 'custom' : ($(this).find(':selected').data('document-key') || ''));
    $('.document-type-input').val(isCustom ? '' : selected);
    $('.custom-document-type-input').toggleClass('d-none', !isCustom).prop('required', isCustom);
});
$(document).on('input', '.custom-document-type-input', function () {
    $('.document-type-input').val($(this).val());
});
</script>
@endsection
</x-master-layout>
