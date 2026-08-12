<x-master-layout>
    @php
        $noteActions = $booking->sanadRequestActions->filter(fn($action) => !empty($action->internal_note));
        $canRespondToOrder = in_array($booking->sanad_stage ?: $booking->status, ['submitted', 'pending', 'pending_review', 'assigned_to_partner'], true);
    @endphp
    <div class="container-fluid provider-order-detail">
        <div class="card card-block card-stretch">
            <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h5 class="font-weight-bold mb-2">{{ $pageTitle }}</h5>
                    <span class="badge badge-primary">{{ Str::headline($booking->sanad_stage ?: 'submitted') }}</span>
                    <span class="badge badge-light">{{ ucfirst($booking->sanad_priority ?: 'normal') }}</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($canRespondToOrder)
                        <form method="POST" action="{{ route('provider.order.update-status') }}">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="accept_order">
                            <button class="btn btn-sm btn-success">Accept Order</button>
                        </form>
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="reject_order">
                            <input name="reason" class="form-control form-control-sm" placeholder="Reject reason" required>
                            <button class="btn btn-sm btn-outline-danger">Reject Order</button>
                        </form>
                    @endif
                    <a href="{{ route('provider.order.index') }}" class="btn btn-sm btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-3 col-lg-4">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Internal Notes</h5></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="mb-3">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <input type="hidden" name="action" value="add_internal_note">
                            <textarea name="internal_note" class="form-control" rows="3" placeholder="Add internal note" required></textarea>
                            <button class="btn btn-primary btn-sm mt-2">Add Note</button>
                        </form>
                        <div class="note-timeline">
                            @forelse($noteActions as $action)
                                <div class="note-entry">
                                    <strong>{{ optional($action->actor)->display_name ?: 'Sanad Team' }}</strong>
                                    <span class="text-muted small d-block">{{ $action->created_at->format('Y-m-d H:i') }}</span>
                                    <p class="mb-0">{{ $action->internal_note }}</p>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No notes yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Execution History</h5></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($booking->sanadRequestActions as $action)
                                <li class="list-group-item">
                                    <strong>{{ Str::headline($action->action) }}</strong>
                                    <div class="text-muted small">{{ optional($action->actor)->display_name }} | {{ $action->created_at->format('Y-m-d H:i') }}</div>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No actions recorded.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-xl-6 col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order Workspace</h5>
                        <form method="POST" action="{{ route('provider.order.update-status') }}" class="d-flex gap-2">
                            @csrf
                            <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                            <select name="action" class="form-control form-control-sm">
                                <option value="complete_current_stage">Complete Current Stage</option>
                                <option value="mark_completed">Mark Order Completed</option>
                            </select>
                            <button class="btn btn-sm btn-primary">Update Status</button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Customer:</strong> {{ optional($booking->customer)->display_name ?: '-' }}</p>
                                <p><strong>Service:</strong> {{ optional($booking->service)->name_en ?: optional($booking->service)->name ?: '-' }}</p>
                                <p><strong>Category:</strong> {{ optional(optional($booking->service)->category)->name ?: '-' }}</p>
                                <p><strong>Priority:</strong> {{ ucfirst($booking->sanad_priority ?: 'normal') }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>SLA Timer:</strong> {{ optional($booking->sla_due_at)->format('Y-m-d H:i') ?: '-' }}</p>
                                <p><strong>Expected Completion:</strong> {{ optional($booking->expected_completion_at)->format('Y-m-d H:i') ?: '-' }}</p>
                                <p><strong>Current Stage:</strong> {{ Str::headline($booking->sanad_stage ?: 'submitted') }}</p>
                                <p><strong>Assigned Employees:</strong> {{ $booking->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Employee Assignment</h5>
                        <a href="{{ route('provider.workflows.create') }}" class="btn btn-sm btn-outline-primary">Create Workflow</a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('provider.order.employees.assign', $booking->id) }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label>Assignment Mode</label>
                                    <select name="assignment_mode" class="form-control">
                                        <option value="manual">Manual</option>
                                        <option value="sequential">Sequential</option>
                                        <option value="parallel">Parallel</option>
                                        <option value="automatic_next_stage">Automatic Next Stage</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Workflow</label>
                                    <select name="workflow_template_id" class="form-control">
                                        <option value="">No template</option>
                                        @foreach($workflowTemplates as $workflow)
                                            <option value="{{ $workflow->id }}">{{ $workflow->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 form-group">
                                    <label>Employees</label>
                                    <select name="handyman_id[]" class="form-control select2" multiple>
                                        @foreach($employees as $employee)
                                            <option value="{{ $employee->id }}" {{ $booking->handymanAdded->pluck('handyman_id')->contains($employee->id) ? 'selected' : '' }}>{{ $employee->display_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <button class="btn btn-primary btn-sm">Assign Job</button>
                        </form>

                        <div class="table-responsive mt-4">
                            <table class="table table-sm table-bordered mb-0">
                                <thead><tr><th>Stage</th><th>Employee</th><th>Mode</th><th>Status</th><th>Duration</th><th></th></tr></thead>
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
                                                        <button class="btn btn-sm btn-success">Complete</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">No workflow assigned to this order.</td></tr>
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
                        <input name="reason" class="form-control" placeholder="Reason for Sanad admin review" required>
                        <div class="input-group-append">
                            <button class="btn btn-warning">Request Admin Review</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-xl-3 col-lg-12">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recommended Employees</h5></div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            @forelse($recommendations as $employee)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <strong>{{ $employee->display_name }}</strong>
                                        <span>{{ $employee->recommendation_score }}</span>
                                    </div>
                                    <small class="text-muted">Active {{ $employee->active_orders_count }} | SLA {{ $employee->sanad_sla_compliance_rate ?: 0 }}%</small>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No employees available.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Document Requests</h5></div>
                    <div class="card-body">
                        @forelse($booking->sanadDocumentRequests as $documentRequest)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $documentRequest->document_name }}</strong>
                                <div><span class="badge badge-light">{{ Str::headline($documentRequest->status) }}</span></div>
                                <small>{{ $documentRequest->reason }}</small>
                            </div>
                        @empty
                            <p class="text-muted">No document requests.</p>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Uploaded Documents</h5></div>
                    <div class="card-body">
                        @forelse($booking->sanadDocuments as $document)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ $document->document_type }}</strong>
                                <div><span class="badge badge-light">{{ Str::headline($document->verification_status ?: 'pending') }}</span></div>
                                @if($document->getFirstMediaUrl('document'))
                                    <a href="{{ $document->getFirstMediaUrl('document') }}" target="_blank">Preview / Download</a>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">No uploaded documents.</p>
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
                                    <option value="">Select document type</option>
                                    @foreach($serviceDocumentOptions as $documentOption)
                                        <option value="{{ $documentOption['name'] }}" data-document-key="{{ $documentOption['key'] }}">{{ $documentOption['name'] }}</option>
                                    @endforeach
                                    <option value="__custom__" data-document-key="">Custom document</option>
                                </select>
                                <input type="hidden" name="document_type" class="document-type-input">
                                <input type="hidden" name="document_key" class="document-key-input">
                                <input name="custom_document_type" class="form-control mb-2 custom-document-type-input d-none" placeholder="Custom document type">
                            @else
                                <input name="document_type" class="form-control mb-2" placeholder="Custom document type" required>
                                <small class="d-block text-muted mb-2">No service document requirements are configured, so this upload will be stored as a custom supporting document.</small>
                            @endif
                            <input type="file" name="document" class="form-control mb-2" required>
                            <button class="btn btn-primary btn-sm">Upload Document</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="request-widget-toggle" type="button" id="request-widget-toggle">Chat</button>
    <div class="request-widget" id="request-widget">
        <div class="request-widget-header">
            <strong>Request Communication</strong>
            <button type="button" id="request-widget-close">x</button>
        </div>
        <div class="request-widget-body">
            <div class="widget-tabs">
                <button type="button" class="active" data-widget-tab="sanad-chat">Private Sanad Chat</button>
                <button type="button" data-widget-tab="customer-action">Request Customer Action</button>
            </div>
            <div class="widget-panel active" id="sanad-chat">
                <div class="chat-log">
                    @forelse($chatMessages as $message)
                        <div class="chat-message">
                            <strong>{{ optional($message->sender)->display_name ?: 'System' }}</strong>
                            <span>{{ $message->created_at->format('Y-m-d H:i') }}</span>
                            <p>{{ $message->message }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No private Sanad messages yet.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('sanad.requests.chat.store', $booking->id) }}" enctype="multipart/form-data" class="chat-composer">
                    @csrf
                    <input type="hidden" name="thread_type" value="partner_internal">
                    <label class="chat-attach" title="Attach file">
                        <input type="file" name="attachment">
                        <i class="fas fa-plus"></i>
                    </label>
                    <textarea name="message" class="chat-message-input" rows="1" placeholder="Message Sanad team" required></textarea>
                    <button class="chat-send" title="Send to Sanad"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
            <div class="widget-panel" id="customer-action">
                <form method="POST" action="{{ route('sanad.requests.document-requests.store', $booking->id) }}">
                    @csrf
                    <input type="hidden" name="requested_from" value="customer">
                    <input name="document_name" class="form-control mb-2" placeholder="Document or action needed" required>
                    <input name="reason" class="form-control mb-2" placeholder="Why this is needed" required>
                    <textarea name="instructions" class="form-control mb-2" rows="3" placeholder="Instructions for Sanad/customer"></textarea>
                    <button class="btn btn-primary btn-sm">Request via Sanad</button>
                </form>
            </div>
        </div>
    </div>

@section('bottom_script')
<style>
.provider-order-detail .gap-2 { gap: .5rem; }
.provider-order-detail .gap-3 { gap: 1rem; }
.note-timeline { border-left: 2px solid #eef1f5; padding-left: 14px; }
.note-entry { position: relative; padding-bottom: 16px; }
.note-entry:before { content: ""; position: absolute; left: -20px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: #f45135; }
.request-widget-toggle { position: fixed; right: 24px; bottom: 24px; z-index: 1050; border: 0; border-radius: 24px; background: #f45135; color: #fff; padding: 12px 18px; box-shadow: 0 12px 30px rgba(0,0,0,.18); }
.request-widget { display: none; position: fixed; right: 24px; bottom: 84px; width: min(420px, calc(100vw - 32px)); max-height: calc(100vh - 130px); z-index: 1050; background: #fff; border: 1px solid #e8edf3; border-radius: 8px; box-shadow: 0 20px 50px rgba(15,23,42,.2); overflow: hidden; }
.request-widget.open { display: block; }
.request-widget-header { display: flex; justify-content: space-between; align-items: center; padding: 14px 16px; border-bottom: 1px solid #eef1f5; }
.request-widget-header button { border: 0; background: transparent; font-size: 18px; }
.request-widget-body { padding: 16px; }
.widget-tabs { display: flex; gap: 8px; margin-bottom: 12px; }
.widget-tabs button { flex: 1; border: 1px solid #d9e1ec; background: #fff; padding: 8px; border-radius: 6px; }
.widget-tabs button.active { background: #f45135; border-color: #f45135; color: #fff; }
.widget-panel { display: none; }
.widget-panel.active { display: block; }
.chat-log { max-height: 220px; overflow: auto; border: 1px solid #eef1f5; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
.chat-message { border-bottom: 1px solid #f1f3f5; padding-bottom: 8px; margin-bottom: 8px; }
.chat-message span { color: #7b8794; font-size: 12px; margin-left: 6px; }
.chat-message p { margin: 4px 0 0; }
.chat-composer { display: flex; align-items: flex-end; gap: 8px; border: 1px solid #d9e1ec; border-radius: 22px; padding: 6px; background: #fff; }
.chat-attach, .chat-send { width: 34px; height: 34px; min-width: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0; cursor: pointer; }
.chat-attach { border: 1px solid #d9e1ec; color: #4a5568; background: #f8fafc; }
.chat-attach input { display: none; }
.chat-message-input { flex: 1; min-height: 34px; max-height: 120px; resize: none; border: 0; outline: 0; padding: 7px 4px; line-height: 20px; overflow-y: auto; }
.chat-message-input:focus { outline: 0; box-shadow: none; }
.chat-send { border: 0; background: #f45135; color: #fff; }
</style>
<script>
$(document).on('click', '#request-widget-toggle', function () {
    $('#request-widget').toggleClass('open');
});
$(document).on('click', '#request-widget-close', function () {
    $('#request-widget').removeClass('open');
});
$(document).on('click', '[data-widget-tab]', function () {
    $('[data-widget-tab]').removeClass('active');
    $('.widget-panel').removeClass('active');
    $(this).addClass('active');
    $('#' + $(this).data('widget-tab')).addClass('active');
});
$(document).on('input', '.chat-message-input', function () {
    this.style.height = '34px';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
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
