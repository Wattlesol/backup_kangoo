<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $kpis = array_values($dashboard['kpis']);
    $totalOrders = (int) ($kpis[0] ?? 0);
    $newOrders = (int) ($kpis[1] ?? 0);
    $inProgress = (int) ($kpis[2] ?? 0);
    $completedOrders = (int) ($kpis[3] ?? 0);
    $delayedOrders = (int) ($kpis[4] ?? 0);
    $activeOrders = max(0, $totalOrders - $completedOrders);
    $slaCompliance = $activeOrders > 0 ? max(0, round(100 - (($delayedOrders / $activeOrders) * 100), 1)) : 100;
    $activeEmployees = (int) ($kpis[9] ?? 0);
    $currentWorkload = (int) ($kpis[10] ?? 0);
    $totalCapacity = max(1, (int) collect($dashboard['employee_workload'])->sum(fn ($employee) => (int) ($employee->sanad_daily_capacity ?: 0)));
    $capacityUtilization = $activeEmployees > 0 ? min(100, round(($currentWorkload / $totalCapacity) * 100)) : 0;
    $monthlyRevenue = $kpis[11] ?? getPriceFormat(0);
    $pendingSettlement = $kpis[12] ?? getPriceFormat(0);
    $partnerName = $auth_user->display_name ?: trim(($auth_user->first_name ?? '').' '.($auth_user->last_name ?? '')) ?: ($isAr ? 'مكتب الشريك' : 'Partner office');
    $priorityRanks = ['urgent'=>0,'critical'=>0,'high'=>1,'normal'=>2,'low'=>3];
    $priorityOrders = collect($dashboard['recent_orders'])->sortBy(fn ($order) => $priorityRanks[strtolower($order->sanad_priority ?: 'normal')] ?? 2)->take(5);
@endphp
<style>
    .quick-partner-dashboard{max-width:1480px;margin-inline:auto;padding:26px 4px 56px;color:var(--quick-shell-ink,#0a1626)}
    .partner-hero{display:flex;align-items:center;justify-content:space-between;gap:26px;padding:30px 34px;border:1px solid #164e91;border-radius:24px;background:linear-gradient(125deg,#0f2933 0%,#174c91 58%,#1f6bff 100%)!important;color:#fff;box-shadow:0 18px 38px rgba(15,41,51,.16)}
    .partner-hero-copy{max-width:780px}.partner-verified{display:inline-flex;align-items:center;gap:7px;padding:6px 11px;border-radius:999px;background:rgba(255,255,255,.15);font-size:11px;font-weight:800}.partner-hero h1{margin:10px 0 5px;color:#fff;font-size:clamp(25px,3vw,36px);font-weight:900;letter-spacing:-.03em}.partner-hero p{margin:0;color:rgba(255,255,255,.82);font-size:13px;line-height:1.8}.partner-hero-actions{display:grid;gap:10px;min-width:220px}.partner-hero-actions a{display:flex;align-items:center;justify-content:center;gap:8px;min-height:44px;border:1px solid rgba(255,255,255,.28);border-radius:12px;background:rgba(255,255,255,.1);color:#fff;font-size:12px;font-weight:800;text-decoration:none}.partner-hero-actions a.primary{border-color:#10b981;background:#10b981}.partner-hero-actions a:hover{color:#fff;filter:brightness(1.05)}
    .partner-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px;margin-top:22px}.partner-kpi{padding:20px;border:1px solid var(--quick-shell-line,#d8e4f2);border-radius:18px;background:var(--quick-shell-surface,#fff);box-shadow:0 7px 22px rgba(15,41,51,.04)}.partner-kpi-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.partner-kpi-label{color:var(--quick-shell-muted,#60728a);font-size:12px;font-weight:800}.partner-kpi-icon{display:grid;width:40px;height:40px;place-items:center;border-radius:13px;background:#edf4ff;color:#1769ff}.partner-kpi strong{display:block;margin-top:15px;font-size:30px;font-weight:900}.partner-kpi small{display:block;margin-top:3px;color:var(--quick-shell-muted,#6a7c93);font-size:10px}.partner-kpi.sla strong{color:#059669}.partner-kpi.capacity strong{color:#d97706}.partner-kpi.settlement strong{color:#8b5cf6}
    .partner-main-grid{display:grid;grid-template-columns:minmax(0,1.6fr) minmax(310px,.75fr);gap:18px;margin-top:18px}.partner-panel{min-width:0;border:1px solid var(--quick-shell-line,#d8e4f2);border-radius:18px;background:var(--quick-shell-surface,#fff);box-shadow:0 7px 22px rgba(15,41,51,.04);overflow:hidden}.partner-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:62px;padding:15px 20px;border-bottom:1px solid var(--quick-shell-line,#d8e4f2)}.partner-panel-head h2{margin:0;font-size:16px;font-weight:900}.partner-panel-head a{color:#1769ff;font-size:11px;font-weight:800}.partner-order-list{padding:0 20px}.partner-order{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(110px,.55fr) auto;gap:16px;align-items:center;padding:16px 0;border-bottom:1px solid var(--quick-shell-line,#e5edf7)}.partner-order:last-child{border-bottom:0}.partner-order-ref{color:#1769ff;font-size:12px;font-weight:900}.partner-order-title{margin-top:3px;font-size:13px;font-weight:800}.partner-order-meta{display:flex;flex-wrap:wrap;gap:7px;margin-top:5px;color:var(--quick-shell-muted,#6a7c93);font-size:10px}.partner-priority{display:inline-flex;padding:4px 8px;border-radius:999px;background:#edf4ff;color:#1769ff;font-size:10px;font-weight:800}.partner-priority.high,.partner-priority.urgent,.partner-priority.critical{background:#fff1f2;color:#e11d48}.partner-order-value{color:#059669;font-size:12px;font-weight:900}.partner-order-action{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 13px;border-radius:10px;background:#1769ff;color:#fff!important;font-size:11px;font-weight:800;text-decoration:none}.partner-empty{padding:30px 20px;color:var(--quick-shell-muted,#6a7c93);text-align:center}
    .partner-team-list{display:grid;gap:10px;padding:16px}.partner-team-item{display:grid;grid-template-columns:40px 1fr auto;gap:10px;align-items:center;padding:11px;border:1px solid var(--quick-shell-line,#e5edf7);border-radius:13px}.partner-avatar{display:grid;width:40px;height:40px;place-items:center;border-radius:50%;background:#edf4ff;color:#1769ff;font-size:12px;font-weight:900}.partner-team-item strong{font-size:12px}.partner-team-item small{display:block;color:var(--quick-shell-muted,#6a7c93);font-size:9px}.partner-load{color:#1769ff;font-size:11px;font-weight:900}.partner-team-link{display:flex;align-items:center;justify-content:center;margin:0 16px 16px;min-height:40px;border:1px solid var(--quick-shell-line,#d8e4f2);border-radius:11px;color:var(--quick-shell-ink,#0a1626);font-size:11px;font-weight:800;text-decoration:none}
    body.quick-theme-dark .quick-partner-dashboard,[data-quick-theme="dark"] .quick-partner-dashboard{color:#f4f8ff}body.quick-theme-dark .partner-kpi-icon,body.quick-theme-dark .partner-avatar,[data-quick-theme="dark"] .partner-kpi-icon,[data-quick-theme="dark"] .partner-avatar{background:#102b48}
    @media(max-width:1050px){.partner-main-grid{grid-template-columns:1fr}.partner-kpi-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:700px){.quick-partner-dashboard{padding-top:14px}.partner-hero{align-items:flex-start;flex-direction:column;padding:23px 19px;border-radius:18px}.partner-hero-actions{width:100%}.partner-kpi-grid{grid-template-columns:1fr}.partner-order{grid-template-columns:1fr;gap:8px}.partner-order-action{justify-self:start}}
</style>

<div class="quick-partner-dashboard" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <section class="partner-hero">
        <div class="partner-hero-copy">
            <span class="partner-verified"><i class="fas fa-shield-alt"></i>{{ $isAr ? 'مكتب معتمد ومرخص' : 'Verified and licensed office' }} · PTR-{{ str_pad((string) $auth_user->id, 4, '0', STR_PAD_LEFT) }}</span>
            <h1>{{ $isAr ? 'مرحباً بك، ' . $partnerName : 'Welcome back, ' . $partnerName }}</h1>
            <p>{{ $isAr ? "لديك {$activeOrders} معاملة نشطة، {$newOrders} طلبات جديدة، و{$delayedOrders} معاملات تحتاج إلى متابعة. المستحقات المتاحة: {$pendingSettlement}." : "You have {$activeOrders} active transactions, {$newOrders} new orders, and {$delayedOrders} items requiring attention. Available settlement: {$pendingSettlement}." }}</p>
        </div>
        <div class="partner-hero-actions">
            <a class="primary" href="{{ route('provider.financial.index') }}"><i class="fas fa-wallet"></i>{{ $isAr ? 'عرض المستحقات وسحب الأرباح' : 'View settlement and payouts' }}</a>
            <a href="{{ route('provider.kanban.index') }}"><i class="fas fa-columns"></i>{{ $isAr ? 'فتح لوحة العمليات' : 'Open operations board' }}</a>
        </div>
    </section>

    <section class="partner-kpi-grid">
        <article class="partner-kpi"><div class="partner-kpi-head"><span class="partner-kpi-label">{{ $isAr ? 'الطلبات المسندة النشطة' : 'Active assigned orders' }}</span><i class="partner-kpi-icon fas fa-wave-square"></i></div><strong>{{ $activeOrders }}</strong><small>{{ $isAr ? "{$inProgress} قيد التنفيذ، {$newOrders} جديدة" : "{$inProgress} in progress, {$newOrders} new" }}</small></article>
        <article class="partner-kpi sla"><div class="partner-kpi-head"><span class="partner-kpi-label">{{ $isAr ? 'نسبة الالتزام باتفاقية الخدمة' : 'SLA compliance' }}</span><i class="partner-kpi-icon far fa-check-circle"></i></div><strong>{{ $slaCompliance }}%</strong><small>{{ $isAr ? "{$delayedOrders} معاملات متأخرة" : "{$delayedOrders} delayed orders" }}</small></article>
        <article class="partner-kpi capacity"><div class="partner-kpi-head"><span class="partner-kpi-label">{{ $isAr ? 'استخدام طاقة الفريق' : 'Team capacity utilization' }}</span><i class="partner-kpi-icon fas fa-users"></i></div><strong>{{ $capacityUtilization }}%</strong><small>{{ $isAr ? "{$activeEmployees} موظفين نشطين" : "{$activeEmployees} active employees" }}</small></article>
        <article class="partner-kpi settlement"><div class="partner-kpi-head"><span class="partner-kpi-label">{{ $isAr ? 'المستحقات المتاحة' : 'Available settlement' }}</span><i class="partner-kpi-icon fas fa-wallet"></i></div><strong>{{ $pendingSettlement }}</strong><small>{{ $isAr ? "إيراد الشهر {$monthlyRevenue}" : "Monthly revenue {$monthlyRevenue}" }}</small></article>
    </section>

    <section class="partner-main-grid">
        <article class="partner-panel">
            <div class="partner-panel-head"><h2><i class="far fa-clock text-primary mr-2"></i>{{ $isAr ? 'المعاملات العاجلة ذات الأولوية' : 'Priority transactions requiring action' }}</h2><a href="{{ route('provider.order.index') }}">{{ $isAr ? 'عرض جميع الطلبات' : 'View all orders' }}</a></div>
            <div class="partner-order-list">
                @forelse($priorityOrders as $order)
                    @php $priority = strtolower($order->sanad_priority ?: 'normal'); @endphp
                    <div class="partner-order">
                        <div><a class="partner-order-ref" href="{{ route('provider.order.show', $order->id) }}">{{ $order->quick_reference }}</a><div class="partner-order-title">{{ localized_model_name($order->service) }}</div><div class="partner-order-meta"><span>{{ optional($order->customer)->display_name ?: ($isAr ? 'عميل كويك' : 'Quick customer') }}</span><span>·</span><span>{{ quick_status_label($order->sanad_stage ?: 'submitted') }}</span><span class="partner-priority {{ $priority }}">{{ quick_status_label($priority) }}</span></div></div>
                        <div><div class="partner-order-value">{{ getPriceFormat(optional($order->payment)->total_amount ?? $order->total_amount ?? 0) }}</div><small class="text-muted">{{ optional($order->sla_due_at)->format('Y-m-d H:i') ?: ($isAr ? 'دون موعد' : 'No SLA') }}</small></div>
                        <a class="partner-order-action" href="{{ route('provider.order.show', $order->id) }}">{{ $isAr ? 'اتخاذ إجراء' : 'Take action' }}</a>
                    </div>
                @empty
                    <div class="partner-empty">{{ $isAr ? 'لا توجد معاملات مسندة تتطلب إجراءً الآن.' : 'No assigned transactions require action right now.' }}</div>
                @endforelse
            </div>
        </article>

        <aside class="partner-panel">
            <div class="partner-panel-head"><h2><i class="fas fa-users text-primary mr-2"></i>{{ $isAr ? 'طاقة فريق المكتب' : 'Office team capacity' }}</h2><span class="partner-priority">{{ $activeEmployees }} {{ $isAr ? 'نشط' : 'active' }}</span></div>
            <div class="partner-team-list">
                @forelse($dashboard['employee_workload'] as $employee)
                    <div class="partner-team-item"><span class="partner-avatar">{{ mb_substr($employee->display_name ?: 'E', 0, 1) }}</span><div><strong>{{ $employee->display_name }}</strong><small>{{ $employee->sanad_job_title ?: ($isAr ? 'موظف عمليات' : 'Operations employee') }}</small></div><span class="partner-load">{{ $employee->active_orders_count }} / {{ $employee->sanad_daily_capacity ?: 0 }}</span></div>
                @empty
                    <div class="partner-empty">{{ $isAr ? 'لم تتم إضافة موظفين إلى المكتب بعد.' : 'No employees have been added to this office.' }}</div>
                @endforelse
            </div>
            <a class="partner-team-link" href="{{ route('provider.employees.index') }}">{{ $isAr ? 'إدارة الموظفين وتوزيع المهام' : 'Manage employees and workload' }}</a>
        </aside>
    </section>
</div>
</x-master-layout>
