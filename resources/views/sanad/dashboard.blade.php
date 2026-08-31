@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'لوحة العمليات' : 'Operations dashboard';
    $recentOrders = $dashboard['recent_orders'] ?? collect();
    $priorityOrders = $dashboard['priority_orders'] ?? collect();
    $slaCompliance = $dashboard['sla_compliance'] ?? null;
@endphp
<x-master-layout>
    <div class="quick-admin-dashboard" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                    <span>{{ $isAr ? 'مركز المتابعة والتحكم الفوري' : 'Live Operational Console' }}</span>
                </div>
                <h1>{{ $isAr ? 'لوحة قيادة العمليات والخدمات' : 'Operations Command Center' }}</h1>
                <p>{{ $isAr ? 'إدارة موحدة لمعالجة المعاملات الحكومية، وتوزيع الطلبات على الشركاء، ومراقبة مؤشرات الأداء وضمان الجودة.' : 'Unified control for government transaction processing, partner dispatch, SLA monitoring, and quality audits.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('sanad.requests.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                    <span>{{ $isAr ? 'طابور الطلبات المفتوحة' : 'Open Request Queue' }}</span>
                </a>
                <a href="{{ route('sanad.assignments.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>{{ $isAr ? 'مساحة الإسناد' : 'Assignment Hub' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. Four KPI Cards -->
        <div class="quick-kpi-grid">
            <!-- Card 1 -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي الطلبات النشطة' : 'Active Operations' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $dashboard['active_operations'] ?? 0 }}</div>
                <div class="quick-kpi-sub">
                    <b class="{{ ($dashboard['pending_action_count'] ?? 0) > 0 ? 'quick-trend-down' : 'quick-trend-up' }}">{{ $dashboard['pending_action_count'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'طلبات بانتظار الإجراء' : 'pending action' }}</span>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'نسبة الالتزام بـ SLA' : 'On-time SLA Compliance' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $slaCompliance === null ? '—' : $slaCompliance . '%' }}</div>
                <div class="quick-kpi-sub">
                    <b class="{{ ($dashboard['overdue_orders'] ?? 0) > 0 ? 'quick-trend-down' : 'quick-trend-up' }}">{{ $dashboard['overdue_orders'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'طلبات متأخرة' : 'overdue requests' }}</span>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'طلبات بحاجة لتدخل فوري' : 'Priority Attention Queue' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $priorityOrders->count() }}</div>
                <div class="quick-kpi-sub">
                    <b class="{{ ($dashboard['unassigned_orders'] ?? 0) > 0 ? 'quick-trend-down' : 'quick-trend-up' }}">{{ $dashboard['unassigned_orders'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'طلبات دون إسناد' : 'unassigned requests' }}</span>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'القيمة المعالجة اليوم' : 'Processed Volume Today' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ getPriceFormat($dashboard['processed_today_volume'] ?? 0) }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $dashboard['processed_today_count'] ?? 0 }}</b>
                    <span>{{ $isAr ? 'طلبات محدثة اليوم' : 'requests updated today' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Split Grid: Left Table + Right Alerts -->
        <div class="quick-admin-split-grid">
            <!-- Left: Recent Transaction Queue Table -->
            <div class="quick-card quick-recent-card">
                <div class="quick-card-header">
                    <div>
                        <h3 class="quick-card-title">{{ $isAr ? 'طابور المعاملات الأحدث' : 'Recent Transaction Queue' }}</h3>
                        <div class="quick-card-sub">{{ $isAr ? 'التدفق المباشر للطلبات المستلمة وقيد الإنجاز' : 'Real-time stream of incoming and processed requests' }}</div>
                    </div>

                    <div class="quick-card-header-actions">
                        <div class="quick-filter-pills" role="tablist">
                            <button type="button" class="active" onclick="filterQueue('all', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                            <button type="button" onclick="filterQueue('in_progress', this)">{{ $isAr ? 'قيد التنفيذ' : 'In Progress' }}</button>
                            <button type="button" onclick="filterQueue('qc_review', this)">{{ $isAr ? 'الجودة' : 'QC' }}</button>
                        </div>
                        <a href="{{ route('sanad.requests.index') }}" class="quick-card-more-link">
                            {{ $isAr ? 'عرض الكل' : 'View full queue' }}
                        </a>
                    </div>
                </div>

                <div class="quick-table-responsive">
                    <table class="quick-table">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'رقم الطلب' : 'Request Ref' }}</th>
                                <th>{{ $isAr ? 'المستفيد' : 'Beneficiary' }}</th>
                                <th>{{ $isAr ? 'الخدمة' : 'Service' }}</th>
                                <th>{{ $isAr ? 'الحالة' : 'Status' }}</th>
                                <th>{{ $isAr ? 'نافذة SLA' : 'SLA Window' }}</th>
                                <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'الإجراء' : 'Action' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOrders as $order)
                                @php
                                    $stage = $order->sanad_stage ?: 'in_progress';
                                    $badgeClass = match($stage) {
                                        'in_progress' => 'quick-badge-in_progress',
                                        'qc_review', 'quality_review', 'awaiting_quality_review' => 'quick-badge-qc_review',
                                        'assigned_to_partner', 'assigned_to_employee' => 'quick-badge-assigned',
                                        'completed', 'closed' => 'quick-badge-completed',
                                        default => 'quick-badge-submitted',
                                    };
                                    $badgeLabel = match($stage) {
                                        'in_progress' => ($isAr ? 'قيد التنفيذ' : 'In Progress'),
                                        'qc_review', 'quality_review', 'awaiting_quality_review' => ($isAr ? 'مراجعة الجودة' : 'QC Review'),
                                        'assigned_to_partner', 'assigned_to_employee' => ($isAr ? 'مسند للشريك' : 'Assigned'),
                                        'completed', 'closed' => ($isAr ? 'مكتمل بنجاح' : 'Completed'),
                                        default => ($isAr ? 'مستلم جديد' : 'Submitted'),
                                    };
                                @endphp
                                <tr class="queue-row" data-stage="{{ $stage }}">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">
                                        {{ $order->quick_reference ?: ('QK-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)) }}
                                    </td>
                                    <td style="font-weight: 700;">
                                        {{ optional($order->customer)->display_name ?: optional($order->customer)->email ?: '-' }}
                                    </td>
                                    <td>
                                        {{ optional($order->service)->name ?: '-' }}
                                    </td>
                                    <td>
                                        <span class="quick-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </td>
                                    <td style="color: var(--quick-shell-muted); font-weight: 600;">
                                        @if($order->sla_due_at && $order->sla_due_at->isPast())
                                            <span style="color: #dc2626; font-weight: 700;">{{ $order->sla_due_at->diffForHumans() }}</span>
                                        @elseif($order->sla_due_at)
                                            {{ $order->sla_due_at->diffForHumans() }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.show', $order->id) }}" class="quick-table-btn">
                                            <span>{{ $isAr ? 'فتح' : 'Open' }}</span>
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:12px;height:12px;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" style="padding: 24px; color: var(--quick-shell-muted); text-align:center;">
                                        {{ $isAr ? 'لا توجد طلبات حديثة.' : 'No recent requests yet.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Priority Work Alerts -->
            <div class="quick-card quick-alerts-card">
                <div class="quick-card-header">
                    <div class="quick-card-title-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <h3 class="quick-card-title">{{ $isAr ? 'تنبيهات العمل العاجل' : 'Priority Work Alerts' }}</h3>
                    </div>
                    <span class="quick-alert-badge">
                        {{ $priorityOrders->count() }} {{ $isAr ? 'عناصر' : 'Items' }}
                    </span>
                </div>

                <div class="quick-alert-list">
                    @forelse($priorityOrders as $order)
                        <div class="quick-alert-item">
                            <div class="quick-alert-title">
                                {{ $order->quick_reference ?: ('QK-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)) }}
                                — {{ Str::headline($order->sanad_priority ?: 'normal') }}
                            </div>
                            <div class="quick-alert-desc">
                                {{ optional($order->service)->name ?: ($isAr ? 'طلب خدمة' : 'Service request') }}
                                @if($order->sla_due_at)
                                    · {{ $order->sla_due_at->diffForHumans() }}
                                @endif
                            </div>
                            <a href="{{ route('sanad.requests.show', $order->id) }}" class="quick-alert-btn">
                                <span>{{ $isAr ? 'فتح الطلب' : 'Open Request' }}</span>
                            </a>
                        </div>
                    @empty
                        <div class="quick-alert-item">
                            <div class="quick-alert-title">{{ $isAr ? 'لا توجد تنبيهات عاجلة' : 'No priority alerts' }}</div>
                            <div class="quick-alert-desc">{{ $isAr ? 'لا توجد طلبات عاجلة أو متأخرة حالياً.' : 'There are no urgent or SLA-risk requests right now.' }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function filterQueue(status, btn) {
            document.querySelectorAll('.quick-filter-pills button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.queue-row').forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else if (status === 'in_progress') {
                    row.style.display = (row.dataset.stage === 'in_progress') ? '' : 'none';
                } else if (status === 'qc_review') {
                    row.style.display = (row.dataset.stage.includes('qc') || row.dataset.stage.includes('quality')) ? '' : 'none';
                }
            });
        }
    </script>
</x-master-layout>
