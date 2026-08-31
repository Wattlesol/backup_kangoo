@php
    $locale = app()->getLocale();
    $isAr = in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $totalCount = count($package_service_booking);
    $finishedCount = $package_service_booking->where('status', \App\Enums\BookingEnums::finished)->count();
    $inProgressCount = $package_service_booking->whereIn('status', [\App\Enums\BookingEnums::handyman_assign, \App\Enums\BookingEnums::approved])->count();
    $pendingCount = $package_service_booking->where('status', \App\Enums\BookingEnums::WaitingForApproval)->count();
@endphp

<x-master-layout>
    <div class="quick-package-booking-page" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
        <!-- 1. Hero Header Banner -->
        <div class="quick-admin-hero">
            <div class="quick-admin-hero-content">
                <div class="quick-admin-hero-eyebrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    <span>{{ $isAr ? 'إدارة وجدولة باقات الخدمات' : 'Package Bookings Administration' }}</span>
                </div>
                <h1>{{ $isAr ? 'سجل حجوزات باقات الخدمات' : 'Package Service Bookings' }}</h1>
                <p>{{ $isAr ? 'إدارة المواعيد وتعيين الفنيين والمنفذين وتأكيد الحجوزات ومتابعة حالات التنفيذ والإنجاز.' : 'Dispatch service requests, assign technicians, reschedule appointments, and monitor completion.' }}</p>
            </div>

            <div class="quick-admin-hero-actions">
                <a href="{{ route('servicepackage.index') }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                    <span>{{ $isAr ? 'دليل باقات الخدمات' : 'Service Packages' }}</span>
                </a>
            </div>
        </div>

        <!-- 2. KPI Metrics Strip -->
        <div class="quick-kpi-grid">
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'إجمالي الحجوزات' : 'Total Bookings' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(31,107,255,.1); color: #1f6bff;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $totalCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $totalCount }}</b>
                    <span>{{ $isAr ? 'حجز مسجل' : 'total package orders' }}</span>
                </div>
            </div>

            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'بانتظار الموافقة' : 'Pending Approval' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value" style="color: #f59e0b;">{{ $pendingCount }}</div>
                <div class="quick-kpi-sub">
                    <b style="color: #f59e0b;">{{ $pendingCount }}</b>
                    <span>{{ $isAr ? 'طلبات تحتاج مراجعة' : 'needs confirmation' }}</span>
                </div>
            </div>

            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'المسندة والنشطة' : 'Assigned & Active' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $inProgressCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $inProgressCount }}</b>
                    <span>{{ $isAr ? 'قيد التنفيذ' : 'in progress' }}</span>
                </div>
            </div>

            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'المكتملة' : 'Completed' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value" style="color: #10b981;">{{ $finishedCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $finishedCount }}</b>
                    <span>{{ $isAr ? 'تم إنجازها' : 'done' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Main Data Table Card -->
        <div class="quick-card quick-table-card">
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'قائمة حجوزات باقات الخدمات' : 'Package Bookings List' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'تأكيد المواعيد، تغيير الأوقات، وإسناد الطلبات إلى المنفذين والفنيين' : 'Confirm dates, reschedule appointments, and assign handymen' }}</div>
                </div>
            </div>

            <div class="quick-table-responsive">
                <table id="datatable" class="quick-table">
                    <thead>
                        <tr>
                            <th>{{ $isAr ? 'الباقة والنوع' : 'Package & Type' }}</th>
                            <th>{{ $isAr ? 'التاريخ والوقت' : 'Date & Time' }}</th>
                            <th>{{ $isAr ? 'المستفيد' : 'Customer' }}</th>
                            <th>{{ $isAr ? 'الموقع / المركبة' : 'Location / Asset' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                            <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'الإجراءات' : 'Action' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($package_service_booking as $package)
                            @php
                                $pkgName = optional(optional($package->subscription)->Serverdecimal)->name ?: ($isAr ? 'باقة خدمة' : 'Service Package');
                                $pkgType = optional($package->subscription)->package_type ?: 'standard';
                                $customerName = trim(optional($package->user)->first_name . ' ' . optional($package->user)->last_name) ?: 'Customer';
                                $customerPhone = optional($package->user)->contact_number ?: '-';
                            @endphp
                            <tr>
                                <td>
                                    <strong style="color:var(--quick-shell-ink);font-size:13px;display:block;">{{ $pkgName }}</strong>
                                    <span class="quick-order-badge" style="display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(31,107,255,.08);color:var(--quick-blue);font-size:11px;font-weight:700;margin-top:2px;">
                                        {{ trans('messages.' . $pkgType) }}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:13px;color:var(--quick-shell-ink);">{{ $package->date ?: '-' }}</div>
                                </td>
                                <td>
                                    <div style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);">{{ $customerName }}</div>
                                    <div class="small text-muted" style="direction:ltr;text-align:{{ $isAr ? 'right' : 'left' }};margin-top:2px;">{{ $customerPhone }}</div>
                                </td>
                                <td>
                                    @if ($package->car)
                                        <div class="small" style="color:var(--quick-shell-ink);">
                                            <span style="font-weight:700;">{{ $isAr ? 'المركبة: ' : 'Car: ' }}</span>{{ @$items->car_number ?: '-' }}
                                        </div>
                                    @endif
                                    @if ($package->address)
                                        <div class="small" style="color:var(--quick-shell-muted);">
                                            {{ @$package->address->CityData->name ?: '' }} {{ @$package->address->address ? ' - ' . @$package->address->address : '' }}
                                        </div>
                                    @endif
                                    @if(!$package->car && !$package->address)
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @php
                                        $st = $package->status;
                                        $statusClass = $st == \App\Enums\BookingEnums::finished ? 'success' : ($st == \App\Enums\BookingEnums::handyman_assign || $st == \App\Enums\BookingEnums::approved ? 'warning' : ($st == \App\Enums\BookingEnums::canceled ? 'danger' : 'neutral'));
                                    @endphp
                                    <span class="quick-badge quick-badge-{{ $statusClass }}">
                                        {{ trans('messages.' . \App\Enums\BookingEnums::GetById($package->status)) }}
                                    </span>
                                </td>
                                <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                    <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                                        @if(in_array($package->status, [\App\Enums\BookingEnums::WaitingForApproval, \App\Enums\BookingEnums::reschedule]))
                                            <form action="{{ route('servicepackage.ChangeData', $package->id) }}" method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                                @csrf
                                                <input type="datetime-local" name="booking_date" class="form-control quick-input" style="min-height:36px;font-size:12px;padding:4px 8px;" required>
                                                <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" style="min-height:36px;padding:4px 10px;font-size:11px;">
                                                    {{ $isAr ? 'تعديل التاريخ' : 'Reschedule' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('servicepackage.change_status', [$package->id, \App\Enums\BookingEnums::approved]) }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height:34px;padding:4px 12px;font-size:12px;">
                                                {{ $isAr ? 'تأكيد الحجز' : 'Confirm' }}
                                            </a>
                                        @endif

                                        @if($package->status == \App\Enums\BookingEnums::approved)
                                            <form action="{{ route('servicepackage.AssignHandyman', $package->id) }}" method="POST" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                                @csrf
                                                <select name="handyman" class="form-control quick-select" style="height:36px;font-size:12px;padding:4px 8px;" required>
                                                    <option value="">{{ $isAr ? 'اختر المنفذ' : 'Select Handyman' }}</option>
                                                    @foreach($handyman as $key => $h)
                                                        <option value="{{ $h }}">{{ $key }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height:36px;padding:4px 10px;font-size:11px;">
                                                    {{ $isAr ? 'إسناد' : 'Assign' }}
                                                </button>
                                            </form>
                                            <a href="{{ route('servicepackage.change_status', [$package->id, \App\Enums\BookingEnums::canceled]) }}" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" style="min-height:34px;padding:4px 12px;font-size:12px;color:#ef4444 !important;">
                                                {{ $isAr ? 'إلغاء' : 'Cancel' }}
                                            </a>
                                        @endif

                                        @if($package->status == \App\Enums\BookingEnums::handyman_assign)
                                            <a href="{{ route('servicepackage.change_status', [$package->id, \App\Enums\BookingEnums::finished]) }}" class="quick-admin-hero-btn quick-admin-hero-btn-primary" style="min-height:34px;padding:4px 12px;font-size:12px;background:#10b981;">
                                                {{ $isAr ? 'اكتمال الخدمة' : 'Mark Finished' }}
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted font-weight-bold">
                                    {{ $isAr ? 'لا توجد حجوزات باقات مسجلة حالياً.' : 'No package service bookings found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-master-layout>
