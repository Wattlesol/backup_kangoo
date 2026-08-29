<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $assignmentState = request('assignment_state', 'all');
    $visibleOrders = collect($orders->items());
    $scoredPartners = collect($recommendations)->flatten(1)->unique('id');
    $unassignedCount = $visibleOrders->whereNull('provider_id')->count();
    $waitingCount = $visibleOrders->filter(fn ($order) => $order->provider_id && !in_array($order->status, ['accept', 'accepted', 'in_progress', 'completed'], true))->count();
    $availableCapacity = $scoredPartners->sum(fn ($partner) => max(0, (int) data_get($partner, 'assignment_metrics.capacity', 0) - (int) data_get($partner, 'assignment_metrics.active', 0)));
    $acceptanceRate = round((float) $scoredPartners->avg(fn ($partner) => (float) data_get($partner, 'assignment_metrics.acceptance', 0)), 1);
@endphp
<div class="sanad-assignment-page quick-assignment-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <div class="quick-admin-hero quick-assignment-hero">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="quick-assignment-kicker"><x-quick-icon name="bot" /> {{ $isAr ? 'محرك التوزيع الذكي للمعاملات' : 'Smart dispatch engine' }}</div>
                <h4 class="font-weight-bold mb-1">{{ $isAr ? 'مساحة الإسناد والتوزيع الذكي' : 'Partner & Employee Assignment Hub' }}</h4>
                <span class="text-muted">{{ $isAr ? 'مطابقة الطلبات الحكومية مع الشركاء المعتمدين حسب الطاقة والالتزام ومستوى الخدمة.' : 'Match government requests with certified partners using capacity, acceptance and SLA signals.' }}</span>
            </div>
            <a href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}" class="quick-table-btn"><x-quick-icon name="briefcase" /> {{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}</a>
        </div>
    </div>

    <div class="quick-assignment-metrics">
        <div><span>{{ $isAr ? 'طلبات بانتظار الإسناد' : 'Unassigned Queue' }}</span><strong>{{ $unassignedCount }}</strong><small>{{ $isAr ? 'ضمن الصفحة الحالية' : 'On this page' }}</small></div>
        <div><span>{{ $isAr ? 'بانتظار قبول الشريك' : 'Waiting Acceptance' }}</span><strong>{{ $waitingCount }}</strong><small>{{ $isAr ? 'بانتظار قرار الشريك' : 'Awaiting partner response' }}</small></div>
        <div><span>{{ $isAr ? 'الشركاء المتاحون الآن' : 'Available Partners' }}</span><strong>{{ $partners->count() }}</strong><small>{{ $availableCapacity }} {{ $isAr ? 'خانة متاحة' : 'open slots' }}</small></div>
        <div><span>{{ $isAr ? 'متوسط معدل القبول' : 'Average Accept Rate' }}</span><strong>{{ $acceptanceRate }}%</strong><small>{{ $isAr ? 'حسب أداء الشركاء' : 'From partner performance' }}</small></div>
    </div>

    <div class="quick-card quick-assignment-panel">
            <div class="btn-group quick-assignment-tabs mb-3">
                <a class="btn btn-sm {{ $assignmentState === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('sanad.assignments.index') }}">{{ $isAr ? 'كافة الطلبات' : 'All Requests' }}</a>
                <a class="btn btn-sm {{ $assignmentState === 'unassigned' ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('sanad.assignments.index', ['assignment_state'=>'unassigned']) }}">{{ $isAr ? 'غير مسند فقط' : 'Unassigned Only' }}</a>
                <a class="btn btn-sm {{ $assignmentState === 'assigned' ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('sanad.assignments.index', ['assignment_state'=>'assigned']) }}">{{ $isAr ? 'مسند ونشط' : 'Assigned & Active' }}</a>
            </div>

            @forelse($orders as $order)
                @php
                    $decision = $latestDecisions[$order->id] ?? null;
                    $acceptedAt = data_get(optional($decision)->score_snapshot, 'accepted_at');
                    $accepted = in_array($order->status, ['accept', 'accepted', 'in_progress'], true) || !empty($acceptedAt);
                    $progressLabel = $accepted ? Str::headline($order->sanad_stage ?: $order->status) : ($order->provider_id ? ($isAr ? 'بانتظار قبول الشريك' : 'Waiting Partner Acceptance') : ($isAr ? 'غير مسند' : 'Unassigned'));
                @endphp
                <div class="sanad-assignment-item">
                    <div class="sanad-assignment-head">
                        <div>
                            <strong>{{ $order->quick_reference }}</strong>
                            <span>· {{ optional($order->service)->name_en ?: optional($order->service)->name }}</span>
                            <div class="text-muted small mt-1">
                                {{ $isAr ? 'العميل:' : 'Customer:' }} {{ optional($order->customer)->display_name ?: '-' }} · {{ $isAr ? 'الأولوية:' : 'Priority:' }} {{ __('messages.priority_' . ($order->sanad_priority ?: 'normal')) ?: Str::headline($order->sanad_priority ?: 'normal') }}
                            </div>
                        </div>
                        <span class="badge {{ $order->provider_id ? 'badge-primary' : 'badge-light' }}">{{ $progressLabel }}</span>
                    </div>

                    <div class="sanad-assignment-status">
                        <div>
                            <span>{{ $isAr ? 'الشريك' : 'Partner' }}</span>
                            <strong>{{ optional($order->provider)->display_name ?: ($isAr ? 'غير مسند' : 'Not assigned') }}</strong>
                        </div>
                        <div>
                            <span>{{ $isAr ? 'تاريخ الإسناد' : 'Assigned On' }}</span>
                            <strong>{{ optional($order->assigned_at)->format('M d, Y h:i A') ?: '-' }}</strong>
                        </div>
                        <div>
                            <span>{{ $isAr ? 'تاريخ القبول' : 'Accepted On' }}</span>
                            <strong>{{ $acceptedAt ? \Carbon\Carbon::parse($acceptedAt)->format('M d, Y h:i A') : '-' }}</strong>
                        </div>
                        <div>
                            <span>{{ $isAr ? 'التسليم المتوقع' : 'Expected Delivery' }}</span>
                            <strong>{{ optional($order->expected_completion_at)->format('M d, Y h:i A') ?: '-' }}</strong>
                        </div>
                    </div>

                    <div class="row mt-3">
                        @forelse($recommendations[$order->id] ?? [] as $candidate)
                            <div class="col-lg-4 mb-2">
                                <div class="sanad-candidate-card">
                                    <strong>{{ $candidate->display_name }}</strong>
                                    <span class="quick-match-score">{{ $candidate->assignment_score }}% {{ $isAr ? 'تطابق' : 'match' }}</span>
                                    <div class="small">{{ $isAr ? 'الطلبات النشطة' : 'Active load' }}: {{ $candidate->assignment_metrics['active'] }} / {{ $candidate->assignment_metrics['capacity'] ?: ($isAr ? 'متاح' : 'open') }}</div>
                                    <div class="small">SLA: {{ $candidate->assignment_metrics['sla'] }}% · {{ $isAr ? 'القبول' : 'Acceptance' }}: {{ $candidate->assignment_metrics['acceptance'] }}% · {{ $isAr ? 'المتوسط' : 'Avg' }}: {{ $candidate->assignment_metrics['avg'] ?: '-' }} {{ $isAr ? 'دقيقة' : 'min' }}</div>
                                    <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="provider_id" value="{{ $candidate->id }}">
                                        <input type="hidden" name="mode" value="suggested">
                                        <button class="btn btn-sm btn-primary quick-primary-btn">{{ $isAr ? 'إسناد الطلب' : 'Assign Partner' }}</button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-muted">{{ $isAr ? 'لا يوجد شريك مقترح متاح حالياً. يمكنك الإسناد يدوياً أدناه.' : 'No suggested partner is currently available. Assign manually below.' }}</div>
                        @endforelse
                    </div>

                    <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}" class="sanad-manual-assignment">
                        @csrf
                        <div class="form-row align-items-end">
                            <div class="col-md-4 mb-2">
                                <label class="form-control-label">{{ $order->provider_id ? ($isAr ? 'إعادة إسناد الشريك' : 'Reassign Partner') : ($isAr ? 'إسناد الشريك' : 'Assign Partner') }}</label>
                                <select name="provider_id" class="form-control" required>
                                    <option value="">{{ $order->provider_id ? ($isAr ? 'إعادة الإسناد إلى شريك...' : 'Reassign to Partner...') : ($isAr ? 'إسناد إلى شريك...' : 'Assign to Partner...') }}</option>
                                    @foreach($partners as $partner)
                                        <option value="{{ $partner->id }}" {{ (int) $order->provider_id === (int) $partner->id ? 'selected' : '' }}>{{ $partner->display_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-5 mb-2">
                                <label class="form-control-label">{{ $isAr ? 'ملاحظة الإسناد' : 'Assignment Note' }}</label>
                                <input name="reason" class="form-control" placeholder="{{ $order->provider_id ? ($isAr ? 'السبب مطلوب لإعادة الإسناد' : 'Reason required for reassignment') : ($isAr ? 'ملاحظة اختيارية لإسناد الشريك' : 'Optional note for Partner assignment') }}">
                            </div>
                            <div class="col-md-3 mb-2">
                                <input type="hidden" name="mode" value="manual">
                                <button class="quick-table-btn btn-block">{{ $order->provider_id ? ($isAr ? 'إعادة الإسناد' : 'Reassign') : ($isAr ? 'إسناد' : 'Assign') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            @empty
                <div class="sanad-empty-state text-center py-5">{{ $isAr ? 'لا توجد طلبات مطابقة لحالة الإسناد الحالية.' : 'No requests match the current assignment state.' }}</div>
            @endforelse

            {{ $orders->links() }}
    </div>
</div>

@push('after-styles')
    <style>
        .quick-assignment-page { max-width: 1180px; margin: 0 auto; }
        .quick-assignment-page .text-muted { color: var(--quick-shell-muted) !important; }
        .quick-assignment-hero h4 { color: var(--quick-shell-ink); font-size: clamp(22px, 2.5vw, 32px); font-weight: 900; }
        .quick-assignment-kicker { display: inline-flex; align-items: center; gap: 8px; border-radius: 999px; padding: 6px 14px; margin-bottom: 12px; color: var(--quick-blue); background: rgba(31,107,255,.1); font-size: 12px; font-weight: 900; }
        .quick-assignment-metrics { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 20px; }
        .quick-assignment-metrics > div { min-height: 112px; padding: 18px; border: 1px solid var(--quick-shell-line); border-radius: 18px; background: var(--quick-shell-surface); box-shadow: 0 2px 10px rgba(10,22,38,.02); }
        .quick-assignment-metrics span, .quick-assignment-metrics small { display: block; color: var(--quick-shell-muted); font-size: 12px; font-weight: 700; }
        .quick-assignment-metrics strong { display: block; margin: 10px 0 4px; color: var(--quick-shell-ink); font-size: 28px; font-weight: 900; }
        .quick-assignment-panel { overflow: hidden; }
        .quick-assignment-tabs { display: inline-flex; gap: 6px; padding: 4px; border-radius: 13px; background: color-mix(in srgb, var(--quick-shell-bg) 82%, transparent); }
        .quick-assignment-tabs .btn { border: 0; border-radius: 10px !important; font-size: 12px; font-weight: 900; padding: 8px 13px; }
        .quick-assignment-tabs .btn-primary { background: var(--quick-blue); color: #fff; }
        .quick-assignment-tabs .btn-outline-secondary { color: var(--quick-shell-muted); background: transparent; }
        .sanad-assignment-item { border: 1px solid var(--quick-shell-line); border-radius: 18px; padding: 18px; margin-bottom: 16px; background: var(--quick-shell-surface); }
        .sanad-assignment-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .sanad-assignment-status { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)); gap: 10px; margin-top: 14px; }
        .sanad-assignment-status > div, .sanad-candidate-card { border: 1px solid var(--quick-shell-line); border-radius: 14px; padding: 14px; background: color-mix(in srgb, var(--quick-shell-bg) 72%, var(--quick-shell-surface)); }
        .sanad-assignment-status span { display: block; color: var(--quick-shell-muted); font-size: 12px; margin-bottom: 4px; }
        .sanad-assignment-status strong { color: var(--quick-shell-ink); }
        .sanad-candidate-card { height: 100%; }
        .quick-match-score { display: inline-flex; margin: 8px 0; border-radius: 999px; padding: 4px 9px; color: var(--quick-blue); background: rgba(31,107,255,.1); font-size: 11px; font-weight: 900; }
        .sanad-manual-assignment { border-top: 1px solid var(--quick-shell-line); margin-top: 14px; padding-top: 14px; }
        .quick-assignment-page .form-control { min-height: 42px; border-color: var(--quick-shell-line); border-radius: 12px; color: var(--quick-shell-ink); background: var(--quick-shell-surface); }
        .quick-primary-btn { border-color: var(--quick-blue); border-radius: 12px; background: var(--quick-blue); color: #fff; font-size: 12px; font-weight: 900; min-height: 38px; padding: 8px 14px; }
        .quick-assignment-page .pagination { margin-top: 18px; }
        .quick-assignment-page .small { color: var(--quick-shell-muted); }
        .quick-assignment-page .badge { border-radius: 999px; padding: 6px 10px; font-size: 11px; font-weight: 900; }
        @media (max-width: 991px) { .sanad-assignment-status { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 1024px) { .quick-assignment-metrics { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 576px) {
            .quick-assignment-page { max-width: none; }
            .quick-assignment-metrics { grid-template-columns: 1fr; }
            .quick-assignment-tabs { display: grid; width: 100%; }
            .sanad-assignment-head { flex-direction: column; }
            .sanad-assignment-status { grid-template-columns: 1fr; }
            .quick-assignment-hero, .quick-assignment-panel { padding: 16px; border-radius: 18px; }
            .quick-assignment-page .quick-table-btn, .quick-assignment-page .quick-primary-btn { width: 100%; }
        }
    </style>
@endpush
</x-master-layout>
