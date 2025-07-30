<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.order_details') }}</h5>
                            <a href="{{ route('provider.order.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-arrow-left"></i> {{ __('messages.back_to_orders') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.order_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.order_number') }}:</strong> {{ $order->formatted_order_number }}</p>
                            <p><strong>{{ __('messages.customer') }}:</strong> {{ $order->customer->display_name ?? 'N/A' }}</p>
                            <p><strong>{{ __('messages.order_date') }}:</strong> {{ $order->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __('messages.status') }}:</strong> 
                                <span class="badge badge-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                            </p>
                            <p><strong>{{ __('messages.payment_status') }}:</strong> 
                                <span class="badge badge-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">{{ ucfirst($order->payment_status) }}</span>
                            </p>
                            <p><strong>{{ __('messages.total_amount') }}:</strong> {{ getPriceFormat($order->total_amount) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Your Products in this Order -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.your_products_in_order') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.product') }}</th>
                                    <th>{{ __('messages.sku') }}</th>
                                    <th>{{ __('messages.quantity') }}</th>
                                    <th>{{ __('messages.unit_price') }}</th>
                                    <th>{{ __('messages.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $providerTotal = 0; @endphp
                                @foreach($providerItems as $item)
                                    @php $providerTotal += $item->total_price; @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->product && $item->product->main_image)
                                                    <img src="{{ $item->product->main_image }}" alt="{{ $item->product_name }}" class="img-thumbnail me-2" style="width: 50px; height: 50px;">
                                                @endif
                                                <div>
                                                    <strong>{{ $item->product_name }}</strong>
                                                    @if($item->variant_details)
                                                        <br><small class="text-muted">{{ $item->variant_details['name'] ?? '' }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->product_sku }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ getPriceFormat($item->unit_price) }}</td>
                                        <td>{{ getPriceFormat($item->total_price) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('messages.your_total') }}:</th>
                                    <th>{{ getPriceFormat($providerTotal) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Actions & Status History -->
        <div class="col-lg-4">
            <!-- Order Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.order_actions') }}</h5>
                </div>
                <div class="card-body">
                    @if($order->status == 'pending' || $order->status == 'confirmed')
                        <button type="button" class="btn btn-success btn-block mb-2" onclick="updateOrderStatus('processing')">
                            <i class="fas fa-cog"></i> {{ __('messages.mark_processing') }}
                        </button>
                    @endif
                    
                    @if($order->status == 'processing')
                        <button type="button" class="btn btn-info btn-block mb-2" onclick="updateOrderStatus('shipped')">
                            <i class="fas fa-shipping-fast"></i> {{ __('messages.mark_shipped') }}
                        </button>
                    @endif
                    
                    @if($order->status == 'shipped')
                        <button type="button" class="btn btn-success btn-block mb-2" onclick="updateOrderStatus('delivered')">
                            <i class="fas fa-check-circle"></i> {{ __('messages.mark_delivered') }}
                        </button>
                    @endif
                    
                    @if($order->can_be_cancelled)
                        <button type="button" class="btn btn-danger btn-block" onclick="cancelOrder()">
                            <i class="fas fa-times"></i> {{ __('messages.cancel_order') }}
                        </button>
                    @endif
                </div>
            </div>

            <!-- Customer Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.customer_information') }}</h5>
                </div>
                <div class="card-body">
                    @if($order->customer)
                        <p><strong>{{ __('messages.name') }}:</strong> {{ $order->customer->display_name }}</p>
                        <p><strong>{{ __('messages.email') }}:</strong> {{ $order->customer->email }}</p>
                        @if($order->customer->contact_number)
                            <p><strong>{{ __('messages.phone') }}:</strong> {{ $order->customer->contact_number }}</p>
                        @endif
                    @endif
                    
                    @if($order->delivery_address)
                        <hr>
                        <h6>{{ __('messages.delivery_address') }}</h6>
                        <p>{{ $order->delivery_address['address'] ?? '' }}</p>
                        @if(isset($order->delivery_address['city']))
                            <p>{{ $order->delivery_address['city'] }}, {{ $order->delivery_address['state'] ?? '' }} {{ $order->delivery_address['postal_code'] ?? '' }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

@section('bottom_script')
<script>
function updateOrderStatus(status) {
    if (confirm('{{ __("messages.are_you_sure") }}')) {
        $.ajax({
            url: "{{ route('provider.order.update-status') }}",
            type: 'POST',
            data: {
                order_id: {{ $order->id }},
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status) {
                    location.reload();
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

function cancelOrder() {
    const reason = prompt('{{ __("messages.cancellation_reason") }}:');
    if (reason) {
        $.ajax({
            url: "{{ route('provider.order.update-status') }}",
            type: 'POST',
            data: {
                order_id: {{ $order->id }},
                status: 'cancelled',
                notes: reason,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.status) {
                    location.reload();
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
@endsection
</x-master-layout>
