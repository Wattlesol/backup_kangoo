<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid quick-role-page quick-partner-page">
        <div class="partner-page-heading">
            <div>
                <h1><i class="fas fa-columns"></i> {{ $isAr ? 'لوحة كانبان التنفيذية للعمليات' : 'Operations Kanban Workspace' }}</h1>
                <p>{{ $isAr ? 'متابعة تدفق المعاملات وتحريك البطاقات ومراقبة مهل SLA لكل مرحلة.' : 'Manage stage progression, drag order cards across the execution pipeline, and monitor SLA commitments.' }}</p>
            </div>
            <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()"><i class="fas fa-sync-alt"></i> {{ $isAr ? 'مزامنة اللوحة' : 'Sync board' }}</button>
        </div>
        <div class="sanad-kanban">
            @foreach($columns as $stage => $orders)
                <div class="sanad-kanban-column" data-stage="{{ $stage }}">
                    <div class="sanad-kanban-header"><span><i></i>{{ quick_status_label($stage) }}</span><b>{{ $orders->count() }}</b></div>
                    <div class="sanad-kanban-drop">
                        @forelse($orders as $order)
                            <div class="sanad-kanban-card" draggable="true" data-id="{{ $order->id }}">
                                <div class="sanad-kanban-card__top"><strong><a href="{{ route('provider.order.show', $order->id) }}">{{ $order->quick_reference }}</a></strong><span class="badge badge-light">{{ quick_status_label($order->sanad_priority ?: 'normal') }}</span></div>
                                <div class="sanad-kanban-card__service">{{ localized_model_name($order->service) }}</div>
                                <div class="text-muted small">{{ optional($order->customer)->display_name ?: '-' }}</div>
                                <div class="mt-2">
                                    <span class="sanad-kanban-sla"><i class="far fa-clock"></i> {{ optional($order->sla_due_at)->format('Y-m-d H:i') ?: ($isAr ? 'بدون SLA' : 'No SLA') }}</span>
                                </div>
                                <div class="sanad-kanban-owner"><i class="far fa-user"></i><small>{{ $order->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: ($isAr ? 'غير مسند' : 'Unassigned') }}</small></div>
                            </div>
                        @empty
                            <div class="text-muted small p-2">{{ $isAr ? 'لا توجد طلبات' : 'No orders' }}</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@section('bottom_script')
<style>
.partner-page-heading{display:flex;align-items:center;justify-content:space-between;gap:20px;margin:10px 0 24px}.partner-page-heading h1{display:flex;align-items:center;gap:10px;margin:0;color:#0f1d33;font-size:28px;font-weight:800}.partner-page-heading h1 i{color:#1f6bff;font-size:25px}.partner-page-heading p{margin:7px 0 0;color:#6d7d94}.partner-page-heading .btn{border-radius:12px;padding:10px 16px;font-weight:700;white-space:nowrap}
.sanad-kanban{display:flex;gap:14px;overflow-x:auto;padding:0 0 18px;scroll-snap-type:inline proximity;scrollbar-color:#b8c5d7 transparent}.sanad-kanban-column{flex:0 0 calc((100% - 42px)/4);min-width:250px;background:rgba(248,250,252,.8);border:1px solid var(--quick-line,#dce5f1);border-radius:16px;overflow:hidden;scroll-snap-align:start}.sanad-kanban-header{display:flex;justify-content:space-between;align-items:center;font-weight:700;padding:14px;border-bottom:1px solid var(--quick-line,#e6e8ee)}.sanad-kanban-header>span{display:flex;align-items:center;gap:8px;font-size:12px}.sanad-kanban-header i{width:9px;height:9px;border-radius:50%;background:#1f6bff}.sanad-kanban-header b{display:inline-flex;min-width:24px;height:24px;align-items:center;justify-content:center;border-radius:12px;background:#e8eef7;color:#5e6e84;font-size:11px}.sanad-kanban-drop{min-height:420px;padding:10px}.sanad-kanban-card{background:var(--quick-card,#fff);border:1px solid var(--quick-line,#e6e8ee);border-radius:13px;padding:13px;margin-bottom:10px;cursor:grab;box-shadow:0 5px 16px rgba(15,41,51,.05)}.sanad-kanban-card__top{display:flex;align-items:center;justify-content:space-between;gap:8px}.sanad-kanban-card__service{font-size:12px;font-weight:800;line-height:1.45;margin:9px 0 4px;color:#17263c}.sanad-kanban-sla{font-size:11px;color:#7c8aa0}.sanad-kanban-owner{display:flex;align-items:center;justify-content:space-between;gap:7px;border-top:1px solid #edf1f6;padding-top:9px;margin-top:10px;color:#53637a}.sanad-kanban-owner small{font-weight:700;overflow-wrap:anywhere}.sanad-kanban-card a{color:#1f6bff}
.quick-theme-dark .partner-page-heading h1,.quick-theme-dark .sanad-kanban-card__service{color:#f3f7fb}.quick-theme-dark .sanad-kanban-column{background:#0f2535;border-color:#294154}.quick-theme-dark .sanad-kanban-owner{border-color:#294154}
@media(max-width:1199px){.sanad-kanban-column{flex-basis:calc((100% - 14px)/2)}}@media(max-width:767px){.partner-page-heading{align-items:flex-start;flex-direction:column}.partner-page-heading h1{font-size:22px}.partner-page-heading .btn{width:100%}.sanad-kanban-column{flex-basis:min(300px,84vw);min-width:min(300px,84vw)}}
</style>
<script>
let draggedCard = null;
document.querySelectorAll('.sanad-kanban-card').forEach(function(card) {
    card.addEventListener('dragstart', function() { draggedCard = this; });
});
document.querySelectorAll('.sanad-kanban-drop').forEach(function(drop) {
    drop.addEventListener('dragover', function(e) { e.preventDefault(); });
    drop.addEventListener('drop', function(e) {
        e.preventDefault();
        if (!draggedCard) return;
        const stage = this.closest('.sanad-kanban-column').dataset.stage;
        const id = draggedCard.dataset.id;
        $.post("{{ url('provider-dashboard/kanban') }}/" + id + "/move", {_token: "{{ csrf_token() }}", sanad_stage: stage})
            .done(function(){ location.reload(); })
            .fail(function(){ toastr.error(@json($isAr ? 'تعذر نقل الطلب.' : 'Unable to move order.')); });
    });
});
</script>
@endsection
</x-master-layout>
