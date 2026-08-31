@php
    $sanadRoleLabels = [
        'admin' => 'Admin',
        'provider' => 'Partner',
        'handyman' => 'Employee',
        'user' => 'Customer',
    ];
    $sanadRoleLabel = fn ($role) => $sanadRoleLabels[$role] ?? Str::headline($role ?: 'role');
    $sanadRoleList = fn ($roles) => collect($roles ?: [])->map(fn ($role) => $sanadRoleLabel($role))->implode(', ');
@endphp

<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $requestReference = $bookingdata->quick_reference ?: ('QK-' . str_pad((string) $bookingdata->id, 4, '0', STR_PAD_LEFT));
    $assignedNames = $bookingdata->handymanAdded
        ->map(fn ($mapping) => optional($mapping->handyman)->display_name)
        ->filter()
        ->implode(', ');
    $selectedEmployeeIds = $bookingdata->handymanAdded->pluck('handyman_id')->map(fn ($id) => (int) $id);
    $isAdminAssignmentUser = auth()->user()->hasAnyRole(['admin', 'demo_admin']);
@endphp
<div class="quick-request-detail" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="row">
            <div class="col-lg-12">
                <div class="quick-admin-hero quick-request-hero">
                        <div class="quick-request-hero-top">
                            <div class="quick-request-heading">
                                <div class="quick-request-kicker">
                                    <span>{{ $requestReference }}</span>
                                    <span class="quick-stage-chip">{{ Str::headline($bookingdata->sanad_stage ?: 'submitted') }}</span>
                                </div>
                                <h4 class="font-weight-bold mb-1">{{ optional($bookingdata->service)->name ?: ($isAr ? 'طلب خدمة كويك' : 'Quick service request') }}</h4>
                                <span class="text-muted">{{ $isAr ? 'مساحة دورة حياة الطلب ومراقبة الجودة' : 'Request lifecycle and quality-control workspace' }}</span>
                            </div>
                            <div class="quick-request-hero-actions">
                                @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                                    <a href="#quick-request-actions" class="quick-table-btn">
                                        <x-quick-icon name="refresh" /> {{ $isAr ? 'طلب تصحيح أو إعادة عمل' : 'Request correction / rework' }}
                                    </a>
                                    <a href="#quick-quality-control" class="quick-primary-link">
                                        <x-quick-icon name="check" /> {{ $isAr ? 'مراجعة واعتماد الطلب' : 'Review and approve' }}
                                    </a>
                                @endif
                                <a href="{{ route('sanad.chat.workspace', ['booking_id' => $bookingdata->id]) }}" class="quick-primary-link">
                                    <x-quick-icon name="message" /> {{ $isAr ? 'فتح المحادثة' : 'Open Chat' }}
                                </a>
                            </div>
                        </div>
                        <a href="{{ route('sanad.requests.index') }}" class="quick-back-link">
                            <x-quick-icon name="arrow" /> {{ $isAr ? 'العودة إلى قائمة الطلبات' : 'Back to request queue' }}
                        </a>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="quick-card quick-request-workspace">
                        <div class="sanad-monitoring-panel mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">{{ $isAr ? 'المراقبة التشغيلية وملخص الطلب' : 'Operational Monitoring — Request Summary' }}@if($isAr)<span class="sr-only">Operational Monitoring</span>@endif</h5>
                                    <span class="text-muted">{{ $isAr ? 'الإسناد والمهلة والمستندات والدفع والتنبيهات' : 'Assignment, SLA, documents, payment and alerts' }}</span>
                                </div>
                                @if($monitoring['needs_action'])
                                    <span class="badge badge-warning">{{ $isAr ? 'يتطلب إجراء' : 'Needs action' }}</span>
                                @else
                                    <span class="badge badge-success">{{ $isAr ? 'مستقر' : 'Clear' }}</span>
                                @endif
                            </div>
                            <div class="row quick-request-metrics">
                                <div class="col-xl col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['is_unassigned'] ? 'is-warning' : '' }}">
                                        <span>{{ $isAr ? 'المسؤول' : 'Assignee' }}</span>
                                        <strong>{{ $monitoring['is_unassigned'] ? ($isAr ? 'غير مسند' : 'Unassigned') : ($assignedNames ?: ($isAr ? 'تم الإسناد' : 'Assigned')) }}</strong>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['is_overdue'] ? 'is-danger' : ($monitoring['is_due_soon'] ? 'is-warning' : '') }}">
                                        <span>{{ $isAr ? 'اتفاقية مستوى الخدمة' : 'SLA' }}</span>
                                        <strong>
                                            @if($monitoring['is_overdue'])
                                                {{ $isAr ? 'متأخر' : 'Overdue' }}
                                            @elseif($monitoring['is_due_soon'])
                                                {{ $isAr ? 'يستحق قريباً' : 'Due soon' }}
                                            @else
                                                {{ $isAr ? 'ضمن المهلة' : 'On track' }}
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['pending_documents'] > 0 ? 'is-warning' : '' }}">
                                        <span>{{ $isAr ? 'حالة المستندات' : 'Document status' }}</span>
                                        <strong>{{ $monitoring['pending_documents'] }} {{ $isAr ? 'قيد المراجعة' : 'pending' }}</strong>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $qualityControl['payment_status'] !== 'paid' ? 'is-warning' : '' }}">
                                        <span>{{ $isAr ? 'الدفع' : 'Payment' }}</span>
                                        <strong>{{ Str::headline($qualityControl['payment_status']) }}</strong>
                                    </div>
                                </div>
                                <div class="col-xl col-md-4 col-12 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['unread_buzz'] > 0 ? 'is-warning' : '' }}">
                                        <span>{{ $isAr ? 'تنبيهات Buzz' : 'Buzz alerts' }}</span>
                                        <strong>{{ $monitoring['unread_buzz'] }} {{ $isAr ? 'غير مقروء' : 'unread' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @include('booking.partials.sanad-lifecycle')

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="sanad-detail-box">
                                    <span>Customer</span>
                                    <strong>{{ optional($bookingdata->customer)->display_name ?: '-' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="sanad-detail-box">
                                    <span>Partner</span>
                                    <strong>{{ optional($bookingdata->provider)->display_name ?: '-' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="sanad-detail-box">
                                    <span>Amount</span>
                                    <strong>{{ $bookingdata->total_amount ? getPriceFormat($bookingdata->total_amount) : '-' }}</strong>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="sanad-detail-box">
                                    <span>Request Status</span>
                                    <strong>{{ Str::headline($bookingdata->status ?: 'pending') }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="sanad-detail-box mb-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                <div>
                                    <span>Assigned Employees</span>
                                    <strong>
                                        @forelse($bookingdata->handymanAdded as $mapping)
                                            {{ optional($mapping->handyman)->display_name ?: '-' }}{{ !$loop->last ? ', ' : '' }}
                                        @empty
                                            -
                                        @endforelse
                                    </strong>
                                </div>
                                <div>
                                    <span>Assigned At</span>
                                    <strong>{{ $bookingdata->assigned_at ? $bookingdata->assigned_at->format('Y-m-d H:i') : '-' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="sanad-assignment-panel" id="quick-request-actions">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">{{ $isAr ? 'إسناد الشريك' : 'Partner Assignment' }}</h5>
                                    <span class="text-muted">{{ $isAr ? 'اختر الشريك المسؤول عن تنفيذ هذا الطلب.' : 'Select the partner responsible for this request.' }}</span>
                                </div>
                                <span class="quick-assignment-status">{{ optional($bookingdata->provider)->display_name ?: ($isAr ? 'فريق كويك' : 'Quick internal') }}</span>
                            </div>
                            <form method="POST" action="{{ route('sanad.requests.employees.assign', $bookingdata->id) }}">
                                @csrf
                                @if($isAdminAssignmentUser)
                                    <input type="hidden" name="assignment_scope" value="partner">
                                    <div class="quick-assignment-row">
                                        <div class="quick-assignment-field">
                                            <label class="form-control-label">{{ $isAr ? 'الشريك' : 'Partner' }}</label>
                                            <select name="provider_id" class="form-control">
                                                <option value="">{{ $isAr ? 'اختر شريكاً' : 'Select a partner' }}</option>
                                                @foreach($assignablePartners as $partner)
                                                    <option value="{{ $partner->id }}" {{ (int) old('provider_id', $bookingdata->provider_id) === (int) $partner->id ? 'selected' : '' }}>
                                                        {{ $partner->display_name ?: $partner->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($assignablePartners->isEmpty())
                                                <small class="text-muted">{{ $isAr ? 'لا يوجد شركاء نشطون متاحون.' : 'No active partners are available.' }}</small>
                                            @else
                                                <small class="text-muted">{{ $isAr ? 'سيتم إسناد الطلب للشريك ومسح أي إسناد موظفين سابق.' : 'This assigns the request to the partner and clears any previous employee assignment.' }}</small>
                                            @endif
                                        </div>
                                        <div class="quick-assignment-action">
                                            <button type="submit" class="btn btn-primary quick-primary-btn">{{ $isAr ? 'حفظ الإسناد' : 'Save Assignment' }}</button>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="assignment_scope" value="employees_only">
                                    <div class="row align-items-end">
                                        <div class="col-md-9 mb-3">
                                            <label class="form-control-label">{{ $isAr ? 'موظفو الشريك' : 'Partner employees' }}</label>
                                            <select name="handyman_id[]" class="form-control" multiple>
                                                @foreach($assignableEmployees as $employee)
                                                    <option value="{{ $employee->id }}" {{ $selectedEmployeeIds->contains((int) $employee->id) ? 'selected' : '' }}>
                                                        {{ $employee->display_name ?: $employee->email }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if($assignableEmployees->isEmpty())
                                                <small class="text-muted">{{ $isAr ? 'لا يوجد موظفون نشطون متاحون لهذا الشريك.' : 'No active employees are available for your partner account.' }}</small>
                                            @else
                                                <small class="text-muted">{{ $isAr ? 'يمكن للشريك إسناد الطلب لموظفيه فقط.' : 'Partner users can assign only their own employees.' }}</small>
                                            @endif
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <button type="submit" class="btn btn-primary quick-primary-btn w-100">{{ $isAr ? 'حفظ الإسناد' : 'Save Assignment' }}</button>
                                        </div>
                                    </div>
                                @endif
                            </form>
                        </div>

                        <div class="sanad-action-panel mt-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Partner Order Actions</h5>
                                    <span class="text-muted">Accept, reject, request documents, complete stages, request admin review, or add internal notes</span>
                                </div>
                                <span class="badge badge-light">{{ Str::headline($bookingdata->sanad_stage ?: 'submitted') }}</span>
                            </div>
                            <form method="POST" action="{{ route('sanad.requests.actions.store', $bookingdata->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label class="form-control-label">Action</label>
                                        <select name="action" class="form-control" required>
                                            <option value="accept_order">Accept Order</option>
                                            <option value="reject_order">Reject With Reason</option>
                                            <option value="request_missing_documents">Request Missing Documents</option>
                                            <option value="reassign_employees">Reassign Employees</option>
                                            <option value="add_internal_note">Add Internal Note</option>
                                            <option value="complete_current_stage">Complete Current Stage</option>
                                            <option value="request_admin_review">Request Admin Review</option>
                                            <option value="mark_completed">Mark Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-control-label">Reason</label>
                                        <input type="text" name="reason" class="form-control" placeholder="Required for rejection, missing docs, reassignment, or review">
                                    </div>
                                    <div class="col-lg-5 mb-3">
                                        <label class="form-control-label">Internal Note</label>
                                        <input type="text" name="internal_note" class="form-control" placeholder="Private operational note">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary quick-primary-btn">Record Action</button>
                            </form>

                            <div class="sanad-action-timeline mt-4">
                                @forelse($requestActions as $action)
                                    <div class="sanad-action-item">
                                        <div>
                                            <strong>{{ Str::headline($action->action) }}</strong>
                                            <span>
                                                {{ Str::headline($action->previous_stage ?: 'none') }}
                                                <i class="fa fa-arrow-right mx-1"></i>
                                                {{ Str::headline($action->current_stage ?: 'none') }}
                                            </span>
                                            @if($action->reason)
                                                <small>Reason: {{ $action->reason }}</small>
                                            @endif
                                            @if($action->internal_note)
                                                <small>Note: {{ $action->internal_note }}</small>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <small>{{ optional($action->actor)->display_name ?: Str::headline($action->actor_role ?: 'system') }}</small>
                                            <small>{{ optional($action->created_at)->diffForHumans() }}</small>
                                        </div>
                                    </div>
                                @empty
                                    <div class="sanad-empty-state">No operational actions recorded yet</div>
                                @endforelse
                            </div>
                        </div>

                        <div class="sanad-quality-panel mt-3" id="quick-quality-control">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Admin Quality Control</h5>
                                    <span class="text-muted">Final checks before delivery approval, rework, or rejection</span>
                                </div>
                                @if($qualityControl['is_ready_for_approval'])
                                    <span class="badge badge-success">Ready for approval</span>
                                @else
                                    <span class="badge badge-warning">Needs review</span>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ $qualityControl['pending_documents'] > 0 ? 'is-warning' : '' }}">
                                        <span>Pending Docs</span>
                                        <strong>{{ $qualityControl['pending_documents'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ $qualityControl['rejected_documents'] > 0 ? 'is-danger' : '' }}">
                                        <span>Rejected Docs</span>
                                        <strong>{{ $qualityControl['rejected_documents'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ $qualityControl['payment_status'] !== 'paid' ? 'is-warning' : '' }}">
                                        <span>Payment</span>
                                        <strong>{{ Str::headline($qualityControl['payment_status']) }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ ! $qualityControl['has_assignment'] ? 'is-warning' : '' }}">
                                        <span>Assignment</span>
                                        <strong>{{ $qualityControl['has_assignment'] ? 'Ready' : 'Open' }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ $qualityControl['open_buzz'] > 0 ? 'is-warning' : '' }}">
                                        <span>Open Buzz</span>
                                        <strong>{{ $qualityControl['open_buzz'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-quality-item {{ $qualityControl['open_chat'] > 0 ? 'is-warning' : '' }}">
                                        <span>Open Chat</span>
                                        <strong>{{ $qualityControl['open_chat'] }}</strong>
                                    </div>
                                </div>
                            </div>

                            @if($qualityControl['latest_decision'])
                                <div class="sanad-quality-decision mb-3">
                                    <strong>Latest Decision: {{ Str::headline($qualityControl['latest_decision']->action) }}</strong>
                                    <span>{{ optional($qualityControl['latest_decision']->created_at)->diffForHumans() }} by {{ optional($qualityControl['latest_decision']->actor)->display_name ?: Str::headline($qualityControl['latest_decision']->actor_role ?: 'admin') }}</span>
                                    @if($qualityControl['latest_decision']->reason)
                                        <small>Reason: {{ $qualityControl['latest_decision']->reason }}</small>
                                    @endif
                                </div>
                            @endif

                            @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                                <form method="POST" action="{{ route('sanad.requests.actions.store', $bookingdata->id) }}">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label class="form-control-label">QC Decision</label>
                                            <select name="action" class="form-control" required>
                                                <option value="quality_approve">Approve Quality</option>
                                                <option value="quality_rework">Send For Rework</option>
                                                <option value="quality_reject">Reject Quality</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-4 col-md-6 mb-3">
                                            <label class="form-control-label">Reason</label>
                                            <input type="text" name="reason" class="form-control" placeholder="Required for rework or rejection">
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <label class="form-control-label">Internal Note</label>
                                            <input type="text" name="internal_note" class="form-control" placeholder="QC note">
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <button type="submit" class="btn btn-primary quick-primary-btn w-100">Save QC</button>
                                        </div>
                                    </div>
                                </form>
                            @endif
                        </div>

                        <div class="sanad-billing-panel mt-3">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Billing And Payment</h5>
                                    <span class="text-muted">Payment status and invoice visibility</span>
                                </div>
                                <span class="badge {{ $billing['is_paid'] ? 'badge-success' : 'badge-light' }}">{{ Str::headline($billing['payment_status']) }}</span>
                            </div>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="sanad-billing-item">
                                        <span>Amount</span>
                                        <strong>{{ $billing['amount'] ? getPriceFormat($billing['amount']) : '-' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="sanad-billing-item">
                                        <span>Payment Type</span>
                                        <strong>{{ $billing['payment_type'] ? Str::headline($billing['payment_type']) : '-' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="sanad-billing-item">
                                        <span>Transaction</span>
                                        <strong>{{ $billing['transaction_id'] ?: '-' }}</strong>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="sanad-billing-item">
                                        <span>History</span>
                                        <strong>{{ $billing['history_count'] }}</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-end flex-wrap gap-3">
                                @if($billing['can_update'])
                                    <form method="POST" action="{{ route('sanad.requests.payment.update', $bookingdata->id) }}" class="sanad-payment-form">
                                        @csrf
                                        <label class="form-control-label">Update Payment Status</label>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <select name="payment_status" class="form-control">
                                                @foreach(['pending', 'paid', 'failed', 'advanced_paid', 'pending_by_admin', 'refunded'] as $status)
                                                    <option value="{{ $status }}" {{ $billing['payment_status'] === $status ? 'selected' : '' }}>{{ Str::headline($status) }}</option>
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary quick-primary-btn">Save Payment</button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-muted">No payment record is linked to this request yet.</span>
                                @endif

                                @if($bookingdata->payment_id !== null || $bookingdata->payment)
                                    <a href="{{ route('invoice_pdf', $bookingdata->id) }}" class="quick-table-btn" target="_blank">Open Invoice</a>
                                @else
                                    <span class="text-muted">Invoice visibility starts after a payment record is linked.</span>
                                @endif
                            </div>
                        </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="quick-card sanad-ops-section" id="quick-request-documents">
                    <div class="card-header">
                        <h5 class="font-weight-bold mb-0">Document Vault</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-light border mb-4">
                            <strong>Quick document policy:</strong>
                            documents default to a 48-hour retention window when no date is selected. Customers must download required files before the retention date; Download before deletion guidance stays visible for every retained document.
                        </div>
                        <form method="POST" enctype="multipart/form-data" action="{{ route('sanad.requests.documents.store', $bookingdata->id) }}" class="quick-document-form mb-4">
                            @csrf
                            <div class="quick-document-grid">
                                <div>
                                    <label class="form-control-label">Document Type</label>
                                    <input type="text" name="document_type" class="form-control" placeholder="ID, contract, evidence" required>
                                </div>
                                <div>
                                    <label class="form-control-label">File Name</label>
                                    <input type="text" name="file_name" class="form-control" placeholder="document.pdf">
                                </div>
                                <div>
                                    <label class="form-control-label">Upload File</label>
                                    <label class="quick-file-picker">
                                        <input type="file" name="document" accept="image/*,.pdf,.doc,.docx">
                                        <span class="quick-file-icon"><i class="fas fa-paperclip"></i></span>
                                        <span class="quick-file-label">Attach document</span>
                                    </label>
                                </div>
                                <div>
                                    <label class="form-control-label">Retention Until</label>
                                    <input type="date" name="retention_until" class="form-control">
                                </div>
                            </div>
                            <label class="form-control-label">Visible to:</label>
                            <div class="sanad-checkbox-row mb-3">
                                @foreach(collect(config('sanad.document_visibility', []))->unique()->values() as $role)
                                    <label><input type="checkbox" name="visible_to[]" value="{{ $role }}" {{ $role === 'admin' ? 'checked' : '' }}> {{ $sanadRoleLabel($role) }}</label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary quick-primary-btn">Add Document</button>
                        </form>

                        <div class="sanad-list">
                            @forelse($documents as $document)
                                <div class="sanad-list-item">
                                    <div>
                                        <strong>{{ Str::headline($document->document_type) }}</strong>
                                        <span>{{ $document->file_name ?: $document->file_path ?: 'No file reference' }}</span>
                                        <small>Visible to: {{ $sanadRoleList($document->visible_to) ?: '-' }}</small>
                                        <small>Retention until: {{ optional($document->retention_until)->format('d M Y H:i') ?: '48 hours after upload' }}</small>
                                        @if($document->file_path)
                                            <a href="{{ $document->file_path }}" target="_blank" rel="noopener" class="small">Download before deletion</a>
                                        @else
                                            <small>Download before deletion once a file URL is available</small>
                                        @endif
                                    </div>
                                    <div class="sanad-list-actions">
                                        <span class="badge badge-light">{{ Str::headline($document->verification_status) }}</span>
                                        @if($document->verification_status !== 'approved')
                                            <form method="POST" action="{{ route('sanad.requests.documents.approve', [$bookingdata->id, $document->id]) }}">
                                                @csrf
                                                <button type="submit" class="quick-table-btn">Approve</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="sanad-empty-state">No documents yet</div>
                            @endforelse
                        </div>
                        <hr>
                        <div class="quick-section-toolbar">
                            <div>
                                <h6 class="mb-1">{{ $isAr ? 'طلبات المستندات المنظمة' : 'Structured Document Requests' }}</h6>
                                <small class="text-muted">{{ $isAr ? 'سجل الطلبات المرسلة من المحادثة.' : 'History of requests sent from chat.' }}</small>
                            </div>
                            <a href="{{ route('sanad.chat.workspace', ['booking_id' => $bookingdata->id]) }}" class="quick-table-btn">
                                <x-quick-icon name="message" /> {{ $isAr ? 'طلب عبر المحادثة' : 'Request in Chat' }}
                            </a>
                        </div>
                        @forelse($bookingdata->sanadDocumentRequests()->latest()->get() as $documentRequest)
                            <div class="border rounded p-2 mb-2"><strong>{{ $documentRequest->document_name }}</strong> <span class="badge badge-light">{{ Str::headline($documentRequest->status) }}</span><div class="small">Requested from {{ Str::headline($documentRequest->requested_from) }}: {{ $documentRequest->reason }}</div>@if($documentRequest->document)<a href="{{ $documentRequest->document->getFirstMediaUrl('document') }}" target="_blank">Open submission</a>@endif</div>
                        @empty <div class="text-muted small">No structured document requests.</div> @endforelse
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="quick-card sanad-ops-section">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="font-weight-bold mb-0">{{ $isAr ? 'تنبيهات وطلبات Buzz' : 'Buzz Notifications & Requests' }}</h5>
                            <small class="text-muted">{{ $isAr ? 'سجل التنبيهات والطلبات المرسلة لهذا الطلب.' : 'History of notifications and requests sent for this request.' }}</small>
                        </div>
                        <a href="{{ route('sanad.chat.workspace', ['booking_id' => $bookingdata->id]) }}" class="quick-table-btn">
                            <x-quick-icon name="message" /> {{ $isAr ? 'فتح المحادثة' : 'Open Chat' }}
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="sanad-list">
                            @forelse($buzzAlerts as $alert)
                                <div class="sanad-list-item">
                                    <div>
                                        <strong>{{ Str::headline($alert->priority) }} buzz</strong>
                                        <span>{{ $alert->message ?: '-' }}</span>
                                        <small>To: {{ $sanadRoleLabel($alert->recipient_role) }}</small>
                                    </div>
                                    <div class="sanad-list-actions">
                                        <span class="badge badge-light">{{ Str::headline($alert->status) }}</span>
                                    </div>
                                </div>
                            @empty
                                <div class="sanad-empty-state">No Buzz alerts awaiting acknowledgement yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    @once
        <style>
            .quick-request-detail {
                max-width: 1180px;
                margin: 0 auto;
            }

            .quick-request-detail .text-muted,
            .quick-request-detail small {
                color: var(--quick-shell-muted) !important;
            }

            .quick-request-workspace,
            .quick-request-detail .sanad-ops-section {
                margin-bottom: 20px;
                overflow: hidden;
            }

            .quick-request-hero-top {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 18px;
                flex-wrap: wrap;
            }

            .quick-request-kicker {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
                margin-bottom: 10px;
                color: var(--quick-blue);
                font-size: 12px;
                font-weight: 900;
            }

            .quick-stage-chip {
                display: inline-flex;
                align-items: center;
                border-radius: 999px;
                padding: 5px 10px;
                background: rgba(31,107,255,.1);
                color: var(--quick-blue);
                font-size: 11px;
                font-weight: 900;
            }

            .quick-request-heading h4 {
                margin: 0;
                color: var(--quick-shell-ink);
                font-size: clamp(22px, 2.5vw, 32px);
                font-weight: 900;
                line-height: 1.2;
            }

            .quick-request-hero-actions {
                display: flex;
                align-items: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .quick-back-link,
            .quick-primary-link {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 900;
                text-decoration: none;
            }

            .quick-primary-link {
                min-height: 38px;
                padding: 8px 14px;
                color: #fff;
                background: var(--quick-blue);
                box-shadow: 0 10px 22px rgba(31,107,255,.18);
            }

            .quick-back-link {
                width: fit-content;
                margin-top: 16px;
                color: var(--quick-blue);
            }

            .sanad-detail-box {
                min-height: 82px;
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-surface) 92%, var(--quick-shell-bg));
            }

            .sanad-detail-box span {
                display: block;
                color: var(--quick-shell-muted);
                font-size: 13px;
                margin-bottom: 6px;
            }

            .sanad-detail-box strong {
                display: block;
                overflow-wrap: anywhere;
            }

            .sanad-assignment-panel {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: var(--quick-shell-surface);
            }

            .quick-assignment-status {
                display: inline-flex;
                align-items: center;
                min-height: 32px;
                padding: 6px 12px;
                border-radius: 999px;
                background: rgba(31, 107, 255, .1);
                color: var(--quick-blue);
                font-size: 12px;
                font-weight: 900;
            }

            .quick-assignment-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: end;
                gap: 16px;
            }

            .quick-assignment-field {
                min-width: 0;
            }

            .quick-assignment-action .quick-primary-btn {
                min-width: 190px;
            }

            .sanad-action-panel {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: var(--quick-shell-surface);
            }

            .sanad-action-timeline {
                display: grid;
                gap: 10px;
            }

            .sanad-action-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding: 12px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
            }

            .sanad-action-item span,
            .sanad-action-item small {
                display: block;
                color: var(--quick-shell-muted);
            }

            .sanad-quality-panel {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: var(--quick-shell-surface);
            }

            .sanad-quality-item {
                min-height: 78px;
                padding: 14px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
            }

            .sanad-quality-item span,
            .sanad-quality-decision span,
            .sanad-quality-decision small {
                display: block;
                color: var(--quick-shell-muted);
            }

            .sanad-quality-item strong {
                display: block;
                overflow-wrap: anywhere;
            }

            .sanad-quality-item.is-warning {
                border-color: #f2c94c;
                background: #fff9e8;
            }

            .sanad-quality-item.is-danger {
                border-color: #eb5757;
                background: #fff1f1;
            }

            .sanad-quality-decision {
                padding: 12px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
            }

            .sanad-billing-panel {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: var(--quick-shell-surface);
            }

            .sanad-billing-item {
                min-height: 78px;
                padding: 14px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
            }

            .sanad-billing-item span {
                display: block;
                color: var(--quick-shell-muted);
                font-size: 13px;
                margin-bottom: 6px;
            }

            .sanad-billing-item strong {
                display: block;
                overflow-wrap: anywhere;
            }

            .sanad-payment-form {
                min-width: min(100%, 420px);
            }

            .sanad-payment-form select {
                width: auto;
                min-width: 190px;
            }

            .sanad-monitoring-panel {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: var(--quick-shell-surface);
            }

            .sanad-monitor-item {
                min-height: 78px;
                padding: 14px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface));
            }

            .sanad-monitor-item span {
                display: block;
                color: var(--quick-shell-muted);
                font-size: 13px;
                margin-bottom: 6px;
            }

            .sanad-monitor-item strong {
                display: block;
                overflow-wrap: anywhere;
            }

            .sanad-monitor-item.is-warning {
                border-color: #f2c94c;
                background: #fff9e8;
            }

            .sanad-monitor-item.is-danger {
                border-color: #eb5757;
                background: #fff1f1;
            }

            .sanad-ops-section .card-header {
                border: 0;
                border-bottom: 1px solid var(--quick-shell-line);
                background: transparent;
                padding: 0 0 14px;
                margin-bottom: 18px;
            }

            .sanad-ops-section .card-body {
                padding: 0;
            }

            .sanad-checkbox-row {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }

            .sanad-checkbox-row label {
                margin-bottom: 0;
                font-size: 13px;
            }

            .quick-document-form {
                padding: 16px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 16px;
                background: color-mix(in srgb, var(--quick-shell-surface) 94%, var(--quick-shell-bg));
            }

            .quick-document-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 16px 18px;
                margin-bottom: 16px;
            }

            .quick-file-picker {
                display: flex;
                align-items: center;
                gap: 10px;
                min-height: 42px;
                width: 100%;
                margin: 0;
                padding: 8px 12px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 12px;
                background: var(--quick-shell-surface);
                color: var(--quick-shell-muted);
                cursor: pointer;
            }

            .quick-file-picker input {
                position: absolute;
                inline-size: 1px;
                block-size: 1px;
                opacity: 0;
                pointer-events: none;
            }

            .quick-file-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 30px;
                height: 30px;
                border-radius: 10px;
                background: rgba(31, 107, 255, .1);
                color: var(--quick-blue);
                flex: 0 0 auto;
            }

            .quick-file-label {
                font-size: 13px;
                font-weight: 800;
                color: var(--quick-shell-ink);
            }

            .quick-section-toolbar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 14px;
                margin-bottom: 14px;
            }

            .sanad-list {
                display: grid;
                gap: 10px;
            }

            .sanad-list-item {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 16px;
                padding: 14px;
                border: 1px solid var(--quick-shell-line);
                border-radius: 14px;
                background: color-mix(in srgb, var(--quick-shell-surface) 92%, var(--quick-shell-bg));
            }

            .sanad-list-item span,
            .sanad-list-item small,
            .sanad-empty-state {
                display: block;
                color: var(--quick-shell-muted);
            }

            .sanad-list-actions {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
                flex-shrink: 0;
            }

            .sanad-empty-state {
                padding: 16px;
                border: 1px dashed var(--quick-shell-line);
                border-radius: 14px;
            }

            .quick-request-detail .form-control {
                min-height: 42px;
                border-color: var(--quick-shell-line);
                border-radius: 12px;
                background: var(--quick-shell-surface);
                color: var(--quick-shell-ink);
                box-shadow: none;
            }

            .quick-request-detail textarea.form-control {
                min-height: 96px;
            }

            .quick-primary-btn {
                border-color: var(--quick-blue);
                border-radius: 12px;
                background: var(--quick-blue);
                color: #fff;
                font-size: 12px;
                font-weight: 900;
                min-height: 42px;
                padding: 9px 16px;
            }

            @media (max-width: 899px) {
                .quick-request-detail {
                    max-width: none;
                }

                .quick-request-workspace,
                .quick-request-detail .sanad-ops-section,
                .quick-request-detail .quick-admin-hero {
                    padding: 16px;
                    border-radius: 18px;
                }

                .quick-request-hero-actions,
                .quick-request-hero-actions a,
                .quick-primary-link,
                .quick-request-detail .quick-table-btn,
                .quick-primary-btn {
                    width: 100%;
                }

                .sanad-action-item,
                .sanad-list-item,
                .quick-assignment-row,
                .quick-document-grid,
                .quick-section-toolbar {
                    display: grid;
                    gap: 12px;
                    grid-template-columns: 1fr;
                }

                .sanad-list-actions,
                .quick-assignment-action .quick-primary-btn {
                    width: 100%;
                }

                .sanad-list-actions {
                    align-items: stretch;
                }

                .sanad-payment-form,
                .sanad-payment-form select {
                    width: 100%;
                    min-width: 0;
                }
            }
        </style>
    @endonce
</x-master-layout>
