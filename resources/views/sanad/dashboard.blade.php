@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'لوحة العمليات' : 'Operations dashboard';
@endphp
<x-master-layout>
    <div class="quick-admin-dashboard" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Card -->
        <div class="quick-admin-hero">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div>
                    <div class="quick-admin-hero-eyebrow">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
                        <span>{{ $isAr ? 'مركز المتابعة والتحكم الفوري' : 'Live Operational Console' }}</span>
                    </div>
                    <h1>{{ $isAr ? 'لوحة قيادة العمليات والخدمات' : 'Operations Command Center' }}</h1>
                    <p>{{ $isAr ? 'إدارة موحدة لمعالجة المعاملات الحكومية، وتوزيع الطلبات على الشركاء، ومراقبة مؤشرات الأداء وضمان الجودة.' : 'Unified control for government transaction processing, partner dispatch, SLA monitoring, and quality audits.' }}</p>
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <a href="{{ route('sanad.requests.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 800; color: #fff; background: #1f6bff; text-decoration: none; box-shadow: 0 4px 14px rgba(31,107,255,.3);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83Z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/></svg>
                        <span>{{ $isAr ? 'طابور الطلبات المفتوحة' : 'Open Request Queue' }}</span>
                    </a>
                    <a href="{{ route('sanad.assignments.index') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 12px; font-size: 13px; font-weight: 800; color: var(--quick-shell-ink); background: var(--quick-shell-surface); border: 1px solid var(--quick-shell-line); text-decoration: none;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>{{ $isAr ? 'مساحة الإسناد' : 'Assignment Hub' }}</span>
                    </a>
                </div>
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
                <div class="quick-kpi-value">{{ $dashboard['metrics'][0]['value'] ?? 142 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">+12%</b>
                    <span>{{ $isAr ? '28 طلب بانتظار الإجراء' : '28 pending action' }}</span>
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
                <div class="quick-kpi-value">99.2%</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">+0.8%</b>
                    <span>{{ $isAr ? 'متوافق مع أهداف الوزارة' : 'Exceeds target' }}</span>
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
                <div class="quick-kpi-value">{{ count($dashboard['priority_orders'] ?? []) ?: 4 }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-down">-2</b>
                    <span>{{ $isAr ? '1 تجاوز الموعد، 3 دون إسناد' : '1 breach, 3 unassigned' }}</span>
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
                <div class="quick-kpi-value">{{ $dashboard['metrics'][4]['value'] ?? '48,350 ر.س' }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">+18%</b>
                    <span>{{ $isAr ? 'عبر 68 معاملة حكومية' : 'Across 68 services' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Split Grid: Left Table + Right Alerts -->
        <div style="display: grid; grid-template-columns: 1.8fr 1fr; gap: 20px;">
            <!-- Left: Recent Transaction Queue Table -->
            <div class="quick-card">
                <div class="quick-card-header">
                    <div>
                        <h3 class="quick-card-title">{{ $isAr ? 'طابور المعاملات الأحدث' : 'Recent Transaction Queue' }}</h3>
                        <div class="quick-card-sub">{{ $isAr ? 'التدفق المباشر للطلبات المستلمة وقيد الإنجاز' : 'Real-time stream of incoming and processed requests' }}</div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="quick-filter-pills">
                            <button type="button" class="active" onclick="filterQueue('all', this)">{{ $isAr ? 'الكل' : 'All' }}</button>
                            <button type="button" onclick="filterQueue('in_progress', this)">{{ $isAr ? 'قيد التنفيذ' : 'In Progress' }}</button>
                            <button type="button" onclick="filterQueue('qc_review', this)">{{ $isAr ? 'الجودة' : 'QC' }}</button>
                        </div>
                        <a href="{{ route('sanad.requests.index') }}" style="color: #1f6bff; font-size: 12px; font-weight: 800; text-decoration: none;">
                            {{ $isAr ? 'عرض الكل' : 'View full queue' }}
                        </a>
                    </div>
                </div>

                <div style="overflow-x: auto;">
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
                            @php
                                $orders = $dashboard['recent_orders'] ?? collect([]);
                            @endphp
                            @forelse($orders as $order)
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
                                        {{ $order->quick_reference ?: 'QK-' . (2840 + $order->id) }}
                                    </td>
                                    <td style="font-weight: 700;">
                                        {{ optional($order->customer)->display_name ?: ($isAr ? 'عبدالله الشمري' : 'Abdullah Al-Shammari') }}
                                    </td>
                                    <td>
                                        {{ optional($order->service)->name ?: ($isAr ? 'تجديد رخصة القيادة المهنية' : 'Commercial Driving Licence') }}
                                    </td>
                                    <td>
                                        <span class="quick-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </td>
                                    <td style="color: var(--quick-shell-muted); font-weight: 600;">
                                        @if($order->sla_due_at && $order->sla_due_at->isPast())
                                            <span style="color: #dc2626; font-weight: 700;">{{ $isAr ? 'متأخر 30 دقيقة' : 'Overdue by 30m' }}</span>
                                        @else
                                            {{ $isAr ? 'متبقي ساعتان' : '2h remaining' }}
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
                                <tr class="queue-row" data-stage="in_progress">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">QK-2841</td>
                                    <td style="font-weight: 700;">{{ $isAr ? 'عبدالله الشمري' : 'Abdullah Al-Shammari' }}</td>
                                    <td>{{ $isAr ? 'تجديد رخصة القيادة المهنية' : 'Commercial Driving Licence' }}</td>
                                    <td><span class="quick-badge quick-badge-in_progress">{{ $isAr ? 'قيد التنفيذ' : 'In Progress' }}</span></td>
                                    <td>{{ $isAr ? 'متبقي ساعتان' : '2h remaining' }}</td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn"><span>{{ $isAr ? 'فتح' : 'Open' }}</span> ↗</a>
                                    </td>
                                </tr>
                                <tr class="queue-row" data-stage="qc_review">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">QK-2840</td>
                                    <td style="font-weight: 700;">{{ $isAr ? 'سارة القحطاني' : 'Sara Al-Qahtani' }}</td>
                                    <td>{{ $isAr ? 'إصدار سجل تجاري فوري' : 'Commercial Register Issuance' }}</td>
                                    <td><span class="quick-badge quick-badge-qc_review">{{ $isAr ? 'مراجعة الجودة' : 'QC Review' }}</span></td>
                                    <td style="color: #dc2626; font-weight: 700;">{{ $isAr ? 'متأخر 30 دقيقة' : 'Overdue by 30m' }}</td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn"><span>{{ $isAr ? 'فتح' : 'Open' }}</span> ↗</a>
                                    </td>
                                </tr>
                                <tr class="queue-row" data-stage="assigned">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">QK-2839</td>
                                    <td style="font-weight: 700;">{{ $isAr ? 'فهد العتيبي' : 'Fahad Al-Otaibi' }}</td>
                                    <td>{{ $isAr ? 'تجديد الهوية الوطنية' : 'National ID Renewal' }}</td>
                                    <td><span class="quick-badge quick-badge-assigned">{{ $isAr ? 'مسند للشريك' : 'Assigned' }}</span></td>
                                    <td>{{ $isAr ? 'متبقي 4 ساعات' : '4h remaining' }}</td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn"><span>{{ $isAr ? 'فتح' : 'Open' }}</span> ↗</a>
                                    </td>
                                </tr>
                                <tr class="queue-row" data-stage="submitted">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">QK-2838</td>
                                    <td style="font-weight: 700;">{{ $isAr ? 'شركة آفاق التقنية' : 'Afaq Tech Co.' }}</td>
                                    <td>{{ $isAr ? 'نقل كفالة وتعديل مهنة' : 'Sponsorship Transfer' }}</td>
                                    <td><span class="quick-badge quick-badge-submitted">{{ $isAr ? 'بانتظار الإسناد' : 'Submitted' }}</span></td>
                                    <td>{{ $isAr ? 'بانتظار الإسناد' : 'Pending assignment' }}</td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn"><span>{{ $isAr ? 'فتح' : 'Open' }}</span> ↗</a>
                                    </td>
                                </tr>
                                <tr class="queue-row" data-stage="completed">
                                    <td style="font-family: monospace; font-weight: 800; color: #1f6bff;">QK-2832</td>
                                    <td style="font-weight: 700;">{{ $isAr ? 'محمد المنصور' : 'Mohammed Al-Mansoor' }}</td>
                                    <td>{{ $isAr ? 'الاستعلام عن المخالفات والاعتراض' : 'Violation Enquiry & Appeal' }}</td>
                                    <td><span class="quick-badge quick-badge-completed">{{ $isAr ? 'مكتمل بنجاح' : 'Completed' }}</span></td>
                                    <td>{{ $isAr ? 'مكتمل في الموعد' : 'Completed in SLA' }}</td>
                                    <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                        <a href="{{ route('sanad.requests.index') }}" class="quick-table-btn"><span>{{ $isAr ? 'فتح' : 'Open' }}</span> ↗</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Priority Work Alerts -->
            <div class="quick-card">
                <div class="quick-card-header">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:18px;height:18px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <h3 class="quick-card-title">{{ $isAr ? 'تنبيهات العمل العاجل' : 'Priority Work Alerts' }}</h3>
                    </div>
                    <span style="display: inline-block; padding: 3px 9px; border-radius: 99px; font-size: 11px; font-weight: 800; background: rgba(245,158,11,.15); color: #b45309;">
                        {{ $isAr ? '3 عناصر' : '3 Items' }}
                    </span>
                </div>

                <div class="quick-alert-list">
                    <!-- Alert 1 -->
                    <div class="quick-alert-item">
                        <div class="quick-alert-title">{{ $isAr ? 'خطر تجاوز SLA لطلب #QK-2840' : 'SLA breach risk on #QK-2840' }}</div>
                        <div class="quick-alert-desc">{{ $isAr ? 'طلب سجل تجاري تجاوز نافذة المراجعة بـ 30 دقيقة.' : 'Commercial registration order exceeded review window by 30m.' }}</div>
                        <a href="{{ route('sanad.assignments.index') }}" class="quick-alert-btn">
                            <span>{{ $isAr ? 'إسناد لمشرف الجودة' : 'Assign to QC Lead' }}</span>
                        </a>
                    </div>

                    <!-- Alert 2 -->
                    <div class="quick-alert-item">
                        <div class="quick-alert-title">{{ $isAr ? '3 طلبات جديدة بدون إسناد' : '3 unassigned new requests' }}</div>
                        <div class="quick-alert-desc">{{ $isAr ? 'طلبات بحاجة لتوزيع فوري على الشركاء المعتمدين.' : 'Requests need immediate dispatch to certified partners.' }}</div>
                        <a href="{{ route('sanad.assignments.index') }}" class="quick-alert-btn">
                            <span>{{ $isAr ? 'فتح مساحة الإسناد' : 'Open Assignment Hub' }}</span>
                        </a>
                    </div>

                    <!-- Alert 3 -->
                    <div class="quick-alert-item">
                        <div class="quick-alert-title">{{ $isAr ? 'تصعيد ذكي: استفسار معقد' : 'AI escalation: Complex query' }}</div>
                        <div class="quick-alert-desc">{{ $isAr ? 'المساعد الآلي رصد استفسار مستندات غير مكتملة.' : 'AI assistant flagged incomplete documentation query.' }}</div>
                        <a href="{{ route('sanad.chat.workspace') }}" class="quick-alert-btn">
                            <span>{{ $isAr ? 'مراجعة المحادثة' : 'Review Chat' }}</span>
                        </a>
                    </div>
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
