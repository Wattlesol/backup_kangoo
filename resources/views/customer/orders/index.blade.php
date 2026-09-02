<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">{{ __('messages.my_orders') }}</h4>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Order Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h3 class="mb-0">{{ $orderStats['total_orders'] }}</h3>
                                            <p class="mb-0">{{ __('messages.total_orders') }}</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-shopping-bag fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h3 class="mb-0">{{ $orderStats['pending_orders'] }}</h3>
                                            <p class="mb-0">{{ __('messages.pending_orders') }}</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h3 class="mb-0">{{ $orderStats['completed_orders'] }}</h3>
                                            <p class="mb-0">{{ __('messages.delivered_orders') }}</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h3 class="mb-0">{{ getPriceFormat($orderStats['total_spent']) }}</h3>
                                            <p class="mb-0">{{ __('messages.total_revenue') }}</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-dollar-sign fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders List -->
                    @if(count($orders) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.order_number') }}</th>
                                        <th>{{ __('messages.product') }}</th>
                                        <th>{{ __('messages.order_date') }}</th>
                                        <th>{{ __('messages.order_status') }}</th>
                                        <th>{{ __('messages.payment_status') }}</th>
                                        <th>{{ __('messages.total_amount') }}</th>
                                        <th>{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($orders as $order)
                                        <tr>
                                            <td>
                                                <strong>#{{ $order->order_number ?? $order->id }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    @php
                                                        $firstItem = $order->items->first();
                                                        $product = $firstItem ? $firstItem->product : null;
                                                    @endphp
                                                    @if($product && $product->getFirstMediaUrl('product_images'))
                                                        <img src="{{ $product->getFirstMediaUrl('product_images') }}" alt="{{ $firstItem->product_name }}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                    @endif
                                                    <div>
                                                        <strong>{{ $firstItem->product_name ?? 'Product Deleted' }}</strong>
                                                        @if($product && $product->category)
                                                            <br><small class="text-muted">{{ $product->category->name }}</small>
                                                        @endif
                                                        @if($order->items->count() > 1)
                                                            <br><small class="text-muted">+{{ $order->items->count() - 1 }} more items</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                                            <td>
                                                @php
                                                    $statusClass = match($order->status) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'info',
                                                        'processing' => 'primary',
                                                        'shipped' => 'secondary',
                                                        'delivered' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $statusClass }}">
                                                    {{ ucfirst($order->status) }}
                                                </span>
                                            </td>
                                            <td>
                                                @php
                                                    $paymentClass = match($order->payment_status) {
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'failed' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                @endphp
                                                <span class="badge bg-{{ $paymentClass }}">
                                                    {{ ucfirst($order->payment_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <strong>{{ getPriceFormat($order->total_amount) }}</strong>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('customer.order.show', $order->id) }}" class="btn btn-sm btn-outline-primary" title="{{ __('messages.view') }}">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($order->can_be_cancelled)
                                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="cancelOrder({{ $order->id }})" title="{{ __('messages.cancel') }}">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-shopping-bag fa-5x text-muted"></i>
                            </div>
                            <h4 class="text-muted">{{ __('messages.no_orders_found') }}</h4>
                            <p class="text-muted mb-4">{{ __('messages.no_orders_description') }}</p>
                            <a href="{{ route('store.unified') }}" class="btn btn-primary">
                                <i class="fas fa-shopping-cart me-2"></i>
                                {{ __('messages.start_shopping') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    Swal.fire({
        title: '{{ __("messages.are_you_sure") }}',
        text: '{{ __("messages.cancel_order_confirmation") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: '{{ __("messages.yes_cancel") }}',
        cancelButtonText: '{{ __("messages.no_keep") }}'
    }).then((result) => {
        if (result.isConfirmed) {
            // Prompt for cancellation reason
            Swal.fire({
                title: '{{ __("messages.cancellation_reason") }}',
                input: 'textarea',
                inputPlaceholder: '{{ __("messages.enter_cancellation_reason") }}',
                inputAttributes: {
                    'aria-label': '{{ __("messages.enter_cancellation_reason") }}'
                },
                showCancelButton: true,
                confirmButtonText: '{{ __("messages.cancel_order") }}',
                cancelButtonText: '{{ __("messages.back") }}',
                inputValidator: (value) => {
                    if (!value) {
                        return '{{ __("messages.reason_required") }}'
                    }
                }
            }).then((reasonResult) => {
                if (reasonResult.isConfirmed) {
                    // Add AJAX call to cancel order
                    fetch(`/my-order/${orderId}/cancel`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            reason: reasonResult.value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('{{ __("messages.cancelled") }}', data.message, 'success')
                                .then(() => location.reload());
                        } else {
                            Swal.fire('{{ __("messages.error") }}', data.message, 'error');
                        }
                    })
                    .catch(error => {
                        Swal.fire('{{ __("messages.error") }}', '{{ __("messages.something_wrong") }}', 'error');
                    });
                }
            });
        }
    });
}
</script>
</x-master-layout>
