<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">Messages</h1><div class="sanad-muted">Unified request inbox for Sanad platform communication.</div></div></div>
    <div class="sanad-grid">@forelse($threads as $thread)<a class="sanad-card p-3 text-decoration-none" href="{{ route('customer-portal.requests.show', $thread->booking_id) }}"><strong>Request #{{ $thread->booking_id }}</strong><div class="sanad-muted">{{ optional($thread->messages->last())->message ?? 'No messages yet.' }}</div><small>{{ optional($thread->last_message_at ?? $thread->updated_at)->format('Y-m-d H:i') }}</small></a>@empty<div class="sanad-card"><div class="sanad-card-body">No conversations yet.</div></div>@endforelse</div>
    <div class="mt-3">{{ $threads->links() }}</div>
</div>
</x-master-layout>
