@if($order)
    <div class="d-flex justify-content-end align-items-center gap-1">
        @if(auth()->user()->can('order view'))
            <button type="button" class="btn btn-sm btn-info" onclick="viewOrder({{ $order->id }})" title="{{ __('messages.view') }}">
                <i class="fas fa-eye"></i>
            </button>
        @endif

        @if(auth()->user()->can('order status update'))
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('messages.update_status') }}">
                    <i class="fas fa-edit"></i>
                </button>
                <div class="dropdown-menu">
                    @if($order->status !== 'confirmed')
                        <a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'confirmed'); return false;">
                            <i class="fas fa-check-circle text-success me-2"></i>{{ __('messages.confirmed') }}
                        </a>
                    @endif
                    @if($order->status !== 'processing')
                        <a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'processing'); return false;">
                            <i class="fas fa-cog text-primary me-2"></i>{{ __('messages.processing') }}
                        </a>
                    @endif
                    @if($order->status !== 'shipped')
                        <a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'shipped'); return false;">
                            <i class="fas fa-shipping-fast text-info me-2"></i>{{ __('messages.shipped') }}
                        </a>
                    @endif
                    @if($order->status !== 'delivered')
                        <a class="dropdown-item" href="#" onclick="updateOrderStatus({{ $order->id }}, 'delivered'); return false;">
                            <i class="fas fa-check-double text-success me-2"></i>{{ __('messages.delivered') }}
                        </a>
                    @endif
                    @if($order->status !== 'cancelled' && !in_array($order->status, ['delivered']))
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="#" onclick="cancelOrder({{ $order->id }}); return false;">
                            <i class="fas fa-times text-danger me-2"></i>{{ __('messages.cancel') }}
                        </a>
                    @endif
                </div>
            </div>
        @endif

        @if(auth()->user()->can('order edit'))
            <button type="button" class="btn btn-sm btn-dark" onclick="openReassignPartnerModal({{ $order->id }})" title="Reassign Partner">
                <i class="fas fa-random"></i>
            </button>

            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{ __('messages.payment_status') }}">
                    <i class="fas fa-credit-card"></i>
                </button>
                <div class="dropdown-menu">
                    @if($order->payment_status !== 'paid')
                        <a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'paid'); return false;">
                            <i class="fas fa-check-circle text-success me-2"></i>{{ __('messages.mark_paid') }}
                        </a>
                    @endif
                    @if($order->payment_status !== 'failed')
                        <a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'failed'); return false;">
                            <i class="fas fa-times-circle text-danger me-2"></i>{{ __('messages.mark_failed') }}
                        </a>
                    @endif
                    @if($order->payment_status === 'paid')
                        <a class="dropdown-item" href="#" onclick="updatePaymentStatus({{ $order->id }}, 'refunded'); return false;">
                            <i class="fas fa-undo text-warning me-2"></i>{{ __('messages.mark_refunded') }}
                        </a>
                    @endif
                </div>
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
                            if (typeof window.renderedDataTable !== 'undefined') {
                                window.renderedDataTable.ajax.reload();
                            }
                            if (typeof showAlert === 'function') {
                                showAlert('success', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(response.message);
                            } else {
                                alert(response.message);
                            }
                        } else {
                            if (typeof showAlert === 'function') {
                                showAlert('error', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    },
                    error: function() {
                        const errorMsg = '{{ __("messages.something_went_wrong") }}';
                        if (typeof showAlert === 'function') {
                            showAlert('error', errorMsg);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
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
                            if (typeof window.renderedDataTable !== 'undefined') {
                                window.renderedDataTable.ajax.reload();
                            }
                            if (typeof showAlert === 'function') {
                                showAlert('success', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(response.message);
                            } else {
                                alert(response.message);
                            }
                        } else {
                            if (typeof showAlert === 'function') {
                                showAlert('error', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    },
                    error: function() {
                        const errorMsg = '{{ __("messages.something_went_wrong") }}';
                        if (typeof showAlert === 'function') {
                            showAlert('error', errorMsg);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
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
                            if (typeof window.renderedDataTable !== 'undefined') {
                                window.renderedDataTable.ajax.reload();
                            }
                            if (typeof showAlert === 'function') {
                                showAlert('success', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.success(response.message);
                            } else {
                                alert(response.message);
                            }
                        } else {
                            if (typeof showAlert === 'function') {
                                showAlert('error', response.message);
                            } else if (typeof toastr !== 'undefined') {
                                toastr.error(response.message);
                            } else {
                                alert(response.message);
                            }
                        }
                    },
                    error: function() {
                        const errorMsg = '{{ __("messages.something_went_wrong") }}';
                        if (typeof showAlert === 'function') {
                            showAlert('error', errorMsg);
                        } else if (typeof toastr !== 'undefined') {
                            toastr.error(errorMsg);
                        } else {
                            alert(errorMsg);
                        }
                    }
                });
            }
        }


        function printOrder(id) {
            window.open(`{{ url('/order') }}/${id}/print`, '_blank');
        }
    </script>
@endif
