@php
    $sanadRoleLabels = [
        'admin' => 'Admin',
        'provider' => 'Partner',
        'handyman' => 'Employee',
        'user' => 'Customer',
    ];
    $sanadRoleLabel = fn ($role) => $sanadRoleLabels[$role] ?? Str::headline($role ?: 'role');
    $testMessages = $interactions->sortBy('created_at')->values();
@endphp

<x-master-layout>
    <div class="container-fluid">
        <div class="card sanad-ai-console">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="font-weight-bold mb-1">Sanad Knowledge Base</h4>
                    <span class="text-muted">Test RAG answers, inspect sources, and index approved knowledge.</span>
                </div>
                <a href="{{ route('sanad.chat.workspace') }}" class="btn btn-sm btn-outline-primary">Open Unified Inbox</a>
            </div>
            <div class="card-body">
                <div id="sanadAlertSlot">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Error:</strong>
                            <ul class="mb-0 pl-3 mt-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                </div>

                <div class="sanad-kpi-grid mb-3">
                    <div class="sanad-ai-kpi"><span>Knowledge Items</span><strong>{{ $aiSummary['active_knowledge_items'] ?? 0 }}/{{ $aiSummary['knowledge_items'] ?? 0 }}</strong></div>
                    <div class="sanad-ai-kpi"><span>Test Interactions</span><strong>{{ $aiSummary['interactions'] ?? 0 }}</strong></div>
                    <div class="sanad-ai-kpi"><span>Escalations</span><strong>{{ $aiSummary['escalations'] ?? 0 }}</strong></div>
                    <div class="sanad-ai-kpi"><span>Vector Store</span><strong>{{ Str::headline(config('sanad.ai.vector_store')) }}</strong></div>
                </div>

                <section class="sanad-chat-lab mb-3">
                    <div class="chat-lab-header">
                        <div>
                            <h5 class="font-weight-bold mb-1">Sanad AI Test Chat</h5>
                            <span>Admin testing workspace for confidence, sources, tags, and retrieval quality.</span>
                        </div>
                        <span class="status-chip">{{ config('sanad.ai.provider') }} · {{ config('sanad.ai.model') }}</span>
                    </div>

                    <div class="chat-lab-feed">
                        @forelse($testMessages as $interaction)
                            @php
                                $sources = collect(data_get($interaction->metadata, 'sources', []));
                                $sourceTags = $sources->pluck('title')->filter()->unique()->take(5);
                                $tags = $sources->flatMap(fn ($source) => data_get($source, 'metadata.tags', []))->filter()->unique()->take(6);
                            @endphp
                            <article class="chat-row admin">
                                <div class="chat-bubble">
                                    <div class="bubble-meta"><strong>{{ optional($interaction->user)->display_name ?: 'Admin' }}</strong><span>{{ optional($interaction->created_at)->format('Y-m-d H:i') }}</span></div>
                                    <p>{{ $interaction->question }}</p>
                                </div>
                            </article>
                            <article class="chat-row ai">
                                <div class="chat-bubble">
                                    <div class="bubble-meta">
                                        <strong>Sanad AI</strong>
                                        <span>{{ round(($interaction->confidence ?? 0) * 100) }}% · {{ Str::headline($interaction->status) }}</span>
                                    </div>
                                    <p>{{ $interaction->answer }}</p>
                                    <div class="metric-row">
                                        <span>Provider {{ Str::headline(config('sanad.ai.provider')) }}</span>
                                        <span>Vector {{ Str::headline(config('sanad.ai.vector_store')) }}</span>
                                        @if(data_get($interaction->metadata, 'response_ms'))<span>{{ data_get($interaction->metadata, 'response_ms') }} ms</span>@endif
                                        @if($interaction->requires_escalation)<span class="danger">Escalated</span>@endif
                                    </div>
                                    <div class="source-row">
                                        @foreach($sourceTags as $source)
                                            <span>{{ $source }}</span>
                                        @endforeach
                                        @foreach($tags as $tag)
                                            <span>#{{ $tag }}</span>
                                        @endforeach
                                        @if($sources->count())<span>{{ $sources->count() }} chunks</span>@endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="empty-state">Ask a test question to see the conversation, confidence, tags, and retrieved sources.</div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('sanad.ai.ask') }}" class="chat-lab-composer" id="sanadAskForm">
                        @csrf
                        <textarea name="question" id="sanadAskQuestion" rows="1" required placeholder="Ask Sanad AI about request status, documents, payment, or next steps"></textarea>
                        <button class="send-btn" type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </section>

                @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                    <div class="row">
                        <div class="col-lg-5 mb-3 mb-lg-0">
                            <div class="sanad-ai-panel">
                                <h5 class="font-weight-bold mb-3">Add Knowledge Item</h5>
                                <form method="POST" action="{{ route('sanad.ai.knowledge.store') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label class="form-control-label">Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="Optional for Website Scraper">
                                    </div>
                                    <div class="sanad-ai-checkboxes mb-3">
                                        @foreach(config('sanad.document_visibility', []) as $role)
                                            <label><input type="checkbox" name="visible_to[]" value="{{ $role }}" checked> {{ $sanadRoleLabel($role) }}</label>
                                        @endforeach
                                    </div>
                                    <ul class="nav nav-tabs sanad-knowledge-tabs mb-3" role="tablist">
                                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#knowledge-text-tab" role="tab">Manual Text</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#knowledge-pdf-tab" role="tab">PDF Upload</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#knowledge-google-tab" role="tab">Google Docs</a></li>
                                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#knowledge-web-tab" role="tab">Website Scraper</a></li>
                                    </ul>
                                    <div class="tab-content sanad-knowledge-tab-content mb-3">
                                        <div class="tab-pane fade show active" id="knowledge-text-tab" role="tabpanel">
                                            <label class="form-control-label">Content</label>
                                            <textarea name="content" class="form-control" rows="7" placeholder="Paste policy text, SOP notes, or answers here"></textarea>
                                        </div>
                                        <div class="tab-pane fade" id="knowledge-pdf-tab" role="tabpanel">
                                            <label class="sanad-upload-dropzone" for="knowledgePdfInput">
                                                <i class="fas fa-cloud-upload-alt"></i>
                                                <strong>Upload PDF documents</strong>
                                                <span>Choose one or more PDFs for indexing</span>
                                            </label>
                                            <input type="file" id="knowledgePdfInput" name="knowledge_pdfs[]" accept="application/pdf,.pdf" multiple hidden>
                                            <div class="sanad-upload-queue" id="knowledgePdfQueue"></div>
                                        </div>
                                        <div class="tab-pane fade" id="knowledge-google-tab" role="tabpanel">
                                            <label class="form-control-label">Google Docs URL</label>
                                            <input type="url" name="google_doc_url" class="form-control" placeholder="https://docs.google.com/document/d/...">
                                        </div>
                                        <div class="tab-pane fade" id="knowledge-web-tab" role="tabpanel">
                                            <div class="form-group">
                                                <label class="form-control-label">Website URL</label>
                                                <input type="url" name="website_url" class="form-control" placeholder="https://www.my.gov.sa/...">
                                            </div>
                                            <div class="form-row">
                                                <div class="col-md-7">
                                                    <label class="form-control-label">Crawl Mode</label>
                                                    <select name="crawl_mode" class="form-control">
                                                        <option value="single_url">Single URL</option>
                                                        <option value="same_domain">Same-domain crawl</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-control-label">Page Limit</label>
                                                    <input type="number" name="crawl_page_limit" class="form-control" min="1" max="50" value="10">
                                                </div>
                                            </div>
                                            <small class="text-muted d-block mt-2">Only public HTTP/HTTPS URLs are allowed. Same-domain crawl stays on the same host.</small>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Save / Scrape and Index</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="sanad-ai-panel knowledge-panel">
                                <h5 class="font-weight-bold mb-3">Knowledge Base Fine Tuning</h5>
                                <div class="sanad-ai-list sanad-knowledge-fine-list">
                                    @forelse($knowledgeItems as $item)
                                        <div class="sanad-ai-list-item" id="sanad-knowledge-item-{{ $item->id }}">
                                            <div>
                                                <strong>{{ $item->title }}</strong>
                                                <span>{{ Str::limit($item->content, 130) }}</span>
                                                <small class="d-block text-muted">Chunks: {{ $item->chunks_count ?? 0 }} · Category: {{ $item->category ?: 'General' }}</small>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-light sanad-icon-btn" data-toggle="modal" data-target="#knowledgeModal{{ $item->id }}" title="View and fine tune"><i class="fas fa-eye"></i></button>
                                                <button type="button" class="btn btn-sm btn-light text-danger sanad-icon-btn sanad-knowledge-delete-btn" data-delete-url="{{ route('sanad.ai.knowledge.delete', $item->id) }}" data-item-title="{{ $item->title }}" title="Delete knowledge item"><i class="fas fa-trash-alt"></i></button>
                                            </div>
                                        </div>
                                        <div class="modal fade" id="knowledgeModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ route('sanad.ai.knowledge.update', $item->id) }}">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Fine Tune Knowledge</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" value="{{ $item->title }}" required></div>
                                                            <div class="form-group"><label>Knowledge Content</label><textarea name="content" class="form-control" rows="10" required>{{ $item->content }}</textarea></div>
                                                            <div class="sanad-ai-checkboxes mb-3">
                                                                @foreach(config('sanad.document_visibility', []) as $role)
                                                                    <label><input type="checkbox" name="visible_to[]" value="{{ $role }}" {{ in_array($role, $item->visible_to ?: [], true) ? 'checked' : '' }}> {{ $sanadRoleLabel($role) }}</label>
                                                                @endforeach
                                                            </div>
                                                            <label><input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}> Active in RAG</label>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Fine Tune</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="empty-state">No knowledge items yet.</div>
                                    @endforelse
                                </div>
                                <div class="mt-3">{{ $knowledgeItems->links() }}</div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @once
        <style>
            .sanad-ai-console { border: 0; box-shadow: none; }
            .sanad-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
            .sanad-ai-kpi, .sanad-ai-panel, .sanad-chat-lab { border: 1px solid #e5e9f2; border-radius: 8px; background: #fff; }
            .sanad-ai-kpi { min-height: 78px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
            .sanad-ai-kpi span, .chat-lab-header span, .metric-row span, .source-row span, .sanad-ai-list-item span, .empty-state { color: #667085; font-size: 13px; }
            .sanad-ai-kpi strong { font-size: 21px; color: #111827; text-align: right; }
            .sanad-chat-lab { overflow: hidden; }
            .chat-lab-header { min-height: 64px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; border-bottom: 1px solid #e5e9f2; }
            .status-chip, .source-row span, .metric-row span { border-radius: 999px; background: #f2f4f7; padding: 4px 9px; font-size: 12px; }
            .chat-lab-feed { min-height: 360px; max-height: 560px; overflow: auto; padding: 18px; background: #f8fafc; display: flex; flex-direction: column; gap: 12px; }
            .chat-row { display: flex; }
            .chat-row.admin { justify-content: flex-end; }
            .chat-row.ai { justify-content: flex-start; }
            .chat-bubble { width: min(720px, 86%); border-radius: 14px; padding: 12px 14px; border: 1px solid #e5e9f2; background: #fff; }
            .chat-row.admin .chat-bubble { background: #4f46e5; color: #fff; border-color: #4f46e5; }
            .chat-row.admin .bubble-meta span { color: rgba(255,255,255,.78); }
            .bubble-meta { display: flex; justify-content: space-between; gap: 12px; margin-bottom: 6px; font-size: 12px; }
            .bubble-meta span { color: #667085; }
            .chat-bubble p { margin: 0; white-space: pre-wrap; }
            .metric-row, .source-row { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
            .metric-row .danger { background: #fee2e2; color: #991b1b; }
            .chat-lab-composer { display: grid; grid-template-columns: minmax(0, 1fr) 44px; gap: 8px; padding: 12px 16px; border-top: 1px solid #e5e9f2; }
            .chat-lab-composer textarea { min-height: 42px; max-height: 120px; resize: vertical; border: 1px solid #dce3ee; border-radius: 14px; padding: 11px 12px; }
            .send-btn { width: 44px; height: 42px; border: 0; border-radius: 12px; background: #4f46e5; color: #fff; }
            .sanad-ai-panel { padding: 16px; min-height: 100%; }
            .knowledge-panel { display: flex; flex-direction: column; }
            .sanad-ai-checkboxes { display: flex; flex-wrap: wrap; gap: 12px; }
            .sanad-ai-checkboxes label { color: #667085; font-size: 13px; margin-bottom: 0; }
            .sanad-knowledge-tabs { gap: 4px; border-bottom-color: #e5e9f2; }
            .sanad-knowledge-tabs .nav-link { border-radius: 8px 8px 0 0; color: #667085; padding: 9px 11px; font-size: 13px; }
            .sanad-knowledge-tabs .nav-link.active { color: #111827; font-weight: 700; }
            .sanad-knowledge-tab-content { border: 1px solid #e5e9f2; border-top: 0; border-radius: 0 0 8px 8px; padding: 14px; background: #fbfcfe; }
            .sanad-upload-dropzone { min-height: 132px; border: 1px dashed #8b86dc; border-radius: 8px; background: #fff; color: #4f46ad; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; text-align: center; padding: 18px; cursor: pointer; }
            .sanad-upload-dropzone i { font-size: 28px; }
            .sanad-upload-file, .sanad-ai-list-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 11px 0; border-top: 1px solid #edf0f5; }
            .sanad-ai-list-item:first-of-type { border-top: 0; }
            .sanad-ai-list-item div { min-width: 0; }
            .sanad-ai-list-item strong, .sanad-ai-list-item span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sanad-icon-btn, .sanad-upload-remove { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .sanad-upload-remove { border: 0; color: #dc3545; background: #fff1f1; }
            .sanad-btn-spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #ffffff; animation: sanadBtnSpin 0.7s linear infinite; vertical-align: middle; margin-right: 6px; }
            @keyframes sanadBtnSpin { to { transform: rotate(360deg); } }
            .empty-state { padding: 18px; text-align: center; }
            @media (max-width: 991px) { .sanad-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .chat-bubble { width: 94%; } }
            @media (max-width: 575px) { .sanad-kpi-grid { grid-template-columns: 1fr; } .chat-lab-header { align-items: flex-start; flex-direction: column; } }
        </style>
    @endonce

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('knowledgePdfInput');
                var queue = document.getElementById('knowledgePdfQueue');
                var askForm = document.getElementById('sanadAskForm');
                var askQuestion = document.getElementById('sanadAskQuestion');
                var chatFeed = document.querySelector('.chat-lab-feed');

                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                if (askForm && askQuestion && chatFeed) {
                    askForm.onsubmit = function (e) {
                        if (e) {
                            e.preventDefault();
                            e.stopPropagation();
                        }

                        var questionText = askQuestion.value.trim();
                        if (!questionText) return false;

                        var formData = new FormData(askForm);
                        askQuestion.value = '';

                        var emptyState = chatFeed.querySelector('.empty-state');
                        if (emptyState) emptyState.remove();

                        var nowTime = new Date().toISOString().slice(0, 16).replace('T', ' ');

                        var userRow = document.createElement('article');
                        userRow.className = 'chat-row admin';
                        userRow.innerHTML = '<div class="chat-bubble">' +
                            '<div class="bubble-meta"><strong>{{ optional(auth()->user())->display_name ?: "Admin" }}</strong><span>' + nowTime + '</span></div>' +
                            '<p>' + escapeHtml(questionText) + '</p>' +
                            '</div>';
                        chatFeed.appendChild(userRow);

                        var thinkingId = 'thinking-' + Date.now();
                        var thinkingRow = document.createElement('article');
                        thinkingRow.className = 'chat-row ai';
                        thinkingRow.id = thinkingId;
                        thinkingRow.innerHTML = '<div class="chat-bubble" style="border-color:#818cf8; background:#f5f3ff;">' +
                            '<div class="bubble-meta"><strong>Sanad AI</strong><span class="text-primary font-weight-bold"><i class="fas fa-spinner fa-spin mr-1"></i> Thinking & RAG searching...</span></div>' +
                            '<p class="text-muted"><span class="sanad-btn-spinner" style="width:12px;height:12px;border-width:2px;border-top-color:#4f46e5;display:inline-block;border-radius:50%;animation:sanadBtnSpin 0.7s linear infinite;margin-right:6px;"></span> Searching Knowledge Base & generating response...</p>' +
                            '</div>';
                        chatFeed.appendChild(thinkingRow);
                        chatFeed.scrollTop = chatFeed.scrollHeight;

                        fetch(askForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            var thinkEl = document.getElementById(thinkingId);
                            if (!thinkEl) return;

                            if (data && data.status && data.interaction) {
                                var item = data.interaction;
                                var confPercent = Math.round((item.confidence || 0) * 100);
                                var statusText = item.requires_escalation ? 'Escalated' : 'Answered';
                                var escalationBadge = item.requires_escalation ? '<span class="danger">Escalated</span>' : '';

                                var sourcesHtml = '';
                                if (item.sources && item.sources.length) {
                                    item.sources.forEach(function (src) {
                                        if (src.title) sourcesHtml += '<span>' + escapeHtml(src.title) + '</span>';
                                    });
                                    sourcesHtml += '<span>' + item.sources.length + ' chunks</span>';
                                }

                                thinkEl.innerHTML = '<div class="chat-bubble">' +
                                    '<div class="bubble-meta"><strong>Sanad AI</strong><span>' + confPercent + '% · ' + statusText + '</span></div>' +
                                    '<p>' + escapeHtml(item.answer) + '</p>' +
                                    '<div class="metric-row">' +
                                        '<span>Provider {{ Str::headline(config("sanad.ai.provider")) }}</span>' +
                                        '<span>Vector {{ Str::headline(config("sanad.ai.vector_store")) }}</span>' +
                                        (item.response_ms ? '<span>' + item.response_ms + ' ms</span>' : '') +
                                        escalationBadge +
                                    '</div>' +
                                    (sourcesHtml ? '<div class="source-row">' + sourcesHtml + '</div>' : '') +
                                    '</div>';
                            } else {
                                var errMsg = 'Error generating response.';
                                if (data && data.errors && typeof data.errors === 'object') {
                                    var firstKey = Object.keys(data.errors)[0];
                                    if (firstKey && data.errors[firstKey] && data.errors[firstKey][0]) {
                                        errMsg = data.errors[firstKey][0];
                                    } else if (data.message) {
                                        errMsg = data.message;
                                    }
                                } else if (data && data.message) {
                                    errMsg = data.message;
                                }
                                thinkEl.innerHTML = '<div class="chat-bubble" style="border-color:#f87171; background:#fef2f2;">' +
                                    '<div class="bubble-meta"><strong>Sanad AI</strong><span class="text-danger">Error</span></div>' +
                                    '<p class="text-danger">' + escapeHtml(errMsg) + '</p>' +
                                    '</div>';
                            }
                            chatFeed.scrollTop = chatFeed.scrollHeight;
                        })
                        .catch(function (err) {
                            var thinkEl = document.getElementById(thinkingId);
                            if (thinkEl) {
                                thinkEl.innerHTML = '<div class="chat-bubble" style="border-color:#f87171; background:#fef2f2;">' +
                                    '<div class="bubble-meta"><strong>Sanad AI</strong><span class="text-danger">Network Error</span></div>' +
                                    '<p class="text-danger">' + escapeHtml(err.message || 'Failed to communicate with AI server.') + '</p>' +
                                    '</div>';
                            }
                        });

                        return false;
                    };

                    askQuestion.onkeydown = function (e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            askForm.onsubmit();
                        }
                    };
                }

                function syncFiles(files) {
                    if (!input) return;
                    var transfer = new DataTransfer();
                    files.forEach(function (file) { transfer.items.add(file); });
                    input.files = transfer.files;
                }

                function renderQueue() {
                    if (!input || !queue) return;
                    var files = Array.from(input.files || []);
                    queue.innerHTML = '';
                    files.forEach(function (file, index) {
                        var row = document.createElement('div');
                        row.className = 'sanad-upload-file';
                        row.innerHTML = '<span class="sanad-upload-file-name"><i class="fas fa-file-pdf mr-2"></i>' + file.name + '</span><button type="button" class="sanad-upload-remove" aria-label="Remove file"><i class="fas fa-times"></i></button>';
                        row.querySelector('button').addEventListener('click', function () {
                            files.splice(index, 1);
                            syncFiles(files);
                            renderQueue();
                        });
                        queue.appendChild(row);
                    });
                }

                if (input && queue) {
                    input.addEventListener('change', renderQueue);
                }

                function showSanadAlert(type, message) {
                    var slot = document.getElementById('sanadAlertSlot');
                    if (!slot) return;
                    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
                    slot.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                        '<i class="fas ' + icon + ' mr-2"></i> ' + message +
                        '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                        '</div>';
                    slot.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                function prependKnowledgeItemUI(item) {
                    var listContainer = document.querySelector('.sanad-knowledge-fine-list');
                    if (!listContainer) return;
                    var emptyState = listContainer.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();

                    var existing = document.getElementById('sanad-knowledge-item-' + item.id);
                    if (existing) existing.remove();

                    var div = document.createElement('div');
                    div.id = 'sanad-knowledge-item-' + item.id;
                    div.className = 'sanad-ai-list-item';
                    var itemTitle = item.title || 'Untitled Knowledge';
                    div.innerHTML = '<div>' +
                        '<strong>' + escapeHtml(itemTitle) + '</strong>' +
                        '<span>' + escapeHtml(item.content || '') + '</span>' +
                        '<small class="d-block text-muted">Chunks: ' + escapeHtml(item.chunks_count || 1) + ' · Category: ' + escapeHtml(item.category || 'General') + '</small>' +
                        '</div>' +
                        '<button type="button" class="btn btn-sm btn-light text-danger sanad-icon-btn sanad-knowledge-delete-btn" data-delete-url="/sanad/ai/knowledge/' + escapeHtml(item.id) + '" data-item-title="' + escapeHtml(itemTitle) + '" title="Delete knowledge item"><i class="fas fa-trash-alt"></i></button>';
                    listContainer.insertBefore(div, listContainer.firstChild);
                }

                function prependPendingScrapeCard(item) {
                    var listContainer = document.querySelector('.sanad-knowledge-fine-list');
                    if (!listContainer) return;
                    var emptyState = listContainer.querySelector('.empty-state');
                    if (emptyState) emptyState.remove();

                    var div = document.createElement('div');
                    div.id = 'sanad-knowledge-item-' + item.id;
                    div.className = 'sanad-ai-list-item sanad-pending-scrape-card';
                    div.innerHTML = '<div>' +
                        '<strong>' + (item.title || 'Website Scrape') + ' <span class="badge badge-warning ml-2" id="badge-item-' + item.id + '"><span class="sanad-btn-spinner" style="width:12px;height:12px;border-width:2px;"></span> Scraping</span></strong>' +
                        '<span class="text-primary font-weight-bold d-block mt-1" id="step-item-' + item.id + '">' + (item.progress_step || 'Scraping & Indexing...') + '</span>' +
                        '<small class="d-block text-muted">Mode: ' + (item.crawl_mode || 'single_url') + ' · Limit: ' + (item.page_limit || 10) + '</small>' +
                        '</div>';
                    listContainer.insertBefore(div, listContainer.firstChild);
                }

                function updatePendingCardStep(itemId, stepText) {
                    var stepEl = document.getElementById('step-item-' + itemId);
                    if (stepEl) {
                        stepEl.textContent = stepText;
                    }
                }

                function updatePendingCardError(itemId, errorMsg) {
                    var card = document.getElementById('sanad-knowledge-item-' + itemId);
                    if (!card) return;
                    var badge = document.getElementById('badge-item-' + itemId);
                    if (badge) {
                        badge.className = 'badge badge-danger ml-2';
                        badge.innerHTML = '<i class="fas fa-times-circle mr-1"></i> Failed';
                    }
                    var stepEl = document.getElementById('step-item-' + itemId);
                    if (stepEl) {
                        stepEl.className = 'text-danger d-block mt-1';
                        stepEl.textContent = errorMsg;
                    }
                }

                var knowledgeForm = document.querySelector('form[action*="knowledge"]');
                if (knowledgeForm) {
                    knowledgeForm.addEventListener('submit', function (e) {
                        e.preventDefault();

                        var webUrlInput = knowledgeForm.querySelector('input[name="website_url"]');
                        var isScrape = webUrlInput && webUrlInput.value.trim() !== '';

                        var submitBtn = knowledgeForm.querySelector('button[type="submit"]');
                        var originalHtml = submitBtn ? submitBtn.innerHTML : 'Save / Scrape and Index';

                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.innerHTML = '<span class="sanad-btn-spinner"></span> ' + (isScrape ? 'Starting Scraper...' : 'Saving...');
                        }

                        var slot = document.getElementById('sanadAlertSlot');
                        if (slot) slot.innerHTML = '';

                        var formData = new FormData(knowledgeForm);
                        var targetAction = isScrape
                            ? '{{ route("sanad.ai.knowledge.scrape-async") }}'
                            : knowledgeForm.action;

                        fetch(targetAction, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (res) {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalHtml;
                            }

                            if (!res.ok || !res.data || !res.data.status) {
                                var errText = (res.data && res.data.message) ? res.data.message : 'Request failed.';
                                showSanadAlert('danger', errText);
                                return;
                            }

                            if (isScrape && res.data.item_id) {
                                var itemId = res.data.item_id;
                                prependPendingScrapeCard(res.data.item);
                                knowledgeForm.reset();
                                if (queue) queue.innerHTML = '';

                                showSanadAlert('success', 'Scraper job started in real-time. Progress is updating below.');

                                var statusPoller = setInterval(function () {
                                    fetch('/sanad/ai/knowledge/' + itemId + '/status')
                                        .then(function (sRes) { return sRes.json(); })
                                        .then(function (sData) {
                                            if (sData && sData.item && sData.item.progress_step) {
                                                updatePendingCardStep(itemId, sData.item.progress_step);
                                            }
                                        })
                                        .catch(function () {});
                                }, 1000);

                                fetch('/sanad/ai/knowledge/' + itemId + '/run-scrape', {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                })
                                .then(function (rRes) {
                                    return rRes.json().then(function (rData) {
                                        return { ok: rRes.ok, data: rData };
                                    });
                                })
                                .then(function (rResult) {
                                    clearInterval(statusPoller);
                                    if (rResult.ok && rResult.data && rResult.data.status) {
                                        showSanadAlert('success', rResult.data.message || 'Website scraped and indexed!');
                                        if (rResult.data.item) {
                                            prependKnowledgeItemUI(rResult.data.item);
                                        }
                                    } else {
                                        var scrapeError = (rResult.data && rResult.data.message) ? rResult.data.message : 'Scraping failed.';
                                        updatePendingCardError(itemId, scrapeError);
                                        showSanadAlert('danger', scrapeError);
                                    }
                                })
                                .catch(function (rErr) {
                                    clearInterval(statusPoller);
                                    updatePendingCardError(itemId, rErr.message || 'Scrape connection error.');
                                    showSanadAlert('danger', rErr.message || 'Scrape connection error.');
                                });
                            } else {
                                showSanadAlert('success', res.data.message || 'Knowledge item added.');
                                knowledgeForm.reset();
                                if (queue) queue.innerHTML = '';
                                if (res.data.item) {
                                    prependKnowledgeItemUI(res.data.item);
                                }
                            }
                        })
                        .catch(function (error) {
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalHtml;
                            }
                            showSanadAlert('danger', error.message || 'Network error occurred.');
                        });
                    });
                }

                document.addEventListener('click', function (e) {
                    var deleteBtn = e.target.closest('.sanad-knowledge-delete-btn');
                    if (!deleteBtn) return;

                    e.preventDefault();
                    var title = deleteBtn.dataset.itemTitle || 'this knowledge item';
                    if (!window.confirm('Delete "' + title + '" and remove its chunks and vector records?')) {
                        return;
                    }

                    var card = deleteBtn.closest('.sanad-ai-list-item');
                    var originalHtml = deleteBtn.innerHTML;
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<span class="sanad-btn-spinner" style="width:12px;height:12px;border-width:2px;"></span>';

                    fetch(deleteBtn.dataset.deleteUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function (response) {
                        return response.json().then(function (data) {
                            return { ok: response.ok, data: data };
                        });
                    })
                    .then(function (res) {
                        if (!res.ok || !res.data || !res.data.status) {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = originalHtml;
                            showSanadAlert('danger', (res.data && res.data.message) ? res.data.message : 'Delete failed.');
                            return;
                        }

                        if (card) card.remove();
                        showSanadAlert('success', res.data.message || 'Knowledge item deleted.');

                        var listContainer = document.querySelector('.sanad-knowledge-fine-list');
                        if (listContainer && !listContainer.querySelector('.sanad-ai-list-item')) {
                            listContainer.innerHTML = '<div class="empty-state">No knowledge items yet.</div>';
                        }
                    })
                    .catch(function (error) {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalHtml;
                        showSanadAlert('danger', error.message || 'Delete failed.');
                    });
                });
            });
        </script>
    @endonce
</x-master-layout>
