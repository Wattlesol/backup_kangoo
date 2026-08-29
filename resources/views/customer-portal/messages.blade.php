<x-master-layout>
@php $isAr = app()->getLocale() === 'ar'; @endphp<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">{{ $isAr ? 'نظام المحادثات' : 'Chat System' }}</h1><div class="sanad-muted">{{ $isAr ? 'صندوق الوارد الموحد للتواصل ومتابعة طلبات منصة كويك.' : 'Unified request inbox for Quick platform communication.' }}</div></div></div>
    <div class="sanad-grid">@forelse($threads as $thread)<a class="sanad-card p-3 text-decoration-none" href="{{ route('customer-portal.requests.show', $thread->booking_id) }}"><strong>{{ $isAr ? 'الطلب' : 'Request' }} #{{ $thread->booking_id }}</strong><div class="sanad-muted">{{ optional($thread->messages->last())->message ?? ($isAr ? 'لا توجد رسائل حتى الآن.' : 'No messages yet.') }}</div><small>{{ optional($thread->last_message_at ?? $thread->updated_at)->format('Y-m-d H:i') }}</small></a>@empty<div class="sanad-card"><div class="sanad-card-body">{{ $isAr ? 'لا توجد محادثات حتى الآن.' : 'No conversations yet.' }}</div></div>@endforelse</div>
    <div class="mt-3">{{ $threads->links() }}</div>
</div>
</x-master-layout>
