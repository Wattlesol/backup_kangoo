@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $pageTitle = $isAr ? 'أداء الشركاء ومؤشرات الجودة' : 'Partner Performance & Compliance';

    $performanceRows = collect($performances->items());
    $totalRecords = $performances->total();
    $averageQuality = $totalRecords > 0 ? round((float) $performanceRows->avg('quality_score'), 1) : 0;
    $averageSla = $totalRecords > 0 ? round((float) $performanceRows->avg('sla_compliance_rate'), 1) : 0;
    $averageAcceptance = $totalRecords > 0 ? round((float) $performanceRows->avg('acceptance_rate'), 1) : 0;
    $completedOrders = (int) $performanceRows->sum('completed_orders');
@endphp

<x-master-layout>
    <div class="quick-pp-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">

        <!-- 1. Hero Banner Card -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    <span>{{ $isAr ? 'الجودة والالتزام التشغيلي' : 'Quality & Operational Compliance' }}</span>
                </div>
                <h1>{{ $isAr ? 'أداء الشركاء ومؤشرات الجودة حسب الخدمة' : 'Partner Performance & Quality Metrics' }}</h1>
                <p>{{ $isAr ? 'مقارنة مستويات الجودة والالتزام باتفاقيات SLA ومعدلات القبول والإلغاء وسرعة الإنجاز لدعم القرارات التشغيلية وإسناد الطلبات.' : 'Compare quality scores, SLA compliance, acceptance rates, cancellation ratios, and completion times to guide dispatch and partner operations.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('sanad.dashboard') }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    <span>{{ $isAr ? 'لوحة العمليات' : 'Operations Dashboard' }}</span>
                </a>
                <a href="{{ route('complaint.index_data') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                    <span>{{ $isAr ? 'مركز مراقبة الجودة' : 'Quality Control Hub' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Summary Strip -->
        <div class="quick-kpi-grid">
            <!-- Metric 1: Average Quality -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'متوسط الجودة' : 'Average Quality Score' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $averageQuality }} <span style="font-size:15px;font-weight:700;color:var(--quick-shell-muted);">/ 100</span></div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $averageQuality > 0 ? $averageQuality : 0 }}</b>
                    <span>{{ $isAr ? 'تقييم جودة الخدمة' : 'overall service rating' }}</span>
                </div>
            </div>

            <!-- Metric 2: SLA Compliance -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الالتزام بالمهلة (SLA)' : 'SLA Compliance Rate' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $averageSla }}%</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $averageSla }}%</b>
                    <span>{{ $isAr ? 'للسجلات المعروضة' : 'visible records' }}</span>
                </div>
            </div>

            <!-- Metric 3: Acceptance Rate -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'معدل قبول الطلبات' : 'Acceptance Rate' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $averageAcceptance }}%</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $averageAcceptance }}%</b>
                    <span>{{ $isAr ? 'متوسط قبول الشركاء' : 'partner average' }}</span>
                </div>
            </div>

            <!-- Metric 4: Completed Orders -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'الطلبات المكتملة' : 'Completed Orders' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $completedOrders }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $completedOrders }}</b>
                    <span>{{ $isAr ? 'طلب منفذ بنجاح' : 'orders fulfilled' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Modern Filter Card -->
        <div class="quick-card mb-4">
            <div class="quick-card-header mb-3">
                <div class="quick-card-title-group">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;color:var(--quick-blue);"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                    <h3 class="quick-card-title">{{ $isAr ? 'تصفية مؤشرات الأداء' : 'Filter Performance Records' }}</h3>
                </div>
                <div class="quick-card-sub">{{ $isAr ? 'تصفية البيانات حسب الشريك أو المعاملة الحكومية لدراسة الأداء والالتزام' : 'Filter metrics by service provider or specific government service' }}</div>
            </div>

            <form method="GET" action="{{ route('sanad.partner-performance') }}" class="quick-filter-form">
                <div class="quick-filter-grid">
                    <!-- Partner Filter -->
                    <div class="quick-filter-field">
                        <label for="pp_provider_id" class="quick-filter-label">{{ $isAr ? 'مزود الخدمة / الشريك' : 'Partner / Provider' }}</label>
                        <select name="provider_id" id="pp_provider_id" class="quick-filter-select">
                            <option value="">{{ $isAr ? 'جميع الشركاء (All Partners)' : 'All partners' }}</option>
                            @foreach($performancePartners as $partner)
                                <option value="{{ $partner->id }}" {{ (string) request('provider_id') === (string) $partner->id ? 'selected' : '' }}>
                                    {{ $partner->display_name ?: trim($partner->first_name.' '.$partner->last_name) ?: ('Partner #'.$partner->id) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Service Filter -->
                    <div class="quick-filter-field">
                        <label for="pp_service_id" class="quick-filter-label">{{ $isAr ? 'الخدمة الحكومية' : 'Government Service' }}</label>
                        <select name="service_id" id="pp_service_id" class="quick-filter-select">
                            <option value="">{{ $isAr ? 'جميع الخدمات (All Services)' : 'All services' }}</option>
                            @foreach($performanceServices as $service)
                                <option value="{{ $service->id }}" {{ (string) request('service_id') === (string) $service->id ? 'selected' : '' }}>
                                    {{ $service->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Actions -->
                    <div class="quick-filter-actions-col">
                        <button type="submit" class="quick-filter-btn quick-filter-btn-primary">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                            <span>{{ $isAr ? 'تطبيق الفلاتر' : 'Apply Filters' }}</span>
                        </button>
                        @if(request()->filled('provider_id') || request()->filled('service_id'))
                            <a href="{{ route('sanad.partner-performance') }}" class="quick-filter-btn quick-filter-btn-secondary" title="{{ $isAr ? 'إعادة ضبط' : 'Clear' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                <span>{{ $isAr ? 'مسح' : 'Clear' }}</span>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- 4. Performance Data Table Card -->
        <div class="quick-card mb-4">
            <div class="quick-card-header">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h3 class="quick-card-title">{{ $isAr ? 'سجل أداء الشركاء ومعدلات الإنجاز' : 'Partner Performance Directory' }}</h3>
                        <span class="quick-badge quick-badge-blue">{{ $totalRecords }}</span>
                    </div>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض تفصيلي لدرجات الجودة والالتزام باتفاقيات SLA وسرعة إنجاز المعاملات' : 'Detailed breakdown of quality scores, SLA adherence, and operational metrics' }}</div>
                </div>

                <div class="quick-card-header-actions">
                    <span class="quick-pill quick-pill-neutral">
                        {{ $isAr ? 'إجمالي السجلات:' : 'Total Records:' }} <strong>{{ $totalRecords }}</strong>
                    </span>
                </div>
            </div>

            <div class="quick-table-responsive">
                <table class="quick-table">
                    <thead>
                        <tr>
                            <th style="width: 45px;">#</th>
                            <th>{{ $isAr ? 'الشريك' : 'Partner' }}</th>
                            <th>{{ $isAr ? 'الخدمة' : 'Service' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'درجة الجودة' : 'Quality Score' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'الالتزام بـ SLA' : 'SLA Compliance' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'معدل القبول' : 'Acceptance' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'معدل الإلغاء' : 'Cancellation' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'متوسط سرعة الإنجاز' : 'Avg. Completion' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'الطلبات المكتملة' : 'Completed Orders' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($performances as $key => $performance)
                            @php
                                $qScore = $performance->quality_score;
                                $slaRate = $performance->sla_compliance_rate;
                                $accRate = $performance->acceptance_rate;
                                $cancRate = $performance->cancellation_rate;
                                $compMins = $performance->average_completion_minutes;
                            @endphp
                            <tr>
                                <td class="font-weight-bold text-muted" style="font-size: 12px;">
                                    {{ method_exists($performances, 'firstItem') ? $performances->firstItem() + $key : $key + 1 }}
                                </td>
                                <td>
                                    <div class="quick-customer-cell">
                                        <div class="quick-customer-avatar" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                                            {{ mb_substr(optional($performance->provider)->display_name ?: optional($performance->provider)->first_name ?: optional($performance->provider)->name ?: 'P', 0, 1) }}
                                        </div>
                                        <div>
                                            <strong class="quick-customer-name">{{ optional($performance->provider)->display_name ?: optional($performance->provider)->name ?: ('Partner #' . $performance->provider_id) }}</strong>
                                            <div class="quick-customer-email">{{ optional($performance->provider)->email ?: '-' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="quick-badge quick-badge-neutral">
                                        {{ optional($performance->service)->name_ar ?: optional($performance->service)->name_en ?: optional($performance->service)->name ?: '-' }}
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    @if($qScore !== null)
                                        <span class="quick-badge {{ $qScore >= 80 ? 'quick-badge-success' : ($qScore >= 60 ? 'quick-badge-warning' : 'quick-badge-danger') }}">
                                            <svg viewBox="0 0 24 24" fill="currentColor" stroke="none" style="width:12px;height:12px;margin-inline-end:3px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                            {{ number_format($qScore, 1) }}
                                        </span>
                                    @else
                                        <span class="quick-table-empty-dash">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($slaRate !== null)
                                        <span class="quick-badge {{ $slaRate >= 90 ? 'quick-badge-success' : ($slaRate >= 75 ? 'quick-badge-warning' : 'quick-badge-danger') }}">
                                            {{ number_format($slaRate, 1) }}%
                                        </span>
                                    @else
                                        <span class="quick-table-empty-dash">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($accRate !== null)
                                        <span class="quick-badge quick-badge-blue">
                                            {{ number_format($accRate, 1) }}%
                                        </span>
                                    @else
                                        <span class="quick-table-empty-dash">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($cancRate !== null)
                                        <span class="quick-badge {{ $cancRate <= 5 ? 'quick-badge-neutral' : 'quick-badge-danger' }}">
                                            {{ number_format($cancRate, 1) }}%
                                        </span>
                                    @else
                                        <span class="quick-table-empty-dash">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if($compMins !== null)
                                        <span class="quick-badge quick-badge-neutral">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;margin-inline-end:3px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            {{ number_format($compMins, 0) }} {{ $isAr ? 'دقيقة' : 'min' }}
                                        </span>
                                    @else
                                        <span class="quick-table-empty-dash">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <span class="quick-badge quick-badge-neutral font-weight-bold" style="font-size: 13px;">
                                        {{ $performance->completed_orders ?? 0 }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="quick-table-empty">
                                    <div class="quick-table-empty-state">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--quick-shell-muted);"><circle cx="12" cy="12" r="10"/><path d="M16 16s-1.5-2-4-2-4 2-4 2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                                        <p>{{ $isAr ? 'لا توجد سجلات أداء مطابقة للفلاتر الحالية.' : 'No partner performance records match the current filters.' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($performances->hasPages())
                <div class="quick-table-pagination-row">
                    <div class="quick-pagination-count">
                        {{ $isAr ? 'عرض الصفحة: ' : 'Page: ' }} <strong>{{ $performances->currentPage() }}</strong> / {{ $performances->lastPage() }}
                    </div>
                    <div>
                        {{ $performances->links() }}
                    </div>
                </div>
            @endif
        </div>

    </div>

    @once
    <style>
        .quick-pp-page {
            width: 100%;
        }

        /* Filter Section */
        .quick-filter-form {
            width: 100%;
        }

        .quick-filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)) auto;
            gap: 16px;
            align-items: flex-end;
        }

        .quick-filter-field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .quick-filter-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--quick-shell-ink);
            margin: 0;
        }

        .quick-filter-select {
            width: 100%;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
            color: var(--quick-shell-ink);
            padding: 0 14px;
            font-size: 13px;
            font-weight: 600;
            outline: none;
            cursor: pointer;
            transition: all .2s ease;
        }

        .quick-filter-select:focus {
            border-color: var(--quick-blue);
            box-shadow: 0 0 0 3px rgba(31,107,255,.12);
        }

        .quick-filter-actions-col {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-filter-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            text-decoration: none;
            transition: all .2s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }

        .quick-filter-btn-primary {
            background: var(--quick-blue);
            color: #ffffff;
            border-color: var(--quick-blue);
        }

        .quick-filter-btn-primary:hover {
            background: #1455d9;
            border-color: #1455d9;
            color: #ffffff;
        }

        .quick-filter-btn-secondary {
            background: var(--quick-shell-surface);
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        .quick-filter-btn-secondary:hover {
            background: color-mix(in srgb, var(--quick-shell-bg) 70%, var(--quick-shell-surface));
            border-color: var(--quick-shell-muted);
            color: var(--quick-shell-ink);
        }

        /* Modern Table Styles */
        .quick-table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid var(--quick-shell-line);
            background: var(--quick-shell-surface);
        }

        .quick-table {
            width: 100%;
            border-collapse: collapse;
            text-align: start;
            margin-bottom: 0;
        }

        .quick-table th {
            background: color-mix(in srgb, var(--quick-shell-bg) 60%, var(--quick-shell-surface));
            color: var(--quick-shell-muted);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .4px;
            padding: 14px 16px;
            border-bottom: 1px solid var(--quick-shell-line);
            white-space: nowrap;
        }

        .quick-table td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--quick-shell-line);
            color: var(--quick-shell-ink);
            font-size: 13px;
        }

        .quick-table tr:last-child td {
            border-bottom: none;
        }

        .quick-table tr:hover td {
            background: color-mix(in srgb, var(--quick-shell-bg) 40%, transparent);
        }

        .quick-customer-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quick-customer-avatar {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
        }

        .quick-customer-name {
            font-size: 13px;
            color: var(--quick-shell-ink);
            display: block;
        }

        .quick-customer-email {
            font-size: 11px;
            color: var(--quick-shell-muted);
            line-height: 1.2;
        }

        .quick-table-empty-dash {
            color: var(--quick-shell-muted);
            font-weight: 700;
            padding: 0 10px;
        }

        /* Badges */
        .quick-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 99px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .quick-badge-blue {
            background: rgba(31,107,255,.1);
            color: #1f6bff;
            border: 1px solid rgba(31,107,255,.2);
        }

        .quick-badge-success {
            background: rgba(16,185,129,.12);
            color: #059669;
            border: 1px solid rgba(16,185,129,.25);
        }

        .quick-badge-warning {
            background: rgba(245,158,11,.12);
            color: #d97706;
            border: 1px solid rgba(245,158,11,.25);
        }

        .quick-badge-danger {
            background: rgba(239,51,64,.12);
            color: #ef3340;
            border: 1px solid rgba(239,51,64,.25);
        }

        .quick-badge-neutral {
            background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
            color: var(--quick-shell-ink);
            border: 1px solid var(--quick-shell-line);
        }

        .quick-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 99px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
        }

        .quick-pill-neutral {
            background: color-mix(in srgb, var(--quick-shell-bg) 80%, var(--quick-shell-surface));
            border-color: var(--quick-shell-line);
            color: var(--quick-shell-ink);
        }

        /* Empty State */
        .quick-table-empty {
            padding: 48px 16px !important;
            text-align: center;
        }

        .quick-table-empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .quick-table-empty-state p {
            color: var(--quick-shell-muted);
            font-size: 13px;
            font-weight: 600;
            margin: 0;
        }

        /* Pagination Row */
        .quick-table-pagination-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 16px 4px 0 4px;
        }

        .quick-pagination-count {
            font-size: 12px;
            color: var(--quick-shell-muted);
        }

        @media (max-width: 768px) {
            .quick-filter-grid {
                grid-template-columns: 1fr;
            }
            .quick-filter-actions-col {
                width: 100%;
            }
            .quick-filter-actions-col .quick-filter-btn {
                flex: 1;
            }
        }
    </style>
    @endonce
</x-master-layout>

