@php
    $isAr = app()->getLocale() === 'ar';
    $statusOptions = [
        'open' => $isAr ? 'مفتوحة' : 'Open',
        'approved' => $isAr ? 'معتمدة' : 'Approved',
        'resolved' => $isAr ? 'تم حلها' : 'Resolved',
        'needs_revision' => $isAr ? 'بحاجة لتعديل' : 'Needs Revision',
        'all' => $isAr ? 'الكل' : 'All',
    ];
@endphp

<x-master-layout>
    <div class="container-fluid quick-ai-escalations-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <div class="sanad-ai-escalations">
            <div class="quick-admin-hero quick-ai-escalations-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="quick-admin-hero-eyebrow"><x-quick-icon name="shield" /> {{ $isAr ? 'المراقبة البشرية وبوابات السلامة' : 'Human review & safety gates' }}</div>
                    <h1>{{ $isAr ? 'مراقبة تصعيدات الذكاء الاصطناعي' : 'AI Escalation & Monitoring' }}</h1>
                    <p>{{ $isAr ? 'مراجعة الإجابات منخفضة الثقة وتوثيق قرار المشرف قبل اعتمادها.' : 'Review low-confidence answers and record a supervisor decision before approval.' }}</p>
                </div>
                <a href="{{ route('sanad.ai.index') }}" class="btn btn-outline-primary">
                    <x-quick-icon name="bot" /> {{ $isAr ? 'كونسول الذكاء الاصطناعي' : 'AI Console' }}
                </a>
            </div>
            <div class="quick-ai-escalations-body">
                <div class="quick-kpi-grid">
                    <div class="quick-kpi-card">
                        <div class="quick-kpi-header">
                            <span>{{ $isAr ? 'تصعيدات مفتوحة' : 'Open Escalations' }}</span>
                            <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;"><x-quick-icon name="bell" /></div>
                        </div>
                        <div class="quick-kpi-value">{{ $summary['open'] ?? 0 }}</div>
                    </div>
                    <div class="quick-kpi-card">
                        <div class="quick-kpi-header">
                            <span>{{ $isAr ? 'معتمدة' : 'Approved' }}</span>
                            <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;"><x-quick-icon name="check-circle" /></div>
                        </div>
                        <div class="quick-kpi-value">{{ $summary['approved'] ?? 0 }}</div>
                    </div>
                    <div class="quick-kpi-card">
                        <div class="quick-kpi-header">
                            <span>{{ $isAr ? 'بحاجة لتعديل' : 'Needs Revision' }}</span>
                            <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;"><x-quick-icon name="alert-triangle" /></div>
                        </div>
                        <div class="quick-kpi-value">{{ $summary['needs_revision'] ?? 0 }}</div>
                    </div>
                    <div class="quick-kpi-card">
                        <div class="quick-kpi-header">
                            <span>{{ $isAr ? 'متوسط الثقة' : 'Average Confidence' }}</span>
                            <div class="quick-kpi-icon" style="background: rgba(99,102,241,.1); color: #4f46e5;"><x-quick-icon name="activity" /></div>
                        </div>
                        <div class="quick-kpi-value">{{ $summary['avg_confidence'] ?? 0 }}%</div>
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
                                    <label class="form-control-label">{{ $isAr ? 'السؤال' : 'Question' }}</label>
                                    <div class="sanad-ai-readonly">{{ $interaction->question }}</div>

                                    <form method="POST" action="{{ route('sanad.ai.escalations.review', $interaction->id) }}" class="mt-3">
                                        @csrf
                                        <div class="form-group">
                                            <label class="form-control-label">{{ $isAr ? 'الإجابة بعد المراجعة' : 'Reviewed Answer' }}</label>
                                            <textarea name="answer" class="form-control" rows="5">{{ old('answer', $interaction->answer) }}</textarea>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-control-label">{{ $isAr ? 'ملاحظة الفريق' : 'Team Note' }}</label>
                                            <input type="text" name="review_note" class="form-control" value="{{ old('review_note', data_get($interaction->metadata, 'review.note')) }}" placeholder="{{ $isAr ? 'ملاحظة اختيارية لفريق كويك' : 'Optional note for the Quick team' }}">
                                        </div>
                                        <div class="sanad-ai-actions">
                                            <button class="btn btn-success btn-sm" type="submit" name="review_action" value="approve">
                                                <i class="fas fa-check mr-1"></i> {{ $isAr ? 'اعتماد' : 'Approve' }}
                                            </button>
                                            <button class="btn btn-primary btn-sm" type="submit" name="review_action" value="edit_approve">
                                                <i class="fas fa-pen mr-1"></i> {{ $isAr ? 'حفظ التعديل والاعتماد' : 'Save Edit & Approve' }}
                                            </button>
                                            <button class="btn btn-warning btn-sm" type="submit" name="review_action" value="needs_revision">
                                                <i class="fas fa-redo mr-1"></i> {{ $isAr ? 'بحاجة لتعديل' : 'Needs Revision' }}
                                            </button>
                                            <button class="btn btn-light btn-sm" type="submit" name="review_action" value="resolve">
                                                <i class="fas fa-archive mr-1"></i> {{ $isAr ? 'حل وإغلاق' : 'Resolve' }}
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" type="submit" name="review_action" value="delete" onclick="return confirm('Are you sure you want to delete this AI escalation?')">
                                                <i class="fas fa-trash-alt mr-1"></i> {{ $isAr ? 'حذف' : 'Delete' }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div class="col-lg-5 mt-3 mt-lg-0">
                                    <div class="sanad-ai-context">
                                        <strong>{{ $isAr ? 'الطلب' : 'Request' }}</strong>
                                        @if($booking)
                                            <a href="{{ route('sanad.requests.show', $booking->id) }}">{{ $booking->quick_reference }}</a>
                                            <span>{{ optional($booking->service)->name ?: 'No service attached' }}</span>
                                            <span>{{ optional($booking->customer)->display_name ?: optional($booking->customer)->first_name ?: 'No customer attached' }}</span>
                                        @else
                                            <span>No request attached</span>
                                        @endif
                                    </div>
                                    <div class="sanad-ai-context mt-3">
                                        <strong>{{ $isAr ? 'طُرح بواسطة' : 'Asked By' }}</strong>
                                        <span>{{ optional($interaction->user)->display_name ?: optional($interaction->user)->first_name ?: optional($interaction->user)->email ?: 'System' }}</span>
                                    </div>
                                    <div class="sanad-ai-context mt-3">
                                        <strong>{{ $isAr ? 'مصادر المعرفة' : 'Knowledge Sources' }}</strong>
                                        @forelse($sources as $source)
                                            <span>{{ $source }}</span>
                                        @empty
                                            <span>No source matched</span>
                                        @endforelse
                                    </div>
                                    @if(data_get($interaction->metadata, 'review.reviewed_at'))
                                        <div class="sanad-ai-context mt-3">
                                            <strong>{{ $isAr ? 'آخر مراجعة' : 'Last Review' }}</strong>
                                            <span>{{ Str::headline(data_get($interaction->metadata, 'review.action')) }} by {{ data_get($interaction->metadata, 'review.reviewed_by_name', 'Quick team') }}</span>
                                            <span>{{ data_get($interaction->metadata, 'review.reviewed_at') }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="sanad-ai-empty">{{ $isAr ? 'لا توجد تصعيدات ذكاء اصطناعي في هذا العرض.' : 'No AI escalations in this view.' }}</div>
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
            .quick-ai-escalations-page {
                max-width: 1180px;
                margin: 0 auto;
                padding: 28px 22px 48px;
            }

            .quick-ai-escalations-body {
                display: grid;
                gap: 22px;
                margin-top: 22px;
            }

            .quick-ai-escalations-hero .btn {
                border-radius: 16px;
                font-weight: 800;
                padding: 12px 18px;
                background: rgba(255,255,255,.72);
                border-color: rgba(31,107,255,.2);
            }

            .quick-ai-escalations-page .quick-kpi-card {
                min-height: 132px;
            }

            .quick-ai-escalations-page .quick-kpi-value {
                margin-top: 22px;
            }

            .quick-ai-escalations-page .form-control {
                border: 1px solid #dce6f4;
                border-radius: 12px;
                color: #0a1626;
                background: #fff;
            }

            .quick-ai-escalations-page .form-control:focus {
                border-color: #1f6bff;
                box-shadow: 0 0 0 4px rgba(31,107,255,.12);
            }

            .sanad-ai-escalation-item {
                background: #fff;
                border: 1px solid #dce6f4;
                border-radius: 24px;
                box-shadow: 0 18px 50px rgba(10,22,38,.06);
            }

            .sanad-ai-escalation-item .badge {
                border-radius: 999px;
                padding: 8px 12px;
                text-transform: none;
            }

            .sanad-ai-context span,
            .sanad-ai-readonly { color: #64748b; }

            .sanad-ai-filter {
                display: inline-flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 8px;
                background: #eaf1fb;
                border: 1px solid #dce6f4;
                border-radius: 16px;
            }

            .sanad-ai-filter a {
                padding: 10px 14px;
                border-radius: 12px;
                color: #53657f;
                font-weight: 800;
            }

            .sanad-ai-filter a.active {
                background: #1f6bff;
                color: #fff;
                box-shadow: 0 10px 24px rgba(31,107,255,.18);
            }

            .sanad-ai-escalation-list {
                display: grid;
                gap: 18px;
            }

            .sanad-ai-escalation-item {
                padding: 24px;
            }

            .sanad-ai-escalation-head { display: flex; justify-content: space-between; gap: 16px; align-items: center; margin-bottom: 16px; }
            .sanad-ai-confidence { min-width: 190px; text-align: right; }
            .sanad-ai-confidence span { display: block; font-weight: 700; color: #14532d; margin-bottom: 4px; }
            .sanad-ai-confidence div { height: 7px; border-radius: 999px; background: #fff; border: 1px solid #e5e7eb; overflow: hidden; }
            .sanad-ai-confidence i { display: block; height: 100%; background: linear-gradient(90deg, #facc15 0%, #16a34a 100%); }

            .sanad-ai-readonly {
                background: #f8fbff;
                border: 1px solid #dce6f4;
                border-radius: 14px;
                padding: 14px;
                white-space: pre-wrap;
            }

            .sanad-ai-actions {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }

            .sanad-ai-actions .btn {
                border-radius: 12px;
                font-weight: 800;
                padding: 9px 12px;
            }

            .sanad-ai-context {
                display: grid;
                gap: 7px;
                padding: 14px;
                border: 1px solid #dce6f4;
                border-radius: 16px;
                background: #f8fbff;
            }

            .sanad-ai-context strong { color: #111827; }
            .sanad-ai-empty { padding: 36px; text-align: center; color: #64748b; background: #f8fafc; border: 1px solid #edf1f7; border-radius: 16px; }

            html[dir="rtl"] .sanad-ai-confidence,
            [dir="rtl"] .sanad-ai-confidence {
                text-align: left;
            }

            @media (max-width: 768px) {
                .quick-ai-escalations-page {
                    padding: 16px 12px 36px;
                }

                .quick-ai-escalations-hero {
                    padding: 22px;
                }

                .quick-ai-escalations-hero .btn {
                    width: 100%;
                    justify-content: center;
                }

                .sanad-ai-filter {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                    width: 100%;
                }

                .sanad-ai-filter a {
                    text-align: center;
                }

                .sanad-ai-escalation-item {
                    padding: 16px;
                    border-radius: 20px;
                }

                .sanad-ai-escalation-head { align-items: flex-start; flex-direction: column; }
                .sanad-ai-confidence { width: 100%; text-align: left; }

                .sanad-ai-actions .btn {
                    flex: 1 1 100%;
                }
            }
        </style>
    @endpush
</x-master-layout>
