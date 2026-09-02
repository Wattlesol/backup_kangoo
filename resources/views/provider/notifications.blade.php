<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-notifications-heading"><div><h1><i class="far fa-bell"></i> {{ $isAr ? 'مركز التنبيهات والإشعارات التشغيلية' : 'Operational Alerts & Notifications' }}</h1><p>{{ $isAr ? 'تابع إسناد الطلبات وتغييرات المراحل والتنبيهات المالية من مكان واحد.' : 'Review assignments, stage changes, financial updates, and office alerts in one place.' }}</p></div></div>
        <div class="card partner-notifications-card">
            <div class="card-body">
                @forelse($notifications as $notification)
                    <div class="partner-notification-item">
                        <span class="partner-notification-icon"><i class="far fa-bell"></i></span>
                        <div>
                            <strong>{{ formatNotificationTitle($notification) }}</strong>
                            <p>{{ formatNotificationMessage($notification) }}</p>
                            <small><i class="far fa-clock"></i> {{ $notification->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                    </div>
                @empty
                    <div class="partner-notification-empty"><i class="far fa-bell-slash"></i><strong>{{ $isAr ? 'لا توجد إشعارات بعد.' : 'No notifications yet.' }}</strong><span>{{ $isAr ? 'ستظهر تنبيهات العمليات هنا.' : 'Operational alerts will appear here.' }}</span></div>
                @endforelse
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
@section('bottom_script')
<style>
.partner-notifications-heading{margin:10px 0 24px}.partner-notifications-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-notifications-heading h1 i{color:#1f6bff;font-size:23px}.partner-notifications-heading p{margin:7px 0 0;color:#6d7d94}.partner-notifications-card{max-width:920px;border:1px solid #dce5f1;border-radius:16px;overflow:hidden;box-shadow:0 8px 22px rgba(15,41,51,.04)}.partner-notifications-card .card-body{padding:0}.partner-notification-item{display:flex;gap:14px;padding:18px 20px;border-bottom:1px solid #edf1f6}.partner-notification-item:last-of-type{border-bottom:0}.partner-notification-icon{display:inline-flex;flex:0 0 38px;width:38px;height:38px;border-radius:11px;align-items:center;justify-content:center;background:rgba(31,107,255,.1);color:#1f6bff}.partner-notification-item strong{display:block;color:#17263c;font-size:13px}.partner-notification-item p{margin:5px 0;color:#63738a;font-size:12px}.partner-notification-item small{color:#98a4b4}.partner-notification-empty{display:flex;align-items:center;flex-direction:column;text-align:center;padding:60px 20px;color:#8795a8}.partner-notification-empty i{font-size:32px;color:#b3bdca;margin-bottom:12px}.partner-notification-empty strong{color:#53637a}.partner-notification-empty span{font-size:12px;margin-top:5px}
.quick-theme-dark .partner-notifications-heading h1,.quick-theme-dark .partner-notification-item strong{color:#f4f8fb}.quick-theme-dark .partner-notifications-card{background:#102536;border-color:#294154}.quick-theme-dark .partner-notification-item{border-color:#294154}
@media(max-width:767px){.partner-notifications-heading h1{font-size:22px}}
</style>
@endsection
</x-master-layout>
