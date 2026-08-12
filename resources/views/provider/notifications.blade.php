<x-master-layout>
    <div class="container-fluid">
        <div class="card"><div class="card-body"><h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5><span class="text-muted">Partner operational notifications and templates supported by the existing notification system.</span></div></div>
        <div class="card">
            <div class="card-body">
                @forelse($notifications as $notification)
                    <div class="border-bottom pb-3 mb-3">
                        <strong>{{ data_get($notification->data, 'subject', data_get($notification->data, 'title', 'Notification')) }}</strong>
                        <div class="text-muted">{{ data_get($notification->data, 'message', '') }}</div>
                        <small>{{ $notification->created_at->format('Y-m-d H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted">No notifications yet.</p>
                @endforelse
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-master-layout>
