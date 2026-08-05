<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="font-weight-bold mb-1">{{ $pageTitle }}</h4>
                                <span class="text-muted">{{ optional($bookingdata->service)->name ?: 'Sanad service request' }}</span>
                            </div>
                            <a href="{{ route('home') }}" class="btn-link btn-link-hover">Back to dashboard</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="sanad-monitoring-panel mb-3">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                <div>
                                    <h5 class="font-weight-bold mb-1">Operational Monitoring</h5>
                                    <span class="text-muted">Action signals for this request</span>
                                </div>
                                @if($monitoring['needs_action'])
                                    <span class="badge badge-warning">Needs action</span>
                                @else
                                    <span class="badge badge-success">Clear</span>
                                @endif
                            </div>
                            <div class="row">
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['is_unassigned'] ? 'is-warning' : '' }}">
                                        <span>Assignment</span>
                                        <strong>{{ $monitoring['is_unassigned'] ? 'Open' : 'Assigned' }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['is_overdue'] ? 'is-danger' : ($monitoring['is_due_soon'] ? 'is-warning' : '') }}">
                                        <span>SLA</span>
                                        <strong>
                                            @if($monitoring['is_overdue'])
                                                Overdue
                                            @elseif($monitoring['is_due_soon'])
                                                Due Soon
                                            @else
                                                Clear
                                            @endif
                                        </strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['pending_documents'] > 0 ? 'is-warning' : '' }}">
                                        <span>Pending Docs</span>
                                        <strong>{{ $monitoring['pending_documents'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item {{ $monitoring['unread_buzz'] > 0 ? 'is-warning' : '' }}">
                                        <span>Unread Buzz</span>
                                        <strong>{{ $monitoring['unread_buzz'] }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item">
                                        <span>Chat</span>
                                        <strong>{{ $monitoring['open_chat'] ? 'Open' : 'None' }}</strong>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6 mb-3">
                                    <div class="sanad-monitor-item">
                                        <span>Priority</span>
                                        <strong>{{ Str::headline($bookingdata->sanad_priority ?: 'normal') }}</strong>
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
                                    <span>Booking Status</span>
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

                        <div class="sanad-assignment-panel">
                            <h5 class="font-weight-bold mb-3">Employee Assignment</h5>
                            <form method="POST" action="{{ route('sanad.requests.employees.assign', $bookingdata->id) }}">
                                @csrf
                                <div class="row align-items-end">
                                    <div class="col-md-9 mb-3">
                                        <label class="form-control-label">Assign Employees</label>
                                        <select name="handyman_id[]" class="form-control" multiple>
                                            @foreach($assignableEmployees as $employee)
                                                <option value="{{ $employee->id }}" {{ $bookingdata->handymanAdded->pluck('handyman_id')->contains($employee->id) ? 'selected' : '' }}>
                                                    {{ $employee->display_name ?: $employee->email }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($assignableEmployees->isEmpty())
                                            <small class="text-muted">No active employees are available for this request partner.</small>
                                        @endif
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <button type="submit" class="btn btn-primary w-100">Save Assignment</button>
                                    </div>
                                </div>
                            </form>
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
                                            <button type="submit" class="btn btn-primary">Save Payment</button>
                                        </div>
                                    </form>
                                @else
                                    <span class="text-muted">No payment record is linked to this request yet.</span>
                                @endif

                                @if($bookingdata->payment_id !== null || $bookingdata->payment)
                                    <a href="{{ route('invoice_pdf', $bookingdata->id) }}" class="btn btn-light" target="_blank">Open Invoice</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card sanad-ops-section">
                    <div class="card-header">
                        <h5 class="font-weight-bold mb-0">Document Vault</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sanad.requests.documents.store', $bookingdata->id) }}" class="mb-4">
                            @csrf
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-control-label">Document Type</label>
                                    <input type="text" name="document_type" class="form-control" placeholder="ID, contract, evidence" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-control-label">File Name</label>
                                    <input type="text" name="file_name" class="form-control" placeholder="document.pdf">
                                </div>
                                <div class="col-md-8 mb-3">
                                    <label class="form-control-label">File Path / URL</label>
                                    <input type="text" name="file_path" class="form-control" placeholder="/storage/documents/document.pdf">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-control-label">Retention Until</label>
                                    <input type="date" name="retention_until" class="form-control">
                                </div>
                            </div>
                            <div class="sanad-checkbox-row mb-3">
                                @foreach(config('sanad.document_visibility', []) as $role)
                                    <label><input type="checkbox" name="visible_to[]" value="{{ $role }}" {{ $role === 'admin' ? 'checked' : '' }}> {{ Str::headline($role) }}</label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary">Add Document</button>
                        </form>

                        <div class="sanad-list">
                            @forelse($documents as $document)
                                <div class="sanad-list-item">
                                    <div>
                                        <strong>{{ Str::headline($document->document_type) }}</strong>
                                        <span>{{ $document->file_name ?: $document->file_path ?: 'No file reference' }}</span>
                                        <small>Visible to: {{ implode(', ', $document->visible_to ?: []) ?: '-' }}</small>
                                    </div>
                                    <div class="sanad-list-actions">
                                        <span class="badge badge-light">{{ Str::headline($document->verification_status) }}</span>
                                        @if($document->verification_status !== 'approved')
                                            <form method="POST" action="{{ route('sanad.requests.documents.approve', [$bookingdata->id, $document->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Approve</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="sanad-empty-state">No documents yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card sanad-ops-section">
                    <div class="card-header">
                        <h5 class="font-weight-bold mb-0">Buzz Alerts</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('sanad.requests.buzz.store', $bookingdata->id) }}" class="mb-4">
                            @csrf
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-control-label">Recipient Role</label>
                                    <select name="recipient_role" class="form-control">
                                        @foreach(config('sanad.document_visibility', []) as $role)
                                            <option value="{{ $role }}">{{ Str::headline($role) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-control-label">Priority</label>
                                    <select name="priority" class="form-control">
                                        <option value="urgent">Urgent</option>
                                        <option value="high">High</option>
                                        <option value="normal">Normal</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">Send Buzz</button>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-control-label">Message</label>
                                    <textarea name="message" class="form-control" rows="3" required></textarea>
                                </div>
                            </div>
                        </form>

                        <div class="sanad-list">
                            @forelse($buzzAlerts as $alert)
                                <div class="sanad-list-item">
                                    <div>
                                        <strong>{{ Str::headline($alert->priority) }} buzz</strong>
                                        <span>{{ $alert->message ?: '-' }}</span>
                                        <small>To: {{ Str::headline($alert->recipient_role ?: 'role') }}</small>
                                    </div>
                                    <div class="sanad-list-actions">
                                        <span class="badge badge-light">{{ Str::headline($alert->status) }}</span>
                                        @if($alert->status !== 'acknowledged')
                                            <form method="POST" action="{{ route('sanad.requests.buzz.acknowledge', [$bookingdata->id, $alert->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Acknowledge</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="sanad-empty-state">No Buzz alerts yet</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card sanad-ops-section">
                    <div class="card-header">
                        <h5 class="font-weight-bold mb-0">Secure Chat</h5>
                    </div>
                    <div class="card-body">
                        <div class="sanad-chat-feed mb-4">
                            @forelse($chatMessages as $message)
                                <div class="sanad-chat-message">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap">
                                        <strong>{{ Str::headline($message->sender_role ?: 'system') }}</strong>
                                        <small>{{ optional($message->created_at)->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1">{{ $message->message }}</p>
                                    <small>Visible to: {{ implode(', ', $message->visible_to ?: []) ?: '-' }}</small>
                                </div>
                            @empty
                                <div class="sanad-empty-state">No messages yet</div>
                            @endforelse
                        </div>

                        <form method="POST" action="{{ route('sanad.requests.chat.store', $bookingdata->id) }}">
                            @csrf
                            <label class="form-control-label">Message</label>
                            <textarea name="message" class="form-control mb-3" rows="3" required></textarea>
                            <div class="sanad-checkbox-row mb-3">
                                @foreach(config('sanad.document_visibility', []) as $role)
                                    <label><input type="checkbox" name="visible_to[]" value="{{ $role }}" checked> {{ Str::headline($role) }}</label>
                                @endforeach
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .sanad-detail-box {
                min-height: 82px;
                padding: 16px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-detail-box span {
                display: block;
                color: #6c757d;
                font-size: 13px;
                margin-bottom: 6px;
            }

            .sanad-detail-box strong {
                display: block;
                overflow-wrap: anywhere;
            }

            .sanad-assignment-panel {
                padding: 16px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-billing-panel {
                padding: 16px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-billing-item {
                min-height: 78px;
                padding: 14px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #f8f9fa;
            }

            .sanad-billing-item span {
                display: block;
                color: #6c757d;
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
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-monitor-item {
                min-height: 78px;
                padding: 14px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #f8f9fa;
            }

            .sanad-monitor-item span {
                display: block;
                color: #6c757d;
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
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
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
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
            }

            .sanad-list-item span,
            .sanad-list-item small,
            .sanad-chat-message small,
            .sanad-empty-state {
                display: block;
                color: #6c757d;
            }

            .sanad-list-actions {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: 8px;
                flex-shrink: 0;
            }

            .sanad-chat-feed {
                max-height: 360px;
                overflow: auto;
                display: grid;
                gap: 10px;
            }

            .sanad-chat-message {
                padding: 14px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-empty-state {
                padding: 16px;
                border: 1px dashed rgba(0, 0, 0, 0.16);
                border-radius: 8px;
            }
        </style>
    @endonce
</x-master-layout>
