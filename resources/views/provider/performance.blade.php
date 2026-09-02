<x-master-layout>
    @php
        $isAr = app()->getLocale() === 'ar';
        $completedTotal = $employees->sum(fn ($employee) => data_get($employee->sanad_metrics, 'completed_orders', 0));
        $delayedTotal = $employees->sum(fn ($employee) => data_get($employee->sanad_metrics, 'delayed_orders', 0));
        $averageSla = $employees->count() ? round($employees->avg(fn ($employee) => data_get($employee->sanad_metrics, 'sla_compliance', 0)), 1) : 0;
        $averageQuality = $employees->count() ? round($employees->avg(fn ($employee) => data_get($employee->sanad_metrics, 'quality_score', 0)), 1) : 0;
    @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-performance-heading">
            <div>
                <h1><i class="fas fa-chart-line"></i> {{ $isAr ? 'مؤشرات الأداء وجودة الخدمات' : 'Partner Performance & Quality Scores' }}</h1>
                <p>{{ $isAr ? 'تحليلات الإنجاز والالتزام بمستوى الخدمة وجودة أداء فريق المكتب.' : 'Track delivery volume, SLA compliance, service quality, and office team performance.' }}</p>
            </div>
            <a href="{{ route('provider.dashboard') }}" class="btn btn-outline-secondary"><i class="fa fa-arrow-{{ $isAr ? 'right' : 'left' }} mr-1"></i> {{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</a>
        </div>

        <div class="partner-performance-kpis">
            <div><span>{{ $isAr ? 'طلبات منجزة' : 'Completed orders' }}</span><strong>{{ $completedTotal }}</strong><small>{{ $isAr ? 'ضمن الفترة المحددة' : 'Selected period' }}</small></div>
            <div><span>{{ $isAr ? 'الالتزام باتفاقية SLA' : 'SLA compliance' }}</span><strong class="text-success">{{ $averageSla }}%</strong><small>{{ $isAr ? 'متوسط الفريق' : 'Team average' }}</small></div>
            <div><span>{{ $isAr ? 'مؤشر الجودة' : 'Quality score' }}</span><strong class="text-primary">{{ $averageQuality }}%</strong><small>{{ $isAr ? 'مراجعات العمليات' : 'Operational reviews' }}</small></div>
            <div><span>{{ $isAr ? 'طلبات متأخرة' : 'Delayed orders' }}</span><strong class="text-warning">{{ $delayedTotal }}</strong><small>{{ $isAr ? 'تتطلب المتابعة' : 'Needs attention' }}</small></div>
        </div>

        <div class="card mb-3 partner-performance-filter">
            <div class="card-body">
                <form method="GET" class="row align-items-end">
                    <div class="col-md-3 form-group mb-md-0">
                        <label class="form-control-label font-weight-bold">{{ $isAr ? 'الفترة الزمنية' : 'Time Range' }}</label>
                        <select name="period" class="form-control">
                            <option value="this_week" {{ request('period') === 'this_week' ? 'selected' : '' }}>{{ $isAr ? 'هذا الأسبوع' : 'This Week' }}</option>
                            <option value="this_month" {{ request('period', 'this_month') === 'this_month' ? 'selected' : '' }}>{{ $isAr ? 'هذا الشهر' : 'This Month' }}</option>
                            <option value="six_months" {{ request('period') === 'six_months' ? 'selected' : '' }}>{{ $isAr ? 'آخر 6 أشهر' : 'Last 6 Months' }}</option>
                            <option value="custom" {{ request('period') === 'custom' ? 'selected' : '' }}>{{ $isAr ? 'فترة مخصصة' : 'Custom Period' }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label class="form-control-label font-weight-bold">{{ $isAr ? 'الخدمة' : 'Service' }}</label>
                        <select name="service_id" class="form-control">
                            <option value="">{{ $isAr ? 'جميع الخدمات' : 'All Services' }}</option>
                            @foreach($services ?? [] as $serv)
                                <option value="{{ $serv->id }}" {{ request('service_id') == $serv->id ? 'selected' : '' }}>{{ localized_model_name($serv) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <label class="form-control-label font-weight-bold">{{ $isAr ? 'نظام توزيع العمولات' : 'Commission Model' }}</label>
                        <select name="commission_tier" class="form-control">
                            <option value="">{{ $isAr ? 'الافتراضي' : 'Standard' }}</option>
                            <option value="volume_tier">{{ $isAr ? 'مكافأة حجم الطلبات (> 20 طلب)' : 'Volume Bonus (> 20 Orders)' }}</option>
                            <option value="revenue_tier">{{ $isAr ? 'مكافأة الإيرادات (> 10,000 ريال)' : 'Revenue Bonus (> 10,000 SAR)' }}</option>
                        </select>
                    </div>
                    <div class="col-md-3 form-group mb-md-0">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter mr-1"></i> {{ $isAr ? 'تصفية التحليلات' : 'Filter Analytics' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card partner-performance-table">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $isAr ? 'لوحة تميز الموظفين وتوزيع العمولات' : 'Employee Leaderboard & Commission Distribution' }}</h5>
                <span class="badge badge-success">{{ $isAr ? 'متابعة الأداء النشط' : 'Active Performance Tracking' }}</span>
            </div>
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>{{ $isAr ? 'الموظف' : 'Employee' }}</th>
                            <th>{{ $isAr ? 'الطلبات المنجزة' : 'Completed Orders' }}</th>
                            <th>{{ $isAr ? 'متوسط الإنجاز' : 'Avg Completion' }}</th>
                            <th>{{ $isAr ? 'المتأخرة' : 'Delayed' }}</th>
                            <th>{{ $isAr ? 'تقييم العملاء' : 'Customer Rating' }}</th>
                            <th>{{ $isAr ? 'تقييم الجودة' : 'Quality Score' }}</th>
                            <th>{{ $isAr ? 'الالتزام باتفاقية الخدمة (SLA)' : 'SLA Compliance' }}</th>
                            <th>{{ $isAr ? 'العمولة المستحقة' : 'Earned Commission' }}</th>
                            <th>{{ $isAr ? 'حالة الصرف' : 'Payout Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $employee)
                            @php
                                $m = $employee->sanad_metrics ?? [];
                                $completed = $m['completed_orders'] ?? 0;
                                $commissionRate = 15; // 15% standard commission
                                $commissionAmount = $completed * 75; // 75 SAR average commission per order
                                $qualifiesBonus = $completed >= 20;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $employee->display_name }}</strong>
                                    <div class="small text-muted">{{ $employee->sanad_job_title ?: ($isAr ? 'أخصائي عمليات' : 'Operations Specialist') }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $completed }}</span>
                                    @if($qualifiesBonus)<span class="badge badge-warning ml-1">{{ $isAr ? 'مؤهل للمكافأة' : 'Bonus Qualified' }}</span>@endif
                                </td>
                                <td>{{ $m['average_completion_time'] ?? 45 }} {{ $isAr ? 'دقيقة' : 'min' }}</td>
                                <td>{{ $m['delayed_orders'] ?? 0 }}</td>
                                <td>
                                    <i class="fas fa-star text-warning"></i> {{ $m['customer_rating'] ?? '4.9' }}
                                </td>
                                <td>{{ $m['quality_score'] ?? 95 }}%</td>
                                <td>
                                    <span class="badge badge-{{ ($m['sla_compliance'] ?? 98) >= 90 ? 'success' : 'danger' }}">
                                        {{ $m['sla_compliance'] ?? 98 }}%
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ getPriceFormat($commissionAmount) }}</strong>
                                    <div class="small text-muted">{{ $commissionRate }}% {{ $isAr ? 'لكل طلب مكتمل' : 'per completed order' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $completed > 0 ? 'info' : 'secondary' }}">
                                        {{ $completed > 0 ? ($isAr ? 'مستحق للتسوية' : 'Accrued for Settlement') : ($isAr ? 'لا يوجد نشاط' : 'No Activity') }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-muted text-center py-4">{{ $isAr ? 'لا توجد مؤشرات موظفين للفلاتر المحددة.' : 'No employee metrics available for the selected filters.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@section('bottom_script')
<style>
.partner-performance-heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:10px 0 24px}.partner-performance-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-performance-heading h1 i{color:#1f6bff;font-size:23px}.partner-performance-heading p{margin:7px 0 0;color:#6d7d94}.partner-performance-heading .btn{border-radius:10px}.partner-performance-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:18px}.partner-performance-kpis>div{background:#fff;border:1px solid #dce5f1;border-radius:16px;padding:18px;box-shadow:0 7px 18px rgba(15,41,51,.035)}.partner-performance-kpis span,.partner-performance-kpis small{display:block;color:#8190a5;font-size:11px}.partner-performance-kpis strong{display:block;color:#17263c;font-size:25px;margin:9px 0 4px}.partner-performance-filter,.partner-performance-table{border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-performance-filter .form-control{border-color:#dce5f1;border-radius:9px}.partner-performance-table .card-header{background:#fff;border-color:#e6ecf4;padding:17px 20px}.partner-performance-table .card-body{padding:0}.partner-performance-table thead th{background:#f6f8fc;color:#627189;border:0;padding:14px 13px;font-size:11px;white-space:nowrap}.partner-performance-table tbody td{padding:14px 13px;border-color:#edf1f6;vertical-align:middle;font-size:12px}
.quick-theme-dark .partner-performance-heading h1,.quick-theme-dark .partner-performance-kpis strong{color:#f4f8fb}.quick-theme-dark .partner-performance-kpis>div,.quick-theme-dark .partner-performance-filter,.quick-theme-dark .partner-performance-table,.quick-theme-dark .partner-performance-table .card-header{background:#102536;border-color:#294154}
@media(max-width:991px){.partner-performance-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.partner-performance-heading{align-items:stretch;flex-direction:column}.partner-performance-heading h1{font-size:22px}.partner-performance-heading .btn{width:100%}}@media(max-width:480px){.partner-performance-kpis{grid-template-columns:1fr}}
</style>
@endsection
</x-master-layout>
