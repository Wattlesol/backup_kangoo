@php
    $isCustomer = !empty($isCustomerPortal) || in_array(optional(auth()->user())->user_type, ['user', 'customer'], true);
    $messagesRoute = $isCustomer ? 'customer-portal.messages' : 'sanad.chat.workspace';
    $storeChatRoute = $isCustomer ? 'customer-portal.requests.messages.store' : 'sanad.requests.chat.store';
    $requestShowRoute = $isCustomer ? 'customer-portal.requests.show' : 'sanad.requests.show';
    $roleLabel = fn ($role) => Str::headline(str_replace(['demo_admin', 'handyman'], ['admin', 'employee'], (string) $role));
    $selectedId = optional($selectedBooking)->id;
    $selectedStage = $selectedBooking ? Str::headline($selectedBooking->sanad_stage ?: $selectedBooking->status) : '-';
    $requiredDocuments = $selectedBooking && $selectedBooking->service ? collect($selectedBooking->service->required_documents ?: [])->map(function ($doc) {
        $name = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc;
        return ['key' => is_array($doc) ? ($doc['key'] ?? Str::slug($name, '_')) : Str::slug($name, '_'), 'name' => $name];
    })->values() : collect();
@endphp

<x-master-layout>
    <script>
        window.toggleSanadAttachmentPopover = function (e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) e.stopImmediatePropagation();
            }
            var popover = document.getElementById('composer-attachment-popover');
            if (popover) {
                popover.classList.toggle('show');
            }
            return false;
        };

        window.selectSanadVaultDocument = function (btn, e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var docId = btn.getAttribute('data-id');
            var docName = btn.getAttribute('data-name');
            var fileInput = document.getElementById('composer-file-input');
            var vaultIdInput = document.getElementById('composer-vault-id');
            var badge = document.getElementById('composer-attachment-badge');
            var badgeName = document.getElementById('composer-attachment-name');
            var popover = document.getElementById('composer-attachment-popover');

            if (fileInput) fileInput.value = '';
            if (vaultIdInput) vaultIdInput.value = docId;
            if (badgeName) badgeName.textContent = 'Vault: ' + docName;
            if (badge) badge.style.setProperty('display', 'flex', 'important');
            if (popover) popover.classList.remove('show');
            return false;
        };

        window.syncSanadComposerMode = function (selectElem) {
            var composer = selectElem ? selectElem.closest('.chat-composer') : document.querySelector('.chat-composer');
            if (!composer) return;
            var modeSelect = selectElem || composer.querySelector('[name="delivery_mode"]');
            if (!modeSelect) return;
            
            var val = modeSelect.value;
            var isBuzz = val === 'buzz';
            var isDocument = val === 'document';
            
            composer.classList.toggle('is-buzz', isBuzz);
            composer.classList.toggle('is-document', isDocument);
            
            var composerText = composer.querySelector('textarea[name="message"]');
            if (composerText) {
                composerText.placeholder = isBuzz
                    ? 'Send an urgent Buzz to this request customer...'
                    : (isDocument ? 'Instructions / Reason for document request...' : 'Type a message...');
            }
            
            var documentName = composer.querySelector('input[name="document_name"]');
            if (documentName) {
                var preset = composer.querySelector('select[name="document_preset"]');
                var isCustom = !preset || preset.value === 'custom';
                documentName.required = isDocument && isCustom;
            }
        };

        window.syncSanadDocumentPreset = function (presetSelect) {
            var composer = presetSelect ? presetSelect.closest('.chat-composer') : document.querySelector('.chat-composer');
            if (!composer) return;
            var documentPreset = presetSelect || composer.querySelector('select[name="document_preset"]');
            var documentName = composer.querySelector('input[name="document_name"]');
            var documentKey = composer.querySelector('input[name="document_key"]');
            if (!documentPreset || !documentName || !documentKey) return;
            
            var option = documentPreset.options[documentPreset.selectedIndex];
            var selectedName = option ? option.dataset.name : '';
            var selectedKey = option ? option.dataset.key : '';
            var isCustom = documentPreset.value === 'custom';
            documentKey.value = selectedKey || '';
            if (!isCustom && selectedName) {
                documentName.value = selectedName;
                var customField = documentName.closest('.document-custom-field');
                if (customField) customField.style.setProperty('display', 'none', 'important');
                documentName.required = false;
            } else {
                documentName.value = '';
                var customField = documentName.closest('.document-custom-field');
                if (customField) customField.style.setProperty('display', 'flex', 'important');
                documentName.placeholder = 'Document name';
                documentName.required = composer.classList.contains('is-document');
            }
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.syncSanadComposerMode();
            window.syncSanadDocumentPreset();
        });

        window.removeSanadAttachment = function (e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            var fileInput = document.getElementById('composer-file-input');
            var vaultIdInput = document.getElementById('composer-vault-id');
            var badge = document.getElementById('composer-attachment-badge');

            if (fileInput) fileInput.value = '';
            if (vaultIdInput) vaultIdInput.value = '';
            if (badge) badge.style.setProperty('display', 'none', 'important');
            return false;
        };

        window.syncCustomerDocumentUploadChoice = function (selectElem) {
            var card = selectElem ? selectElem.closest('.customer-document-submit') : null;
            if (!card) return;

            var hasVaultSelection = !!selectElem.value;
            var uploadRow = card.querySelector('.customer-upload-row');
            var fileInput = card.querySelector('input[type="file"]');

            if (uploadRow) {
                uploadRow.classList.toggle('is-disabled', hasVaultSelection);
            }
            if (fileInput) {
                if (hasVaultSelection) fileInput.value = '';
                fileInput.disabled = hasVaultSelection;
            }
        };

        window.validateCustomerDocumentUpload = function (form) {
            var vaultSelect = form.querySelector('select[name="vault_document_id"]');
            var fileInput = form.querySelector('input[type="file"]');
            var hasVault = vaultSelect && vaultSelect.value;
            var hasFile = fileInput && !fileInput.disabled && fileInput.files && fileInput.files.length > 0;

            if (!hasVault && !hasFile) {
                alert('Choose a vault document or attach a file first.');
                return false;
            }

            return true;
        };

        window.syncCustomerDocumentFileName = function (input) {
            var form = input ? input.closest('.customer-document-submit') : null;
            var fileName = form ? form.querySelector('.customer-file-name') : null;
            if (!fileName) return;

            fileName.textContent = input.files && input.files[0] ? input.files[0].name : 'No file selected';
        };
    </script>
    <div class="sanad-inbox-shell" data-booking-id="{{ $selectedId }}" data-snapshot-url="{{ route('sanad.chat.workspace.snapshot') }}">
        <aside class="sanad-inbox-panel">
            <div class="inbox-top">
                <div>
                    <h4>Unified Inbox</h4>
                    <span>Chat, Buzz, documents, and AI review</span>
                </div>
                <a class="icon-btn" href="{{ route($messagesRoute) }}" title="Refresh inbox"><i class="fas fa-sync-alt"></i></a>
            </div>
            <form method="GET" action="{{ route($messagesRoute) }}" class="inbox-search">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search conversations">
                <input type="hidden" name="action_state" value="{{ request('action_state') }}">
            </form>
            <nav class="inbox-tabs">
                @foreach([
                    '' => 'All',
                    'open_chat' => 'Unread',
                    'unread_buzz' => 'Buzz',
                    'pending_documents' => 'Docs',
                    'ai_escalations' => 'AI Review',
                ] as $state => $label)
                    @continue($state === 'ai_escalations' && !$isAdmin)
                    <a class="{{ request('action_state') === $state ? 'active' : '' }}" href="{{ route($messagesRoute, array_filter(['action_state' => $state, 'search' => request('search')], fn ($value) => $value !== null && $value !== '')) }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="conversation-list">
                @forelse($conversations as $conversation)
                    @php
                        $openBuzz = $conversation->sanadBuzzAlerts->where('status', 'unread')->count();
                        $pendingDocs = $conversation->sanadDocumentRequests->whereIn('status', ['pending', 'submitted', 'replacement_requested'])->count();
                        $aiReviews = $isAdmin ? $conversation->sanadAiInteractions->where('requires_escalation', true)->count() : 0;
                        $latestThread = $conversation->sanadChatThreads->sortByDesc('last_message_at')->first();
                        $lastMessage = $latestThread && $latestThread->relationLoaded('messages')
                            ? optional($latestThread->messages->last())->message
                            : null;
                    @endphp
                    <a class="conversation-item {{ $selectedId === $conversation->id ? 'active' : '' }}" data-booking-id="{{ $conversation->id }}" href="{{ route($messagesRoute, array_filter(['booking_id' => $conversation->id, 'action_state' => request('action_state'), 'search' => request('search')])) }}">
                        <div class="avatar">{{ Str::upper(Str::substr(optional($conversation->customer)->display_name ?: 'C', 0, 1)) }}</div>
                        <div class="conversation-copy">
                            <div class="conversation-line"><strong>{{ optional($conversation->customer)->display_name ?: 'Customer' }}</strong><time>{{ optional($conversation->updated_at)->diffForHumans() }}</time></div>
                            <div class="conversation-ref">{{ $conversation->sanad_reference ?: '#' . $conversation->id }} · {{ optional($conversation->service)->name ?: 'No service' }}</div>
                            <p>{{ Str::limit($lastMessage ?: Str::headline($conversation->sanad_stage ?: $conversation->status), 74) }}</p>
                            <div class="chips">
                                @if($conversation->sanad_priority)<span>{{ Str::headline($conversation->sanad_priority) }}</span>@endif
                                @if($openBuzz)<span class="danger">{{ $openBuzz }} Buzz</span>@endif
                                @if($pendingDocs)<span class="info">{{ $pendingDocs }} Docs</span>@endif
                                @if($aiReviews)<span class="ai">AI Review</span>@endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="empty-panel">No conversations found.</div>
                @endforelse
            </div>
        </aside>

        <main class="sanad-chat-panel">
            @if(request('action_state') === 'ai_escalations' && $isAdmin)
                <header class="chat-header">
                    <div class="avatar large" style="background:#7c3aed;color:#fff;"><i class="fas fa-robot"></i></div>
                    <div>
                        <h4>AI Review & Escalations Center</h4>
                        <span>Review, edit, and approve escalated AI responses for customer queries</span>
                    </div>
                    <div class="chat-header-actions">
                        <span class="status-pill" style="background:#f3e8ff;color:#6b21a8;">{{ $aiEscalations->count() }} Pending Reviews</span>
                    </div>
                </header>

                <section class="chat-feed" id="sanad-chat-feed" style="overflow-y: auto !important; max-height: calc(100vh - 160px); min-height: 0;">
                    @forelse($aiEscalations as $interaction)
                        <article class="event-card ai-review shadow-sm mb-3 border-left border-warning" data-event-type="ai">
                            <div class="event-head d-flex justify-content-between align-items-center mb-2">
                                <strong><i class="fas fa-robot text-warning mr-1"></i> AI Escalation #{{ $interaction->id }}</strong>
                                <span class="badge badge-warning">{{ round(($interaction->confidence ?? 0) * 100) }}% Confidence · {{ Str::headline($interaction->status) }}</span>
                            </div>
                            <div class="mb-2">
                                <strong>User Query:</strong> <span class="text-dark font-weight-bold">"{{ $interaction->question }}"</span>
                                @if($interaction->user)<small class="text-muted ml-2">Asked by: {{ $interaction->user->display_name ?: $interaction->user->email }} ({{ $interaction->created_at->diffForHumans() }})</small>@endif
                            </div>
                            <form method="POST" action="{{ route('sanad.ai.escalations.review', $interaction->id) }}">
                                @csrf
                                <label class="small text-muted font-weight-bold mb-1">AI Response Draft / Alternative Reply:</label>
                                <textarea name="answer" class="form-control mb-2" rows="3" style="font-size: 13px;">{{ $interaction->answer }}</textarea>
                                <input name="review_note" class="form-control mb-2" placeholder="Admin review note (optional)">
                                <div class="action-row d-flex flex-wrap gap-2">
                                    <button type="submit" class="btn btn-success btn-sm" name="review_action" value="approve"><i class="fas fa-check mr-1"></i> Approve & Send</button>
                                    <button type="submit" class="btn btn-primary btn-sm" name="review_action" value="edit_approve"><i class="fas fa-edit mr-1"></i> Edit & Send Reply</button>
                                    <button type="submit" class="btn btn-secondary btn-sm" name="review_action" value="resolve"><i class="fas fa-check-double mr-1"></i> Mark Resolved</button>
                                    <button type="submit" class="btn btn-outline-danger btn-sm" name="review_action" value="delete" onclick="return confirm('Are you sure you want to delete this AI escalation?')"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
                                </div>
                            </form>
                        </article>
                    @empty
                        <div class="empty-chat py-5 text-center text-muted">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <h5>No Pending AI Escalations</h5>
                            <p>All AI questions have been reviewed or resolved.</p>
                        </div>
                    @endforelse
                </section>
            @elseif($selectedBooking)
                <header class="chat-header">
                    <div class="avatar large">{{ Str::upper(Str::substr(optional($selectedBooking->customer)->display_name ?: 'C', 0, 1)) }}</div>
                    <div>
                        <h4>{{ optional($selectedBooking->customer)->display_name ?: optional($selectedBooking->customer)->email ?: 'Customer' }}</h4>
                        <span>{{ $selectedBooking->sanad_reference ?: '#' . $selectedBooking->id }} · {{ optional($selectedBooking->service)->name ?: 'No service' }}</span>
                    </div>
                    <div class="chat-header-actions">
                        <span class="status-pill">{{ $selectedStage }}</span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route($requestShowRoute, $selectedBooking->id) }}">Request</a>
                    </div>
                </header>

                <section class="chat-feed" id="sanad-chat-feed">
                    @forelse($timeline as $item)
                        @if($item->type === 'buzz')
                            @php $buzz = $item->data; @endphp
                            <article id="buzz-{{ $buzz->id }}" class="event-card buzz {{ $highlightBuzzId === $buzz->id ? 'highlight' : '' }}" data-event-type="buzz">
                                <div class="event-head"><strong>{{ Str::headline($buzz->priority) }} Buzz</strong><span>Target: {{ $roleLabel($buzz->recipient_role) }} · {{ Str::headline($buzz->status) }}</span></div>
                                <p>{{ $buzz->message }}</p>
                                <div class="event-replies">
                                    @forelse($buzz->replies as $reply)
                                        <div><strong>{{ optional($reply->sender)->display_name ?: $roleLabel($reply->sender_role) }}</strong>{{ $reply->message }}</div>
                                    @empty
                                        <small>No reply yet.</small>
                                    @endforelse
                                </div>
                                @if($isCustomer && $buzz->status === 'unread')
                                    <form method="POST" action="{{ route('customer-portal.requests.buzz.reply', [$selectedBooking->id, $buzz->id]) }}" class="mt-2 d-flex gap-2 align-items-center">
                                        @csrf
                                        <input name="message" class="form-control form-control-sm" placeholder="Reply to this buzz..." required>
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-reply mr-1"></i> Reply</button>
                                    </form>
                                @endif
                            </article>
                        @elseif($item->type === 'document')
                            @php $documentRequest = $item->data; @endphp
                            <article class="event-card document" data-event-type="document">
                                <div class="event-head">
                                    <strong><i class="fas fa-file-invoice text-info mr-1"></i> Document Request: {{ $documentRequest->document_name }}</strong>
                                    <span class="badge badge-{{ $documentRequest->document ? 'success' : 'warning' }}">{{ Str::headline($documentRequest->status) }}</span>
                                </div>
                                <p class="mb-2"><strong>Target:</strong> {{ Str::headline($documentRequest->requested_from ?: 'customer') }}<br><strong>Instructions / Reason:</strong> {{ $documentRequest->instructions ?: $documentRequest->reason }}</p>
                                @if($documentRequest->due_at)
                                    <div class="document-deadline mb-2"><i class="far fa-clock mr-1"></i> Submit by {{ \Carbon\Carbon::parse($documentRequest->due_at)->format('Y-m-d') }} · {{ \Carbon\Carbon::parse($documentRequest->due_at)->diffForHumans() }}</div>
                                @endif

                                @if($documentRequest->document)
                                    @php
                                        $documentUrl = $documentRequest->document->publicDocumentUrl();
                                    @endphp
                                    <div class="mt-2 p-2 bg-light rounded border d-flex justify-content-between align-items-center">
                                        <span class="small font-weight-bold text-success"><i class="fas fa-check-circle mr-1"></i> Document Submitted</span>
                                        @if($documentUrl)
                                            <a target="_blank" href="{{ $documentUrl }}" class="btn btn-sm btn-success text-white"><i class="fas fa-download mr-1"></i> View / Download Document</a>
                                        @endif
                                    </div>
                                @elseif($isCustomer && in_array($documentRequest->requested_from, ['customer', 'user', null], true))
                                    <form method="POST" enctype="multipart/form-data" action="{{ route('customer-portal.requests.document-requests.upload', [$selectedBooking->id, $documentRequest->id]) }}" class="customer-document-submit mt-2 d-flex flex-column gap-2" onsubmit="return window.validateCustomerDocumentUpload(this)">
                                        @csrf
                                        @if(!empty($vaultDocuments) && $vaultDocuments->isNotEmpty())
                                            <div class="d-flex gap-2 align-items-center">
                                                <select name="vault_document_id" class="form-control form-control-sm customer-vault-select" onchange="window.syncCustomerDocumentUploadChoice(this)">
                                                    <option value="">-- Attach from Document Vault --</option>
                                                    @foreach($vaultDocuments as $vDoc)
                                                        <option value="{{ $vDoc->id }}">📁 {{ $vDoc->document_type }} ({{ $vDoc->file_name }})</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success text-nowrap"><i class="fas fa-check mr-1"></i> Submit Vault Doc</button>
                                            </div>
                                            <div class="text-muted small text-center">- OR Upload New File -</div>
                                        @endif
                                        <div class="customer-upload-row d-flex gap-2 align-items-center">
                                            <label class="btn btn-sm btn-light customer-attach-icon mb-0" title="Choose file" aria-label="Choose file">
                                                <i class="fas fa-paperclip"></i>
                                                <input type="file" name="file" class="customer-file-input" onchange="window.syncCustomerDocumentFileName(this)">
                                            </label>
                                            <span class="customer-file-name text-muted small">No file selected</span>
                                            <button type="submit" class="btn btn-sm btn-primary customer-upload-icon" title="Upload file" aria-label="Upload file"><i class="fas fa-upload"></i></button>
                                        </div>
                                    </form>
                                @else
                                    <div class="small text-muted fst-italic mt-1"><i class="fas fa-hourglass-half mr-1"></i> Awaiting customer submission...</div>
                                @endif
                            </article>
                        @elseif($item->type === 'message')
                            @php $message = $item->data; @endphp
                            <article class="message-row {{ in_array($message->sender_role, ['user', 'customer'], true) ? 'customer' : 'team' }}" data-message-id="{{ $message->id }}">
                                <div class="message-bubble">
                                    <div class="message-meta"><strong>{{ $message->sender_role === 'system' ? 'Sanad AI' : (optional($message->sender)->display_name ?: $roleLabel($message->sender_role)) }}</strong><span>{{ optional($message->created_at)->format('Y-m-d H:i') }}</span></div>
                                    @if($message->message)<p>{{ $message->message }}</p>@endif
                                    @if($message->getFirstMediaUrl('sanad_chat_attachment'))
                                        <div class="mt-2">
                                            <a href="{{ $message->getFirstMediaUrl('sanad_chat_attachment') }}" target="_blank" class="btn btn-sm btn-light border text-primary">
                                                <i class="fas fa-paperclip mr-1"></i> {{ optional($message->getFirstMedia('sanad_chat_attachment'))->file_name ?: 'Download Attachment' }}
                                            </a>
                                        </div>
                                    @endif
                                    <div class="message-links">
                                        @if($message->buzz_alert_id)<span>Buzz #{{ $message->buzz_alert_id }}</span>@endif
                                        @if($message->document_request_id)<span>Document #{{ $message->document_request_id }}</span>@endif
                                        @if($message->ai_interaction_id && $isAdmin)<span>AI #{{ $message->ai_interaction_id }}</span>@endif
                                    </div>
                                </div>
                            </article>
                        @endif
                    @empty
                        <div class="empty-chat py-5 text-center text-muted">
                            <i class="far fa-comments fa-3x mb-3 text-muted"></i>
                            <h5>No activity yet</h5>
                            <p>Start a conversation by typing a message below.</p>
                        </div>
                    @endforelse

                    @if($isAdmin && $aiEscalations->isNotEmpty())
                        @foreach($aiEscalations as $interaction)
                            <article class="event-card ai-review shadow-sm mb-3 border-left border-warning" data-event-type="ai">
                                <div class="event-head d-flex justify-content-between align-items-center mb-2">
                                    <strong><i class="fas fa-robot text-warning mr-1"></i> AI Escalation Review</strong>
                                    <span class="badge badge-warning">{{ round(($interaction->confidence ?? 0) * 100) }}% Confidence · {{ Str::headline($interaction->status) }}</span>
                                </div>
                                <div class="mb-2">
                                    <strong>User Query:</strong> <span class="text-dark font-weight-bold">"{{ $interaction->question }}"</span>
                                </div>
                                <form method="POST" action="{{ route('sanad.ai.escalations.review', $interaction->id) }}">
                                    @csrf
                                    <label class="small text-muted font-weight-bold mb-1">AI Generated Answer / Edit Alternative Reply:</label>
                                    <textarea name="answer" class="form-control mb-2" rows="3" style="font-size: 13px;">{{ $interaction->answer }}</textarea>
                                    <input name="review_note" class="form-control mb-2" placeholder="Admin review note (optional)">
                                    <div class="action-row d-flex flex-wrap gap-2">
                                        <button type="submit" class="btn btn-success btn-sm" name="review_action" value="approve"><i class="fas fa-check mr-1"></i> Approve & Send</button>
                                        <button type="submit" class="btn btn-primary btn-sm" name="review_action" value="edit_approve"><i class="fas fa-edit mr-1"></i> Edit & Send Reply</button>
                                        <button type="submit" class="btn btn-secondary btn-sm" name="review_action" value="resolve"><i class="fas fa-check-double mr-1"></i> Mark Resolved</button>
                                        <button type="submit" class="btn btn-outline-danger btn-sm" name="review_action" value="delete" onclick="return confirm('Are you sure you want to delete this AI escalation?')"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
                                    </div>
                                </form>
                            </article>
                        @endforeach
                    @endif
                </section>

                <form method="POST" enctype="multipart/form-data" action="{{ route($storeChatRoute, $selectedBooking->id) }}" class="chat-composer">
                    @csrf
                    <input type="hidden" name="thread_type" value="shared">
                    <div class="document-fields mb-2" dir="rtl">
                        <div class="document-request-grid">
                            <label class="document-field">
                                <span>Document</span>
                                <select name="document_preset" class="document-preset" title="Document to request" onchange="window.syncSanadDocumentPreset(this)">
                            @foreach($requiredDocuments as $doc)
                                <option value="{{ $doc['key'] }}" data-key="{{ $doc['key'] }}" data-name="{{ $doc['name'] }}">{{ $doc['name'] }}</option>
                            @endforeach
                                    <option value="custom" data-key="" data-name="">Custom document</option>
                                </select>
                            </label>
                            <input type="hidden" name="document_key">
                            <label class="document-field document-custom-field">
                                <span>Custom document name</span>
                                <input name="document_name" id="composer-document-name" placeholder="Document name">
                            </label>
                            <label class="document-field">
                                <span>Submit by</span>
                                <input name="due_at" type="date" class="composer-due-at" title="Due Date">
                            </label>
                            <input type="hidden" name="requested_from" value="customer">
                        </div>
                    </div>

                    <div class="composer-main-bar d-flex align-items-end gap-2 w-100">
                        @if($canCreateBuzz || $canRequestDocuments)
                            <select name="delivery_mode" class="composer-mode" title="Delivery type" onchange="window.syncSanadComposerMode(this)">
                                <option value="message">Message</option>
                                @if($canCreateBuzz)<option value="buzz">Buzz</option>@endif
                                @if($canRequestDocuments)<option value="document">Document</option>@endif
                            </select>
                        @else
                            <input type="hidden" name="delivery_mode" value="message">
                        @endif
                        <select name="buzz_priority" class="composer-priority" title="Buzz priority">
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="normal">Normal</option>
                            <option value="low">Low</option>
                        </select>
                    <!-- Hidden File & Vault Inputs -->
                    <input type="file" name="attachment" id="composer-file-input" class="d-none" accept="image/jpeg,image/png,application/pdf,.doc,.docx">
                    <input type="hidden" name="vault_document_id" id="composer-vault-id">

                    @if(!empty($vaultDocuments) && $vaultDocuments->isNotEmpty())
                        <!-- Paperclip Button with Attachment Options Popover -->
                        <div class="composer-tool-wrapper position-relative">
                            <button type="button" class="composer-tool" id="composer-attachment-btn" title="Attach file or document" onclick="toggleSanadAttachmentPopover(event)">
                                <i class="fas fa-paperclip"></i>
                            </button>

                            <div class="attachment-popover shadow border" id="composer-attachment-popover" style="display: none; position: absolute; bottom: 48px; left: 0; background: #fff; border-radius: 12px; padding: 10px; width: 230px; z-index: 1050; box-shadow: 0 10px 25px rgba(0,0,0,0.15)!important;">
                                <div class="small font-weight-bold text-muted mb-2 px-1 text-uppercase" style="letter-spacing:0.5px; font-size:10px;">Select Attachment Source</div>
                                
                                <label for="composer-file-input" class="btn btn-sm btn-light w-100 text-left mb-1 d-flex align-items-center py-2 cursor-pointer mb-0" id="btn-attach-device" style="cursor: pointer;">
                                    <i class="fas fa-desktop text-primary mr-2 fa-fw"></i> Upload from Device
                                </label>

                                <div class="dropdown-divider my-2"></div>
                                <div class="small font-weight-bold text-muted mb-1 px-1 text-uppercase" style="letter-spacing:0.5px; font-size:10px;">Document Vault</div>
                                @foreach($vaultDocuments as $vDoc)
                                    <button type="button" class="btn btn-sm btn-light w-100 text-left text-truncate py-1 select-vault-doc-btn mb-1" data-id="{{ $vDoc->id }}" data-name="{{ $vDoc->document_type }}" onclick="selectSanadVaultDocument(this, event)">
                                        <i class="fas fa-folder text-warning mr-2 fa-fw"></i> {{ $vDoc->document_type }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <!-- Direct file picker label when no vault docs -->
                        <label class="composer-tool mb-0" for="composer-file-input" title="Attach image or document from device" style="cursor: pointer;">
                            <i class="fas fa-paperclip"></i>
                        </label>
                    @endif

                    <div class="composer-text-wrapper position-relative w-100">
                        <div id="composer-attachment-badge" class="badge badge-light border text-primary p-2 mb-1 w-100 d-flex justify-content-between align-items-center" style="display: none !important; font-size: 12px;">
                            <span><i class="fas fa-paperclip mr-1"></i> <span id="composer-attachment-name"></span></span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2" id="btn-remove-attachment" onclick="removeSanadAttachment(event)" style="text-decoration:none;">&times;</button>
                        </div>
                        <textarea name="message" rows="1" placeholder="Type a message..."></textarea>
                    </div>

                    <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            @else
                <div class="empty-chat">Select a conversation to start.</div>
            @endif
        </main>

        <aside class="sanad-context-panel">
            @if($selectedBooking)
                <section class="context-tab active" id="tab-request">
                    <h5>Request Context</h5>
                    <div class="context-list">
                        <span>Stage <strong>{{ $selectedStage }}</strong></span>
                        <span>Priority <strong>{{ Str::headline($selectedBooking->sanad_priority ?: 'normal') }}</strong></span>
                        <span>SLA <strong>{{ optional($selectedBooking->sla_due_at)->format('Y-m-d H:i') ?: '-' }}</strong></span>
                        <span>Partner <strong>{{ optional($selectedBooking->provider)->display_name ?: '-' }}</strong></span>
                    </div>
                </section>
            @endif
        </aside>
    </div>

        <style>
            .content-page { min-height: 100vh; }
            .sanad-inbox-shell { height: calc(100vh - 90px); display: grid; grid-template-columns: 340px minmax(0, 1fr) 340px; overflow: hidden !important; background: #f5f7fb; border-top: 1px solid #e5e9f2; }
            .sanad-inbox-panel, .sanad-context-panel { background: #fff; overflow-y: auto; height: 100%; border-right: 1px solid #e4e9f2; }
            .sanad-context-panel { border-right: 0; border-left: 1px solid #e4e9f2; padding: 14px; }
            .inbox-top { display: flex; justify-content: space-between; gap: 12px; padding: 18px 16px 12px; }
            .inbox-top h4, .chat-header h4 { margin: 0; font-weight: 800; color: #111827; }
            .inbox-top span, .conversation-ref, .conversation-copy p, .chat-header span, .muted { color: #667085; }
            .icon-btn, .composer-tool, .send-btn { border: 0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
            .icon-btn { width: 34px; height: 34px; border-radius: 8px; color: #42526e; background: #f1f4f9; }
            .inbox-search { padding: 0 16px 12px; }
            .inbox-search input { width: 100%; border: 1px solid #dce3ee; border-radius: 8px; padding: 10px 12px; }
            .inbox-tabs { display: flex; gap: 6px; padding: 0 16px 12px; overflow-x: auto; }
            .inbox-tabs a { color: #475467; background: #f2f4f7; padding: 7px 10px; border-radius: 999px; white-space: nowrap; }
            .inbox-tabs a.active { color: #fff; background: #4f46e5; }
            .conversation-list { display: grid; gap: 4px; padding: 0 8px 16px; }
            .conversation-item { display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: 10px; padding: 12px 10px; border-radius: 10px; color: #111827; border: 1px solid transparent; }
            .conversation-item:hover, .conversation-item.active { background: #eef2ff; border-color: #d9defb; color: #111827; }
            .avatar { width: 42px; height: 42px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #4f46e5; color: #fff; font-weight: 800; }
            .avatar.large { width: 48px; height: 48px; }
            .conversation-line { display: flex; justify-content: space-between; gap: 10px; }
            .conversation-line time { color: #98a2b3; font-size: 12px; white-space: nowrap; }
            .conversation-copy p { margin: 3px 0 7px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .chips { display: flex; flex-wrap: wrap; gap: 5px; }
            .chips span, .status-pill { font-size: 11px; padding: 3px 8px; border-radius: 999px; background: #e0f2fe; color: #075985; }
            .chips .danger { background: #fee2e2; color: #991b1b; }
            .chips .info { background: #ecfdf3; color: #027a48; }
            .chips .ai { background: #f3e8ff; color: #6b21a8; }
            .sanad-chat-panel { display: flex; flex-direction: column; min-width: 0; background: #f8fafc; height: 100%; overflow: hidden; }
            .chat-header { min-height: 74px; display: grid; grid-template-columns: 48px minmax(0, 1fr) auto; align-items: center; gap: 12px; padding: 12px 18px; border-bottom: 1px solid #e4e9f2; background: #fff; flex-shrink: 0; }
            .chat-header-actions { display: flex; align-items: center; gap: 8px; }
            .chat-feed { flex: 1; overflow-y: auto !important; padding: 20px 24px; display: flex; flex-direction: column; gap: 12px; min-height: 0; }
            .chat-feed::-webkit-scrollbar { width: 6px; }
            .chat-feed::-webkit-scrollbar-track { background: #f1f5f9; }
            .chat-feed::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
            .message-row { display: flex; }
            .message-row.team { justify-content: flex-end; }
            .message-bubble { max-width: min(680px, 78%); border-radius: 16px; padding: 10px 12px; background: #fff; border: 1px solid #e5e9f2; box-shadow: 0 4px 16px rgba(15,23,42,.04); }
            .message-row.team .message-bubble { background: #4f46e5; color: #fff; border-color: #4f46e5; }
            .message-meta { display: flex; justify-content: space-between; gap: 14px; font-size: 12px; margin-bottom: 4px; opacity: .82; }
            .message-bubble p, .event-card p { margin: 0; white-space: pre-wrap; }
            .message-links { display: flex; gap: 6px; margin-top: 6px; font-size: 11px; opacity: .78; }
            .event-card { align-self: center; width: min(620px, 88%); background: #fff; border: 1px solid #e5e9f2; border-left: 3px solid #64748b; border-radius: 10px; padding: 9px 11px; font-size: 13px; }
            .event-card.buzz { border-left-color: #ef4444; }
            .event-card.document { border-left-color: #0ea5e9; }
            .document-deadline { display: inline-flex; align-items: center; gap: 3px; color: #b42318; background: #fff1f3; border: 1px solid #ffd5da; border-radius: 999px; padding: 5px 9px; font-size: 12px; font-weight: 700; }
            .customer-upload-row.is-disabled { opacity: .48; pointer-events: none; }
            .customer-upload-icon { width: 42px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .customer-attach-icon { width: 42px; height: 38px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; border-radius: 10px; }
            .customer-file-input { display: none !important; }
            .customer-file-name { min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .event-card.ai-review { border-left-color: #7c3aed; }
            .event-card.highlight { box-shadow: 0 0 0 3px rgba(239,68,68,.14); }
            .event-head { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 5px; }
            .event-head span, .event-replies small { color: #667085; }
            .event-replies { display: grid; gap: 5px; margin-top: 5px; }
            .event-replies div { background: #f8fafc; border-radius: 8px; padding: 6px 8px; }
            .event-replies strong { display: block; font-size: 12px; }
            .chat-composer { display: flex; flex-direction: column; gap: 8px; padding: 12px 16px; background: #fff; border-top: 1px solid #e4e9f2; flex-shrink: 0; width: 100%; box-sizing: border-box; }
            .composer-mode, .composer-priority, .document-fields select, .document-fields input { height: 38px; border: 1px solid #dce3ee; border-radius: 10px; background: #fff; color: #344054; padding: 0 10px; flex-shrink: 0; }
            .composer-mode { width: 110px; }
            .composer-priority { display: none; width: 100px; }
            .chat-composer.is-buzz .composer-priority { display: block !important; }
            .document-fields { display: none !important; width: 100%; }
            .chat-composer.is-document .document-fields { display: block !important; }
            .document-request-grid { display: grid; grid-template-columns: minmax(220px, 1.5fr) minmax(220px, 1.2fr) minmax(160px, .8fr); gap: 8px; width: 100%; direction: ltr; align-items: end; }
            .document-field { display: flex; flex-direction: column; gap: 5px; align-items: stretch; min-width: 0; margin: 0; direction: ltr; width: 100%; }
            .document-field span { font-size: 12px; line-height: 1.2; font-weight: 700; color: #667085; white-space: nowrap; }
            .document-field select, .document-field input { width: 100%; min-width: 0; }
            .document-field select[name="requested_from"] { color: #667085; background: #f8fafc; }
            .chat-composer.is-document .send-btn { background: #0ea5e9; }
            .chat-composer.is-buzz .send-btn { background: #dc2626; }
            .composer-tool-wrapper { flex-shrink: 0; }
            .composer-tool { width: 38px; height: 38px; border-radius: 10px; color: #475467; background: #f2f4f7; border: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; }
            .composer-tool input { display: none; }
            .composer-text-wrapper { flex: 1; min-width: 0; display: flex; flex-direction: column; }
            .chat-composer textarea { width: 100% !important; min-width: 0; resize: none; min-height: 38px; max-height: 120px; border: 1px solid #dce3ee; border-radius: 14px; padding: 8px 12px; font-size: 14px; outline: none; box-sizing: border-box; }
            .chat-composer textarea:focus { border-color: #4f46e5; }
            .send-btn { width: 44px; height: 38px; border-radius: 12px; background: #4f46e5; color: #fff; flex-shrink: 0; border: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; }
            .context-tab h5 { font-weight: 800; margin-bottom: 12px; }
            .context-list { display: grid; gap: 8px; }
            .context-list span, .learning-card { display: flex; justify-content: space-between; gap: 10px; padding: 10px; border: 1px solid #e5e9f2; border-radius: 10px; background: #fff; }
            .compact-form { display: grid; gap: 8px; }
            .compact-form input, .compact-form select, .compact-form textarea { border: 1px solid #dce3ee; border-radius: 8px; padding: 9px 10px; width: 100%; }
            .learning-card { display: grid; margin-bottom: 8px; }
            .empty-panel, .empty-chat { color: #667085; padding: 22px; text-align: center; }
            .attachment-popover { display: none; position: absolute; bottom: 48px; left: 0; background: #fff; border-radius: 12px; padding: 10px; width: 230px; z-index: 1050; box-shadow: 0 10px 25px rgba(0,0,0,0.15)!important; }
            .attachment-popover.show { display: block !important; }
            @media (max-width: 1200px) {
                .sanad-inbox-shell { height: auto; grid-template-columns: 1fr; overflow: visible; }
                .sanad-inbox-panel, .sanad-context-panel, .sanad-chat-panel { min-height: 420px; }
            }
            @media (max-width: 640px) {
                .document-request-grid { grid-template-columns: 1fr; }
                .composer-main-bar { align-items: stretch !important; }
                .composer-mode, .composer-priority { width: 100%; }
            }
    </style>

    <script>
        (function () {
            document.addEventListener('click', function (e) {
                var popover = document.getElementById('composer-attachment-popover');
                if (popover && popover.classList.contains('show')) {
                    if (!popover.contains(e.target) && !e.target.closest('#composer-attachment-btn')) {
                        popover.classList.remove('show');
                    }
                }
            });

                document.addEventListener('change', function (e) {
                    if (e.target && e.target.id === 'composer-file-input') {
                        var fileInput = e.target;
                        var vaultIdInput = document.getElementById('composer-vault-id');
                        var badge = document.getElementById('composer-attachment-badge');
                        var badgeName = document.getElementById('composer-attachment-name');

                        if (fileInput.files && fileInput.files[0]) {
                            if (vaultIdInput) vaultIdInput.value = '';
                            if (badgeName) badgeName.textContent = 'Device File: ' + fileInput.files[0].name;
                            if (badge) badge.style.setProperty('display', 'flex', 'important');
                        }
                    }
                });

                function initSanadChatWorkspace() {
                    var shell = document.querySelector('.sanad-inbox-shell');
                    if (!shell) return;

                    var feed = document.getElementById('sanad-chat-feed');
                    var composer = document.querySelector('.chat-composer');
                    var modeSelect = composer ? composer.querySelector('select[name="delivery_mode"]') : null;
                    var composerText = composer ? composer.querySelector('textarea[name="message"]') : null;
                    var documentPreset = composer ? composer.querySelector('select[name="document_preset"]') : null;
                    var documentKey = composer ? composer.querySelector('input[name="document_key"]') : null;
                    var documentName = composer ? composer.querySelector('input[name="document_name"]') : null;
                    var fileInput = document.getElementById('composer-file-input');
                    var vaultIdInput = document.getElementById('composer-vault-id');
                    var chatHeader = document.querySelector('.chat-header');
                    var contextList = document.querySelector('.context-list');

                    function syncComposerMode() {
                        if (!composer || !modeSelect) return;
                        var isBuzz = modeSelect.value === 'buzz';
                        var isDocument = modeSelect.value === 'document';
                        composer.classList.toggle('is-buzz', isBuzz);
                        composer.classList.toggle('is-document', isDocument);
                        if (composerText) {
                            composerText.placeholder = isBuzz
                                ? 'Send an urgent Buzz to this request customer...'
                                : (isDocument ? 'Reason and instructions for the customer...' : 'Type a message...');
                        }
                        if (documentName) {
                            var isCustom = !documentPreset || documentPreset.value === 'custom';
                            documentName.required = isDocument && isCustom;
                        }
                    }

                    function syncDocumentPreset() {
                        if (!documentPreset || !documentName || !documentKey) return;
                        var option = documentPreset.options[documentPreset.selectedIndex];
                        var selectedName = option ? option.dataset.name : '';
                        var selectedKey = option ? option.dataset.key : '';
                        var isCustom = documentPreset.value === 'custom';
                        documentKey.value = selectedKey || '';
                        if (!isCustom && selectedName) {
                            documentName.value = selectedName;
                            var customField = documentName.closest('.document-custom-field');
                            if (customField) customField.style.setProperty('display', 'none', 'important');
                            documentName.required = false;
                        } else {
                            documentName.value = '';
                            var customField = documentName.closest('.document-custom-field');
                            if (customField) customField.style.setProperty('display', 'flex', 'important');
                            documentName.placeholder = 'Document name';
                            documentName.required = composer.classList.contains('is-document');
                        }
                    }

                    if (documentPreset) {
                        documentPreset.addEventListener('change', syncDocumentPreset);
                        syncDocumentPreset();
                    }
                    if (modeSelect) {
                        modeSelect.addEventListener('change', function () {
                            syncComposerMode();
                            syncDocumentPreset();
                        });
                        syncComposerMode();
                    }

                    function escapeHtml(value) {
                        return String(value || '').replace(/[&<>"']/g, function (char) {
                            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
                        });
                    }

                    function setActiveConversation(bookingId) {
                        document.querySelectorAll('.conversation-item').forEach(function (item) {
                            item.classList.toggle('active', String(item.dataset.bookingId) === String(bookingId));
                        });
                    }

                    function updateDocumentOptions(documents) {
                        if (!documentPreset) return;
                        var selected = documentPreset.value;
                        var optionsHtml = '';
                        (documents || []).forEach(function (doc) {
                            optionsHtml += '<option value="' + escapeHtml(doc.key) + '" data-key="' + escapeHtml(doc.key) + '" data-name="' + escapeHtml(doc.name) + '">' + escapeHtml(doc.name) + '</option>';
                        });
                        optionsHtml += '<option value="custom" data-key="" data-name="">Custom document</option>';
                        documentPreset.innerHTML = optionsHtml;
                        var hasSelected = Array.prototype.some.call(documentPreset.options, function (option) {
                            return option.value === selected;
                        });
                        documentPreset.value = hasSelected ? selected : ((documents || []).length ? documents[0].key : 'custom');
                        syncDocumentPreset();
                    }

                    function updateRequestChrome(snapshot) {
                        if (!snapshot || !snapshot.request) return;
                        var request = snapshot.request;
                        var bookingChanged = String(shell.dataset.bookingId || '') !== String(request.id || '');
                        shell.dataset.bookingId = request.id;
                        setActiveConversation(request.id);

                        if (chatHeader) {
                            var avatar = chatHeader.querySelector('.avatar.large');
                            var title = chatHeader.querySelector('h4');
                            var subtitle = chatHeader.querySelector('div > span');
                            var status = chatHeader.querySelector('.status-pill');
                            var requestLink = chatHeader.querySelector('.chat-header-actions a');

                            if (avatar) avatar.textContent = request.avatar || 'C';
                            if (title) title.textContent = request.customer || 'Customer';
                            if (subtitle) subtitle.textContent = (request.reference || ('#' + request.id)) + ' · ' + (request.service || 'No service');
                            if (status) status.textContent = request.stage || '-';
                            if (requestLink && request.request_url) requestLink.href = request.request_url;
                        }

                        if (contextList) {
                            contextList.innerHTML =
                                '<span>Stage <strong>' + escapeHtml(request.stage || '-') + '</strong></span>' +
                                '<span>Priority <strong>' + escapeHtml(request.priority || 'Normal') + '</strong></span>' +
                                '<span>SLA <strong>' + escapeHtml(request.sla || '-') + '</strong></span>' +
                                '<span>Partner <strong>' + escapeHtml(request.partner || '-') + '</strong></span>';
                        }

                        if (composer && snapshot.composer) {
                            if (snapshot.composer.store_url) composer.action = snapshot.composer.store_url;
                            if (bookingChanged) {
                                updateDocumentOptions(snapshot.composer.required_documents || []);
                                if (modeSelect) {
                                    modeSelect.value = 'message';
                                    syncComposerMode();
                                }
                                if (composerText) composerText.value = '';
                                if (documentName) documentName.value = '';
                                if (fileInput) fileInput.value = '';
                                if (vaultIdInput) vaultIdInput.value = '';
                                var badge = document.getElementById('composer-attachment-badge');
                                if (badge) badge.style.setProperty('display', 'none', 'important');
                            }
                        }
                    }

                    function render(snapshot) {
                        if (!feed || !snapshot.status) return;
                        updateRequestChrome(snapshot);
                        var html = '';
                        var items = snapshot.timeline || [];
                        if (items && items.length) {
                            items.forEach(function (item) {
                                if (item.type === 'buzz') {
                                    html += '<article class="event-card buzz" data-event-type="buzz"><div class="event-head"><strong>' + escapeHtml(item.priority) + ' Buzz</strong><span>Target: ' + escapeHtml(item.recipient_role) + ' · ' + escapeHtml(item.status) + '</span></div><p>' + escapeHtml(item.message) + '</p><div class="event-replies">';
                                    if (item.replies && item.replies.length) {
                                        item.replies.forEach(function (reply) { html += '<div><strong>' + escapeHtml(reply.sender) + '</strong>' + escapeHtml(reply.message) + '</div>'; });
                                    } else {
                                        html += '<small>No reply yet.</small>';
                                    }
                                    html += '</div></article>';
                                } else if (item.type === 'document') {
                                    html += '<article class="event-card document" data-event-type="document"><div class="event-head"><strong>Document Request (From ' + escapeHtml(item.requested_from) + ')</strong><span>' + escapeHtml(item.status) + '</span></div><p><b>' + escapeHtml(item.document_name) + '</b><br>' + escapeHtml(item.instructions) + '</p>';
                                    if (item.due_at) {
                                        html += '<div class="document-deadline mt-2"><i class="far fa-clock mr-1"></i>Submit by ' + escapeHtml(item.due_at) + (item.due_label ? ' · ' + escapeHtml(item.due_label) : '') + '</div>';
                                    }
                                    if (item.has_file && item.file_url) {
                                        html += '<div class="mt-2 p-2 bg-light rounded border d-flex justify-content-between align-items-center"><span class="small font-weight-bold text-success"><i class="fas fa-check-circle mr-1"></i> Document Submitted</span><a target="_blank" href="' + item.file_url + '" class="btn btn-sm btn-success text-white"><i class="fas fa-download mr-1"></i> View / Download Document</a></div>';
                                    }
                                    html += '</article>';
                                } else if (item.type === 'message') {
                                    var side = ['user', 'customer'].indexOf(item.sender_role) >= 0 ? 'customer' : 'team';
                                    html += '<article class="message-row ' + side + '" data-message-id="' + item.id + '"><div class="message-bubble"><div class="message-meta"><strong>' + escapeHtml(item.sender) + '</strong><span>' + escapeHtml(item.created_at) + '</span></div><p>' + escapeHtml(item.message) + '</p>';
                                    if (item.attachment_url) {
                                        html += '<div class="mt-2"><a href="' + item.attachment_url + '" target="_blank" class="btn btn-sm btn-light border text-primary"><i class="fas fa-paperclip mr-1"></i> ' + escapeHtml(item.attachment_name || 'Download Attachment') + '</a></div>';
                                    }
                                    html += '</div></article>';
                                }
                            });
                        } else {
                            if (snapshot.buzz_alerts && snapshot.buzz_alerts.length) {
                                snapshot.buzz_alerts.forEach(function (buzz) {
                                    html += '<article class="event-card buzz" data-event-type="buzz"><div class="event-head"><strong>' + escapeHtml(buzz.priority) + ' Buzz</strong><span>' + escapeHtml(buzz.recipient_role) + ' · ' + escapeHtml(buzz.status) + '</span></div><p>' + escapeHtml(buzz.message) + '</p></article>';
                                });
                            }
                            if (snapshot.documents && snapshot.documents.length) {
                                snapshot.documents.forEach(function (doc) {
                                    html += '<article class="event-card document" data-event-type="document"><div class="event-head"><strong>Document Request</strong><span>' + escapeHtml(doc.status) + '</span></div><p><b>' + escapeHtml(doc.document_name) + '</b><br>' + escapeHtml(doc.instructions) + '</p>';
                                    if (doc.due_at) {
                                        html += '<div class="document-deadline mt-2"><i class="far fa-clock mr-1"></i>Submit by ' + escapeHtml(doc.due_at) + (doc.due_label ? ' · ' + escapeHtml(doc.due_label) : '') + '</div>';
                                    }
                                    html += '</article>';
                                });
                            }
                            if (snapshot.messages && snapshot.messages.length) {
                                snapshot.messages.forEach(function (message) {
                                    var side = ['user', 'customer'].indexOf(message.sender_role) >= 0 ? 'customer' : 'team';
                                    html += '<article class="message-row ' + side + '" data-message-id="' + message.id + '"><div class="message-bubble"><div class="message-meta"><strong>' + escapeHtml(message.sender) + '</strong><span>' + escapeHtml(message.created_at) + '</span></div><p>' + escapeHtml(message.message) + '</p></div></article>';
                                });
                            }
                        }
                        if (snapshot.ai_escalations && snapshot.ai_escalations.length) {
                            snapshot.ai_escalations.forEach(function (item) {
                                html += '<article class="event-card ai-review shadow-sm mb-3 border-left border-warning" data-event-type="ai">' +
                                    '<div class="event-head d-flex justify-content-between align-items-center mb-2"><strong><i class="fas fa-robot text-warning mr-1"></i> AI Escalation Review</strong><span class="badge badge-warning">' + roundPercent(item.confidence) + '% Confidence · ' + escapeHtml(item.status) + '</span></div>' +
                                    '<div class="mb-2"><strong>User Query:</strong> <span class="text-dark font-weight-bold">"' + escapeHtml(item.question) + '"</span></div>' +
                                    '<form method="POST" action="/sanad/ai/escalations/' + item.id + '/review">' +
                                    '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                                    '<label class="small text-muted font-weight-bold mb-1">AI Answer / Edit Alternative Reply:</label>' +
                                    '<textarea name="answer" class="form-control mb-2" rows="3" style="font-size:13px;">' + escapeHtml(item.answer) + '</textarea>' +
                                    '<input name="review_note" class="form-control mb-2" placeholder="Admin review note (optional)">' +
                                    '<div class="action-row d-flex flex-wrap gap-2">' +
                                    '<button type="submit" class="btn btn-success btn-sm" name="review_action" value="approve"><i class="fas fa-check mr-1"></i> Approve & Send</button>' +
                                    '<button type="submit" class="btn btn-primary btn-sm" name="review_action" value="edit_approve"><i class="fas fa-edit mr-1"></i> Edit & Send Reply</button>' +
                                    '<button type="submit" class="btn btn-secondary btn-sm" name="review_action" value="resolve"><i class="fas fa-check-double mr-1"></i> Mark Resolved</button>' +
                                    '</div></form></article>';
                            });
                        }
                        var shouldStick = feed.scrollTop + feed.clientHeight >= feed.scrollHeight - 120;
                        feed.innerHTML = html;
                        if (shouldStick) feed.scrollTop = feed.scrollHeight;
                    }

                    function roundPercent(val) {
                        val = parseFloat(val) || 0;
                        return Math.round(val > 1 ? val : val * 100);
                    }

                    function snapshotUrl(bookingId) {
                        var url = new URL(shell.dataset.snapshotUrl, window.location.origin);
                        url.searchParams.set('booking_id', bookingId);
                        var currentUrl = new URL(window.location.href);
                        ['action_state', 'search'].forEach(function (key) {
                            if (currentUrl.searchParams.has(key)) url.searchParams.set(key, currentUrl.searchParams.get(key));
                        });
                        return url.toString();
                    }

                    function refreshConversation(bookingId) {
                        bookingId = bookingId || shell.dataset.bookingId;
                        if (!shell || !bookingId) return Promise.resolve(null);
                        var url = snapshotUrl(bookingId);
                        return fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                            .then(function (response) { return response.ok ? response.json() : null; })
                            .then(function (snapshot) { if (snapshot) render(snapshot); })
                            .catch(function () {});
                    }

                    function loadConversation(bookingId, href, pushState) {
                        if (!bookingId) return;
                        setActiveConversation(bookingId);
                        refreshConversation(bookingId);
                        if (pushState && href) {
                            window.history.pushState({bookingId: bookingId}, '', href);
                        }
                    }

                    if (composer) {
                        composer.onsubmit = function (e) {
                            if (e) {
                                e.preventDefault();
                                e.stopPropagation();
                            }
                            var msgText = composerText ? composerText.value.trim() : '';
                            var docNameVal = documentName ? documentName.value.trim() : '';
                            var vaultIdVal = vaultIdInput ? vaultIdInput.value.trim() : '';
                            var hasFile = fileInput && fileInput.files && fileInput.files.length > 0;

                            if (!msgText && !docNameVal && !vaultIdVal && !hasFile) return;

                            var sendBtn = composer.querySelector('.send-btn');
                            var origBtnHtml = sendBtn ? sendBtn.innerHTML : '';
                            if (sendBtn) {
                                sendBtn.disabled = true;
                                sendBtn.innerHTML = '<span class="sanad-btn-spinner" style="width:12px;height:12px;border-width:2px;border-top-color:#fff;display:inline-block;border-radius:50%;animation:sanadBtnSpin 0.7s linear infinite;"></span>';
                            }

                            if (composerText) composerText.value = '';

                            var formData = new FormData(composer);

                            fetch(composer.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(function (res) { return res.json(); })
                            .then(function (data) {
                                if (sendBtn) {
                                    sendBtn.disabled = false;
                                    sendBtn.innerHTML = origBtnHtml;
                                }
                                refreshConversation();
                            })
                            .catch(function (err) {
                                if (sendBtn) {
                                    sendBtn.disabled = false;
                                    sendBtn.innerHTML = origBtnHtml;
                                }
                                refreshConversation();
                            });
                        };

                        if (composerText) {
                            composerText.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter' && !e.shiftKey) {
                                    e.preventDefault();
                                    composer.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
                                }
                            });
                        }
                    }

                    document.addEventListener('submit', function (e) {
                        var form = e.target;
                        if (form && form.action && (form.action.indexOf('/sanad/ai/interactions/') !== -1 || form.action.indexOf('/review') !== -1)) {
                            e.preventDefault();
                            var submitter = e.submitter;
                            var formData = new FormData(form);
                            if (submitter && submitter.name) {
                                formData.append(submitter.name, submitter.value);
                            }

                            fetch(form.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(function (res) { return res.json(); })
                            .then(function () {
                                refreshConversation();
                            })
                            .catch(function () {
                                refreshConversation();
                            });
                        }
                    });

                    document.querySelectorAll('.conversation-item[data-booking-id]').forEach(function (item) {
                        item.addEventListener('click', function (e) {
                            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
                            e.preventDefault();
                            loadConversation(item.dataset.bookingId, item.href, true);
                        });
                    });

                    window.addEventListener('popstate', function () {
                        var params = new URL(window.location.href).searchParams;
                        var bookingId = params.get('booking_id') || shell.dataset.bookingId;
                        loadConversation(bookingId, null, false);
                    });

                    if (feed) feed.scrollTop = feed.scrollHeight;
                    setInterval(refreshConversation, 2000);

                    if (window.Echo && shell.dataset.bookingId) {
                        window.Echo.private('sanad.request.' + shell.dataset.bookingId)
                            .listen('.sanad.conversation.updated', refreshConversation);
                    }
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initSanadChatWorkspace);
                } else {
                    initSanadChatWorkspace();
                }
            })();
        </script>
</x-master-layout>
