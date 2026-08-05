<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card sanad-ai-console">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h4 class="font-weight-bold mb-1">Sanad AI Assistant</h4>
                            <span class="text-muted">Knowledge base, customer questions, confidence, and escalation tracking</span>
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
                                    <span>AI Status</span>
                                    <strong>{{ config('sanad.ai.enabled') ? 'Enabled' : 'Disabled' }}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-stretch">
                            <div class="col-lg-6 mb-3 mb-lg-0">
                                <div class="sanad-ai-panel">
                                    <h5 class="font-weight-bold mb-3">Ask Sanad AI</h5>
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
                                        <form method="POST" action="{{ route('sanad.ai.knowledge.store') }}">
                                            @csrf
                                            <div class="form-group">
                                                <label class="form-control-label">Title</label>
                                                <input type="text" name="title" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label class="form-control-label">Category</label>
                                                <input type="text" name="category" class="form-control" placeholder="Documents, Payment, Workflow">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-control-label">Content</label>
                                                <textarea name="content" class="form-control" rows="5" required></textarea>
                                            </div>
                                            <div class="sanad-ai-checkboxes mb-3">
                                                @foreach(config('sanad.document_visibility', []) as $role)
                                                    <label>
                                                        <input type="checkbox" name="visible_to[]" value="{{ $role }}" checked>
                                                        {{ Str::headline($role) }}
                                                    </label>
                                                @endforeach
                                            </div>
                                            <button class="btn btn-primary" type="submit">Save Knowledge</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="sanad-ai-panel">
                                        <h5 class="font-weight-bold mb-3">Knowledge Base</h5>
                                        <div class="sanad-ai-list">
                                            @forelse($knowledgeItems as $item)
                                                <div class="sanad-ai-list-item">
                                                    <div>
                                                        <strong>{{ $item->title }}</strong>
                                                        <span>{{ Str::limit($item->content, 130) }}</span>
                                                    </div>
                                                    <span class="badge badge-light">{{ $item->category ?: 'General' }}</span>
                                                </div>
                                            @empty
                                                <div class="sanad-ai-empty">No knowledge items yet</div>
                                            @endforelse
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
            .sanad-ai-list-item span {
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

            .sanad-ai-empty {
                padding: 18px 0;
                border-top: 1px solid rgba(0, 0, 0, 0.06);
            }
        </style>
    @endonce
</x-master-layout>
