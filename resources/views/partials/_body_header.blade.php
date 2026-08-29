@php
    $currentLocale = app()->getLocale();
    $isAr = in_array($currentLocale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
    $authUser = auth()->user();
    $userType = $authUser->user_type ?? 'customer';
    $portalNames = [
        'admin' => [$isAr ? 'بوابة الإدارة' : 'Admin portal'],
        'demo_admin' => [$isAr ? 'بوابة الإدارة' : 'Admin portal'],
        'provider' => [$isAr ? 'بوابة الشريك' : 'Partner portal'],
        'handyman' => [$isAr ? 'بوابة الموظف' : 'Employee portal'],
        'customer' => [$isAr ? 'بوابة العميل' : 'Customer portal'],
        'user' => [$isAr ? 'بوابة العميل' : 'Customer portal']
    ];
    $portalLabel = $portalNames[$userType][0] ?? ($isAr ? 'بوابة كويك' : 'Quick portal');
    $routeName = Route::currentRouteName();
    $quickPageTitles = [
        'sanad.dashboard' => $isAr ? 'لوحة العمليات' : 'Operations dashboard',
        'sanad.requests.index' => $isAr ? 'طابور الطلبات' : 'Request queue',
        'sanad.requests.show' => $isAr ? 'تفاصيل الطلب' : 'Request detail',
        'sanad.assignments.index' => $isAr ? 'مساحة الإسناد' : 'Assignment hub',
        'sanad.documents.queue' => $isAr ? 'طابور المستندات' : 'Document queue',
        'sanad.chat.workspace' => $isAr ? 'مساحة المحادثات' : 'Chat workspace',
        'sanad.ai.index' => $isAr ? 'عمليات الذكاء الاصطناعي' : 'AI operations',
        'sanad.ai.escalations.index' => $isAr ? 'تصعيدات الذكاء الاصطناعي' : 'AI escalations',
        'sanad.partner-performance' => $isAr ? 'أداء الشركاء' : 'Partner performance',
        'payment.index' => $isAr ? 'المركز المالي' : 'Financial center',
        'cash.list' => $isAr ? 'مدفوعات نقدية' : 'Cash payments',
        'wallet.index' => $isAr ? 'المحفظة' : 'Wallet',
        'earning.index' => $isAr ? 'الأرباح' : 'Earnings',
        'service.index' => $isAr ? 'دليل الخدمات' : 'Service catalog',
        'service.create' => $isAr ? 'إضافة خدمة' : 'Add service',
        'service.edit' => $isAr ? 'تعديل خدمة' : 'Edit service',
        'provider.index' => $isAr ? 'دليل الشركاء' : 'Partner directory',
        'provider.pending' => $isAr ? 'اعتماد الشركاء' : 'Partner approvals',
        'provider.create' => $isAr ? 'إضافة شريك' : 'Add partner',
        'provider.edit' => $isAr ? 'تعديل شريك' : 'Edit partner',
        'provider.show' => $isAr ? 'تفاصيل الشريك' : 'Partner detail',
        'provider.dashboard' => $isAr ? 'لوحة الشريك' : 'Partner dashboard',
        'provider.order.index' => $isAr ? 'الطلبات المسندة' : 'Assigned orders',
        'provider.order.show' => $isAr ? 'تفاصيل الطلب' : 'Order details',
        'provider.kanban.index' => $isAr ? 'لوحة العمليات' : 'Operations board',
        'provider.services.index' => $isAr ? 'الخدمات المفعلة' : 'Enabled services',
        'provider.workflows.index' => $isAr ? 'مسارات عمل الموظفين' : 'Employee workflows',
        'provider.workflows.create' => $isAr ? 'إنشاء مسار عمل' : 'Create workflow',
        'provider.workflows.edit' => $isAr ? 'تعديل مسار العمل' : 'Edit workflow',
        'provider.employees.index' => $isAr ? 'دليل الموظفين' : 'Employee directory',
        'provider.performance.index' => $isAr ? 'الأداء' : 'Performance',
        'provider.financial.index' => $isAr ? 'المركز المالي' : 'Financial center',
        'provider.notifications.index' => $isAr ? 'الإشعارات' : 'Notifications',
        'provider.profile.index' => $isAr ? 'ملف الشريك' : 'Partner profile',
        'customer-portal.dashboard' => $isAr ? 'لوحة تحكم العميل' : 'Customer dashboard',
        'customer-portal.catalog' => $isAr ? 'دليل الخدمات' : 'Service catalog',
        'customer-portal.catalog.show' => $isAr ? 'تفاصيل الخدمة' : 'Service detail',
        'customer-portal.requests.create' => $isAr ? 'طلب جديد' : 'New request',
        'customer-portal.requests.index' => $isAr ? 'طلباتي' : 'My requests',
        'customer-portal.requests.show' => $isAr ? 'تفاصيل الطلب' : 'Request details',
        'customer-portal.vault' => $isAr ? 'خزينة المستندات' : 'Document vault',
        'customer-portal.messages' => $isAr ? 'الرسائل' : 'Messages',
        'customer-portal.billing' => $isAr ? 'الفواتير والمدفوعات' : 'Billing and payments',
        'customer-portal.support' => $isAr ? 'الدعم والشكاوى' : 'Support and complaints',
        'customer-portal.notifications' => $isAr ? 'الإشعارات' : 'Notifications',
        'customer-portal.profile' => $isAr ? 'الملف الشخصي' : 'Customer profile',
        'customer-portal.ai' => $isAr ? 'مساعد كويك الذكي' : 'Quick AI assistant',
    ];
    $resolvedPageTitle = $pageTitle ?? ($quickPageTitles[$routeName] ?? ($isAr ? 'لوحة العمليات' : 'Operations dashboard'));
    $isCustomerPortal = in_array($userType, ['user', 'customer'], true);
    $notificationUrl = $isCustomerPortal
        ? route('customer-portal.notifications')
        : ($userType === 'provider' ? route('provider.notifications.index') : route('notification.index'));
    $headerNotifications = collect();
    $headerUnreadCount = 0;
    if ($isCustomerPortal && $authUser) {
        $platformNotifications = $authUser->notifications()->latest()->get()->map(function ($notification) use ($isAr) {
            return [
                'title' => formatNotificationTitle($notification),
                'message' => formatNotificationMessage($notification),
                'created_at' => $notification->created_at,
                'unread' => is_null($notification->read_at),
                'url' => null,
                'urgent' => false,
            ];
        });
        $requestIds = \App\Models\Booking::where('customer_id', $authUser->id)->pluck('id');
        $buzzNotifications = \App\Models\SanadBuzzAlert::whereIn('booking_id', $requestIds)
            ->where(function ($query) use ($authUser) {
                $query->where('recipient_id', $authUser->id)->orWhereIn('recipient_role', ['user', 'customer']);
            })->latest()->get()->map(function ($buzz) use ($isAr) {
                return [
                    'title' => $isAr ? 'تنبيه عاجل' : 'Urgent request alert',
                    'message' => $buzz->message,
                    'created_at' => $buzz->created_at,
                    'unread' => $buzz->status === 'unread',
                    'url' => route('customer-portal.requests.show', ['id' => $buzz->booking_id, 'buzz_id' => $buzz->id]).'#buzz-'.$buzz->id,
                    'urgent' => true,
                ];
            });
        $headerNotifications = $platformNotifications->concat($buzzNotifications)->sortByDesc('created_at')->values();
        $headerUnreadCount = $headerNotifications->where('unread', true)->count();
    }
@endphp

<header class="quick-shell-header" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
    <div class="quick-shell-brand">
        <a href="{{ route('frontend.index') }}" aria-label="Quick">
            <x-quick-logo :dark="false" />
        </a>
    </div>

    <button class="quick-mobile-menu" type="button" onclick="toggleQuickMobileNav(true)" aria-label="{{ $isAr ? 'فتح القائمة' : 'Open navigation' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
    </button>

    <div class="quick-shell-context">
        <span>{{ $portalLabel }}</span>
        <strong>{{ $resolvedPageTitle }}</strong>
    </div>

    <div class="quick-shell-actions">
        <a href="{{ route('switch-language', ['locale' => $isAr ? 'en' : 'ar']) }}" aria-label="{{ $isAr ? 'Switch to English' : 'التبديل إلى العربية' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
            <span>{{ $isAr ? 'English' : 'العربية' }}</span>
        </a>

        <button type="button" onclick="toggleQuickTheme()" aria-label="Toggle theme">
            <svg id="quick-theme-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            <svg id="quick-theme-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
        </button>

        @if($isCustomerPortal)
            <details class="quick-notification-menu">
                <summary aria-label="{{ $isAr ? 'فتح الإشعارات' : 'Open notifications' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    @if($headerUnreadCount > 0)<span class="quick-notification-count">{{ $headerUnreadCount > 99 ? '99+' : $headerUnreadCount }}</span>@endif
                </summary>
                <div class="quick-notification-dropdown" role="menu">
                    <div class="quick-notification-heading"><strong>{{ $isAr ? 'الإشعارات' : 'Notifications' }}</strong><span>{{ $headerNotifications->count() }}</span></div>
                    <div class="quick-notification-list">
                        @forelse($headerNotifications as $item)
                            @if($item['url'])<a class="quick-notification-item {{ $item['unread'] ? 'is-unread' : '' }}" href="{{ $item['url'] }}" role="menuitem">@else<div class="quick-notification-item {{ $item['unread'] ? 'is-unread' : '' }}">@endif
                                <span class="quick-notification-dot {{ $item['urgent'] ? 'is-urgent' : '' }}"></span>
                                <span class="quick-notification-copy"><strong>{{ $item['title'] }}</strong><span>{{ $item['message'] }}</span><small>{{ optional($item['created_at'])->format('Y-m-d H:i') }}</small></span>
                            @if($item['url'])</a>@else</div>@endif
                        @empty
                            <div class="quick-notification-empty">{{ $isAr ? 'لا توجد إشعارات حالياً.' : 'No notifications right now.' }}</div>
                        @endforelse
                    </div>
                </div>
            </details>
        @else
            <a href="{{ $notificationUrl }}" aria-label="{{ $isAr ? 'الإشعارات' : 'Notifications' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            </a>
        @endif
    </div>
</header>
