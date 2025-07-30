@php
    $auth_user = authSession();
@endphp

<div class="d-flex gap-2">
    <a href="{{ route('provider.order.show', $order->id) }}" class="btn btn-sm btn-primary" title="{{ __('messages.view_details') }}">
        <i class="fas fa-eye"></i>
    </a>
    
    @if($order->status == 'pending' || $order->status == 'confirmed')
        <button type="button" class="btn btn-sm btn-success" onclick="updateOrderStatus({{ $order->id }}, 'processing')" title="{{ __('messages.mark_processing') }}">
            <i class="fas fa-cog"></i>
        </button>
    @endif
    
    @if($order->status == 'processing')
        <button type="button" class="btn btn-sm btn-info" onclick="updateOrderStatus({{ $order->id }}, 'shipped')" title="{{ __('messages.mark_shipped') }}">
            <i class="fas fa-shipping-fast"></i>
        </button>
    @endif
    
    @if($order->status == 'shipped')
        <button type="button" class="btn btn-sm btn-success" onclick="updateOrderStatus({{ $order->id }}, 'delivered')" title="{{ __('messages.mark_delivered') }}">
            <i class="fas fa-check-circle"></i>
        </button>
    @endif
</div>

<script>
function updateOrderStatus(orderId, status) {
    if (confirm('{{ __("messages.are_you_sure") }}')) {
        $.ajax({
            url: "{{ route('provider.order.update-status') }}",
            type: 'POST',
            data: {
                order_id: orderId,
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status) {
                    window.renderedDataTable.ajax.reload();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('{{ __("messages.something_went_wrong") }}');
            }
        });
    }
}
</script>
