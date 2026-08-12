@php
    $statusOptions = [
        'open' => 'Open',
        'approved' => 'Approved',
        'resolved' => 'Resolved',
        'needs_revision' => 'Needs Revision',
        'all' => 'All',
    ];
@endphp

<x-master-layout>
    <div class="container-fluid">
        <div class="card sanad-ai-escalations">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="font-weight-bold mb-1">AI Escalation Workspace</h4>
                    <span class="text-muted">Review low-confidence AI answers before the Sanad team relies on them.</span>
                </div>
                <a href="{{ route('sanad.ai.index') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-robot mr-1"></i> AI Console
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="sanad-ai-escalation-kpi">
                            <span>Open Escalations</span>
                            <strong>{{ $summary['open'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="sanad-ai-escalation-kpi">
                            <span>Approved</span>
                            <strong>{{ $summary['approved'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="sanad-ai-escalation-kpi">
                            <span>Needs Revision</span>
                            <strong>{{ $summary['needs_revision'] ?? 0 }}</strong>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="sanad-ai-escalation-kpi">
                            <span>Average Confidence</span>
                            <strong>{{ $summary['avg_confidence'] ?? 0 }}%</strong>
                        </div>
                    </div>
                </div>

                <div class="sanad-ai-filter mb-3">
                    @foreach($statusOptions as $value => $label)
                        <a href="{{ route('sanad.ai.escalations.index', ['status' => $value]) }}" class="{{ $status === $value ? 'active' : '' }}">{{ $label }}</a>
                    @endforeach
                </div>

                <div class="sanad-ai-escalation-list">
                    @forelse($interactions as $interaction)
                        @php
                            $sources = collect(data_get($interaction->metadata, 'sources', []))->pluck('title')->filter()->values();
                            $booking = $interaction->booking;
                            $confidence = round(($interaction->confidence ?? 0) * 100);
                        @endphp
                        <div class="sanad-ai-escalation-item">
                            <div class="sanad-ai-escalation-head">
                                <div>
                                    <span class="badge {{ $interaction->requires_escalation ? 'badge-warning' : 'badge-success' }}">{{ Str::headline($interaction->status ?: 'pending') }}</span>
                                    <small class="text-muted ml-2">{{ optional($interaction->created_at)->format('M d, Y h:i A') }}</small>
                                </div>
                                <div class="sanad-ai-confidence">
                                    <span>{{ $confidence }}%</span>
                                    <div><i style="width: {{ min(100, max(0, $confidence)) }}%"></i></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-lg-7">
                                    <label class="form-control-label">Question</label>
                                    <div class="sanad-ai-readonly">{{ $interaction->question }}</div>

                                    <form method="POST" action="{{ route('sanad.ai.escalations.review', $interaction->id) }}" class="mt-3">
                                        @csrf
                                        <div class="form-group">
                                            <label class="form-control-label">Reviewed Answer</label>
                                            <textarea name="answer" class="form-control" rows="5">{{ old('answer', $interaction->answer) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-control-label">Team Note</label>
                                            <input type="text" name="review_note" class="form-control" value="{{ old('review_note', data_get($interaction->metadata, 'review.note')) }}" placeholder="Optional note for the Sanad team">
                                        </div>
                                        <div class="sanad-ai-actions">
                                            <button class="btn btn-success btn-sm" type="submit" name="review_action" value="approve">
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                            <button class="btn btn-primary btn-sm" type="submit" name="review_action" value="edit_approve">
                                                <i class="fas fa-pen mr-1"></i> Save Edit & Approve
                                            </button>
                                            <button class="btn btn-warning btn-sm" type="submit" name="review_action" value="needs_revision">
                                                <i class="fas fa-redo mr-1"></i> Needs Revision
                                            </button>
                                            <button class="btn btn-light btn-sm" type="submit" name="review_action" value="resolve">
                                                <i class="fas fa-archive mr-1"></i> Resolve
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-5 mt-3 mt-lg-0">
                                    <div class="sanad-ai-context">
                                        <strong>Request</strong>
                                        @if($booking)
                                            <a href="{{ route('sanad.requests.show', $booking->id) }}">{{ $booking->sanad_reference ?: '#' . $booking->id }}</a>
                                            <span>{{ optional($booking->service)->name ?: 'No service attached' }}</span>
                                            <span>{{ optional($booking->customer)->display_name ?: optional($booking->customer)->first_name ?: 'No customer attached' }}</span>
                                        @else
                                            <span>No request attached</span>
                                        @endif
                                    </div>
                                    <div class="sanad-ai-context mt-3">
                                        <strong>Asked By</strong>
                                        <span>{{ optional($interaction->user)->display_name ?: optional($interaction->user)->first_name ?: optional($interaction->user)->email ?: 'System' }}</span>
                                    </div>
                                    <div class="sanad-ai-context mt-3">
                                        <strong>Knowledge Sources</strong>
                                        @forelse($sources as $source)
                                            <span>{{ $source }}</span>
                                        @empty
                                            <span>No source matched</span>
                                        @endforelse
                                    </div>
                                    @if(data_get($interaction->metadata, 'review.reviewed_at'))
                                        <div class="sanad-ai-context mt-3">
                                            <strong>Last Review</strong>
                                            <span>{{ Str::headline(data_get($interaction->metadata, 'review.action')) }} by {{ data_get($interaction->metadata, 'review.reviewed_by_name', 'Sanad team') }}</span>
                                            <span>{{ data_get($interaction->metadata, 'review.reviewed_at') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sanad-ai-empty">No AI escalations in this view</div>
                    @endforelse
                </div>

                <div class="mt-3">
                    {{ $interactions->links() }}
                </div>
            </div>
        </div>
    </div>

    @push('after-styles')
        <style>
            .sanad-ai-escalations .card-header { background: #fff; border-bottom: 1px solid #eef0f5; }
            .sanad-ai-escalation-kpi,
            .sanad-ai-escalation-item {
                background: #fff;
                border: 1px solid #e9edf5;
                border-radius: 8px;
                box-shadow: 0 10px 28px rgba(31, 41, 55, .04);
            }
            .sanad-ai-escalation-kpi { min-height: 92px; padding: 18px; display: flex; justify-content: space-between; align-items: center; }
            .sanad-ai-escalation-kpi span,
            .sanad-ai-context span,
            .sanad-ai-readonly { color: #64748b; }
            .sanad-ai-escalation-kpi strong { font-size: 24px; color: #111827; }
            .sanad-ai-filter { display: inline-flex; flex-wrap: wrap; gap: 8px; padding: 6px; background: #f8fafc; border: 1px solid #edf1f7; border-radius: 8px; }
            .sanad-ai-filter a { padding: 8px 12px; border-radius: 6px; color: #334155; }
            .sanad-ai-filter a.active { background: #5f58c9; color: #fff; }
            .sanad-ai-escalation-list { display: grid; gap: 14px; }
            .sanad-ai-escalation-item { padding: 18px; }
            .sanad-ai-escalation-head { display: flex; justify-content: space-between; gap: 16px; align-items: center; margin-bottom: 16px; }
            .sanad-ai-confidence { min-width: 150px; text-align: right; }
            .sanad-ai-confidence span { display: block; font-weight: 700; color: #14532d; margin-bottom: 4px; }
            .sanad-ai-confidence div { height: 7px; border-radius: 999px; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; }
            .sanad-ai-confidence i { display: block; height: 100%; background: linear-gradient(90deg, #facc15 0%, #16a34a 100%); }
            .sanad-ai-readonly { background: #f8fafc; border: 1px solid #edf1f7; border-radius: 8px; padding: 12px; white-space: pre-wrap; }
            .sanad-ai-actions { display: flex; flex-wrap: wrap; gap: 8px; }
            .sanad-ai-context { display: grid; gap: 7px; padding: 12px; border: 1px solid #edf1f7; border-radius: 8px; background: #fbfcfe; }
            .sanad-ai-context strong { color: #111827; }
            .sanad-ai-empty { padding: 36px; text-align: center; color: #64748b; background: #f8fafc; border: 1px solid #edf1f7; border-radius: 8px; }
            @media (max-width: 768px) {
                .sanad-ai-escalation-head { align-items: flex-start; flex-direction: column; }
                .sanad-ai-confidence { width: 100%; text-align: left; }
            }
        </style>
    @endpush
</x-master-layout>
