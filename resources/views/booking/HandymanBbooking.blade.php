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
                    <span>{{ $isAr ? 'إدارة وتنفيذ باقات الخدمات' : 'Handyman Package Execution' }}</span>
                </div>
                <h1>{{ $isAr ? 'حجوزات ومهام باقات الخدمات' : 'Package Service Bookings' }}</h1>
                <p>{{ $isAr ? 'متابعة المهام الميدانية، وتسجيل أوقات البدء والإنجاز، ورفع صور الإثبات وتوثيق التقييمات.' : 'Track service tasks, log execution time and duration, upload proof before/after, and capture ratings.' }}</p>
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
            <!-- Metric 1: Total -->
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

            <!-- Metric 2: In Progress -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'قيد التنفيذ والمسندة' : 'In Progress' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(245,158,11,.1); color: #f59e0b;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value" style="color: #f59e0b;">{{ $inProgressCount }}</div>
                <div class="quick-kpi-sub">
                    <b style="color: #f59e0b;">{{ $inProgressCount }}</b>
                    <span>{{ $isAr ? 'مهمة نشطة' : 'active executions' }}</span>
                </div>
            </div>

            <!-- Metric 3: Finished -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'المكتملة والمنجزة' : 'Finished Tasks' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(16,185,129,.1); color: #10b981;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value" style="color: #10b981;">{{ $finishedCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $finishedCount }}</b>
                    <span>{{ $isAr ? 'تم إنجازها بنجاح' : 'successfully completed' }}</span>
                </div>
            </div>

            <!-- Metric 4: Pending -->
            <div class="quick-kpi-card">
                <div class="quick-kpi-header">
                    <span>{{ $isAr ? 'بانتظار الموافقة' : 'Pending Approval' }}</span>
                    <div class="quick-kpi-icon" style="background: rgba(139,92,246,.1); color: #8b5cf6;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                </div>
                <div class="quick-kpi-value">{{ $pendingCount }}</div>
                <div class="quick-kpi-sub">
                    <b class="quick-trend-up">{{ $pendingCount }}</b>
                    <span>{{ $isAr ? 'طلبات جديدة' : 'new incoming requests' }}</span>
                </div>
            </div>
        </div>

        <!-- 3. Main Data Table Card -->
        <div class="quick-card quick-table-card">
            <div class="quick-card-header">
                <div>
                    <h3 class="quick-card-title">{{ $isAr ? 'جدول حجوزات ومواعيد باقات الخدمات' : 'Package Execution Directory' }}</h3>
                    <div class="quick-card-sub">{{ $isAr ? 'عرض تفاصيل الطلب، حالة الخدمة، معلومات المستفيد، وإثباتات التنفيذ' : 'Manage booking assignments, execution statuses, proofs, and feedback' }}</div>
                </div>
            </div>

            <div class="quick-table-responsive">
                <table id="datatable" class="quick-table">
                    <thead>
                        <tr>
                            <th>{{ $isAr ? 'الباقة والنوع' : 'Package & Type' }}</th>
                            <th>{{ $isAr ? 'التاريخ والوقت' : 'Date & Schedule' }}</th>
                            <th>{{ $isAr ? 'المستفيد / العميل' : 'Customer & Contact' }}</th>
                            <th>{{ $isAr ? 'الموقع / المركبة' : 'Location / Asset' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'الحالة' : 'Status' }}</th>
                            <th style="text-align: center;">{{ $isAr ? 'التقييم' : 'Rating' }}</th>
                            <th style="text-align: {{ $isAr ? 'left' : 'right' }};">{{ $isAr ? 'الإجراءات والإثبات' : 'Action & Proof' }}</th>
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
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="quick-category-avatar-placeholder" style="width:36px;height:36px;border-radius:10px;background:rgba(31,107,255,.09);color:var(--quick-blue);display:grid;place-items:center;font-weight:900;font-size:13px;border:1px solid rgba(31,107,255,.15);flex-shrink:0;">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>
                                        </div>
                                        <div style="min-width: 0;">
                                            <strong style="color:var(--quick-shell-ink);font-size:13px;display:block;">{{ $pkgName }}</strong>
                                            <span class="quick-order-badge" style="display:inline-block;padding:2px 8px;border-radius:6px;background:rgba(31,107,255,.08);color:var(--quick-blue);font-size:11px;font-weight:700;margin-top:2px;">
                                                {{ trans('messages.' . $pkgType) }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight:700;font-size:13px;color:var(--quick-shell-ink);">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;color:var(--quick-shell-muted);display:inline;margin-inline-end:4px;"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        {{ $package->date ?: '-' }}
                                    </div>
                                    @if($package->start_at != null && $package->end_at != null)
                                        @php
                                            $to = CarbonCarbon::parse($package->end_at);
                                            $from = CarbonCarbon::parse($package->start_at);
                                            $diff_in_minuts = $from->diffInMinutes($to);
                                            $diff_in_houres = $from->diffInHours($to);
                                            $diff_in_minutes = str_pad($diff_in_minuts % 60, 2, '0', STR_PAD_LEFT);
                                            $diff_in_hours = str_pad($diff_in_houres, 2, '0', STR_PAD_LEFT);
                                        @endphp
                                        <div class="small text-muted" style="margin-top:2px;">
                                            <span style="font-weight:700;color:var(--quick-blue);">{{ $isAr ? 'المدة: ' : 'Duration: ' }}</span>{{ $diff_in_hours }}:{{ $diff_in_minutes }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);">{{ $customerName }}</div>
                                    <div class="small text-muted" style="direction:ltr;text-align:{{ $isAr ? 'right' : 'left' }};margin-top:2px;">{{ $customerPhone }}</div>
                                </td>
                                <td>
                                    @if ($package->car)
                                        <div class="small" style="color:var(--quick-shell-ink);">
                                            <span style="font-weight:700;">{{ $isAr ? 'المركبة: ' : 'Car: ' }}</span>{{ @$items->car_number ?: '-' }} ({{ @$items->car_model ?: '-' }})
                                        </div>
                                    @endif
                                    @if ($package->address)
                                        <div class="small" style="color:var(--quick-shell-muted);">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;display:inline;color:var(--quick-blue);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
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
                                <td style="text-align: center;">
                                    <a href="{{ route('servicepackage.rate_view', $package->subscription_id) }}" class="quick-drawer-action-btn" title="{{ $isAr ? 'عرض التقييمات' : 'View Ratings' }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </a>
                                </td>
                                <td style="text-align: {{ $isAr ? 'left' : 'right' }};">
                                    <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: flex-end;">
                                        @if ($package->start_at == null)
                                            <a href="{{ route('handy_man_change.start_service', $package->id) }}" class="quick-btn-action-start">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                                                <span>{{ $isAr ? 'بدء الخدمة' : 'Start' }}</span>
                                            </a>
                                        @else
                                            @if ($package->status == \App\Enums\BookingEnums::handyman_assign)
                                                <a href="{{ route('handy_man_change.change_status', [$package->id, \App\Enums\BookingEnums::finished]) }}" class="quick-btn-action-finish">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><polyline points="20 6 9 17 4 12"/></svg>
                                                    <span>{{ $isAr ? 'إنهاء' : 'Finish' }}</span>
                                                </a>
                                            @endif
                                            @if ($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type == "handyman" && count($package->rate) == 0)
                                                <button type="button" class="quick-btn-action-rate" data-toggle="modal" data-target="#rateModal_{{ $package->id }}">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                                                    <span>{{ $isAr ? 'تقييم' : 'Rate' }}</span>
                                                </button>
                                            @endif
                                        @endif

                                        <button type="button" class="quick-drawer-action-btn" data-toggle="modal" data-target="#proofModal_{{ $package->id }}" title="{{ $isAr ? 'رفع الإثبات والتعليق' : 'Proof & Comment' }}">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:13px;height:13px;"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                            <span>{{ $isAr ? 'الإثبات' : 'Proof' }}</span>
                                        </button>
                                    </div>

                                    <!-- Proof Modal -->
                                    <div class="modal fade sanad-document-modal" id="proofModal_{{ $package->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title font-weight-bold">{{ $isAr ? 'توثيق وإثبات تنفيذ الخدمة' : 'Proof Image & Comment' }}</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                </div>
                                                <form action="{{ route('proof_image_comment.store') }}" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <input type="hidden" name="package_id" value="{{ $package->id }}">
                                                        <div class="form-group mb-3">
                                                            <label class="quick-filter-label">{{ $isAr ? 'صورة الإثبات (قبل التنفيذ)' : 'Upload Proof Before' }} <span style="color:#ef4444;">*</span></label>
                                                            <input type="file" class="form-control quick-input" name="proof_image" accept="image/*" required>
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label class="quick-filter-label">{{ $isAr ? 'ملاحظات قبل البدء' : 'Comment Before' }}</label>
                                                            <textarea class="form-control quick-input" name="comment_before" rows="2" placeholder="{{ $isAr ? 'أدخل تفاصيل الحالة قبل التنفيذ' : 'Enter pre-service notes' }}"></textarea>
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label class="quick-filter-label">{{ $isAr ? 'صورة الإثبات (بعد الإنجاز)' : 'Upload Proof After' }} <span style="color:#ef4444;">*</span></label>
                                                            <input type="file" class="form-control quick-input" name="proof_image" accept="image/*" required>
                                                        </div>
                                                        <div class="form-group mb-3">
                                                            <label class="quick-filter-label">{{ $isAr ? 'ملاحظات بعد الإنجاز' : 'Comment After' }}</label>
                                                            <textarea class="form-control quick-input" name="comment_after" rows="2" placeholder="{{ $isAr ? 'أدخل تفاصيل ما بعد التنفيذ' : 'Enter post-service notes' }}"></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                                        <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary">{{ __('messages.save') }}</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Rate Modal -->
                                    @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type == "handyman" && count($package->rate) == 0)
                                        <div class="modal fade sanad-document-modal" id="rateModal_{{ $package->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title font-weight-bold">{{ $isAr ? 'تقييم الخدمة' : 'Rate Service Execution' }}</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                                    </div>
                                                    <form action="{{ route('servicepackage.rate', $package->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-body">
                                                            <div class="form-group text-center mb-3">
                                                                <label class="quick-filter-label">{{ $isAr ? 'التقييم بالنجوم' : 'Rating Score' }}</label>
                                                                <div id="star-rating-{{ $package->id }}" class="star-rating" style="display:inline-flex;gap:8px;font-size:26px;color:#f59e0b;cursor:pointer;margin-top:6px;">
                                                                    <i class="far fa-star" data-value="1"></i>
                                                                    <i class="far fa-star" data-value="2"></i>
                                                                    <i class="far fa-star" data-value="3"></i>
                                                                    <i class="far fa-star" data-value="4"></i>
                                                                    <i class="far fa-star" data-value="5"></i>
                                                                </div>
                                                                <input type="hidden" id="rating_{{ $package->id }}" name="rating" value="5" required>
                                                            </div>
                                                            <div class="form-group mb-3">
                                                                <label class="quick-filter-label">{{ $isAr ? 'ملاحظات وانطباع المستفيد' : 'Customer Feedback' }}</label>
                                                                <textarea id="feedback_{{ $package->id }}" name="feedback" class="form-control quick-input" rows="3" placeholder="{{ $isAr ? 'أدخل تعليق وملاحظات التقييم...' : 'Enter execution feedback...' }}"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="quick-admin-hero-btn quick-admin-hero-btn-secondary" data-dismiss="modal">{{ __('messages.cancel') }}</button>
                                                            <button type="submit" class="quick-admin-hero-btn quick-admin-hero-btn-primary">{{ $isAr ? 'إرسال التقييم' : 'Submit Rating' }}</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted font-weight-bold">
                                    {{ $isAr ? 'لا توجد حجوزات باقات مسجلة حالياً.' : 'No package service bookings found.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @once
    <style>
        .quick-package-booking-page {
            width: 100%;
        }

        .quick-btn-action-start {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 8px;
            background: #10b981;
            color: #ffffff !important;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: all .15s ease;
        }
        .quick-btn-action-start:hover {
            background: #059669;
            color: #ffffff !important;
        }

        .quick-btn-action-finish {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 8px;
            background: #ef4444;
            color: #ffffff !important;
            font-size: 12px;
            font-weight: 800;
            text-decoration: none;
            transition: all .15s ease;
        }
        .quick-btn-action-finish:hover {
            background: #dc2626;
            color: #ffffff !important;
        }

        .quick-btn-action-rate {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 8px;
            background: rgba(245,158,11,.1);
            color: #d97706 !important;
            border: 1px solid rgba(245,158,11,.25);
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            transition: all .15s ease;
        }
        .quick-btn-action-rate:hover {
            background: #f59e0b;
            color: #ffffff !important;
        }

        .star-rating i {
            transition: transform .15s ease, color .15s ease;
        }
        .star-rating i:hover {
            transform: scale(1.2);
        }
    </style>
    @endonce

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.star-rating i').forEach(function(star) {
                star.addEventListener('click', function() {
                    let value = parseInt(this.getAttribute('data-value'));
                    let container = this.parentElement;
                    let stars = container.querySelectorAll('i');

                    stars.forEach(function(s) {
                        let sVal = parseInt(s.getAttribute('data-value'));
                        if (sVal <= value) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });

                    let hiddenInput = container.parentElement.querySelector('input[type="hidden"]');
                    if (hiddenInput) {
                        hiddenInput.value = value;
                    }
                });
            });
        });
    </script>
</x-master-layout>
