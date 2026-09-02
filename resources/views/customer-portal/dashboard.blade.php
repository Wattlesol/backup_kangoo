<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $customerName = $user->display_name ?? $user->first_name ?? $user->email;
    $activeRequests = collect($activeRequests);
    $totalRequests = $stats['total'];
    $weeklyActivity = collect($weeklyActivity);
    $weeklyMaximum = max(1, (int) $weeklyActivity->max('total'));
    $stageProgress = ['submitted'=>15,'pending_review'=>25,'assigned_to_partner'=>40,'assigned_to_employee'=>55,'in_progress'=>70,'awaiting_customer_action'=>65,'awaiting_quality_review'=>85,'escalated'=>60,'completed'=>100,'closed'=>100];
@endphp
<style>
    .quick-customer-dashboard{max-width:1480px;margin-inline:auto;color:var(--quick-shell-ink,#0a1626)}
    .quick-customer-hero{display:flex;align-items:center;justify-content:space-between;gap:24px;padding:30px 34px;border:1px solid #174f91;border-radius:24px;background:linear-gradient(135deg,#0f2933,#1769ff);color:#fff;box-shadow:0 12px 30px rgba(15,41,51,.16);overflow:hidden}
    .quick-customer-hero h1{margin:10px 0 5px;color:#fff;font-size:clamp(25px,3vw,36px);font-weight:900;letter-spacing:-.03em}.quick-customer-hero p{margin:0;color:rgba(255,255,255,.8)}
    .quick-ai-badge{display:inline-flex;align-items:center;gap:7px;padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.17);font-size:12px;font-weight:800}
    .quick-hero-actions{display:flex;flex-wrap:wrap;gap:10px}.quick-hero-actions .sanad-btn{white-space:nowrap;border-radius:14px;padding:12px 17px}.quick-hero-actions .secondary{border-color:rgba(255,255,255,.28);background:rgba(255,255,255,.1);color:#fff}
    .quick-section-head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin:28px 0 14px}.quick-eyebrow{display:block;color:#1769ff;font-size:11px;font-weight:900;letter-spacing:.14em;text-transform:uppercase}.quick-section-head h2{margin:5px 0 0;font-size:23px;font-weight:900}
    .quick-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.quick-kpi{padding:20px;border:1px solid #d8e4f2;border-radius:18px;background:#fff;box-shadow:0 7px 22px rgba(15,41,51,.04)}.quick-kpi-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.quick-kpi-label{color:#52657e;font-size:12px;font-weight:800}.quick-kpi-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:#edf4ff;color:#1769ff}.quick-kpi strong{display:block;margin-top:16px;font-size:32px;font-weight:900}.quick-kpi small{color:#6a7c93;font-weight:600}.quick-kpi.active strong{color:#0891b2}.quick-kpi.completed strong{color:#059669}.quick-kpi.action strong{color:#d97706}
    .quick-insights{display:grid;grid-template-columns:minmax(0,1.25fr) minmax(290px,.75fr);gap:16px;margin-top:16px}.quick-panel{border:1px solid #d8e4f2;border-radius:18px;background:#fff;box-shadow:0 7px 22px rgba(15,41,51,.04);padding:22px}.quick-panel-title{font-size:15px;font-weight:900}.quick-panel-subtitle{margin-top:3px;color:#6a7c93;font-size:12px}.quick-chart{display:grid;grid-template-columns:repeat(7,1fr);align-items:end;gap:10px;height:180px;margin-top:22px}.quick-chart-column{display:flex;height:100%;flex-direction:column;justify-content:flex-end;gap:7px;text-align:center}.quick-chart-value{min-height:18px;color:#52657e;font-size:11px;font-weight:900}.quick-chart-bars{display:flex;align-items:flex-end;justify-content:center;gap:3px;height:125px;border-bottom:1px solid #dce6f2}.quick-chart-bar{display:block;width:min(18px,42%);min-height:3px;border-radius:7px 7px 2px 2px;background:#1769ff}.quick-chart-bar.completed{background:#20c5e8}.quick-chart-column small{color:#8a9ab0;font-weight:800}.quick-chart-legend{display:flex;gap:14px;margin-top:12px;color:#6a7c93;font-size:11px;font-weight:700}.quick-chart-legend span{display:inline-flex;align-items:center;gap:5px}.quick-chart-legend i{width:9px;height:9px;border-radius:3px;background:#1769ff}.quick-chart-legend .completed i{background:#20c5e8}
    .quick-action-list{display:grid;gap:9px;margin-top:16px}.quick-action-item{display:flex;align-items:center;gap:12px;min-height:58px;padding:9px 12px;border:1px solid #e5edf7;border-radius:13px;color:inherit;text-decoration:none}.quick-action-item:hover{border-color:#8db4ff;background:#f7faff;color:inherit}.quick-action-item i{display:grid;place-items:center;width:38px;height:38px;border-radius:11px;background:#edf4ff;color:#1769ff}.quick-action-item span{flex:1;font-size:12px;font-weight:800}.quick-action-item strong{font-size:19px}
    .quick-tracker{margin-top:18px}.quick-request-card{display:grid;grid-template-columns:minmax(170px,1.2fr) minmax(130px,.8fr) minmax(120px,.7fr) auto;gap:18px;align-items:center;padding:15px 0;border-bottom:1px solid #e5edf7}.quick-request-card:last-child{border-bottom:0}.quick-request-ref{color:#1769ff;font-weight:900}.quick-request-service{margin-top:3px;font-weight:800}.quick-request-meta{color:#6a7c93;font-size:12px}.quick-empty{padding:28px;text-align:center;color:#6a7c93}
    body.quick-theme-dark .quick-kpi,body.quick-theme-dark .quick-panel,[data-quick-theme="dark"] .quick-kpi,[data-quick-theme="dark"] .quick-panel{border-color:#213b58;background:#0d2136;color:#f4f8ff}body.quick-theme-dark .quick-kpi-label,body.quick-theme-dark .quick-panel-subtitle,[data-quick-theme="dark"] .quick-kpi-label,[data-quick-theme="dark"] .quick-panel-subtitle{color:#b9c9dc}body.quick-theme-dark .quick-action-item,[data-quick-theme="dark"] .quick-action-item{border-color:#213b58}body.quick-theme-dark .quick-action-item:hover,[data-quick-theme="dark"] .quick-action-item:hover{background:#10283f}body.quick-theme-dark .quick-request-card,[data-quick-theme="dark"] .quick-request-card{border-color:#213b58}
    @media(max-width:991px){.quick-customer-hero{align-items:flex-start;flex-direction:column}.quick-kpi-grid{grid-template-columns:repeat(2,1fr)}.quick-insights{grid-template-columns:1fr}.quick-request-card{grid-template-columns:1fr 1fr}.quick-request-card .sanad-btn{justify-self:start}}
    @media(max-width:575px){.quick-customer-hero{padding:23px 19px;border-radius:18px}.quick-hero-actions,.quick-hero-actions .sanad-btn{width:100%}.quick-section-head{align-items:flex-start;flex-direction:column}.quick-kpi-grid{grid-template-columns:1fr}.quick-request-card{grid-template-columns:1fr;gap:9px}.quick-panel{padding:17px}.quick-chart{gap:5px;height:145px}}
</style>

<div class="quick-customer-dashboard" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <section class="quick-customer-hero">
        <div>
            <span class="quick-ai-badge"><i class="fas fa-robot"></i>{{ $isAr ? 'مساعد كويك الذكي متاح' : 'Quick AI Assistant Enabled' }}</span>
            <h1>{{ $isAr ? 'مرحباً ' . $customerName . '، كيف يمكننا مساعدتك اليوم؟' : 'Hello ' . $customerName . ', how can we help today?' }}</h1>
            <p>{{ $isAr ? 'أنجز معاملات منشأتك عبر مكاتب معتمدة مع متابعة حية لكل مرحلة.' : 'Execute business transactions across certified offices with real-time stage tracking.' }}</p>
        </div>
        <div class="quick-hero-actions">
            <a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}"><i class="fas fa-plus"></i>{{ $isAr ? 'طلب معاملة حكومية جديدة' : 'New Government Request' }}</a>
            <a class="sanad-btn secondary" href="{{ route('customer-portal.catalog') }}"><i class="fas fa-search"></i>{{ $isAr ? 'تصفح دليل الخدمات' : 'Explore Catalog' }}</a>
        </div>
    </section>

    <div class="quick-section-head">
        <div><span class="quick-eyebrow">{{ $isAr ? 'نظرة تشغيلية' : 'Operational overview' }}</span><h2>{{ $isAr ? 'كل ما يحتاج انتباهك اليوم' : 'Everything requiring your attention today' }}</h2></div>
        <a class="sanad-btn ghost" href="{{ route('customer-portal.requests.index') }}">{{ $isAr ? 'عرض جميع الطلبات' : 'View all requests' }}</a>
    </div>

    <section class="quick-kpi-grid">
        <article class="quick-kpi"><div class="quick-kpi-top"><span class="quick-kpi-label">{{ $isAr ? 'إجمالي الطلبات' : 'Total requests' }}</span><i class="quick-kpi-icon fas fa-folder-open"></i></div><strong>{{ $totalRequests }}</strong><small>{{ $isAr ? 'جميع المعاملات' : 'All transactions' }}</small></article>
        <article class="quick-kpi active"><div class="quick-kpi-top"><span class="quick-kpi-label">{{ $isAr ? 'طلبات نشطة' : 'Active requests' }}</span><i class="quick-kpi-icon fas fa-wave-square"></i></div><strong>{{ $stats['active'] }}</strong><small>{{ $isAr ? 'قيد المتابعة' : 'Currently tracked' }}</small></article>
        <article class="quick-kpi completed"><div class="quick-kpi-top"><span class="quick-kpi-label">{{ $isAr ? 'معاملات مكتملة' : 'Completed requests' }}</span><i class="quick-kpi-icon fas fa-check-circle"></i></div><strong>{{ $stats['completed'] }}</strong><small>{{ $isAr ? 'تم تسليمها' : 'Successfully delivered' }}</small></article>
        <article class="quick-kpi action"><div class="quick-kpi-top"><span class="quick-kpi-label">{{ $isAr ? 'إجراء مطلوب' : 'Action required' }}</span><i class="quick-kpi-icon fas fa-exclamation-circle"></i></div><strong>{{ $stats['pending_actions'] }}</strong><small>{{ $isAr ? 'يتطلب انتباهك' : 'Needs your attention' }}</small></article>
    </section>

    <section class="quick-insights">
        <article class="quick-panel"><div class="quick-panel-title">{{ $isAr ? 'نشاط الطلبات — آخر 7 أيام' : 'Request activity — last 7 days' }}</div><div class="quick-panel-subtitle">{{ $isAr ? 'بيانات حية للطلبات الجديدة والمكتملة' : 'Live data for new and completed requests' }}</div><div class="quick-chart">@foreach($weeklyActivity as $day)<div class="quick-chart-column" title="{{ $day['date'] }} · {{ $isAr ? 'جديدة' : 'New' }}: {{ $day['submissions'] }} · {{ $isAr ? 'مكتملة' : 'Completed' }}: {{ $day['completions'] }}"><span class="quick-chart-value">{{ $day['total'] }}</span><div class="quick-chart-bars"><i class="quick-chart-bar" style="height:{{ $day['submissions'] ? max(8, round(($day['submissions'] / $weeklyMaximum) * 100)) : 3 }}%"></i><i class="quick-chart-bar completed" style="height:{{ $day['completions'] ? max(8, round(($day['completions'] / $weeklyMaximum) * 100)) : 3 }}%"></i></div><small>{{ $isAr ? $day['day_ar'] : Str::substr($day['day_en'], 0, 1) }}</small></div>@endforeach</div><div class="quick-chart-legend"><span><i></i>{{ $isAr ? 'طلبات جديدة' : 'New requests' }}</span><span class="completed"><i></i>{{ $isAr ? 'طلبات مكتملة' : 'Completed requests' }}</span></div></article>
        <article class="quick-panel"><div class="quick-panel-title">{{ $isAr ? 'مركز الإجراءات' : 'Action center' }}</div><div class="quick-action-list">
            <a class="quick-action-item" href="{{ route('customer-portal.vault') }}"><i class="fas fa-file-alt"></i><span>{{ $isAr ? 'مستندات معلقة' : 'Pending documents' }}</span><strong>{{ $actionStats['pending_documents'] }}</strong></a>
            <a class="quick-action-item" href="{{ route('customer-portal.billing') }}"><i class="fas fa-credit-card"></i><span>{{ $isAr ? 'دفعات معلقة' : 'Pending payments' }}</span><strong>{{ $actionStats['pending_payments'] }}</strong></a>
            <a class="quick-action-item" href="{{ route('customer-portal.messages') }}"><i class="fas fa-comments"></i><span>{{ $isAr ? 'رسائل غير مقروءة' : 'Unread messages' }}</span><strong>{{ $actionStats['unread_messages'] }}</strong></a>
            <a class="quick-action-item" href="{{ route('customer-portal.vault') }}"><i class="fas fa-shield-alt"></i><span>{{ $isAr ? 'وثائق معتمدة' : 'Approved documents' }}</span><strong>{{ $actionStats['approved_documents'] }}</strong></a>
        </div></article>
    </section>

    <section class="quick-panel quick-tracker">
        <div class="quick-panel-title"><i class="far fa-clock text-primary mr-2"></i>{{ $isAr ? 'متابعة مسار المعاملات الحالية' : 'Live Request Tracker' }}</div>
        @forelse($activeRequests as $request)
            @php $progress = $stageProgress[$request->sanad_stage ?? $request->status] ?? 20; @endphp
            <div class="quick-request-card">
                <div><a class="quick-request-ref" href="{{ route('customer-portal.requests.show', $request->id) }}">{{ $request->quick_reference }}</a><div class="quick-request-service">{{ localized_model_name($request->service) }}</div></div>
                <div><span class="sanad-badge">{{ quick_status_label($request->sanad_stage ?? $request->status) }}</span><div class="quick-request-meta mt-2">{{ $isAr ? 'فريق كويك' : 'Quick team' }}</div></div>
                <div><div class="sanad-progress"><span style="width:{{ $progress }}%"></span></div><div class="quick-request-meta mt-2">{{ $progress }}% · {{ optional($request->expected_completion_at)->format('Y-m-d') ?? ($isAr ? 'قيد المتابعة' : 'In progress') }}</div></div>
                <a class="sanad-btn" href="{{ route('customer-portal.requests.show', $request->id) }}">{{ $isAr ? 'تتبع' : 'Track' }}</a>
            </div>
        @empty
            <div class="quick-empty">{{ $isAr ? 'لا توجد طلبات نشطة حالياً.' : 'No active requests right now.' }}</div>
        @endforelse
    </section>
</div>
</x-master-layout>
