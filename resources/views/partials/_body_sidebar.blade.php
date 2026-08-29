@php
    $currentLocale = app()->getLocale();
    $isAr = in_array($currentLocale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $authUser = auth()->user();
    $userType = $authUser->user_type ?? 'customer';
    $currentRoute = Route::currentRouteName();
    $canViewPayment = !$authUser || empty($authUser->provider_id) || $authUser->hasSanadModulePermission('payment_status', 'read');
    $canViewTeam = !$authUser || empty($authUser->provider_id) || $authUser->hasSanadModulePermission('team_employees', 'read');

    $portalTitle = match($userType) {
        'admin', 'demo_admin' => $isAr ? 'بوابة الإدارة' : 'Admin portal',
        'provider' => $isAr ? 'بوابة الشريك' : 'Partner portal',
        'handyman' => $isAr ? 'بوابة الموظف' : 'Employee portal',
        default => $isAr ? 'بوابة العميل' : 'Customer portal',
    };
@endphp

<aside class="quick-shell-sidebar" dir="{{ $isAr ? 'rtl' : 'ltr' }}" aria-label="{{ $portalTitle }}">
    <div class="quick-sidebar-top">
        <span>{{ $portalTitle }}</span>
        <button type="button" class="quick-mobile-close" onclick="toggleQuickMobileNav(false)" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <button type="button" class="quick-collapse-button" onclick="toggleQuickSidebar()" aria-label="Collapse">
            @if($isAr)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M15 3v18"/><path d="m8 9 3 3-3 3"/></svg>
            @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/><path d="m16 9-3 3 3 3"/></svg>
            @endif
        </button>
    </div>

    <nav class="quick-sidebar-scroll">
        @if(in_array($userType, ['admin', 'demo_admin']))
            <!-- 1. Overview -->
            <details {{ in_array($currentRoute, ['sanad.dashboard', 'home']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'نظرة عامة' : 'Overview' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('sanad.dashboard') }}" class="{{ in_array($currentRoute, ['sanad.dashboard', 'home']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>{{ $isAr ? 'لوحة العمليات' : 'Operations dashboard' }}</span>
                        @if(in_array($currentRoute, ['sanad.dashboard', 'home']))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;"><polyline points="{{ $isAr ? '15 18 9 12 15 6' : '9 18 15 12 9 6' }}"/></svg>
                        @endif
                    </a>
                </div>
            </details>

            <!-- 2. Service Management -->
            <details {{ in_array($currentRoute, ['category.index', 'subcategory.index', 'service.index', 'servicepackage.index', 'serviceaddon.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'إدارة الخدمات' : 'Service management' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('category.index') }}" class="{{ $currentRoute === 'category.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                        <span>{{ $isAr ? 'التصنيفات' : 'Categories' }}</span>
                    </a>
                    <a href="{{ route('subcategory.index') }}" class="{{ $currentRoute === 'subcategory.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="16" y="16" width="6" height="6" rx="1"/><rect x="2" y="16" width="6" height="6" rx="1"/><rect x="9" y="2" width="6" height="6" rx="1"/><path d="M5 16v-3a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3"/><path d="M12 12V8"/></svg>
                        <span>{{ $isAr ? 'التصنيفات الفرعية' : 'Subcategories' }}</span>
                    </a>
                    <a href="{{ route('service.index') }}" class="{{ $currentRoute === 'service.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>{{ $isAr ? 'قائمة الخدمات' : 'Service list' }}</span>
                    </a>
                    <a href="{{ route('servicepackage.index') }}" class="{{ $currentRoute === 'servicepackage.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                        <span>{{ $isAr ? 'حزم الخدمات' : 'Service bundles' }}</span>
                    </a>
                    <a href="{{ route('serviceaddon.index') }}" class="{{ $currentRoute === 'serviceaddon.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M9 15h6"/><path d="M12 18v-6"/></svg>
                        <span>{{ $isAr ? 'الخدمات الإضافية' : 'Additional services' }}</span>
                    </a>
                </div>
            </details>

            <!-- 3. Booking Management -->
            <details {{ in_array($currentRoute, ['sanad.requests.index', 'booking.create', 'sanad.assignments.index', 'sanad.documents.queue', 'servicepackage.Handyman_booking']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'إدارة الحجوزات' : 'Booking management' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('sanad.requests.index') }}" class="{{ $currentRoute === 'sanad.requests.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <span>{{ $isAr ? 'الحجوزات والطلبات' : 'Bookings & requests' }}</span>
                    </a>
                    <a href="{{ route('booking.create') }}" class="{{ $currentRoute === 'booking.create' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                        <span>{{ $isAr ? 'إنشاء طلب' : 'Create request' }}</span>
                    </a>
                    <a href="{{ route('sanad.assignments.index') }}" class="{{ $currentRoute === 'sanad.assignments.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        <span>{{ $isAr ? 'الإسناد' : 'Assignments' }}</span>
                    </a>
                    <a href="{{ route('sanad.documents.queue') }}" class="{{ $currentRoute === 'sanad.documents.queue' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span>{{ $isAr ? 'مستندات الطلبات' : 'Request documents' }}</span>
                    </a>
                    <a href="{{ route('servicepackage.Handyman_booking') }}" class="{{ $currentRoute === 'servicepackage.Handyman_booking' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 6 4 14H4L8 6"/><path d="M12 2v4"/><path d="M9 14h6"/></svg>
                        <span>{{ $isAr ? 'حجوزات الباقات' : 'Package bookings' }}</span>
                    </a>
                </div>
            </details>

            <!-- 4. AI & Knowledge -->
            <details {{ in_array($currentRoute, ['sanad.ai.index', 'sanad.knowledge.index', 'sanad.ai.escalations.index', 'sanad.chat.workspace']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الذكاء والمعرفة' : 'AI & knowledge' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('sanad.ai.index') }}" class="{{ $currentRoute === 'sanad.ai.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m4.93 4.93 2.83 2.83"/><path d="M2 12h4"/><path d="m4.93 19.07 2.83-2.83"/><path d="M12 22v-4"/><path d="m19.07 19.07-2.83-2.83"/><path d="M22 12h-4"/><path d="m19.07 4.93-2.83 2.83"/><circle cx="12" cy="12" r="4"/></svg>
                        <span>{{ $isAr ? 'لوحة تحكم الذكاء' : 'AI operations console' }}</span>
                    </a>
                    <a href="{{ route('sanad.knowledge.index') }}" class="{{ $currentRoute === 'sanad.knowledge.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                        <span>{{ $isAr ? 'قاعدة المعرفة' : 'Knowledge base' }}</span>
                    </a>
                    <a href="{{ route('sanad.ai.escalations.index') }}" class="{{ $currentRoute === 'sanad.ai.escalations.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ $isAr ? 'تصعيدات الذكاء الاصطناعي' : 'AI escalations & monitoring' }}</span>
                    </a>
                    <a href="{{ route('sanad.chat.workspace') }}" class="{{ $currentRoute === 'sanad.chat.workspace' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>{{ $isAr ? 'صندوق الوارد الموحد' : 'Unified inbox' }}</span>
                    </a>
                </div>
            </details>

            <!-- 5. Performance & Quality -->
            <details {{ in_array($currentRoute, ['complaint.index_data', 'sanad.partner-performance', 'ratingreview.index', 'handyman-rating.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الأداء والجودة' : 'Performance & quality' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('complaint.index_data') }}" class="{{ $currentRoute === 'complaint.index_data' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                        <span>{{ $isAr ? 'مراقبة الجودة' : 'Quality control' }}</span>
                    </a>
                    <a href="{{ route('sanad.partner-performance') }}" class="{{ $currentRoute === 'sanad.partner-performance' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                        <span>{{ $isAr ? 'أداء الشركاء' : 'Partner performance' }}</span>
                    </a>
                    <a href="{{ route('ratingreview.index') }}" class="{{ $currentRoute === 'ratingreview.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>{{ $isAr ? 'تقييمات العملاء' : 'Customer ratings' }}</span>
                    </a>
                    <a href="{{ route('handyman-rating.index') }}" class="{{ $currentRoute === 'handyman-rating.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                        <span>{{ $isAr ? 'تقييمات الموظفين' : 'Employee ratings' }}</span>
                    </a>
                </div>
            </details>

            <!-- 6. Partner Management -->
            <details {{ in_array($currentRoute, ['provider.index', 'provider.pending', 'providertype.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الشركاء' : 'Partner management' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('provider.index') }}" class="{{ $currentRoute === 'provider.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>{{ $isAr ? 'قائمة الشركاء' : 'Partner list' }}</span>
                    </a>
                    <a href="{{ route('provider.pending', ['status' => 'pending']) }}" class="{{ $currentRoute === 'provider.pending' && request('status') === 'pending' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>{{ $isAr ? 'طلبات اعتماد الشركاء' : 'Partner approvals' }}</span>
                    </a>
                    <a href="{{ route('provider.pending', ['status' => 'subscribe']) }}" class="{{ $currentRoute === 'provider.pending' && request('status') === 'subscribe' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span>{{ $isAr ? 'اشتراكات الشركاء' : 'Subscriptions' }}</span>
                    </a>
                    <a href="{{ route('providertype.index') }}" class="{{ $currentRoute === 'providertype.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                        <span>{{ $isAr ? 'أنواع الشركاء' : 'Partner types' }}</span>
                    </a>
                </div>
            </details>

            <!-- 7. Employee Management -->
            <details {{ in_array($currentRoute, ['handyman.index', 'handymantype.index', 'handymanEarning']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الموظفون' : 'Employee management' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('handyman.index') }}" class="{{ $currentRoute === 'handyman.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>{{ $isAr ? 'قائمة الموظفين' : 'Employee list' }}</span>
                    </a>
                    <a href="{{ route('handymantype.index') }}" class="{{ $currentRoute === 'handymantype.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>{{ $isAr ? 'أنواع الموظفين' : 'Employee types' }}</span>
                    </a>
                    <a href="{{ route('handymanEarning') }}" class="{{ $currentRoute === 'handymanEarning' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        <span>{{ $isAr ? 'أرباح الموظفين' : 'Employee earnings' }}</span>
                    </a>
                </div>
            </details>

            <!-- 8. Customers & Users -->
            <details {{ in_array($currentRoute, ['user.index', 'user.all']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'العملاء والمستخدمون' : 'Customers & users' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('user.index', ['user_type' => 'user']) }}" class="{{ $currentRoute === 'user.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>{{ $isAr ? 'قائمة العملاء' : 'Customer list' }}</span>
                    </a>
                    <a href="{{ route('user.all', ['status' => 'unverified']) }}" class="{{ $currentRoute === 'user.all' && request('status') === 'unverified' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="17" y1="8" x2="23" y2="14"/><line x1="23" y1="8" x2="17" y2="14"/></svg>
                        <span>{{ $isAr ? 'حسابات غير موثقة' : 'Unverified accounts' }}</span>
                    </a>
                    <a href="{{ route('user.all', ['status' => 'all']) }}" class="{{ $currentRoute === 'user.all' && request('status') === 'all' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>{{ $isAr ? 'كافة المستخدمين' : 'All users' }}</span>
                    </a>
                </div>
            </details>

            <!-- 9. Transactions -->
            <details {{ in_array($currentRoute, ['payment.index', 'earning', 'wallet.index', 'tax.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'المعاملات' : 'Transactions' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('payment.index') }}" class="{{ $currentRoute === 'payment.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>{{ $isAr ? 'المدفوعات والمركز المالي' : 'Payments & financial center' }}</span>
                    </a>
                    <a href="{{ route('earning') }}" class="{{ $currentRoute === 'earning' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M16 8h-6a2 2 0 1 0 0 4h4a2 2 0 1 1 0 4H8"/><path d="M12 18V6"/></svg>
                        <span>{{ $isAr ? 'الأرباح' : 'Earnings' }}</span>
                    </a>
                    <a href="{{ route('wallet.index') }}" class="{{ $currentRoute === 'wallet.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 7H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="M16 12h4"/></svg>
                        <span>{{ $isAr ? 'المحفظة' : 'Wallet' }}</span>
                    </a>
                    <a href="{{ route('tax.index') }}" class="{{ $currentRoute === 'tax.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                        <span>{{ $isAr ? 'الضرائب والرسوم' : 'Taxes' }}</span>
                    </a>
                </div>
            </details>

            <!-- 10. Promotion -->
            <details {{ in_array($currentRoute, ['coupon.index', 'coupon.create']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'العروض' : 'Promotion' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('coupon.index') }}" class="{{ $currentRoute === 'coupon.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 12H16c-.7 2-2 3-4 3s-3.3-1-4-3H2.5"/></svg>
                        <span>{{ $isAr ? 'قائمة الكوبونات' : 'Coupon list' }}</span>
                    </a>
                    <a href="{{ route('coupon.create') }}" class="{{ $currentRoute === 'coupon.create' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>{{ $isAr ? 'إضافة كوبون' : 'Add coupon' }}</span>
                    </a>
                </div>
            </details>

            <!-- 11. System & Operations Settings -->
            <details {{ in_array($currentRoute, ['plans.index', 'document.index', 'slider.index', 'pushNotification.index', 'setting.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'النظام والإعدادات' : 'System & settings' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('plans.index') }}" class="{{ $currentRoute === 'plans.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        <span>{{ $isAr ? 'خطط الاشتراكات' : 'Subscription plans' }}</span>
                    </a>
                    <a href="{{ route('document.index') }}" class="{{ $currentRoute === 'document.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span>{{ $isAr ? 'أنواع المستندات' : 'Document types' }}</span>
                    </a>
                    <a href="{{ route('slider.index') }}" class="{{ $currentRoute === 'slider.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="14" x="3" y="5" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        <span>{{ $isAr ? 'الشرائح الإعلانية' : 'Sliders' }}</span>
                    </a>
                    <a href="{{ route('pushNotification.index') }}" class="{{ $currentRoute === 'pushNotification.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>{{ $isAr ? 'إشعارات النظام' : 'Push notifications' }}</span>
                    </a>
                    <a href="{{ route('setting.index') }}" class="{{ $currentRoute === 'setting.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>{{ $isAr ? 'إعدادات النظام' : 'System settings' }}</span>
                    </a>
                </div>
            </details>
        @elseif($userType === 'provider')
            <!-- 1. Main -->
            <details {{ in_array($currentRoute, ['provider.dashboard', 'home']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الرئيسية' : 'Main' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('provider.dashboard') }}" class="{{ in_array($currentRoute, ['provider.dashboard', 'home']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>{{ $isAr ? 'لوحة التحكم' : 'Dashboard' }}</span>
                        @if(in_array($currentRoute, ['provider.dashboard', 'home']))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;"><polyline points="{{ $isAr ? '15 18 9 12 15 6' : '9 18 15 12 9 6' }}"/></svg>
                        @endif
                    </a>
                </div>
            </details>

            <!-- 2. Operations -->
            <details {{ in_array($currentRoute, ['provider.order.index', 'provider.order.show', 'sanad.chat.workspace', 'provider.kanban.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'العمليات' : 'Operations' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('provider.order.index') }}" class="{{ in_array($currentRoute, ['provider.order.index', 'provider.order.show']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                        <span>{{ $isAr ? 'الطلبات المسندة' : 'Assigned Orders' }}</span>
                    </a>
                    <a href="{{ route('sanad.chat.workspace') }}" class="{{ $currentRoute === 'sanad.chat.workspace' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>{{ $isAr ? 'مساحة المحادثات' : 'Chat Workspace' }}</span>
                    </a>
                    <a href="{{ route('provider.kanban.index') }}" class="{{ $currentRoute === 'provider.kanban.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18"/><path d="M15 3v18"/></svg>
                        <span>{{ $isAr ? 'لوحة العمليات' : 'Operations Board' }}</span>
                    </a>
                </div>
            </details>

            <!-- 3. Team Management -->
            <details {{ in_array($currentRoute, ['provider.services.index', 'provider.workflows.index', 'provider.workflows.create', 'provider.workflows.edit', 'provider.employees.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'إدارة الفريق' : 'Team Management' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('provider.services.index') }}" class="{{ $currentRoute === 'provider.services.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <span>{{ $isAr ? 'الخدمات المفعلة' : 'Enabled Services' }}</span>
                    </a>
                    <a href="{{ route('provider.workflows.index') }}" class="{{ in_array($currentRoute, ['provider.workflows.index', 'provider.workflows.create']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="18" r="3"/><circle cx="6" cy="6" r="3"/><path d="M18 6a9 9 0 0 1-9 9"/></svg>
                        <span>{{ $isAr ? 'مسارات عمل الموظفين' : 'Employee Workflows' }}</span>
                    </a>
                    <a href="{{ route('provider.employees.index') }}" class="{{ $currentRoute === 'provider.employees.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>{{ $isAr ? 'دليل الموظفين' : 'Employee Directory' }}</span>
                    </a>
                </div>
            </details>

            <!-- 4. Performance & Account -->
            <details {{ in_array($currentRoute, ['provider.performance.index', 'provider.financial.index', 'provider.notifications.index', 'provider.profile.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الأداء والحساب' : 'Performance & Account' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('provider.performance.index') }}" class="{{ $currentRoute === 'provider.performance.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"/><line x1="18" y1="20" x2="18" y2="4"/><line x1="6" y1="20" x2="6" y2="16"/></svg>
                        <span>{{ $isAr ? 'الأداء' : 'Performance' }}</span>
                    </a>
                    <a href="{{ route('provider.financial.index') }}" class="{{ $currentRoute === 'provider.financial.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>{{ $isAr ? 'المركز المالي' : 'Financial Center' }}</span>
                    </a>
                    <a href="{{ route('provider.notifications.index') }}" class="{{ $currentRoute === 'provider.notifications.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                        <span>{{ $isAr ? 'الإشعارات' : 'Notifications' }}</span>
                    </a>
                    <a href="{{ route('provider.profile.index') }}" class="{{ $currentRoute === 'provider.profile.index' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>{{ $isAr ? 'ملف الشريك' : 'Partner Profile' }}</span>
                    </a>
                </div>
            </details>
@php
            if (auth()->user()->user_type == "handyman") {
                // employee navigation
            }
            $canViewPayment = !$authUser || empty($authUser->provider_id) || $authUser->hasSanadModulePermission('payment_status', 'read');
            $canViewTeam = !$authUser || empty($authUser->provider_id) || $authUser->hasSanadModulePermission('team_employees', 'read');
        @endphp
        @elseif($userType === 'handyman')
            <!-- 1. Main -->
            <details {{ in_array($currentRoute, ['home']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الرئيسية' : 'Main' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('home') }}" class="{{ in_array($currentRoute, ['home']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>{{ $isAr ? 'لوحة الموظف' : 'Employee Dashboard' }}</span>
                        @if(in_array($currentRoute, ['home']))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;"><polyline points="{{ $isAr ? '15 18 9 12 15 6' : '9 18 15 12 9 6' }}"/></svg>
                        @endif
                    </a>
                </div>
            </details>

            <!-- 2. Employee Operations -->
            <details {{ in_array($currentRoute, ['sanad.requests.index', 'sanad.requests.show', 'sanad.documents.queue', 'sanad.chat.workspace', 'payment.index', 'handyman.index']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'مهام الموظف' : 'Employee Operations' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('sanad.requests.index') }}" class="{{ in_array($currentRoute, ['sanad.requests.index', 'sanad.requests.show']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                        <span>{{ $isAr ? 'الطلبات والمهام المسندة' : 'Assigned Tasks' }}</span>
                    </a>
                    <a href="{{ route('sanad.documents.queue') }}" class="{{ $currentRoute === 'sanad.documents.queue' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span>{{ $isAr ? 'طلبات النواقص والمستندات' : 'Document Requests' }}</span>
                    </a>
                    <a href="{{ route('sanad.chat.workspace') }}" class="{{ $currentRoute === 'sanad.chat.workspace' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>{{ $isAr ? 'محادثات المستفيدين' : 'Customer Chat' }}</span>
                    </a>
                    @if($canViewPayment)
                        <a href="{{ route('payment.index') }}" class="{{ $currentRoute === 'payment.index' ? 'active' : '' }}">
                            <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            <span>{{ $isAr ? 'حالة المدفوعات' : 'Payment Status' }}</span>
                        </a>
                    @endif
                    @if($canViewTeam)
                        <a href="{{ route('handyman.index') }}" class="{{ $currentRoute === 'handyman.index' ? 'active' : '' }}">
                            <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                            <span>{{ $isAr ? 'موظفو الفريق' : 'Team Employees' }}</span>
                        </a>
                    @endif
                </div>
            </details>
        @else
            <!-- 1. Main -->
            <details {{ in_array($currentRoute, ['customer-portal.dashboard']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الرئيسية' : 'Main' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('customer-portal.dashboard') }}" class="{{ $currentRoute === 'customer-portal.dashboard' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                        <span>{{ $isAr ? 'لوحة العميل' : 'Customer Dashboard' }}</span>
                        @if($currentRoute === 'customer-portal.dashboard')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;"><polyline points="{{ $isAr ? '15 18 9 12 15 6' : '9 18 15 12 9 6' }}"/></svg>
                        @endif
                    </a>
                </div>
            </details>

            <!-- 2. Services & Requests -->
            <details {{ in_array($currentRoute, ['customer-portal.catalog', 'customer-portal.catalog.show', 'customer-portal.requests.create', 'customer-portal.requests.index', 'customer-portal.requests.show', 'customer-portal.vault']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'الخدمات والطلبات' : 'Services & Requests' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('customer-portal.catalog') }}" class="{{ in_array($currentRoute, ['customer-portal.catalog', 'customer-portal.catalog.show']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>
                        <span>{{ $isAr ? 'دليل الخدمات' : 'Service Catalog' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.requests.create') }}" class="{{ $currentRoute === 'customer-portal.requests.create' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                        <span>{{ $isAr ? 'طلب جديد' : 'Create Request' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.requests.index') }}" class="{{ in_array($currentRoute, ['customer-portal.requests.index', 'customer-portal.requests.show']) ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M9 12h6"/><path d="M9 16h6"/></svg>
                        <span>{{ $isAr ? 'طلباتي' : 'My Requests' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.vault') }}" class="{{ $currentRoute === 'customer-portal.vault' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/></svg>
                        <span>{{ $isAr ? 'خزنة المستندات' : 'Document Vault' }}</span>
                    </a>
                </div>
            </details>

            <!-- 3. Communication & Support -->
            <details {{ in_array($currentRoute, ['customer-portal.messages', 'customer-portal.billing', 'customer-portal.support', 'customer-portal.profile']) ? 'open' : '' }}>
                <summary>
                    <span>{{ $isAr ? 'التواصل والدعم' : 'Communication & Support' }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
                </summary>
                <div class="quick-sidebar-group">
                    <a href="{{ route('customer-portal.messages') }}" class="{{ $currentRoute === 'customer-portal.messages' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        <span>{{ $isAr ? 'المحادثات والرسائل' : 'Messages' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.billing') }}" class="{{ $currentRoute === 'customer-portal.billing' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>{{ $isAr ? 'الفواتير والمدفوعات' : 'Billing & Payments' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.support') }}" class="{{ $currentRoute === 'customer-portal.support' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>{{ $isAr ? 'الدعم والشكاوى' : 'Support & Tickets' }}</span>
                    </a>
                    <a href="{{ route('customer-portal.profile') }}" class="{{ $currentRoute === 'customer-portal.profile' ? 'active' : '' }}">
                        <svg class="quick-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>{{ $isAr ? 'الملف الشخصي' : 'Customer Profile' }}</span>
                    </a>
                </div>
            </details>
        @endif
    </nav>

    <div class="quick-sidebar-footer">
        @php
            $footerSettingsUrl = match($userType) {
                'provider' => route('provider.profile.index'),
                'customer', 'user' => route('customer-portal.profile'),
                default => route('setting.index'),
            };
        @endphp
        <a href="{{ $footerSettingsUrl }}" title="{{ $isAr ? 'الإعدادات' : 'Settings' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>{{ $isAr ? 'الإعدادات' : 'Settings' }}</span>
        </a>
        <form method="POST" action="{{ route('logout') }}" id="sidebar-logout-form" style="margin:0;">
            @csrf
            <button type="submit" class="logout-btn" title="{{ $isAr ? 'تسجيل الخروج' : 'Logout' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>{{ $isAr ? 'تسجيل الخروج' : 'Logout' }}</span>
            </button>
        </form>
    </div>
</aside>
