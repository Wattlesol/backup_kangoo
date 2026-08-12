@php
    $sanadRoleLabels = [
        'admin' => 'Admin',
        'provider' => 'Partner',
        'handyman' => 'Employee',
        'user' => 'Customer',
    ];
    $sanadRoleLabel = fn ($role) => $sanadRoleLabels[$role] ?? Str::headline($role ?: 'role');
@endphp

<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card sanad-ai-console">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h4 class="font-weight-bold mb-1">Sanad AI Assistant</h4>
                                    <span class="text-muted">Vector-backed knowledge base, customer questions, confidence, and escalation tracking</span>
                        </div>
                        <a href="{{ route('sanad.requests.index') }}" class="btn-link btn-link-hover"><u>Open requests</u></a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="sanad-ai-kpi">
                                    <span>Knowledge Items</span>
                                    <strong>{{ $aiSummary['active_knowledge_items'] ?? 0 }}/{{ $aiSummary['knowledge_items'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="sanad-ai-kpi">
                                    <span>Interactions</span>
                                    <strong>{{ $aiSummary['interactions'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="sanad-ai-kpi">
                                    <span>Escalations</span>
                                    <strong>{{ $aiSummary['escalations'] ?? 0 }}</strong>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="sanad-ai-kpi">
                                    <span>AI Provider</span>
                                    <strong>Sanad</strong>
                                </div>
                            </div>
                            <div class="col-xl-3 col-md-6 mb-3">
                                <div class="sanad-ai-kpi">
                                    <span>Vector Store</span>
                                    <strong>{{ Str::headline(config('sanad.ai.vector_store')) }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-stretch">
                            <div class="col-lg-6 mb-3 mb-lg-0">
                                <div class="sanad-ai-panel">
                                    <h5 class="font-weight-bold mb-3">Ask Sanad AI</h5>
                                    <div class="sanad-ai-policy mb-3">
                                        <strong>Fallback to human support:</strong>
                                        low-confidence answers are automatically marked as escalated and routed to the Sanad support team for review.
                                        <a href="{{ route('sanad.ai.escalations.index') }}" class="sanad-policy-link">Review AI escalations</a>
                                    </div>
                                    <form method="POST" action="{{ route('sanad.ai.ask') }}">
                                        @csrf
                                        <div class="form-group">
                                            <label class="form-control-label">Question</label>
                                            <textarea name="question" class="form-control" rows="4" required placeholder="Ask about request status, documents, payment, or next steps"></textarea>
                                        </div>
                                        <button class="btn btn-primary" type="submit">Ask Assistant</button>
                                    </form>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="sanad-ai-panel">
                                    <h5 class="font-weight-bold mb-3">Recent AI Interactions</h5>
                                    <div class="sanad-ai-list">
                                        @forelse($interactions as $interaction)
                                            <div class="sanad-ai-list-item">
                                                <div>
                                                    <strong>{{ Str::limit($interaction->question, 76) }}</strong>
                                                    <span>{{ Str::limit($interaction->answer, 110) }}</span>
                                                    @if(data_get($interaction->metadata, 'sources'))
                                                        <small class="d-block text-muted">Sources: {{ collect(data_get($interaction->metadata, 'sources'))->pluck('title')->implode(', ') }}</small>
                                                    @endif
                                                    @if($interaction->requires_escalation)
                                                        <small class="sanad-ai-escalation-note">Needs human review by Sanad support</small>
                                                    @endif
                                                </div>
                                                <div class="sanad-ai-status">
                                                    <span class="badge {{ $interaction->requires_escalation ? 'badge-warning' : 'badge-success' }}">{{ Str::headline($interaction->status) }}</span>
                                                    <small>{{ round(($interaction->confidence ?? 0) * 100) }}%</small>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="sanad-ai-empty">No AI interactions yet</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
                            <div class="row mt-3">
                                <div class="col-lg-5 mb-3 mb-lg-0">
                                    <div class="sanad-ai-panel">
                                        <h5 class="font-weight-bold mb-3">Add Knowledge Item</h5>
                                        <form method="POST" action="{{ route('sanad.ai.knowledge.store') }}" enctype="multipart/form-data">
                                            @csrf
                                            <div class="form-group">
                                                <label class="form-control-label">Title</label>
                                                <input type="text" name="title" class="form-control" required>
                                            </div>
                                            <div class="sanad-ai-checkboxes mb-3">
                                                @foreach(config('sanad.document_visibility', []) as $role)
                                                    <label>
                                                        <input type="checkbox" name="visible_to[]" value="{{ $role }}" checked>
                                                        {{ $sanadRoleLabel($role) }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            <ul class="nav nav-tabs sanad-knowledge-tabs mb-3" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" data-toggle="tab" href="#knowledge-text-tab" role="tab">Manual Text</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-toggle="tab" href="#knowledge-pdf-tab" role="tab">PDF Upload</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" data-toggle="tab" href="#knowledge-google-tab" role="tab">Google Docs</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content sanad-knowledge-tab-content mb-3">
                                                <div class="tab-pane fade show active" id="knowledge-text-tab" role="tabpanel">
                                                    <div class="form-group mb-0">
                                                        <label class="form-control-label">Content</label>
                                                        <textarea name="content" class="form-control" rows="7" placeholder="Paste policy text, SOP notes, or answers here"></textarea>
                                                        <small class="text-muted">Best for short policies, FAQ answers, and operational notes.</small>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="knowledge-pdf-tab" role="tabpanel">
                                                    <div class="form-group mb-0">
                                                        <label class="sanad-upload-dropzone" for="knowledgePdfInput">
                                                            <i class="fas fa-cloud-upload-alt"></i>
                                                            <strong>Upload PDF documents</strong>
                                                            <span>Choose one or more files for the processing queue</span>
                                                        </label>
                                                        <input type="file" id="knowledgePdfInput" name="knowledge_pdfs[]" accept="application/pdf,.pdf" multiple hidden>
                                                        <div class="sanad-upload-queue" id="knowledgePdfQueue"></div>
                                                    </div>
                                                </div>
                                                <div class="tab-pane fade" id="knowledge-google-tab" role="tabpanel">
                                                    <div class="form-group mb-0">
                                                        <label class="form-control-label">Google Docs URL</label>
                                                        <input type="url" name="google_doc_url" class="form-control" placeholder="https://docs.google.com/document/d/...">
                                                        <small class="text-muted">Use a public/shared Google Doc link so the backend can export text and index it in Chroma.</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-primary" type="submit">Save Knowledge</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="sanad-ai-panel">
                                        <h5 class="font-weight-bold mb-3">Knowledge Base Fine Tuning</h5>
                                        <div class="sanad-ai-list sanad-knowledge-fine-list">
                                            @forelse($knowledgeItems as $item)
                                                <div class="sanad-ai-list-item">
                                                    <div>
                                                        <strong>{{ $item->title }}</strong>
                                                        <span>{{ Str::limit($item->content, 130) }}</span>
                                                        <small class="d-block text-muted">Chunks: {{ $item->chunks_count ?? 0 }} · Agent category: {{ $item->category ?: 'General' }}</small>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-light sanad-icon-btn" data-toggle="modal" data-target="#knowledgeModal{{ $item->id }}" title="View and fine tune">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <div class="modal fade" id="knowledgeModal{{ $item->id }}" tabindex="-1" role="dialog" aria-labelledby="knowledgeModalLabel{{ $item->id }}" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg" role="document">
                                                        <div class="modal-content">
                                                            <form method="POST" action="{{ route('sanad.ai.knowledge.update', $item->id) }}">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="knowledgeModalLabel{{ $item->id }}">Fine Tune Knowledge</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                        <span aria-hidden="true">&times;</span>
                                                                    </button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="form-group">
                                                                        <label class="form-control-label">Title</label>
                                                                        <input type="text" name="title" class="form-control" value="{{ $item->title }}" required>
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label class="form-control-label">Knowledge Content</label>
                                                                        <textarea name="content" class="form-control" rows="10" required>{{ $item->content }}</textarea>
                                                                    </div>
                                                                    <div class="sanad-ai-checkboxes mb-3">
                                                                        @foreach(config('sanad.document_visibility', []) as $role)
                                                                            <label>
                                                                                <input type="checkbox" name="visible_to[]" value="{{ $role }}" {{ in_array($role, $item->visible_to ?: [], true) ? 'checked' : '' }}>
                                                                                {{ $sanadRoleLabel($role) }}
                                                                            </label>
                                                                        @endforeach
                                                                    </div>
                                                                    <label class="d-flex align-items-center gap-2">
                                                                        <input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }}>
                                                                        Active in RAG
                                                                    </label>
                                                                    <small class="d-block text-muted mt-2">Category and tags will be re-evaluated by the agent after saving.</small>
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
                                                <div class="sanad-ai-empty">No knowledge items yet</div>
                                            @endforelse
                                        </div>
                                        <div class="mt-3">
                                            {{ $knowledgeItems->links() }}
                                        </div>
                                        <div class="sanad-confidence-footer">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span>AI Agent Confidence</span>
                                                <strong>{{ $aiSummary['agent_confidence'] ?? 0 }}%</strong>
                                            </div>
                                            <div class="sanad-confidence-track" aria-label="AI Agent Confidence">
                                                <div class="sanad-confidence-fill" style="width: {{ min(100, max(0, $aiSummary['agent_confidence'] ?? 0)) }}%;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @once
        <style>
            .sanad-ai-console .card-header {
                border-bottom: 1px solid rgba(0, 0, 0, 0.06);
            }

            .sanad-ai-kpi,
            .sanad-ai-panel {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                background: #fff;
            }

            .sanad-ai-kpi {
                min-height: 84px;
                padding: 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
            }

            .sanad-ai-kpi span,
            .sanad-ai-list-item span,
            .sanad-ai-status small,
            .sanad-ai-escalation-note,
            .sanad-ai-empty {
                color: #6c757d;
                font-size: 13px;
            }

            .sanad-ai-kpi strong {
                font-size: 22px;
                line-height: 1.1;
                text-align: right;
            }

            .sanad-ai-panel {
                min-height: 100%;
                padding: 16px;
            }

            .col-lg-7 > .sanad-ai-panel {
                display: flex;
                flex-direction: column;
            }

            .sanad-ai-policy {
                border: 1px solid rgba(255, 193, 7, 0.45);
                border-radius: 8px;
                background: #fff8e1;
                color: #6c5200;
                padding: 12px;
                font-size: 13px;
            }

            .sanad-policy-link {
                display: inline-block;
                margin-left: 6px;
                color: #4f46ad;
                font-weight: 600;
                text-decoration: underline;
            }

            .sanad-ai-list-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 12px;
                padding: 12px 0;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
            }

            .sanad-ai-list-item:first-of-type {
                border-top: 0;
            }

            .sanad-ai-list-item div {
                min-width: 0;
            }

            .sanad-ai-list-item strong,
            .sanad-ai-list-item span,
            .sanad-ai-escalation-note {
                display: block;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .sanad-ai-status {
                flex-shrink: 0;
                text-align: right;
            }

            .sanad-ai-checkboxes {
                display: flex;
                flex-wrap: wrap;
                gap: 12px;
            }

            .sanad-ai-checkboxes label {
                margin-bottom: 0;
                color: #6c757d;
                font-size: 13px;
            }

            .sanad-knowledge-tabs {
                border-bottom-color: rgba(0, 0, 0, 0.08);
                gap: 4px;
            }

            .sanad-knowledge-tabs .nav-link {
                border-radius: 8px 8px 0 0;
                color: #6c757d;
                font-size: 13px;
                padding: 9px 12px;
            }

            .sanad-knowledge-tabs .nav-link.active {
                color: #111827;
                font-weight: 600;
            }

            .sanad-knowledge-tab-content {
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-top: 0;
                border-radius: 0 0 8px 8px;
                padding: 14px;
                background: #fbfcfe;
            }

            .sanad-upload-dropzone {
                min-height: 132px;
                border: 1px dashed rgba(90, 82, 180, 0.55);
                border-radius: 8px;
                background: #fff;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 6px;
                color: #5f5bb6;
                cursor: pointer;
                text-align: center;
                padding: 18px;
            }

            .sanad-upload-dropzone i {
                font-size: 28px;
            }

            .sanad-upload-dropzone span,
            .sanad-upload-queue small,
            .sanad-confidence-footer span {
                color: #6c757d;
                font-size: 12px;
            }

            .sanad-upload-file {
                margin-top: 10px;
                border: 1px solid rgba(0, 0, 0, 0.08);
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding: 10px 12px;
                background: #fff;
            }

            .sanad-upload-file-name {
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                color: #111827;
                font-size: 13px;
            }

            .sanad-upload-remove,
            .sanad-icon-btn {
                width: 34px;
                height: 34px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .sanad-upload-remove {
                border: 0;
                color: #dc3545;
                background: #fff1f1;
            }

            .sanad-knowledge-fine-list {
                flex: 1 1 auto;
            }

            .sanad-confidence-footer {
                margin-top: auto;
                padding-top: 14px;
            }

            .sanad-confidence-footer strong {
                color: #14532d;
                line-height: 1;
            }

            .sanad-confidence-track {
                height: 10px;
                border-radius: 999px;
                background: #fff;
                border: 1px solid rgba(0, 0, 0, 0.08);
                overflow: hidden;
            }

            .sanad-confidence-fill {
                height: 100%;
                border-radius: inherit;
                background: linear-gradient(90deg, #f4d35e 0%, #86c36f 55%, #14532d 100%);
            }

            .sanad-ai-empty {
                padding: 18px 0;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
            }
        </style>
    @endonce

    @once
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var input = document.getElementById('knowledgePdfInput');
                var queue = document.getElementById('knowledgePdfQueue');
                if (!input || !queue) return;

                function syncFiles(files) {
                    var transfer = new DataTransfer();
                    files.forEach(function (file) { transfer.items.add(file); });
                    input.files = transfer.files;
                }

                function renderQueue() {
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

                input.addEventListener('change', renderQueue);
            });
        </script>
    @endonce
</x-master-layout>
