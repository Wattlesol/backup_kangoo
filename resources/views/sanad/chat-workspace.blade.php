@php
    $isAr = app()->getLocale() === 'ar';
    $isCustomer = !empty($isCustomerPortal) || in_array(optional(auth()->user())->user_type, ['user', 'customer'], true);
    $messagesRoute = $isCustomer ? 'customer-portal.messages' : 'sanad.chat.workspace';
    $snapshotRoute = $isCustomer ? 'customer-portal.messages.snapshot' : 'sanad.chat.workspace.snapshot';
    $storeChatRoute = $isCustomer ? 'customer-portal.requests.messages.store' : 'sanad.requests.chat.store';
    $requestShowRoute = $isCustomer ? 'customer-portal.requests.show' : 'sanad.requests.show';
    $roleLabel = fn ($role) => Str::headline(str_replace(['demo_admin', 'handyman'], ['admin', 'employee'], (string) $role));
    $selectedId = optional($selectedBooking)->id;
    $arabicStages = [
        'submitted' => 'تم التقديم',
        'pending_review' => 'قيد المراجعة',
        'waiting_for_documents' => 'بانتظار المستندات',
        'awaiting_customer_action' => 'بانتظار إجراء العميل',
        'government_processing' => 'المعالجة الحكومية',
        'legal_review' => 'المراجعة القانونية',
        'accounting' => 'المحاسبة',
        'quality_review' => 'مراجعة الجودة',
        'awaiting_quality_review' => 'بانتظار مراجعة الجودة',
        'assigned_to_partner' => 'مُسند إلى الشريك',
        'assigned_to_employee' => 'مُسند إلى الموظف',
        'in_progress' => 'قيد التنفيذ',
        'ready_for_delivery' => 'جاهز للتسليم',
        'completed' => 'مكتمل',
        'closed' => 'مغلق',
        'cancelled' => 'ملغي',
    ];
    $arabicPriorities = ['urgent' => 'عاجلة', 'high' => 'مرتفعة', 'normal' => 'عادية', 'low' => 'منخفضة'];
    $localizedTargetRole = function ($role) use ($isAr) {
        if (!$isAr) {
            return $role;
        }

        return match (Str::snake((string) $role)) {
            'sanad' => 'كويك',
            'partner' => 'الشريك',
            'admin', 'administrator' => 'مسؤول النظام',
            'employee', 'staff', 'handyman' => 'موظف',
            'customer', 'user' => 'عميل',
            default => $role,
        };
    };
    $selectedStageKey = $selectedBooking ? ($selectedBooking->sanad_stage ?: $selectedBooking->status) : null;
    $selectedStage = $selectedStageKey
        ? ($isAr ? ($arabicStages[Str::snake($selectedStageKey)] ?? Str::headline($selectedStageKey)) : Str::headline($selectedStageKey))
        : '-';
    $requiredDocuments = $selectedBooking && $selectedBooking->service ? collect($selectedBooking->service->required_documents ?: [])->map(function ($doc) {
        $storedName = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc;
        return ['key' => is_array($doc) ? ($doc['key'] ?? Str::slug($storedName, '_')) : Str::slug($storedName, '_'), 'name' => localized_service_document_name($doc)];
    })->values() : collect();
@endphp

<x-master-layout>
    <script>
        window.sanadWorkspaceIsArabic = @json($isAr);
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
                    ? (window.sanadWorkspaceIsArabic ? 'أرسل تنبيهاً عاجلاً إلى العميل...' : 'Send an urgent Buzz to this request customer...')
                    : (isDocument
                        ? (window.sanadWorkspaceIsArabic ? 'تعليمات أو سبب طلب المستند...' : 'Instructions / Reason for document request...')
                        : (window.sanadWorkspaceIsArabic ? 'اكتب رسالة...' : 'Type a message...'));
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
                documentName.placeholder = window.sanadWorkspaceIsArabic ? 'اسم المستند' : 'Document name';
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
                alert(window.sanadWorkspaceIsArabic ? 'اختر مستنداً من الخزنة أو أرفق ملفاً أولاً.' : 'Choose a vault document or attach a file first.');
                return false;
            }

            return true;
        };

        window.syncCustomerDocumentFileName = function (input) {
            var form = input ? input.closest('.customer-document-submit') : null;
            var fileName = form ? form.querySelector('.customer-file-name') : null;
            if (!fileName) return;

            fileName.textContent = input.files && input.files[0] ? input.files[0].name : (window.sanadWorkspaceIsArabic ? 'لم يتم اختيار ملف' : 'No file selected');
        };

        window.syncChatAssignmentTarget = function (selectElem) {
            var form = selectElem ? selectElem.closest('.chat-assignment-form') : document.querySelector('.chat-assignment-form');
            if (!form) return;
            var typeSelect = form.querySelector('select[name="target_type"]');
            var targetSelect = form.querySelector('.chat-target-select');
            var selectedType = typeSelect ? typeSelect.value : '';
            if (targetSelect) {
                var hasVisibleOption = false;
                Array.prototype.forEach.call(targetSelect.options, function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }
                    var visible = option.dataset.team === selectedType;
                    option.hidden = !visible;
                    if (visible) hasVisibleOption = true;
                });
                targetSelect.style.display = 'block';
                targetSelect.required = false;
                if (targetSelect.value) {
                    var selectedOption = targetSelect.options[targetSelect.selectedIndex];
                    if (selectedOption && selectedOption.hidden) targetSelect.value = '';
                }
                targetSelect.options[0].textContent = selectedType === 'partner_team'
                    ? (hasVisibleOption
                        ? (window.sanadWorkspaceIsArabic ? 'إسناد إلى فريق الشريك بالكامل أو اختيار عضو' : 'Assign whole partner team or choose member')
                        : (window.sanadWorkspaceIsArabic ? 'لا يوجد أعضاء متاحون في فريق الشريك' : 'No partner team members available'))
                    : (hasVisibleOption
                        ? (window.sanadWorkspaceIsArabic ? 'إسناد إلى فريق كويك بالكامل أو اختيار عضو' : 'Assign whole Quick team or choose member')
                        : (window.sanadWorkspaceIsArabic ? 'لا يوجد أعضاء متاحون في فريق كويك' : 'No Quick team members available'));
            }
        };
    </script>
    <div class="sanad-inbox-shell {{ $isCustomer ? 'is-customer-chat' : '' }}" data-booking-id="{{ $selectedId }}" data-snapshot-url="{{ route($snapshotRoute) }}">
        <aside class="sanad-inbox-panel">
            <div class="inbox-top">
                <div>
                    <h4>{{ app()->getLocale() === "ar" ? "صندوق الوارد الموحد" : "Unified Inbox" }}</h4>
                    <span>{{ app()->getLocale() === "ar" ? "المحادثات، تنبيهات بز، المستندات، ومراجعة الذكاء الاصطناعي" : "Chat, Buzz, documents, and AI review" }}</span>
                </div>
                <a class="icon-btn" href="{{ route($messagesRoute) }}" title="{{ $isAr ? 'تحديث صندوق الوارد' : 'Refresh inbox' }}"><i class="fas fa-sync-alt"></i></a>
            </div>
            <form method="GET" action="{{ route($messagesRoute) }}" class="inbox-search">
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ $isAr ? 'البحث في المحادثات' : 'Search conversations' }}">
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
                    @php
                        $localizedInboxLabel = $isAr ? match ($state) {
                            '' => 'الكل',
                            'open_chat' => 'غير مقروء',
                            'unread_buzz' => 'التنبيهات',
                            'pending_documents' => 'المستندات',
                            'ai_escalations' => 'مراجعة الذكاء الاصطناعي',
                            default => $label,
                        } : $label;
                    @endphp
                    <a class="{{ request('action_state') === $state ? 'active' : '' }}" href="{{ route($messagesRoute, array_filter(['action_state' => $state, 'search' => request('search')], fn ($value) => $value !== null && $value !== '')) }}">{{ $localizedInboxLabel }}</a>
                @endforeach
            </nav>
            <div class="conversation-list">
                @forelse($conversations as $conversation)
                    @php
                        $openBuzz = $conversation->sanadBuzzAlerts
                            ->filter(function ($buzz) use ($isCustomer) {
                                if ($buzz->action_type !== 'chat_assignment_accept') {
                                    return true;
                                }

                                return !$isCustomer && (int) $buzz->recipient_id === (int) auth()->id();
                            })
                            ->where('status', 'unread')
                            ->count();
                        $pendingDocs = $conversation->sanadDocumentRequests->whereIn('status', ['pending', 'submitted', 'replacement_requested'])->count();
                        $aiReviews = $isAdmin ? $conversation->sanadAiInteractions->where('requires_escalation', true)->count() : 0;
                        $latestThread = $conversation->sanadChatThreads->sortByDesc('last_message_at')->first();
                        $lastMessage = $latestThread && $latestThread->relationLoaded('messages')
                            ? optional($latestThread->messages->last())->message
                            : null;
                        $conversationService = optional($conversation->service);
                        $conversationServiceName = $isAr
                            ? ($conversationService->name_ar ?: $conversationService->name_en ?: $conversationService->name ?: 'لا توجد خدمة')
                            : ($conversationService->name_en ?: $conversationService->name ?: 'No service');
                        $conversationStageKey = Str::snake($conversation->sanad_stage ?: $conversation->status);
                        $conversationStage = $isAr
                            ? ($arabicStages[$conversationStageKey] ?? Str::headline($conversationStageKey))
                            : Str::headline($conversationStageKey);
                        $conversationPriorityKey = Str::snake($conversation->sanad_priority ?: 'normal');
                        $conversationPriority = $isAr
                            ? ($arabicPriorities[$conversationPriorityKey] ?? Str::headline($conversationPriorityKey))
                            : Str::headline($conversationPriorityKey);
                    @endphp
                    <a class="conversation-item {{ $selectedId === $conversation->id ? 'active' : '' }}" data-booking-id="{{ $conversation->id }}" href="{{ route($messagesRoute, array_filter(['booking_id' => $conversation->id, 'action_state' => request('action_state'), 'search' => request('search')])) }}">
                        <div class="avatar">{{ Str::upper(Str::substr(optional($conversation->customer)->display_name ?: 'C', 0, 1)) }}</div>
                        <div class="conversation-copy">
                            <div class="conversation-line"><strong>{{ optional($conversation->customer)->display_name ?: ($isAr ? 'عميل' : 'Customer') }}</strong><time>{{ optional($conversation->updated_at)->diffForHumans() }}</time></div>
                            <div class="conversation-ref">{{ $conversation->quick_reference }} · {{ $conversationServiceName }}</div>
                            <p>{{ Str::limit($lastMessage ?: $conversationStage, 74) }}</p>
                            <div class="chips">
                                @if($conversation->sanad_priority)<span>{{ $conversationPriority }}</span>@endif
                                @if($openBuzz)<span class="danger">{{ $openBuzz }} {{ $isAr ? 'تنبيه' : 'Buzz' }}</span>@endif
                                @if($pendingDocs)<span class="info">{{ $pendingDocs }} {{ $isAr ? 'مستندات' : 'Docs' }}</span>@endif
                                @if($aiReviews)<span class="ai">{{ $isAr ? 'مراجعة الذكاء الاصطناعي' : 'AI Review' }}</span>@endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="empty-panel">{{ $isAr ? 'لم يتم العثور على محادثات.' : 'No conversations found.' }}</div>
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
                @php
                    $aiEnabled = $selectedBooking->ai_first_responder_enabled !== false;
                    $chatOwnerType = $selectedBooking->chat_owner_type ?: 'ai';
                    $chatOwnerLabel = match ($chatOwnerType) {
                        'sanad_team' => $isAr ? 'فريق كويك' : 'Quick Team',
                        'partner_team' => $isAr ? 'فريق الشريك' : 'Partner Team',
                        'user' => optional(\App\Models\User::find($selectedBooking->chat_owner_user_id))->display_name ?: optional(\App\Models\User::find($selectedBooking->chat_owner_user_id))->email ?: ($isAr ? 'عضو الفريق المُسند إليه' : 'Assigned Team Member'),
                        default => $isAr ? 'المستجيب الآلي الأول' : 'AI First Responder',
                    };
                    $chatTargets = !$isCustomer ? app(\App\Http\Controllers\SanadWebController::class)->assignableChatTargets($selectedBooking) : collect();
                    $selectedChatTarget = $chatTargets->firstWhere('id', (int) $selectedBooking->chat_owner_user_id);
                    $assignmentFormType = $chatOwnerType === 'user'
                        ? ($selectedChatTarget['team'] ?? ($selectedBooking->provider_id ? 'partner_team' : 'sanad_team'))
                        : ($chatOwnerType === 'partner_team' ? 'partner_team' : 'sanad_team');
                    $directMessageLock = !$isCustomer
                        ? app(\App\Http\Controllers\SanadWebController::class)->directMessageLock($selectedBooking, auth()->user())
                        : ['locked' => false, 'message' => null];
                @endphp
                <header class="chat-header">
                    <div class="avatar large">{{ Str::upper(Str::substr(optional($selectedBooking->customer)->display_name ?: 'C', 0, 1)) }}</div>
                    <div>
                        <h4>{{ optional($selectedBooking->customer)->display_name ?: optional($selectedBooking->customer)->email ?: ($isAr ? 'عميل' : 'Customer') }}</h4>
                        <span>{{ $selectedBooking->quick_reference }} · {{ $isAr ? (optional($selectedBooking->service)->name_ar ?: optional($selectedBooking->service)->name_en ?: optional($selectedBooking->service)->name ?: 'لا توجد خدمة') : (optional($selectedBooking->service)->name_en ?: optional($selectedBooking->service)->name ?: 'No service') }}</span>
                    </div>
                    <div class="chat-header-actions">
                        <span class="status-pill">{{ $selectedStage }}</span>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route($requestShowRoute, $selectedBooking->id) }}">{{ $isAr ? 'الطلب' : 'Request' }}</a>
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
                                        <small>{{ $isAr ? 'لم يتم الرد بعد.' : 'No reply yet.' }}</small>
                                    @endforelse
                                </div>
                                @if(!$isCustomer && $buzz->action_type === 'chat_assignment_accept' && $buzz->status === 'unread' && (int) $buzz->recipient_id === (int) auth()->id())
                                    <form method="POST" action="{{ route('sanad.requests.buzz.acknowledge', [$selectedBooking->id, $buzz->id]) }}" class="buzz-accept-form mt-2">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i> Accept</button>
                                    </form>
                                @endif
                                @if($isCustomer && $buzz->replies->isEmpty())
                                    <form method="POST" action="{{ route('customer-portal.requests.buzz.reply', [$selectedBooking->id, $buzz->id]) }}" class="buzz-reply-form mt-2 d-flex gap-2 align-items-center">
                                        @csrf
                                        <input name="message" class="form-control form-control-sm" placeholder="{{ $isAr ? 'اكتب ردك على التنبيه...' : 'Reply to this Buzz...' }}" maxlength="3000" required>
                                        <button type="submit" class="btn btn-sm btn-danger text-nowrap"><i class="fas fa-reply mr-1"></i>{{ $isAr ? 'رد' : 'Reply' }}</button>
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
                            @if($message->message_type === 'ai_handover_prompt')
                                <article class="event-card ai-handover" data-event-type="ai-handover" data-message-id="{{ $message->id }}">
                                    <div class="event-head"><strong><i class="fas fa-robot text-primary mr-1"></i> Quick AI</strong><span>{{ Str::headline(optional($message->aiInteraction)->status ?: 'Handover Required') }}</span></div>
                                    <p>{{ $message->message }}</p>
                                    @if($isCustomer && optional($message->aiInteraction)->status === 'handover_required')
                                        <div class="action-row d-flex gap-2">
                                            <form method="POST" action="{{ route('customer-portal.ai.handover', $message->ai_interaction_id) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="yes">
                                                <button type="submit" class="btn btn-sm btn-primary">Yes</button>
                                            </form>
                                            <form method="POST" action="{{ route('customer-portal.ai.handover', $message->ai_interaction_id) }}">
                                                @csrf
                                                <input type="hidden" name="decision" value="no">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">No</button>
                                            </form>
                                        </div>
                                    @elseif(in_array(optional($message->aiInteraction)->status, ['handover_accepted', 'handover_declined'], true))
                                        <small class="text-muted">{{ optional($message->aiInteraction)->status === 'handover_accepted' ? 'Quick team has been notified.' : 'No handover requested.' }}</small>
                                    @endif
                                </article>
                            @else
                            <article class="message-row {{ in_array($message->sender_role, ['user', 'customer'], true) ? 'customer' : 'team' }} {{ $message->message_type === 'system_note' ? 'system' : '' }}" data-message-id="{{ $message->id }}">
                                <div class="message-bubble">
                                    <div class="message-meta"><strong>{{ $message->sender_role === 'system' ? 'Quick AI' : (optional($message->sender)->display_name ?: $roleLabel($message->sender_role)) }}</strong><span>{{ optional($message->created_at)->format('Y-m-d H:i') }}</span></div>
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
                        @endif
                    @empty
                        <div class="empty-chat py-5 text-center text-muted">
                            <i class="far fa-comments fa-3x mb-3 text-muted"></i>
                            <h5>{{ $isAr ? 'لا يوجد نشاط حتى الآن' : 'No activity yet' }}</h5>
                            <p>{{ $isAr ? 'ابدأ المحادثة بكتابة رسالة أدناه.' : 'Start a conversation by typing a message below.' }}</p>
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

                <form method="POST" enctype="multipart/form-data" action="{{ route($storeChatRoute, $selectedBooking->id) }}" class="chat-composer {{ (!$isCustomer && $directMessageLock['locked']) ? 'ai-blocks-message' : '' }}" data-ai-enabled="{{ $aiEnabled ? '1' : '0' }}" data-is-customer="{{ $isCustomer ? '1' : '0' }}" data-direct-message-locked="{{ $directMessageLock['locked'] ? '1' : '0' }}" data-direct-message-lock-message="{{ $isAr ? 'عطّل المستجيب الآلي الأول لإرسال رسالة مباشرة.' : $directMessageLock['message'] }}">
                    @csrf
                    <input type="hidden" name="thread_type" value="shared">
                    <div class="document-fields mb-2" dir="rtl">
                        <div class="document-request-grid">
                            <label class="document-field">
                                <span>{{ $isAr ? 'المستند' : 'Document' }}</span>
                                <select name="document_preset" class="document-preset" title="{{ $isAr ? 'المستند المطلوب' : 'Document to request' }}" onchange="window.syncSanadDocumentPreset(this)">
                            @foreach($requiredDocuments as $doc)
                                <option value="{{ $doc['key'] }}" data-key="{{ $doc['key'] }}" data-name="{{ $doc['name'] }}">{{ $doc['name'] }}</option>
                            @endforeach
                                    <option value="custom" data-key="" data-name="">{{ $isAr ? 'مستند مخصص' : 'Custom document' }}</option>
                                </select>
                            </label>
                            <input type="hidden" name="document_key">
                            <label class="document-field document-custom-field">
                                <span>{{ $isAr ? 'اسم المستند المخصص' : 'Custom document name' }}</span>
                                <input name="document_name" id="composer-document-name" placeholder="{{ $isAr ? 'اسم المستند' : 'Document name' }}">
                            </label>
                            <label class="document-field">
                                <span>{{ $isAr ? 'موعد التسليم' : 'Submit by' }}</span>
                                <input name="due_at" type="date" class="composer-due-at" title="{{ $isAr ? 'تاريخ الاستحقاق' : 'Due Date' }}">
                            </label>
                            <input type="hidden" name="requested_from" value="customer">
                        </div>
                    </div>

                    <div class="composer-main-bar d-flex align-items-end gap-2 w-100">
                        @if($canCreateBuzz || $canRequestDocuments)
                            <select name="delivery_mode" class="composer-mode" title="{{ $isAr ? 'نوع الإرسال' : 'Delivery type' }}" onchange="window.syncSanadComposerMode(this)">
                                <option value="message">{{ $isAr ? 'رسالة' : 'Message' }}</option>
                                @if($canCreateBuzz)<option value="buzz">{{ $isAr ? 'تنبيه' : 'Buzz' }}</option>@endif
                                @if($canRequestDocuments)<option value="document">{{ $isAr ? 'مستند' : 'Document' }}</option>@endif
                            </select>
                        @else
                            <input type="hidden" name="delivery_mode" value="message">
                        @endif
                        <select name="buzz_priority" class="composer-priority" title="{{ $isAr ? 'أولوية التنبيه' : 'Buzz priority' }}">
                            <option value="urgent">{{ $isAr ? 'عاجلة' : 'Urgent' }}</option>
                            <option value="high">{{ $isAr ? 'مرتفعة' : 'High' }}</option>
                            <option value="normal">{{ $isAr ? 'عادية' : 'Normal' }}</option>
                            <option value="low">{{ $isAr ? 'منخفضة' : 'Low' }}</option>
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
                        @if(!$isCustomer)
                            <div class="composer-ai-warning" role="tooltip">{{ $isAr ? 'عطّل المستجيب الآلي الأول لإرسال رسالة مباشرة. تظل التنبيهات وطلبات المستندات متاحة.' : (($directMessageLock['message'] ?: 'Disable AI first responder to send a direct message.') . ' Buzz and document requests remain available.') }}</div>
                        @endif
                        <div id="composer-attachment-badge" class="badge badge-light border text-primary p-2 mb-1 w-100 d-flex justify-content-between align-items-center" style="display: none !important; font-size: 12px;">
                            <span><i class="fas fa-paperclip mr-1"></i> <span id="composer-attachment-name"></span></span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 ml-2" id="btn-remove-attachment" onclick="removeSanadAttachment(event)" style="text-decoration:none;">&times;</button>
                        </div>
                        <textarea name="message" rows="1" placeholder="{{ $isAr ? 'اكتب رسالة...' : 'Type a message...' }}"></textarea>
                    </div>

                    <button class="send-btn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </form>
            @else
                <div class="empty-chat">Select a conversation to start.</div>
            @endif
        </main>

        @if(!$isCustomer)
            <aside class="sanad-context-panel">
                @if($selectedBooking)
                    <section class="context-tab active" id="tab-request">
                        <h5>{{ $isAr ? 'سياق الطلب' : 'Request Context' }}</h5>
                        <div class="context-list">
                            <span>{{ $isAr ? 'المرحلة' : 'Stage' }} <strong>{{ $selectedStage }}</strong></span>
                            <span>{{ $isAr ? 'الأولوية' : 'Priority' }} <strong>{{ $isAr ? ($arabicPriorities[Str::snake($selectedBooking->sanad_priority ?: 'normal')] ?? Str::headline($selectedBooking->sanad_priority ?: 'normal')) : Str::headline($selectedBooking->sanad_priority ?: 'normal') }}</strong></span>
                            <span>{{ $isAr ? 'مهلة الخدمة' : 'SLA' }} <strong>{{ optional($selectedBooking->sla_due_at)->format('Y-m-d H:i') ?: '-' }}</strong></span>
                            <span>{{ $isAr ? 'الشريك' : 'Partner' }} <strong>{{ optional($selectedBooking->provider)->display_name ?: '-' }}</strong></span>
                            <span>{{ $isAr ? 'الذكاء الاصطناعي' : 'AI' }} <strong data-ai-state-label>{{ $aiEnabled ? ($isAr ? 'المستجيب الآلي الأول مفعّل' : 'AI First Responder On') : ($isAr ? 'استلام يدوي' : 'Manual Takeover') }}</strong></span>
                            <span>{{ $isAr ? 'الإسناد' : 'Assignment' }} <strong data-chat-assignment-label>{{ $chatOwnerLabel }}</strong></span>
                        </div>
                        <div class="chat-control-panel mt-3" data-ai-toggle-url="{{ route('sanad.requests.ai-first-responder', $selectedBooking->id) }}" data-chat-assignment-url="{{ route('sanad.requests.chat-assignment', $selectedBooking->id) }}">
                            <form class="ai-toggle-form mb-3">
                                @csrf
                                <input type="hidden" name="enabled" value="{{ $aiEnabled ? 0 : 1 }}">
                                <button type="submit" class="btn btn-sm {{ $aiEnabled ? 'btn-outline-danger' : 'btn-outline-success' }} w-100">
                                    <i class="fas {{ $aiEnabled ? 'fa-user-shield' : 'fa-robot' }} mr-1"></i>
                                    {{ $aiEnabled ? ($isAr ? 'تعطيل الذكاء الاصطناعي / استلام يدوي' : 'Disable AI / Manual Takeover') : ($isAr ? 'إعادة تفعيل المستجيب الآلي الأول' : 'Re-enable AI First Responder') }}
                                </button>
                            </form>
                            <form class="chat-assignment-form">
                                @csrf
                                <label class="small text-muted font-weight-bold mb-1">{{ $isAr ? 'الإسناد' : 'Assignment' }}</label>
                                <select name="target_type" class="form-control form-control-sm mb-2" onchange="window.syncChatAssignmentTarget(this)">
                                    <option value="sanad_team" {{ $assignmentFormType === 'sanad_team' ? 'selected' : '' }}>{{ $isAr ? 'إسناد إلى فريق كويك' : 'Assign to Quick Team' }}</option>
                                    <option value="partner_team" {{ $assignmentFormType === 'partner_team' ? 'selected' : '' }}>{{ $isAr ? 'إسناد إلى فريق الشريك' : 'Assign to Partner Team' }}</option>
                                </select>
                                <select name="target_user_id" class="form-control form-control-sm mb-2 chat-target-select">
                                    <option value="">{{ $isAr ? 'اختر عضو الفريق' : 'Select team member' }}</option>
                                    @foreach($chatTargets as $target)
                                        <option value="{{ $target['id'] }}" data-team="{{ $target['team'] ?? '' }}" {{ (int) $selectedBooking->chat_owner_user_id === (int) $target['id'] ? 'selected' : '' }}>{{ $target['name'] }} · {{ $localizedTargetRole($target['role']) }}</option>
                                    @endforeach
                                </select>
                                <input name="note" class="form-control form-control-sm mb-2" placeholder="{{ $isAr ? 'ملاحظة الإسناد (اختياري)' : 'Assignment note (optional)' }}">
                                <button type="submit" class="btn btn-sm btn-primary w-100">{{ $isAr ? 'حفظ الإسناد' : 'Save Assignment' }}</button>
                            </form>
                        </div>
                    </section>
                @endif
            </aside>
        @endif
    </div>

        <style>
            .content-page { min-height: 100vh; }
            .sanad-inbox-shell { height: calc(100vh - 90px); display: grid; grid-template-columns: 340px minmax(0, 1fr) 340px; overflow: hidden !important; background: #f5f7fb; border-top: 1px solid #e5e9f2; }
            .sanad-inbox-shell.is-customer-chat { grid-template-columns: 340px minmax(0, 1fr); }
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
            .message-row.pending .message-bubble { opacity: .82; }
            .message-row.failed .message-bubble { border-color: #fecaca; background: #fff7f7; color: #991b1b; }
            .message-bubble { max-width: min(680px, 78%); border-radius: 16px; padding: 10px 12px; background: #fff; border: 1px solid #e5e9f2; box-shadow: 0 4px 16px rgba(15,23,42,.04); }
            .message-row.team .message-bubble { background: #4f46e5; color: #fff; border-color: #4f46e5; }
            .message-meta { display: flex; justify-content: space-between; gap: 14px; font-size: 12px; margin-bottom: 4px; opacity: .82; }
            .message-bubble p, .event-card p { margin: 0; white-space: pre-wrap; }
            .message-links { display: flex; gap: 6px; margin-top: 6px; font-size: 11px; opacity: .78; }
            .event-card { align-self: center; width: min(620px, 88%); background: #fff; border: 1px solid #e5e9f2; border-left: 3px solid #64748b; border-radius: 10px; padding: 9px 11px; font-size: 13px; }
            .event-card.buzz { border-left-color: #ef4444; }
            .event-card.buzz > p { padding: 8px 10px; border-radius: 8px; background: #fff7ed; color: #7c2d12; font-weight: 600; line-height: 1.55; }
            .buzz-reply-form input { min-width: 0; }
            .event-card.document { border-left-color: #0ea5e9; }
            .event-card.ai-handover { border-left-color: #6366f1; background: #f8faff; }
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
            .chat-composer textarea:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; border-color: #d8dee8; }
            .send-btn { width: 44px; height: 38px; border-radius: 12px; background: #4f46e5; color: #fff; flex-shrink: 0; border: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; }
            .context-tab h5 { font-weight: 800; margin-bottom: 12px; }
            .context-list { display: grid; gap: 8px; }
            .context-list span, .learning-card { display: flex; justify-content: space-between; gap: 10px; padding: 10px; border: 1px solid #e5e9f2; border-radius: 10px; background: #fff; }
            .chat-control-panel { border-top: 1px solid #edf1f7; padding-top: 12px; }
            .chat-target-select { display: none; }
            .composer-ai-warning { position: absolute; inset-inline: 0; bottom: calc(100% + 8px); z-index: 20; color: #92400e; font-size: 12px; line-height: 1.45; padding: 8px 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; box-shadow: 0 8px 20px rgba(15, 23, 42, .12); opacity: 0; visibility: hidden; transform: translateY(4px); pointer-events: none; transition: opacity .15s ease, transform .15s ease, visibility .15s ease; }
            .chat-composer.ai-blocks-message[data-ai-enabled="1"]:not(.is-buzz):not(.is-document) .composer-text-wrapper:hover .composer-ai-warning,
            .chat-composer.ai-blocks-message[data-ai-enabled="1"]:not(.is-buzz):not(.is-document) .composer-text-wrapper:focus-within .composer-ai-warning { opacity: 1; visibility: visible; transform: translateY(0); }
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
                    var controlPanel = document.querySelector('.chat-control-panel');
                    var isArabic = window.sanadWorkspaceIsArabic === true;
                    var arabicStages = {
                        submitted: 'تم التقديم', pending_review: 'قيد المراجعة', waiting_for_documents: 'بانتظار المستندات',
                        awaiting_customer_action: 'بانتظار إجراء العميل', government_processing: 'المعالجة الحكومية',
                        legal_review: 'المراجعة القانونية', accounting: 'المحاسبة', quality_review: 'مراجعة الجودة',
                        awaiting_quality_review: 'بانتظار مراجعة الجودة', assigned_to_partner: 'مُسند إلى الشريك',
                        assigned_to_employee: 'مُسند إلى الموظف', in_progress: 'قيد التنفيذ', ready_for_delivery: 'جاهز للتسليم',
                        completed: 'مكتمل', closed: 'مغلق', cancelled: 'ملغي'
                    };
                    var arabicPriorities = {urgent: 'عاجلة', high: 'مرتفعة', normal: 'عادية', low: 'منخفضة'};

                    function normalizedLabelKey(value) {
                        return String(value || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
                    }

                    function localizedStage(value) {
                        return isArabic ? (arabicStages[normalizedLabelKey(value)] || value || '-') : (value || '-');
                    }

                    function localizedPriority(value) {
                        return isArabic ? (arabicPriorities[normalizedLabelKey(value)] || value || 'عادية') : (value || 'Normal');
                    }

                    function localizedAssignment(value) {
                        if (!isArabic) return value || 'AI First Responder';
                        var labels = {
                            ai_first_responder: 'المستجيب الآلي الأول', sanad_team: 'فريق كويك', partner_team: 'فريق الشريك',
                            assigned_team_member: 'عضو الفريق المُسند إليه'
                        };
                        return labels[normalizedLabelKey(value)] || value || 'المستجيب الآلي الأول';
                    }

                    function localizedTargetRole(value) {
                        if (!isArabic) return value || 'Staff';
                        var labels = {sanad: 'كويك', partner: 'الشريك', admin: 'مسؤول النظام', administrator: 'مسؤول النظام', employee: 'موظف', staff: 'موظف', handyman: 'موظف', customer: 'عميل', user: 'عميل'};
                        return labels[normalizedLabelKey(value)] || value || 'موظف';
                    }

                    function isControlPanelActive() {
                        return !!(controlPanel && document.activeElement && controlPanel.contains(document.activeElement));
                    }

                    function syncComposerMode() {
                        if (!composer || !modeSelect) return;
                        var isBuzz = modeSelect.value === 'buzz';
                        var isDocument = modeSelect.value === 'document';
                        composer.classList.toggle('is-buzz', isBuzz);
                        composer.classList.toggle('is-document', isDocument);
                        if (composerText) {
                            composerText.placeholder = isBuzz
                                ? (isArabic ? 'أرسل تنبيهاً عاجلاً إلى العميل...' : 'Send an urgent Buzz to this request customer...')
                                : (isDocument
                                    ? (isArabic ? 'السبب والتعليمات الموجهة إلى العميل...' : 'Reason and instructions for the customer...')
                                    : (isArabic ? 'اكتب رسالة...' : 'Type a message...'));
                        }
                        if (documentName) {
                            var isCustom = !documentPreset || documentPreset.value === 'custom';
                            documentName.required = isDocument && isCustom;
                        }
                        syncAiComposerLock();
                    }

                    function syncAiComposerLock() {
                        if (!composer || !composerText) return;
                        var deliveryMode = modeSelect ? modeSelect.value : 'message';
                        var lockMessage = composer.dataset.directMessageLockMessage || (isArabic ? 'عطّل المستجيب الآلي الأول لإرسال رسالة مباشرة.' : 'Disable AI first responder to send a direct message.');
                        var shouldLock = composer.dataset.isCustomer !== '1' && composer.dataset.directMessageLocked === '1' && deliveryMode === 'message';
                        var normalPlaceholder = deliveryMode === 'buzz'
                            ? (isArabic ? 'أرسل تنبيهاً عاجلاً إلى العميل...' : 'Send an urgent Buzz to this request customer...')
                            : (deliveryMode === 'document'
                                ? (isArabic ? 'السبب والتعليمات الموجهة إلى العميل...' : 'Reason and instructions for the customer...')
                                : (isArabic ? 'اكتب رسالة...' : 'Type a message...'));
                        composerText.disabled = shouldLock;
                        composerText.readOnly = shouldLock;
                        composerText.placeholder = shouldLock ? lockMessage : normalPlaceholder;
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
                            documentName.placeholder = isArabic ? 'اسم المستند' : 'Document name';
                            documentName.required = composer.classList.contains('is-document');
                        }
                    }

                    if (documentPreset) {
                        documentPreset.addEventListener('change', syncDocumentPreset);
                        syncDocumentPreset();
                    }
                    document.querySelectorAll('.chat-assignment-form select[name="target_type"]').forEach(function (select) {
                        window.syncChatAssignmentTarget(select);
                    });
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

                    function showSnapshotError(message) {
                        if (!feed) return;
                        var existing = feed.querySelector('.chat-refresh-error');
                        if (existing) existing.remove();
                        var error = document.createElement('div');
                        error.className = 'chat-refresh-error alert alert-warning py-2 px-3 mb-2';
                        error.style.fontSize = '13px';
                        error.textContent = message || 'Unable to load this conversation. Please try again.';
                        feed.prepend(error);
                    }

                    function updateDocumentOptions(documents) {
                        if (!documentPreset) return;
                        var selected = documentPreset.value;
                        var optionsHtml = '';
                        (documents || []).forEach(function (doc) {
                            optionsHtml += '<option value="' + escapeHtml(doc.key) + '" data-key="' + escapeHtml(doc.key) + '" data-name="' + escapeHtml(doc.name) + '">' + escapeHtml(doc.name) + '</option>';
                        });
                        optionsHtml += '<option value="custom" data-key="" data-name="">' + (isArabic ? 'مستند مخصص' : 'Custom document') + '</option>';
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
                            if (subtitle) subtitle.textContent = (request.reference || ('#' + request.id)) + ' · ' + (request.service || (isArabic ? 'لا توجد خدمة' : 'No service'));
                            if (status) status.textContent = localizedStage(request.stage);
                            if (requestLink && request.request_url) requestLink.href = request.request_url;
                        }

                        if (contextList) {
                            contextList.innerHTML =
                                '<span>' + (isArabic ? 'المرحلة' : 'Stage') + ' <strong>' + escapeHtml(localizedStage(request.stage)) + '</strong></span>' +
                                '<span>' + (isArabic ? 'الأولوية' : 'Priority') + ' <strong>' + escapeHtml(localizedPriority(request.priority)) + '</strong></span>' +
                                '<span>' + (isArabic ? 'مهلة الخدمة' : 'SLA') + ' <strong>' + escapeHtml(request.sla || '-') + '</strong></span>' +
                                '<span>' + (isArabic ? 'الشريك' : 'Partner') + ' <strong>' + escapeHtml(request.partner || '-') + '</strong></span>' +
                                '<span>' + (isArabic ? 'الذكاء الاصطناعي' : 'AI') + ' <strong data-ai-state-label>' + escapeHtml(request.ai_first_responder_enabled === false ? (isArabic ? 'استلام يدوي' : 'Manual Takeover') : (isArabic ? 'المستجيب الآلي الأول مفعّل' : 'AI First Responder On')) + '</strong></span>' +
                                '<span>' + (isArabic ? 'الإسناد' : 'Assignment') + ' <strong data-chat-assignment-label>' + escapeHtml(localizedAssignment(request.chat_assignment_label)) + '</strong></span>';
                        }

                        if (composer && snapshot.composer) {
                            if (snapshot.composer.store_url) composer.action = snapshot.composer.store_url;
                            composer.dataset.aiEnabled = request.ai_first_responder_enabled === false ? '0' : '1';
                            composer.dataset.directMessageLocked = snapshot.composer.direct_message_locked ? '1' : '0';
                            composer.dataset.directMessageLockMessage = snapshot.composer.direct_message_lock_message || '';
                            composer.classList.toggle('ai-blocks-message', composer.dataset.isCustomer !== '1' && composer.dataset.directMessageLocked === '1');
                            var composerWarning = composer.querySelector('.composer-ai-warning');
                            if (composerWarning) {
                                composerWarning.textContent = isArabic
                                    ? 'عطّل المستجيب الآلي الأول لإرسال رسالة مباشرة. تظل التنبيهات وطلبات المستندات متاحة.'
                                    : (snapshot.composer.direct_message_lock_message || 'Disable AI first responder to send a direct message.') + ' Buzz and document requests remain available.';
                            }
                            syncAiComposerLock();
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

                        if (controlPanel && snapshot.composer) {
                            controlPanel.dataset.aiToggleUrl = snapshot.composer.ai_toggle_url || '';
                            controlPanel.dataset.chatAssignmentUrl = snapshot.composer.chat_assignment_url || '';
                            if (!isControlPanelActive()) {
                                var toggleForm = controlPanel.querySelector('.ai-toggle-form');
                                if (toggleForm) {
                                    var enabledInput = toggleForm.querySelector('[name="enabled"]');
                                    var toggleBtn = toggleForm.querySelector('button');
                                    var aiOn = request.ai_first_responder_enabled !== false;
                                    if (enabledInput) enabledInput.value = aiOn ? '0' : '1';
                                    if (toggleBtn) {
                                        toggleBtn.className = 'btn btn-sm ' + (aiOn ? 'btn-outline-danger' : 'btn-outline-success') + ' w-100';
                                        toggleBtn.innerHTML = '<i class="fas ' + (aiOn ? 'fa-user-shield' : 'fa-robot') + ' mr-1"></i>' + (aiOn
                                            ? (isArabic ? 'تعطيل الذكاء الاصطناعي / استلام يدوي' : 'Disable AI / Manual Takeover')
                                            : (isArabic ? 'إعادة تفعيل المستجيب الآلي الأول' : 'Re-enable AI First Responder'));
                                    }
                                }
                                var typeSelect = controlPanel.querySelector('.chat-assignment-form select[name="target_type"]');
                                if (typeSelect) {
                                    var targetType = request.chat_owner_type || 'sanad_team';
                                    if (targetType === 'ai' || targetType === 'user') targetType = request.chat_owner_team || 'sanad_team';
                                    if (!Array.prototype.some.call(typeSelect.options, function (option) { return option.value === targetType; })) {
                                        targetType = 'sanad_team';
                                    }
                                    typeSelect.value = targetType;
                                }
                                var targetSelect = controlPanel.querySelector('.chat-target-select');
                                if (targetSelect) {
                                    var selectedUserId = String(request.chat_owner_user_id || '');
                                    targetSelect.innerHTML = '<option value="">' + (isArabic ? 'اختر عضو الفريق' : 'Select team member') + '</option>';
                                    (snapshot.composer.assignable_chat_targets || []).forEach(function (target) {
                                    var option = document.createElement('option');
                                    option.value = String(target.id || '');
                                    option.textContent = (target.name || (isArabic ? 'عضو الفريق' : 'Team member')) + ' · ' + localizedTargetRole(target.role);
                                    option.dataset.team = target.team || '';
                                    option.selected = selectedUserId && selectedUserId === option.value;
                                    targetSelect.appendChild(option);
                                });
                                }
                                if (typeSelect) {
                                    window.syncChatAssignmentTarget(typeSelect);
                                }
                            }
                        }
                    }

                    function handoverPromptHtml(item) {
                        var status = item.handover_status || 'handover_required';
                        var html = '<article class="event-card ai-handover" data-event-type="ai-handover" data-message-id="' + escapeHtml(item.id || '') + '">' +
                            '<div class="event-head"><strong><i class="fas fa-robot text-primary mr-1"></i> Quick AI</strong><span>' + escapeHtml(status.replace(/_/g, ' ')) + '</span></div>' +
                            '<p>' + escapeHtml(item.message || "I don't have enough information on this. I can connect you with a Quick agent if you want.") + '</p>';
                        if (composer && composer.dataset.isCustomer === '1' && status === 'handover_required' && item.ai_interaction_id) {
                            html += '<div class="action-row d-flex gap-2">' +
                                '<form class="ai-handover-form" data-interaction-id="' + item.ai_interaction_id + '"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="decision" value="yes"><button type="submit" class="btn btn-sm btn-primary">Yes</button></form>' +
                                '<form class="ai-handover-form" data-interaction-id="' + item.ai_interaction_id + '"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="decision" value="no"><button type="submit" class="btn btn-sm btn-outline-secondary">No</button></form>' +
                                '</div>';
                        } else if (status === 'handover_accepted') {
                            html += '<small class="text-muted">Quick team has been notified.</small>';
                        } else if (status === 'handover_declined') {
                            html += '<small class="text-muted">No handover requested.</small>';
                        }
                        html += '</article>';
                        return html;
                    }

                    function render(snapshot) {
                        if (!feed || !snapshot.status) return;
                        updateRequestChrome(snapshot);
                        var html = '';
                        var items = snapshot.timeline || [];
                        if (items && items.length) {
                            items.forEach(function (item) {
                                if (item.type === 'buzz') {
                                    var buzzNumericId = String(item.id || '').replace('buzz-', '');
                                    var highlightedBuzz = new URL(window.location.href).searchParams.get('buzz_id');
                                    html += '<article id="' + escapeHtml(item.id || '') + '" class="event-card buzz' + (highlightedBuzz === buzzNumericId ? ' highlight' : '') + '" data-event-type="buzz"><div class="event-head"><strong>' + escapeHtml(item.priority) + ' Buzz</strong><span>Target: ' + escapeHtml(item.recipient_role) + ' · ' + escapeHtml(item.status) + '</span></div><p>' + escapeHtml(item.message) + '</p><div class="event-replies">';
                                    if (item.replies && item.replies.length) {
                                        item.replies.forEach(function (reply) { html += '<div><strong>' + escapeHtml(reply.sender) + '</strong>' + escapeHtml(reply.message) + '</div>'; });
                                    } else {
                                        html += '<small>' + (isArabic ? 'لم يتم الرد بعد.' : 'No reply yet.') + '</small>';
                                    }
                                    html += '</div>';
                                    if (item.can_accept && item.accept_url) {
                                        html += '<form class="buzz-accept-form mt-2" action="' + escapeHtml(item.accept_url) + '"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i> Accept</button></form>';
                                    }
                                    if (composer && composer.dataset.isCustomer === '1' && item.can_reply && item.reply_url) {
                                        html += '<form method="POST" action="' + escapeHtml(item.reply_url) + '" class="buzz-reply-form mt-2 d-flex gap-2 align-items-center"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input name="message" class="form-control form-control-sm" maxlength="3000" placeholder="' + (isArabic ? 'اكتب ردك على التنبيه...' : 'Reply to this Buzz...') + '" required><button type="submit" class="btn btn-sm btn-danger text-nowrap"><i class="fas fa-reply mr-1"></i>' + (isArabic ? 'رد' : 'Reply') + '</button></form>';
                                    }
                                    html += '</article>';
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
                                    if (item.message_type === 'ai_handover_prompt') {
                                        html += handoverPromptHtml(item);
                                    } else {
                                        var side = ['user', 'customer'].indexOf(item.sender_role) >= 0 ? 'customer' : 'team';
                                        html += '<article class="message-row ' + side + (item.message_type === 'system_note' ? ' system' : '') + '" data-message-id="' + item.id + '"><div class="message-bubble"><div class="message-meta"><strong>' + escapeHtml(item.sender) + '</strong><span>' + escapeHtml(item.created_at) + '</span></div><p>' + escapeHtml(item.message) + '</p>';
                                        if (item.attachment_url) {
                                            html += '<div class="mt-2"><a href="' + item.attachment_url + '" target="_blank" class="btn btn-sm btn-light border text-primary"><i class="fas fa-paperclip mr-1"></i> ' + escapeHtml(item.attachment_name || 'Download Attachment') + '</a></div>';
                                        }
                                        html += '</div></article>';
                                    }
                                }
                            });
                        } else {
                            if (snapshot.buzz_alerts && snapshot.buzz_alerts.length) {
                                snapshot.buzz_alerts.forEach(function (buzz) {
                                    html += '<article id="buzz-' + escapeHtml(buzz.id) + '" class="event-card buzz" data-event-type="buzz"><div class="event-head"><strong>' + escapeHtml(buzz.priority) + ' Buzz</strong><span>' + escapeHtml(buzz.recipient_role) + ' · ' + escapeHtml(buzz.status) + '</span></div><p>' + escapeHtml(buzz.message) + '</p>';
                                    if (buzz.can_accept && buzz.accept_url) {
                                        html += '<form class="buzz-accept-form mt-2" action="' + escapeHtml(buzz.accept_url) + '"><input type="hidden" name="_token" value="{{ csrf_token() }}"><button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i> Accept</button></form>';
                                    }
                                    if (composer && composer.dataset.isCustomer === '1' && buzz.can_reply && buzz.reply_url) {
                                        html += '<form method="POST" action="' + escapeHtml(buzz.reply_url) + '" class="buzz-reply-form mt-2 d-flex gap-2 align-items-center"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input name="message" class="form-control form-control-sm" maxlength="3000" placeholder="' + (isArabic ? 'اكتب ردك على التنبيه...' : 'Reply to this Buzz...') + '" required><button type="submit" class="btn btn-sm btn-danger text-nowrap"><i class="fas fa-reply mr-1"></i>' + (isArabic ? 'رد' : 'Reply') + '</button></form>';
                                    }
                                    html += '</article>';
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
                                    if (message.message_type === 'ai_handover_prompt') {
                                        html += handoverPromptHtml(message);
                                    } else {
                                        var side = ['user', 'customer'].indexOf(message.sender_role) >= 0 ? 'customer' : 'team';
                                        html += '<article class="message-row ' + side + '" data-message-id="' + message.id + '"><div class="message-bubble"><div class="message-meta"><strong>' + escapeHtml(message.sender) + '</strong><span>' + escapeHtml(message.created_at) + '</span></div><p>' + escapeHtml(message.message) + '</p></div></article>';
                                    }
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
                        ['action_state', 'search', 'buzz_id'].forEach(function (key) {
                            if (currentUrl.searchParams.has(key)) url.searchParams.set(key, currentUrl.searchParams.get(key));
                        });
                        return url.toString();
                    }

                    function refreshConversation(bookingId, showError) {
                        bookingId = bookingId || shell.dataset.bookingId;
                        if (!shell || !bookingId) return Promise.resolve(null);
                        var url = snapshotUrl(bookingId);
                        return fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}})
                            .then(function (response) {
                                if (!response.ok) throw new Error('Snapshot request failed with status ' + response.status);
                                return response.json();
                            })
                            .then(function (snapshot) {
                                if (!snapshot || !snapshot.status) throw new Error('Snapshot response was not usable');
                                render(snapshot);
                                return snapshot;
                            })
                            .catch(function (error) {
                                if (showError) showSnapshotError(error.message);
                                throw error;
                            });
                    }

                    function resetComposerAttachments() {
                        if (fileInput) fileInput.value = '';
                        if (vaultIdInput) vaultIdInput.value = '';
                        var badge = document.getElementById('composer-attachment-badge');
                        if (badge) badge.style.setProperty('display', 'none', 'important');
                    }

                    function messageBubbleHtml(message, statusLabel) {
                        var html = '<div class="message-bubble">' +
                            '<div class="message-meta"><strong>' + escapeHtml(message.sender_name || message.sender || 'Customer') + '</strong><span>' + escapeHtml(statusLabel || message.created_at || '') + '</span></div>';

                        if (message.message) {
                            html += '<p>' + escapeHtml(message.message) + '</p>';
                        }
                        if (message.attachment_url) {
                            html += '<div class="mt-2"><a href="' + escapeHtml(message.attachment_url) + '" target="_blank" class="btn btn-sm btn-light border text-primary"><i class="fas fa-paperclip mr-1"></i> ' + escapeHtml(message.attachment_name || 'Download Attachment') + '</a></div>';
                        } else if (message.attachment_name) {
                            html += '<div class="message-links"><span><i class="fas fa-paperclip mr-1"></i>' + escapeHtml(message.attachment_name) + '</span></div>';
                        }
                        if (message.ai_response_pending) {
                            html += '<div class="message-links"><span>Waiting for Quick AI...</span></div>';
                        }
                        html += '</div>';

                        return html;
                    }

                    function upsertMessageBubble(message, options) {
                        options = options || {};
                        if (!feed || !message || !message.id) return null;
                        var messageId = String(message.id);
                        var selector = '[data-message-id="' + escapeHtml(messageId) + '"], [data-message-id="msg-' + escapeHtml(messageId) + '"]';
                        var article = options.replaceId ? feed.querySelector('[data-message-id="' + options.replaceId + '"]') : feed.querySelector(selector);

                        var emptyChat = feed.querySelector('.empty-chat');
                        if (emptyChat) emptyChat.remove();

                        var side = ['user', 'customer'].indexOf(message.sender_role) >= 0 ? 'customer' : 'team';
                        if (!article) {
                            article = document.createElement('article');
                            feed.appendChild(article);
                        }

                        article.className = 'message-row ' + side + (options.pending ? ' pending' : '') + (options.failed ? ' failed' : '');
                        article.dataset.messageId = options.domId || ('msg-' + messageId);
                        article.innerHTML = messageBubbleHtml(message, options.statusLabel);
                        feed.scrollTop = feed.scrollHeight;
                        return article;
                    }

                    function appendSentMessage(message) {
                        upsertMessageBubble(message);
                    }

                    function createOptimisticMessage(formData, deliveryMode) {
                        var file = fileInput && fileInput.files && fileInput.files.length ? fileInput.files[0] : null;
                        return {
                            id: 'pending-' + Date.now() + '-' + Math.random().toString(16).slice(2),
                            message: formData.get('message') || (file ? 'Attachment' : 'Message'),
                            sender_role: 'user',
                            sender_name: 'You',
                            created_at: 'Sending...',
                            message_type: deliveryMode === 'message' ? (file ? 'attachment' : 'text') : deliveryMode,
                            attachment_name: file ? file.name : '',
                            ai_response_pending: false
                        };
                    }

                    function pollForAiReply(attemptsLeft) {
                        attemptsLeft = attemptsLeft || 8;
                        if (attemptsLeft <= 0) return;

                        window.setTimeout(function () {
                            refreshConversation(shell.dataset.bookingId, false)
                                .catch(function () {})
                                .finally(function () {
                                    pollForAiReply(attemptsLeft - 1);
                                });
                        }, 900);
                    }

                    function loadConversation(bookingId, href, pushState) {
                        if (!bookingId) return;
                        var previousBookingId = shell.dataset.bookingId;
                        return refreshConversation(bookingId, true)
                            .then(function () {
                                setActiveConversation(bookingId);
                                if (pushState && href) {
                                    window.history.pushState({bookingId: bookingId}, '', href);
                                }
                            })
                            .catch(function () {
                                setActiveConversation(previousBookingId);
                            });
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
                            var deliveryMode = modeSelect ? modeSelect.value : 'message';

                            if (!msgText && !docNameVal && !vaultIdVal && !hasFile) return;
                            if (composer.dataset.isCustomer !== '1' && composer.dataset.directMessageLocked === '1' && deliveryMode === 'message') {
                                showSnapshotError(composer.dataset.directMessageLockMessage || 'Disable AI first responder to send a direct message.');
                                return;
                            }

                            var sendBtn = composer.querySelector('.send-btn');
                            var origBtnHtml = sendBtn ? sendBtn.innerHTML : '';
                            if (sendBtn) {
                                sendBtn.disabled = true;
                                sendBtn.innerHTML = '<span class="sanad-btn-spinner" style="width:12px;height:12px;border-width:2px;border-top-color:#fff;display:inline-block;border-radius:50%;animation:sanadBtnSpin 0.7s linear infinite;"></span>';
                            }

                            var formData = new FormData(composer);
                            var optimisticMessage = null;
                            if (deliveryMode === 'message') {
                                optimisticMessage = createOptimisticMessage(formData, deliveryMode);
                                upsertMessageBubble(optimisticMessage, {
                                    domId: optimisticMessage.id,
                                    pending: true,
                                    statusLabel: 'Sending...'
                                });
                                if (composerText) composerText.value = '';
                                resetComposerAttachments();
                            }

                            fetch(composer.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(function (res) {
                                return res.json().catch(function () { return {}; }).then(function (data) {
                                    if (!res.ok || data.status === false) {
                                        var firstError = data.errors ? Object.values(data.errors)[0] : null;
                                        throw new Error((Array.isArray(firstError) ? firstError[0] : firstError) || data.message || 'Message send failed.');
                                    }
                                    return data;
                                });
                            })
	                            .then(function (data) {
	                                if (sendBtn) {
	                                    sendBtn.disabled = false;
	                                    sendBtn.innerHTML = origBtnHtml;
	                                }
                                    if (deliveryMode !== 'message') {
	                                    if (composerText) composerText.value = '';
	                                    resetComposerAttachments();
                                    }
                                    if (data.chat_message) {
                                        upsertMessageBubble(data.chat_message, {
                                            replaceId: optimisticMessage ? optimisticMessage.id : null
                                        });
                                    }
	                                refreshConversation(shell.dataset.bookingId, true).catch(function () {});
                                    if (data.chat_message && data.chat_message.ai_response_pending) {
                                        pollForAiReply(10);
                                    }
	                            })
                            .catch(function (err) {
                                if (sendBtn) {
                                    sendBtn.disabled = false;
                                    sendBtn.innerHTML = origBtnHtml;
                                }
                                if (optimisticMessage) {
                                    optimisticMessage.created_at = 'Not sent';
                                    upsertMessageBubble(optimisticMessage, {
                                        domId: optimisticMessage.id,
                                        failed: true,
                                        statusLabel: 'Not sent'
                                    });
                                }
                                showSnapshotError(err.message || 'Message send failed.');
                                refreshConversation(shell.dataset.bookingId, true).catch(function () {});
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
	                        if (form && form.classList && form.classList.contains('ai-handover-form')) {
	                            e.preventDefault();
	                            var interactionId = form.dataset.interactionId;
	                            var formData = new FormData(form);
	                            form.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
	                            fetch('/customer-dashboard/ai/interactions/' + interactionId + '/handover', {
	                                method: 'POST',
	                                body: formData,
	                                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
	                            }).then(function (res) { return res.json(); })
	                                .then(function () { refreshConversation(shell.dataset.bookingId, true).catch(function () {}); })
	                                .catch(function () { refreshConversation(shell.dataset.bookingId, true).catch(function () {}); });
	                            return;
	                        }
	                        if (form && form.classList && form.classList.contains('ai-toggle-form')) {
	                            e.preventDefault();
	                            if (!controlPanel || !controlPanel.dataset.aiToggleUrl) return;
	                            fetch(controlPanel.dataset.aiToggleUrl, {
	                                method: 'POST',
	                                body: new FormData(form),
	                                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
	                            }).then(function (res) { return res.json(); })
	                                .then(function () { refreshConversation(shell.dataset.bookingId, true).catch(function () {}); })
	                                .catch(function () { refreshConversation(shell.dataset.bookingId, true).catch(function () {}); });
	                            return;
	                        }
	                        if (form && form.classList && form.classList.contains('buzz-accept-form')) {
	                            e.preventDefault();
	                            form.querySelectorAll('button').forEach(function (button) { button.disabled = true; });
	                            fetch(form.action, {
	                                method: 'POST',
	                                body: new FormData(form),
	                                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
	                            }).then(function (res) {
	                                if (!res.ok) throw new Error('Accept failed with status ' + res.status);
	                                return res.json().catch(function () { return {}; });
	                            }).then(function () {
	                                refreshConversation(shell.dataset.bookingId, true).catch(function () {});
	                            }).catch(function (error) {
	                                showSnapshotError(error.message || 'Accept failed.');
	                                refreshConversation(shell.dataset.bookingId, false).catch(function () {});
	                            });
	                            return;
	                        }
	                        if (form && form.classList && form.classList.contains('chat-assignment-form')) {
	                            e.preventDefault();
	                            if (!controlPanel || !controlPanel.dataset.chatAssignmentUrl) return;
                            fetch(controlPanel.dataset.chatAssignmentUrl, {
                                method: 'POST',
                                body: new FormData(form),
                                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'}
                            }).then(function (res) {
                                if (!res.ok) throw new Error('Assignment update failed with status ' + res.status);
                                return res.json();
                            })
                                .then(function () { refreshConversation(shell.dataset.bookingId, true).catch(function () {}); })
                                .catch(function (error) {
                                    showSnapshotError(error.message || 'Assignment update failed.');
                                    refreshConversation(shell.dataset.bookingId, false).catch(function () {});
                                });
                            return;
                        }
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
                                refreshConversation(shell.dataset.bookingId).catch(function () {});
                            })
                            .catch(function () {
                                refreshConversation(shell.dataset.bookingId).catch(function () {});
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
                    setInterval(function () {
                        if (isControlPanelActive()) return;
                        refreshConversation(shell.dataset.bookingId).catch(function () {});
                    }, 2000);

                    if (window.Echo && shell.dataset.bookingId) {
                        window.Echo.private('sanad.request.' + shell.dataset.bookingId)
                            .listen('.sanad.conversation.updated', function () {
                                refreshConversation(shell.dataset.bookingId).catch(function () {});
                            });
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
