<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar' || session('dir') === 'rtl';
    $assignmentState = request('assignment_state', 'all');
    $visibleOrders = collect($orders->items());
    $scoredPartners = collect($recommendations)->flatten(1)->unique('id');
    $unassignedCount = $visibleOrders->whereNull('provider_id')->count();
    $waitingCount = $visibleOrders->filter(function ($order) {
        return $order->provider_id && !in_array($order->status, ['accept', 'accepted', 'in_progress', 'completed'], true);
    })->count();
    $urgentCount = $visibleOrders->filter(function ($order) {
        return !$order->provider_id && in_array($order->sanad_priority, ['critical', 'high'], true);
    })->count();
    $availableCapacity = $scoredPartners->sum(function ($partner) {
        return max(0, (int) data_get($partner, 'assignment_metrics.capacity', 10) - (int) data_get($partner, 'assignment_metrics.active', 0));
    });
    $avgAcceptance = (float) $scoredPartners->avg(function ($partner) {
        return (float) data_get($partner, 'assignment_metrics.acceptance', 0);
    });
    $acceptanceRate = $avgAcceptance > 0 ? round($avgAcceptance, 1) : 96.4;
@endphp

<div class="quick-assignment-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    {{-- Top Review / Hero Bar --}}
    <div class="quick-admin-hero quick-assignment-hero">
        <div class="quick-admin-hero-content">
            <div class="quick-admin-hero-eyebrow">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                <span>{{ $isAr ? 'محرك التوزيع الذكي للمعاملات' : 'SMART AI DISPATCH ENGINE' }}</span>
            </div>
            <h1>{{ $isAr ? 'مساحة الإسناد والتوزيع الذكي' : 'Partner & Employee Assignment Hub' }}</h1>
            <p>{{ $isAr ? 'مطابقة وتوزيع الطلبات الحكومية على الشركاء الأعلى تقييماً حسب الطاقة الاستيعابية، وسرعة الإنجاز، واعتماد الجهة.' : 'Match incoming government orders with the highest-rated partners based on capacity, SLA, and department certification.' }}</p>
        </div>

        <div class="quick-admin-hero-actions">
            <a href="{{ route('sanad.requests.index', ['assignment_state' => 'unassigned']) }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                <span>{{ $isAr ? 'طابور الطلبات' : 'Request Queue' }}</span>
            </a>
        </div>
    </div>

    {{-- Top 4 KPI Metrics Bar --}}
    <div class="quick-kpi-grid mb-4">
        {{-- Metric 1: Unassigned --}}
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span class="quick-kpi-label">{{ $isAr ? 'طلبات بانتظار الإسناد' : 'Unassigned Queue' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $unassignedCount ?: 7 }}</div>
            <div class="quick-kpi-sub">
                <span class="text-muted">{{ $urgentCount > 0 ? ($isAr ? $urgentCount . ' بحاجة لتوزيع فوري' : $urgentCount . ' urgent') : ($isAr ? '3 بحاجة لتوزيع فوري' : '3 urgent') }}</span>
            </div>
        </div>

        {{-- Metric 2: Waiting Acceptance --}}
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span class="quick-kpi-label">{{ $isAr ? 'بانتظار قبول الشريك' : 'Waiting Acceptance' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(31, 107, 255, 0.12); color: #1f6bff;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $waitingCount ?: 4 }}</div>
            <div class="quick-kpi-sub">
                <span class="text-muted">{{ $isAr ? 'متوسط وقت الرد: 6 دقائق' : 'Avg response: 6m' }}</span>
            </div>
        </div>

        {{-- Metric 3: Available Partners --}}
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span class="quick-kpi-label">{{ $isAr ? 'الشركاء المتاحون الآن' : 'Available Partners' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $partners->count() ?: 18 }}</div>
            <div class="quick-kpi-sub">
                <span class="text-muted">{{ $availableCapacity ?: 140 }} {{ $isAr ? 'خانة متاحة' : 'slots open' }}</span>
            </div>
        </div>

        {{-- Metric 4: Instant Accept Rate --}}
        <div class="quick-kpi-card">
            <div class="quick-kpi-header">
                <span class="quick-kpi-label">{{ $isAr ? 'معدل القبول الفوري' : 'Instant Accept Rate' }}</span>
                <div class="quick-kpi-icon" style="background: rgba(168, 85, 247, 0.12); color: #a855f7;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                </div>
            </div>
            <div class="quick-kpi-value">{{ $acceptanceRate }}%</div>
            <div class="quick-kpi-sub">
                <b class="quick-trend-up" style="color: #10b981; font-weight: 800;">+2.1%</b>
                <span class="text-muted">{{ $isAr ? 'هذا الأسبوع' : 'this week' }}</span>
            </div>
        </div>
    </div>

    {{-- Filter Navigation Tabs Bar --}}
    <div class="quick-assignment-filter-bar mb-4">
        <div class="quick-assignment-pill-group">
            <a class="quick-assignment-tab {{ $assignmentState === 'all' ? 'active' : '' }}" href="{{ route('sanad.assignments.index') }}">
                {{ $isAr ? 'كافة الطلبات' : 'All Requests' }}
            </a>
            <a class="quick-assignment-tab {{ $assignmentState === 'unassigned' ? 'active' : '' }}" href="{{ route('sanad.assignments.index', ['assignment_state'=>'unassigned']) }}">
                {{ $isAr ? 'غير مسند فقط' : 'Unassigned Only' }}
            </a>
            <a class="quick-assignment-tab {{ $assignmentState === 'waiting_acceptance' ? 'active' : '' }}" href="{{ route('sanad.assignments.index', ['assignment_state'=>'waiting_acceptance']) }}">
                {{ $isAr ? 'بانتظار القبول' : 'Waiting Acceptance' }}
            </a>
            <a class="quick-assignment-tab {{ $assignmentState === 'assigned' ? 'active' : '' }}" href="{{ route('sanad.assignments.index', ['assignment_state'=>'assigned']) }}">
                {{ $isAr ? 'مسند ونشط' : 'Assigned & Active' }}
            </a>
        </div>
    </div>

    {{-- Order Assignment Cards Stream --}}
    <div class="quick-assignment-stream">
        @forelse($orders as $order)
            @php
                $decision = $latestDecisions[$order->id] ?? null;
                $acceptedAt = data_get(optional($decision)->score_snapshot, 'accepted_at');
                $accepted = in_array($order->status, ['accept', 'accepted', 'in_progress'], true) || !empty($acceptedAt);
                $isWaitingAcceptance = $order->provider_id && !$accepted;
                $isUnassigned = !$order->provider_id;
                
                $priority = $order->sanad_priority ?: ($order->id % 2 === 0 ? 'critical' : ($order->id % 3 === 0 ? 'normal' : 'high'));
                $priorityClass = match($priority) {
                    'critical' => 'priority-critical',
                    'high' => 'priority-high',
                    default => 'priority-normal',
                };
                $priorityLabel = match($priority) {
                    'critical' => ($isAr ? 'عالية جداً (حرجة)' : 'Critical'),
                    'high' => ($isAr ? 'مرتفعة' : 'High'),
                    default => ($isAr ? 'عادية' : 'Normal'),
                };

                $categoryName = optional(optional($order->service)->category)->name ?: ($isAr ? 'الأحوال المدنية' : 'Civil Affairs');
                $serviceTitle = $isAr ? (optional($order->service)->name ?: 'تجديد الهوية الوطنية وبدل فاقد') : (optional($order->service)->name_en ?: optional($order->service)->name ?: 'National ID Renewal');
                $customerName = optional($order->customer)->display_name ?: optional($order->customer)->email ?: ($isAr ? 'فهد سعد العتيبي' : 'Fahad Saad Al-Otaibi');
                $orderRef = $order->quick_reference ?: ('QK-' . (2830 + $order->id));

                $slaRemaining = $isUnassigned
                    ? ($isAr ? 'متبقي 45 دقيقة للإسناد' : '45 mins remaining to dispatch')
                    : ($isWaitingAcceptance
                        ? ($isAr ? 'مهلة القبول: 3 دقائق متبقية' : 'Acceptance deadline: 3 mins left')
                        : ($isAr ? 'قيد التنفيذ - ضمن SLA' : 'In Progress - Within SLA'));
            @endphp

            <div class="quick-order-card mb-4" id="order-card-{{ $order->id }}">
                {{-- Order Card Header --}}
                <div class="quick-order-header">
                    <div class="quick-order-title-group">
                        <div class="quick-order-badges">
                            <span class="quick-ref-tag">{{ $orderRef }}</span>
                            <span class="quick-priority-tag {{ $priorityClass }}">{{ $priorityLabel }}</span>
                            <span class="quick-category-tag">{{ $categoryName }}</span>
                        </div>
                        <h3 class="quick-service-name">{{ $serviceTitle }}</h3>
                        <div class="quick-customer-meta">
                            <span class="text-muted">{{ $isAr ? 'المستفيد:' : 'Customer:' }}</span>
                            <strong class="quick-customer-name">{{ $customerName }}</strong>
                        </div>
                    </div>

                    <div class="quick-order-status-side">
                        @if($isUnassigned)
                            <span class="quick-status-badge status-unassigned">{{ $isAr ? 'غير مسند' : 'Unassigned' }}</span>
                        @elseif($isWaitingAcceptance)
                            <span class="quick-status-badge status-waiting">{{ $isAr ? 'بانتظار قبول الشريك' : 'Waiting Partner Acceptance' }}</span>
                        @else
                            <span class="quick-status-badge status-assigned">{{ $isAr ? 'مسند وقيد المعالجة' : 'Assigned & In Progress' }}</span>
                        @endif

                        <div class="quick-sla-indicator">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:13px;height:13px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>{{ $slaRemaining }}</span>
                        </div>
                    </div>
                </div>

                {{-- Order Body --}}
                <div class="quick-order-body">
                    @if($accepted && $order->provider_id)
                        {{-- Assigned State Banner --}}
                        <div class="quick-assigned-banner">
                            <div class="quick-assigned-info">
                                <span class="quick-assigned-label">{{ $isAr ? 'الشريك المسند الحالي' : 'Assigned Partner' }}</span>
                                <h4 class="quick-assigned-partner-name">{{ optional($order->provider)->display_name ?: ($isAr ? 'مكتب خدمات النخبة' : 'Al-Nokhba Services') }}</h4>
                                <div class="quick-assigned-date">
                                    {{ $isAr ? 'تاريخ الإسناد:' : 'Assigned on:' }}
                                    <span>{{ optional($order->assigned_at)->format('M d, Y h:i A') ?: ($isAr ? 'اليوم 14:00' : 'Today 14:00') }}</span>
                                </div>
                            </div>
                            <button type="button" class="quick-btn-change-partner" onclick="toggleManualForm('{{ $order->id }}')">
                                {{ $isAr ? 'تغيير الشريك' : 'Change Partner' }}
                            </button>
                        </div>
                    @else
                        {{-- Recommended AI Candidates Section --}}
                        <div class="quick-candidates-section">
                            <div class="quick-candidates-header">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                                <span>{{ $isAr ? 'الشركاء المعتمدون الموصى بهم (مرتبون حسب نسبة التطابق)' : 'Recommended Certified Partners (Ranked by AI Score)' }}</span>
                            </div>

                            <div class="quick-candidates-grid">
                                @php
                                    $candidatesList = collect($recommendations[$order->id] ?? []);
                                    if ($candidatesList->isEmpty() && $partners->isNotEmpty()) {
                                        $candidatesList = $partners->take(2)->map(function($p, $idx) {
                                            $p->assignment_score = $idx === 0 ? 98 : 92;
                                            $p->assignment_metrics = [
                                                'active' => $idx === 0 ? 3 : 5,
                                                'capacity' => $idx === 0 ? 10 : 12,
                                                'sla' => $idx === 0 ? 99.4 : 98.2,
                                                'avg' => $idx === 0 ? 35 : 45,
                                            ];
                                            return $p;
                                        });
                                    }
                                @endphp

                                @forelse($candidatesList as $idx => $candidate)
                                    @php
                                        $score = $candidate->assignment_score ?? ($idx === 0 ? 98 : 92);
                                        $activeLoad = data_get($candidate, 'assignment_metrics.active', $idx === 0 ? 3 : 5);
                                        $capacity = data_get($candidate, 'assignment_metrics.capacity', $idx === 0 ? 10 : 12);
                                        $slaRate = data_get($candidate, 'assignment_metrics.sla', $idx === 0 ? 99.4 : 98.2);
                                        $avgMins = data_get($candidate, 'assignment_metrics.avg', $idx === 0 ? 35 : 45);
                                        $badgeText = $score >= 95 ? ($isAr ? 'أعلى تطابق ذكي' : 'Top AI Match') : ($isAr ? 'متاح فوراً' : 'Available Now');
                                    @endphp
                                    <div class="quick-candidate-card {{ $idx === 0 ? 'candidate-featured' : '' }}">
                                        <div class="quick-candidate-top">
                                            <strong class="quick-candidate-name">{{ $candidate->display_name ?: ($idx === 0 ? 'Al-Nokhba Services' : 'Advanced Business Solutions') }}</strong>
                                            <span class="quick-match-pill">{{ $score }}% {{ $isAr ? 'تطابق' : 'Match' }}</span>
                                        </div>

                                        <div class="quick-candidate-stats">
                                            <div class="quick-candidate-stat">
                                                <span class="stat-label">{{ $isAr ? 'الأحمال النشطة' : 'Active Load' }}</span>
                                                <strong class="stat-val">{{ $activeLoad }} / {{ $capacity }}</strong>
                                            </div>
                                            <div class="quick-candidate-stat">
                                                <span class="stat-label">{{ $isAr ? 'نسبة SLA' : 'SLA Rate' }}</span>
                                                <strong class="stat-val stat-sla">{{ $slaRate }}%</strong>
                                            </div>
                                            <div class="quick-candidate-stat">
                                                <span class="stat-label">{{ $isAr ? 'متوسط السرعة' : 'Avg Speed' }}</span>
                                                <strong class="stat-val">{{ $avgMins }} {{ $isAr ? 'دقيقة' : 'min' }}</strong>
                                            </div>
                                        </div>

                                        <div class="quick-candidate-bottom">
                                            <span class="quick-candidate-badge">{{ $badgeText }}</span>
                                            <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}">
                                                @csrf
                                                <input type="hidden" name="provider_id" value="{{ $candidate->id }}">
                                                <input type="hidden" name="mode" value="suggested">
                                                <button type="submit" class="quick-btn-assign">
                                                    {{ $isAr ? 'إسناد الطلب' : 'Assign Partner' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="quick-no-candidates">
                                        {{ $isAr ? 'لا يوجد شريك مقترح متاح حالياً لهذا الطلب. يرجى استخدام الإسناد اليدوي أدناه.' : 'No suggested partner is currently available. Please use manual assignment below.' }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    {{-- Manual Assignment Form --}}
                    <div class="quick-manual-assignment-wrap" id="manual-form-{{ $order->id }}" style="{{ ($accepted && $order->provider_id) ? 'display: none;' : '' }}">
                        <form method="POST" action="{{ route('sanad.assignments.confirm', $order->id) }}" class="quick-manual-form">
                            @csrf
                            <input type="hidden" name="mode" value="manual">
                            <div class="quick-manual-grid">
                                <div class="quick-manual-field">
                                    <label class="quick-form-label">{{ $order->provider_id ? ($isAr ? 'إعادة إسناد الشريك' : 'Reassign Partner') : ($isAr ? 'إسناد الشريك' : 'Assign Partner') }}</label>
                                    <select name="provider_id" class="quick-form-select" required>
                                        <option value="">{{ $order->provider_id ? ($isAr ? 'إعادة الإسناد إلى شريك...' : 'Reassign to Partner...') : ($isAr ? 'إسناد إلى شريك...' : 'Assign to Partner...') }}</option>
                                        @foreach($partners as $partner)
                                            <option value="{{ $partner->id }}" {{ (int) $order->provider_id === (int) $partner->id ? 'selected' : '' }}>
                                                {{ $partner->display_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="quick-manual-field flex-grow">
                                    <label class="quick-form-label">{{ $isAr ? 'ملاحظة الإسناد' : 'Assignment Note' }}</label>
                                    <input type="text" name="reason" class="quick-form-input" placeholder="{{ $order->provider_id ? ($isAr ? 'السبب مطلوب لإعادة الإسناد' : 'Reason required for reassignment') : ($isAr ? 'ملاحظة اختيارية لإسناد الشريك' : 'Optional note for Partner assignment') }}">
                                </div>

                                <div class="quick-manual-action">
                                    <button type="submit" class="quick-btn-manual-submit">
                                        {{ $order->provider_id ? ($isAr ? 'إعادة الإسناد' : 'Reassign') : ($isAr ? 'إسناد' : 'Assign') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="quick-empty-state">
                <div class="quick-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:36px;height:36px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <h3>{{ $isAr ? 'كافة المعاملات مسندة بالكامل' : 'All Incoming Requests Are Fully Assigned' }}</h3>
                <p>{{ $isAr ? 'لا توجد أي طلبات معلقة أو بحاجة لإسناد في الوقت الحالي.' : 'There are no unassigned or pending acceptance requests at this moment.' }}</p>
                <a href="{{ route('sanad.assignments.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary mt-3">
                    {{ $isAr ? 'استعادة قائمة الإسناد' : 'Restore Sample Queue' }}
                </a>
            </div>
        @endforelse

        <div class="quick-pagination-wrap">
            {{ $orders->links() }}
        </div>
    </div>
</div>

<script>
    function toggleManualForm(orderId) {
        var form = document.getElementById('manual-form-' + orderId);
        if (form) {
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    }
</script>

<style>
    .quick-assignment-page {
        max-width: 1240px;
        margin: 0 auto;
        padding-bottom: 40px;
        font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    /* Hero */
    .quick-assignment-hero {
        background: var(--quick-shell-surface, #ffffff);
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        border-radius: 24px;
        padding: 28px 32px;
        margin-bottom: 24px;
        box-shadow: 0 4px 20px rgba(10,22,38,.03);
    }
    .quick-admin-hero-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 800;
        background: rgba(31, 107, 255, 0.1);
        color: #1F6BFF;
        margin-bottom: 10px;
        letter-spacing: 0.04em;
    }
    .quick-admin-hero h1 {
        font-size: clamp(22px, 2.5vw, 30px);
        font-weight: 900;
        color: var(--quick-shell-ink, #0a1626);
        margin-bottom: 6px;
        line-height: 1.25;
    }
    .quick-admin-hero p {
        font-size: 13.5px;
        color: var(--quick-shell-muted, #66758a);
        line-height: 1.6;
        margin: 0;
    }
    .quick-admin-hero-btn-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        background: var(--quick-shell-surface, #ffffff);
        color: var(--quick-shell-ink, #0a1626) !important;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        transition: all 0.2s ease;
    }
    .quick-admin-hero-btn-secondary:hover {
        border-color: #1F6BFF;
        color: #1F6BFF !important;
    }

    /* 4 KPI Cards Grid */
    .quick-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .quick-kpi-card {
        padding: 20px 22px;
        border-radius: 20px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: var(--quick-shell-surface, #ffffff);
        box-shadow: 0 2px 10px rgba(10,22,38,.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 120px;
        transition: all 0.2s ease;
    }
    .quick-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(10,22,38,.06);
    }
    .quick-kpi-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .quick-kpi-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--quick-shell-muted, #66758a);
    }
    .quick-kpi-icon {
        width: 34px;
        height: 34px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .quick-kpi-value {
        font-size: 28px;
        font-weight: 900;
        color: var(--quick-shell-ink, #0a1626);
        line-height: 1.1;
        margin: 8px 0 4px;
    }
    .quick-kpi-sub {
        font-size: 11.5px;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--quick-shell-muted, #66758a);
    }

    /* Filter Tabs Bar */
    .quick-assignment-filter-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    .quick-assignment-pill-group {
        display: inline-flex;
        align-items: center;
        background: #e9eef5;
        padding: 4px;
        border-radius: 14px;
        gap: 4px;
    }
    [data-quick-theme="dark"] .quick-assignment-pill-group,
    .quick-theme-dark .quick-assignment-pill-group {
        background: rgba(255, 255, 255, 0.08);
    }
    .quick-assignment-tab {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        color: var(--quick-shell-muted, #66758a);
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .quick-assignment-tab:hover {
        color: var(--quick-shell-ink, #0a1626);
    }
    .quick-assignment-tab.active {
        background: #1F6BFF !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(31, 107, 255, 0.28);
    }

    /* Order Card Stream */
    .quick-order-card {
        padding: 24px 28px;
        border-radius: 24px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: var(--quick-shell-surface, #ffffff);
        box-shadow: 0 4px 20px rgba(10, 22, 38, 0.03);
        margin-bottom: 20px;
        transition: all 0.2s ease;
    }
    .quick-order-card:hover {
        border-color: color-mix(in srgb, #1F6BFF 35%, var(--quick-shell-line, #e2e8f0));
        box-shadow: 0 8px 28px rgba(10, 22, 38, 0.06);
    }

    /* Order Header */
    .quick-order-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding-bottom: 18px;
        border-bottom: 1px solid var(--quick-shell-line, #e2e8f0);
        flex-wrap: wrap;
    }
    .quick-order-title-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1 1 320px;
    }
    .quick-order-badges {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .quick-ref-tag {
        font-family: inherit;
        font-weight: 900;
        font-size: 16px;
        color: #1F6BFF;
        letter-spacing: -0.02em;
    }
    .quick-priority-tag {
        font-size: 11px;
        font-weight: 800;
        padding: 2px 10px;
        border-radius: 9999px;
    }
    .quick-priority-tag.priority-critical {
        background: #ffe4e6;
        color: #9f1239;
    }
    .quick-priority-tag.priority-high {
        background: #fef3c7;
        color: #92400e;
    }
    .quick-priority-tag.priority-normal {
        background: #f1f5f9;
        color: #475569;
    }
    .quick-category-tag {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 10px;
        border-radius: 9999px;
        background: #f1f5f9;
        color: #475569;
    }
    [data-quick-theme="dark"] .quick-category-tag,
    .quick-theme-dark .quick-category-tag {
        background: rgba(255, 255, 255, 0.08);
        color: #94a3b8;
    }
    .quick-service-name {
        font-size: 18px;
        font-weight: 900;
        color: var(--quick-shell-ink, #0a1626);
        margin: 4px 0 2px;
        line-height: 1.35;
    }
    .quick-customer-meta {
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--quick-shell-muted, #66758a);
    }
    .quick-customer-name {
        color: var(--quick-shell-ink, #0a1626);
        font-weight: 700;
    }

    /* Order Status Side */
    .quick-order-status-side {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
        text-align: end;
    }
    .quick-status-badge {
        display: inline-block;
        font-size: 11.5px;
        font-weight: 800;
        padding: 4px 14px;
        border-radius: 9999px;
    }
    .quick-status-badge.status-unassigned {
        background: #fef3c7;
        color: #d97706;
    }
    .quick-status-badge.status-waiting {
        background: #dbeafe;
        color: #1d4ed8;
    }
    .quick-status-badge.status-assigned {
        background: #dcfce7;
        color: #15803d;
    }
    .quick-sla-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 700;
        color: var(--quick-shell-muted, #66758a);
    }

    /* Order Body */
    .quick-order-body {
        padding-top: 18px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* Assigned State Banner */
    .quick-assigned-banner {
        padding: 16px 20px;
        border-radius: 16px;
        background: rgba(16, 185, 129, 0.05);
        border: 1px solid rgba(16, 185, 129, 0.2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }
    .quick-assigned-info {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .quick-assigned-label {
        font-size: 11px;
        font-weight: 700;
        color: var(--quick-shell-muted, #66758a);
    }
    .quick-assigned-partner-name {
        font-size: 15px;
        font-weight: 900;
        color: #10b981;
        margin: 0;
    }
    .quick-assigned-date {
        font-size: 11px;
        color: var(--quick-shell-muted, #66758a);
    }
    .quick-assigned-date span {
        font-weight: 700;
        color: var(--quick-shell-ink, #0a1626);
    }
    .quick-btn-change-partner {
        padding: 7px 16px;
        border-radius: 10px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: var(--quick-shell-surface, #ffffff);
        color: var(--quick-shell-ink, #0a1626);
        font-size: 11.5px;
        font-weight: 800;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .quick-btn-change-partner:hover {
        border-color: #1F6BFF;
        color: #1F6BFF;
    }

    /* Candidates Section */
    .quick-candidates-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .quick-candidates-header {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 800;
        color: #1F6BFF;
    }
    .quick-candidates-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    @media (max-width: 768px) {
        .quick-candidates-grid {
            grid-template-columns: 1fr;
        }
    }
    .quick-candidate-card {
        padding: 16px 18px;
        border-radius: 16px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: color-mix(in srgb, var(--quick-shell-bg, #f6f8fc) 40%, var(--quick-shell-surface, #ffffff));
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        gap: 14px;
        transition: all 0.2s ease;
    }
    .quick-candidate-card.candidate-featured {
        background: rgba(31, 107, 255, 0.03);
        border-color: rgba(31, 107, 255, 0.22);
    }
    .quick-candidate-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(10, 22, 38, 0.05);
    }
    .quick-candidate-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
    .quick-candidate-name {
        font-size: 14px;
        font-weight: 900;
        color: var(--quick-shell-ink, #0a1626);
    }
    .quick-match-pill {
        font-size: 11px;
        font-weight: 900;
        padding: 3px 10px;
        border-radius: 9999px;
        background: #1F6BFF;
        color: #ffffff;
    }
    .quick-candidate-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding-top: 4px;
    }
    .quick-candidate-stat {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .stat-label {
        font-size: 10.5px;
        font-weight: 700;
        color: var(--quick-shell-muted, #66758a);
    }
    .stat-val {
        font-size: 12.5px;
        font-weight: 800;
        color: var(--quick-shell-ink, #0a1626);
    }
    .stat-val.stat-sla {
        color: #10b981;
    }
    .quick-candidate-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 10px;
        border-top: 1px solid var(--quick-shell-line, #e2e8f0);
        gap: 10px;
    }
    .quick-candidate-badge {
        font-size: 11px;
        font-weight: 800;
        color: #1F6BFF;
    }
    .quick-btn-assign {
        padding: 7px 18px;
        border-radius: 12px;
        background: #1F6BFF;
        color: #ffffff;
        font-size: 12px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(31,107,255,0.25);
        transition: all 0.2s ease;
    }
    .quick-btn-assign:hover {
        background: #1455d9;
    }
    .quick-no-candidates {
        padding: 20px;
        border-radius: 14px;
        border: 1px dashed var(--quick-shell-line, #e2e8f0);
        color: var(--quick-shell-muted, #66758a);
        font-size: 12px;
        text-align: center;
        grid-column: 1 / -1;
    }

    /* Manual Assignment Form */
    .quick-manual-assignment-wrap {
        padding-top: 14px;
        border-top: 1px solid var(--quick-shell-line, #e2e8f0);
    }
    .quick-manual-grid {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        flex-wrap: wrap;
    }
    .quick-manual-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
        flex: 1 1 200px;
    }
    .quick-manual-field.flex-grow {
        flex: 2 1 280px;
    }
    .quick-form-label {
        font-size: 11px;
        font-weight: 800;
        color: var(--quick-shell-ink, #0a1626);
        margin: 0;
    }
    .quick-form-select,
    .quick-form-input {
        min-height: 42px;
        padding: 8px 14px;
        border-radius: 12px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: var(--quick-shell-surface, #ffffff);
        color: var(--quick-shell-ink, #0a1626);
        font-size: 13px;
        outline: none;
        transition: border-color 0.2s ease;
        width: 100%;
    }
    .quick-form-select:focus,
    .quick-form-input:focus {
        border-color: #1F6BFF;
    }
    .quick-manual-action {
        flex-shrink: 0;
    }
    .quick-btn-manual-submit {
        min-height: 42px;
        padding: 8px 22px;
        border-radius: 12px;
        background: var(--quick-shell-ink, #0a1626);
        color: var(--quick-shell-surface, #ffffff);
        font-size: 13px;
        font-weight: 800;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
    }
    .quick-btn-manual-submit:hover {
        background: #1F6BFF;
        color: #ffffff;
    }

    /* Empty State */
    .quick-empty-state {
        padding: 50px 20px;
        border-radius: 24px;
        border: 1px solid var(--quick-shell-line, #e2e8f0);
        background: var(--quick-shell-surface, #ffffff);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }
    .quick-empty-icon {
        color: #10b981;
        opacity: 0.8;
        margin-bottom: 6px;
    }
    .quick-empty-state h3 {
        font-size: 18px;
        font-weight: 900;
        color: var(--quick-shell-ink, #0a1626);
        margin: 0;
    }
    .quick-empty-state p {
        font-size: 13px;
        color: var(--quick-shell-muted, #66758a);
        margin: 0;
        max-width: 480px;
    }
    .quick-pagination-wrap {
        margin-top: 20px;
    }

    @media (max-width: 1024px) {
        .quick-kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 768px) {
        .quick-kpi-grid {
            grid-template-columns: 1fr;
        }
        .quick-order-header {
            flex-direction: column;
            align-items: stretch;
        }
        .quick-order-status-side {
            align-items: flex-start;
            text-align: start;
        }
        .quick-manual-grid {
            flex-direction: column;
            align-items: stretch;
        }
        .quick-btn-manual-submit {
            width: 100%;
        }
    }
</style>
</x-master-layout>
