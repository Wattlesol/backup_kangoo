<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $performanceRows = collect($performances->items());
    $averageQuality = round((float) $performanceRows->avg('quality_score'), 1);
    $averageSla = round((float) $performanceRows->avg('sla_compliance_rate'), 1);
    $averageAcceptance = round((float) $performanceRows->avg('acceptance_rate'), 1);
    $completedOrders = (int) $performanceRows->sum('completed_orders');
@endphp
<div class="container-fluid quick-partner-performance-page">
        <div class="card card-block card-stretch quick-partner-performance-card">
            <div class="card-header quick-partner-performance-hero d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <div class="quick-partner-performance-kicker"><x-quick-icon name="shield" /> {{ $isAr ? 'الجودة والالتزام التشغيلي' : 'Quality & operational compliance' }}</div>
                    <h4 class="font-weight-bold mb-1">{{ $isAr ? 'أداء الشركاء حسب الخدمة' : 'Partner Performance by Service' }}</h4>
                    <span class="text-muted">{{ $isAr ? 'مقارنة الجودة والالتزام والقبول والإلغاء وسرعة الإنجاز للقرارات التشغيلية.' : 'Compare quality, SLA, acceptance, cancellation and completion speed for operational decisions.' }}</span>
                </div>
                <a href="{{ route('sanad.dashboard') }}" class="btn btn-outline-primary"><x-quick-icon name="arrow" /> {{ $isAr ? 'لوحة العمليات' : 'Operations Dashboard' }}</a>
            </div>
            <div class="card-body">
                <div class="quick-partner-performance-metrics">
                    <div><span>{{ $isAr ? 'متوسط الجودة' : 'Average Quality' }}</span><strong>{{ $averageQuality }}</strong><small>{{ $isAr ? 'من 100' : 'out of 100' }}</small></div>
                    <div><span>{{ $isAr ? 'الالتزام بالمهلة' : 'SLA Compliance' }}</span><strong>{{ $averageSla }}%</strong><small>{{ $isAr ? 'للسجلات المعروضة' : 'visible records' }}</small></div>
                    <div><span>{{ $isAr ? 'معدل القبول' : 'Acceptance Rate' }}</span><strong>{{ $averageAcceptance }}%</strong><small>{{ $isAr ? 'متوسط الشركاء' : 'partner average' }}</small></div>
                    <div><span>{{ $isAr ? 'الطلبات المكتملة' : 'Completed Orders' }}</span><strong>{{ $completedOrders }}</strong><small>{{ $isAr ? 'في العرض الحالي' : 'current view' }}</small></div>
                </div>

                <form method="GET" action="{{ route('sanad.partner-performance') }}" class="quick-partner-performance-filters">
                    <label><span>{{ $isAr ? 'الشريك' : 'Partner' }}</span><select name="provider_id" class="form-control"><option value="">{{ $isAr ? 'كل الشركاء' : 'All partners' }}</option>@foreach($performancePartners as $partner)<option value="{{ $partner->id }}" {{ (string) request('provider_id') === (string) $partner->id ? 'selected' : '' }}>{{ $partner->display_name ?: trim($partner->first_name.' '.$partner->last_name) }}</option>@endforeach</select></label>
                    <label><span>{{ $isAr ? 'الخدمة' : 'Service' }}</span><select name="service_id" class="form-control"><option value="">{{ $isAr ? 'كل الخدمات' : 'All services' }}</option>@foreach($performanceServices as $service)<option value="{{ $service->id }}" {{ (string) request('service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->name }}</option>@endforeach</select></label>
                    <button class="btn btn-primary" type="submit">{{ $isAr ? 'تطبيق الفلاتر' : 'Apply Filters' }}</button>
                    @if(request()->filled('provider_id') || request()->filled('service_id'))<a class="btn btn-light" href="{{ route('sanad.partner-performance') }}">{{ $isAr ? 'مسح' : 'Clear' }}</a>@endif
                </form>

                <div class="table-responsive">
                    <table class="table mb-0 quick-partner-performance-table">
                        <thead>
                            <tr>
                                <th>{{ $isAr ? 'الشريك' : 'Partner' }}</th>
                                <th>{{ $isAr ? 'الخدمة' : 'Service' }}</th>
                                <th>{{ $isAr ? 'الجودة' : 'Quality Score' }}</th>
                                <th>{{ $isAr ? 'الالتزام SLA' : 'SLA Compliance' }}</th>
                                <th>{{ $isAr ? 'القبول' : 'Acceptance' }}</th>
                                <th>{{ $isAr ? 'الإلغاء' : 'Cancellation' }}</th>
                                <th>{{ $isAr ? 'متوسط الإنجاز' : 'Avg. Completion' }}</th>
                                <th>{{ $isAr ? 'مكتملة' : 'Completed Orders' }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($performances as $performance)
                                <tr>
                                    <td>{{ optional($performance->provider)->display_name ?: optional($performance->provider)->name ?: '-' }}</td>
                                    <td>{{ optional($performance->service)->name_en ?: optional($performance->service)->name ?: '-' }}</td>
                                    <td>{{ $performance->quality_score !== null ? number_format($performance->quality_score, 2) : '-' }}</td>
                                    <td>{{ $performance->sla_compliance_rate !== null ? number_format($performance->sla_compliance_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->acceptance_rate !== null ? number_format($performance->acceptance_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->cancellation_rate !== null ? number_format($performance->cancellation_rate, 2).'%' : '-' }}</td>
                                    <td>{{ $performance->average_completion_minutes !== null ? number_format($performance->average_completion_minutes, 0).' min' : '-' }}</td>
                                    <td>{{ $performance->completed_orders ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-5">{{ $isAr ? 'لا توجد سجلات أداء مطابقة للفلاتر الحالية.' : 'No partner performance records match the current filters.' }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($performances->hasPages())
                    <div class="pt-3">{{ $performances->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-master-layout>
