<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.view_form_title',['form' => trans('messages.order')]) }}</h5>
                            @if($auth_user->can('order list'))
                                <a href="{{ route('order.index') }}" class="float-right btn btn-sm btn-primary">
                                    <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.order_details') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ __('messages.order_number') }}:</strong></td>
                                    <td>{{ $order->formatted_order_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.order_date') }}:</strong></td>
                                    <td>{{ $order->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.customer') }}:</strong></td>
                                    <td>{{ $order->customer ? $order->customer->display_name : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.store') }}:</strong></td>
                                    <td>
                                        @if($order->is_admin_order)
                                            <span class="badge badge-primary">{{ __('messages.admin') }}</span>
                                        @else
                                            {{ $order->store ? $order->store->name : '-' }}
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>{{ __('messages.order_status') }}:</strong></td>
                                    <td>
                                        <span class="badge badge-{{ $order->status_color }}">
                                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.payment_status') }}:</strong></td>
                                    <td>
                                        @php
                                            $colors = ['pending' => 'warning', 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'info'];
                                            $color = $colors[$order->payment_status] ?? 'secondary';
                                        @endphp
                                        <span class="badge badge-{{ $color }}">{{ ucfirst($order->payment_status) }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.payment_method') }}:</strong></td>
                                    <td>{{ ucfirst($order->payment_method) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>{{ __('messages.total_amount') }}:</strong></td>
                                    <td><strong>{{ getPriceFormat($order->total_amount) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.order_items') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ __('messages.product') }}</th>
                                    <th>{{ __('messages.variant') }}</th>
                                    <th>{{ __('messages.quantity') }}</th>
                                    <th>{{ __('messages.unit_price') }}</th>
                                    <th>{{ __('messages.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($item->product && getMediaFileExit($item->product, 'product_image'))
                                                    <img src="{{ getSingleMedia($item->product, 'product_image') }}" 
                                                         alt="product" class="img-fluid rounded me-3" style="width: 50px; height: 50px;">
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $item->product ? $item->product->name : $item->product_name }}</h6>
                                                    <small class="text-muted">{{ $item->product ? $item->product->sku : $item->product_sku }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $item->productVariant ? $item->productVariant->name : '-' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ getPriceFormat($item->unit_price) }}</td>
                                        <td>{{ getPriceFormat($item->total_price) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">{{ __('messages.subtotal') }}:</th>
                                    <th>{{ getPriceFormat($order->subtotal) }}</th>
                                </tr>
                                @if($order->tax_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-right">{{ __('messages.tax') }}:</th>
                                        <th>{{ getPriceFormat($order->tax_amount) }}</th>
                                    </tr>
                                @endif
                                @if($order->delivery_fee > 0)
                                    <tr>
                                        <th colspan="4" class="text-right">{{ __('messages.delivery_fee') }}:</th>
                                        <th>{{ getPriceFormat($order->delivery_fee) }}</th>
                                    </tr>
                                @endif
                                @if($order->discount_amount > 0)
                                    <tr>
                                        <th colspan="4" class="text-right">{{ __('messages.discount') }}:</th>
                                        <th>-{{ getPriceFormat($order->discount_amount) }}</th>
                                    </tr>
                                @endif
                                <tr class="table-active">
                                    <th colspan="4" class="text-right">{{ __('messages.total') }}:</th>
                                    <th>{{ getPriceFormat($order->total_amount) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Delivery Information -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.delivery_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>{{ __('messages.delivery_address') }}</h6>
                            @if($order->delivery_address)
                                <address>
                                    {{ $order->delivery_address['street'] ?? '' }}<br>
                                    {{ $order->delivery_address['city'] ?? '' }}, {{ $order->delivery_address['state'] ?? '' }}<br>
                                    {{ $order->delivery_address['country'] ?? '' }} {{ $order->delivery_address['postal_code'] ?? '' }}
                                </address>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('messages.delivery_contact') }}</h6>
                            <p>{{ $order->delivery_phone }}</p>
                            @if($order->delivery_notes)
                                <h6>{{ __('messages.delivery_notes') }}</h6>
                                <p>{{ $order->delivery_notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Actions & Status History -->
        <div class="col-lg-4">
            <!-- Order Actions -->
            @if($auth_user->can('order edit'))
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">{{ __('messages.order_actions') }}</h5>
                    </div>
                    <div class="card-body">
                        <!-- Status Update -->
                        <form id="status-update-form" class="mb-3">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <div class="form-group">
                                <label for="status">{{ __('messages.update_status') }}</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                                    <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>{{ __('messages.confirmed') }}</option>
                                    <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>{{ __('messages.processing') }}</option>
                                    <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>{{ __('messages.shipped') }}</option>
                                    <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>{{ __('messages.delivered') }}</option>
                                    <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="notes">{{ __('messages.notes') }}</label>
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="{{ __('messages.status_update_notes') }}"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">{{ __('messages.update_status') }}</button>
                        </form>

                        <!-- Payment Status Update -->
                        <form id="payment-status-form" class="mb-3">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <div class="form-group">
                                <label for="payment_status">{{ __('messages.payment_status') }}</label>
                                <select name="payment_status" id="payment_status" class="form-control">
                                    <option value="pending" {{ $order->payment_status == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                                    <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>{{ __('messages.paid') }}</option>
                                    <option value="failed" {{ $order->payment_status == 'failed' ? 'selected' : '' }}>{{ __('messages.failed') }}</option>
                                    <option value="refunded" {{ $order->payment_status == 'refunded' ? 'selected' : '' }}>{{ __('messages.refunded') }}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="payment_transaction_id">{{ __('messages.transaction_id') }}</label>
                                <input type="text" name="payment_transaction_id" id="payment_transaction_id" class="form-control" value="{{ $order->payment_transaction_id }}" placeholder="{{ __('messages.transaction_id') }}">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">{{ __('messages.update_payment_status') }}</button>
                        </form>

                        @if($order->can_be_cancelled)
                            <!-- Cancel Order -->
                            <form id="cancel-order-form">
                                @csrf
                                <input type="hidden" name="order_id" value="{{ $order->id }}">
                                <div class="form-group">
                                    <label for="reason">{{ __('messages.cancellation_reason') }}</label>
                                    <textarea name="reason" id="reason" class="form-control" rows="3" placeholder="{{ __('messages.cancellation_reason') }}" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-danger btn-block">{{ __('messages.cancel_order') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Status History -->
            @if($order->statusHistories->count() > 0)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title">{{ __('messages.status_history') }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($order->statusHistories as $history)
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-{{ $history->status_color }}"></div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</h6>
                                        <p class="timeline-text">{{ $history->notes }}</p>
                                        <small class="text-muted">
                                            {{ $history->created_at->format('Y-m-d H:i:s') }}
                                            @if($history->changedBy)
                                                by {{ $history->changedBy->display_name }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @section('bottom_script')
    <script>
        $(document).ready(function() {
            // Status Update Form
            $('#status-update-form').on('submit', function(e) {
                e.preventDefault();
                
                $.post('{{ route("order.update-status") }}', $(this).serialize())
                    .done(function(response) {
                        if (response.status) {
                            showAlert('success', response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('error', response.message);
                        }
                    })
                    .fail(function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    });
            });

            // Payment Status Form
            $('#payment-status-form').on('submit', function(e) {
                e.preventDefault();
                
                $.post('{{ route("order.update-payment-status") }}', $(this).serialize())
                    .done(function(response) {
                        if (response.status) {
                            showAlert('success', response.message);
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showAlert('error', response.message);
                        }
                    })
                    .fail(function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    });
            });

            // Cancel Order Form
            $('#cancel-order-form').on('submit', function(e) {
                e.preventDefault();
                
                if (confirm('{{ __("messages.confirm_cancel_order") }}')) {
                    $.post('{{ route("order.cancel") }}', $(this).serialize())
                        .done(function(response) {
                            if (response.status) {
                                showAlert('success', response.message);
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showAlert('error', response.message);
                            }
                        })
                        .fail(function() {
                            showAlert('error', '{{ __("messages.something_went_wrong") }}');
                        });
                }
            });
        });

        function showAlert(type, message) {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            $('body').prepend(alertHtml);
            
            setTimeout(function() {
                $('.alert').fadeOut();
            }, 5000);
        }
    </script>
    @endsection
</x-master-layout>
