<x-master-layout>
    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                <span class="text-muted">Drag cards between Sanad execution stages.</span>
            </div>
        </div>
        <div class="sanad-kanban">
            @foreach($columns as $stage => $orders)
                <div class="sanad-kanban-column" data-stage="{{ $stage }}">
                    <div class="sanad-kanban-header">{{ Str::headline($stage) }} <span>{{ $orders->count() }}</span></div>
                    <div class="sanad-kanban-drop">
                        @forelse($orders as $order)
                            <div class="sanad-kanban-card" draggable="true" data-id="{{ $order->id }}">
                                <strong><a href="{{ route('provider.order.show', $order->id) }}">{{ $order->sanad_reference ?: 'SANAD-'.$order->id }}</a></strong>
                                <div>{{ optional($order->customer)->display_name ?: '-' }}</div>
                                <div class="text-muted small">{{ optional($order->service)->name_en ?: optional($order->service)->name ?: '-' }}</div>
                                <div class="mt-2">
                                    <span class="badge badge-light">{{ ucfirst($order->sanad_priority ?: 'normal') }}</span>
                                    <span class="badge badge-primary">{{ optional($order->sla_due_at)->format('Y-m-d') ?: 'No SLA' }}</span>
                                </div>
                                <small>{{ $order->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: 'No employees' }}</small>
                            </div>
                        @empty
                            <div class="text-muted small p-2">No orders</div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@section('bottom_script')
<style>
.sanad-kanban{display:flex;gap:12px;overflow:auto;padding-bottom:16px}.sanad-kanban-column{min-width:280px;background:#f8f9fb;border:1px solid #e6e8ee;border-radius:8px}.sanad-kanban-header{display:flex;justify-content:space-between;font-weight:700;padding:12px;border-bottom:1px solid #e6e8ee}.sanad-kanban-drop{min-height:260px;padding:10px}.sanad-kanban-card{background:#fff;border:1px solid #e6e8ee;border-radius:8px;padding:10px;margin-bottom:10px;cursor:grab;box-shadow:0 1px 4px rgba(0,0,0,.04)}
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
            .fail(function(){ toastr.error('Unable to move order.'); });
    });
});
</script>
@endsection
</x-master-layout>
