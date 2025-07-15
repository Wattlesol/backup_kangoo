@if($order)
    <div class="d-flex justify-content-end align-items-center">
        @if(auth()->user()->can('order view'))
            <button type="button" class="btn btn-sm btn-info me-2" onclick="viewOrder({{ $order->id }})" title="{{ __('messages.view') }}">
                <i class="fas fa-eye"></i>
            </button>
        @endif
        
        @if(auth()->user()->can('order status update'))
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-bs-toggle="dropdown" title="{{ __('messages.update_status') }}">
                    <i class="fas fa-edit"></i>
                </button>
                <ul class="dropdown-menu">
                    @if($order->status !== 'confirmed')
                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'confirmed')">{{ __('messages.confirmed') }}</a></li>
                    @endif
                    @if($order->status !== 'processing')
                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'processing')">{{ __('messages.processing') }}</a></li>
                    @endif
                    @if($order->status !== 'shipped')
                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'shipped')">{{ __('messages.shipped') }}</a></li>
                    @endif
                    @if($order->status !== 'delivered')
                        <li><a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'delivered')">{{ __('messages.delivered') }}</a></li>
                    @endif
                    @if($order->status !== 'cancelled' && !in_array($order->status, ['delivered']))
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="cancelOrder({{ $order->id }})">{{ __('messages.cancel') }}</a></li>
                    @endif
                </ul>
            </div>
        @endif
        
        @if(auth()->user()->can('order edit') && $order->payment_status !== 'paid')
            <div class="btn-group me-2">
                <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown" title="{{ __('messages.payment_status') }}">
                    <i class="fas fa-credit-card"></i>
                </button>
                <ul class="dropdown-menu">
                    @if($order->payment_status !== 'paid')
                        <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'paid')">{{ __('messages.mark_paid') }}</a></li>
                    @endif
                    @if($order->payment_status !== 'failed')
                        <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'failed')">{{ __('messages.mark_failed') }}</a></li>
                    @endif
                    @if($order->payment_status === 'paid')
                        <li><a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'refunded')">{{ __('messages.mark_refunded') }}</a></li>
                    @endif
                </ul>
            </div>
        @endif
        
        <button type="button" class="btn btn-sm btn-secondary" onclick="printOrder({{ $order->id }})" title="{{ __('messages.print') }}">
            <i class="fas fa-print"></i>
        </button>
    </div>

    <script>
        function viewOrder(id) {
            window.location.href = `/order/${id}`;
        }

        function updateOrderStatus(id, status) {
            const notes = prompt('{{ __("messages.status_update_notes") }}:');
            if (notes !== null) {
                $.ajax({
                    url: '{{ route("order.update-status") }}',
                    type: 'POST',
                    data: {
                        order_id: id,
                        status: status,
                        notes: notes,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            showAlert('success', response.message);
                        } else {
                            showAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    }
                });
            }
        }

        function updatePaymentStatus(id, paymentStatus) {
            if (confirm('{{ __("messages.confirm_payment_status_update") }}')) {
                $.ajax({
                    url: '{{ route("order.update-payment-status") }}',
                    type: 'POST',
                    data: {
                        order_id: id,
                        payment_status: paymentStatus,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            showAlert('success', response.message);
                        } else {
                            showAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    }
                });
            }
        }

        function cancelOrder(id) {
            const reason = prompt('{{ __("messages.cancellation_reason") }}:');
            if (reason !== null) {
                $.ajax({
                    url: '{{ route("order.cancel") }}',
                    type: 'POST',
                    data: {
                        order_id: id,
                        reason: reason,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            showAlert('success', response.message);
                        } else {
                            showAlert('error', response.message);
                        }
                    },
                    error: function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    }
                });
            }
        }

        function printOrder(id) {
            window.open(`/order/${id}/print`, '_blank');
        }
    </script>
@endif
