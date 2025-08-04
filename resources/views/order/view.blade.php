<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">Order Details - {{ $order->formatted_order_number }}</h5>
                            <div class="d-flex gap-1">
                                <a href="{{ route('order.index') }}" class="btn btn-xs btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back
                                </a>
                                <button type="button" class="btn btn-xs btn-info" onclick="printOrder()">
                                    <i class="fa fa-print"></i> Print
                                </button>
                                @if($order->can_be_cancelled)
                                    <button type="button" class="btn btn-xs btn-danger" onclick="cancelOrder({{ $order->id }})">
                                        <i class="fa fa-times"></i> Cancel
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-8">
            <!-- Order Summary -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="order-info-item">
                                <label>Order Number</label>
                                <p class="font-weight-bold">{{ $order->formatted_order_number }}</p>
                            </div>
                            <div class="order-info-item">
                                <label>Order Date</label>
                                <p>{{ $order->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="order-info-item">
                                <label>Customer</label>
                                <p>{{ $order->customer ? $order->customer->display_name : 'Guest' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="order-info-item">
                                <label>Order Status</label>
                                <p>
                                    <span class="badge bg-soft-{{ $order->status_color }} text-{{ $order->status_color }} px-3 py-2">
                                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                    </span>
                                </p>
                            </div>
                            <div class="order-info-item">
                                <label>Payment Status</label>
                                <p>
                                    @php
                                        $colors = ['pending' => 'warning', 'paid' => 'success', 'failed' => 'danger', 'refunded' => 'info'];
                                        $color = $colors[$order->payment_status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-soft-{{ $color }} text-{{ $color }} px-3 py-2">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </p>
                            </div>
                            <div class="order-info-item">
                                <label>Total Amount</label>
                                <p class="font-weight-bold text-primary h5">{{ getPriceFormat($order->total_amount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($item->product && $item->product->featured_image)
                                                <img src="{{ $item->product->featured_image }}" alt="{{ $item->product_name }}"
                                                     class="avatar-40 rounded me-3" style="object-fit: cover;">
                                            @else
                                                <div class="avatar-40 bg-soft-primary rounded me-3 d-flex align-items-center justify-content-center">
                                                    <i class="fa fa-box text-primary"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <h6 class="mb-0">{{ $item->product_name }}</h6>
                                                @if($item->product_variant_name)
                                                    <small class="text-muted">{{ $item->product_variant_name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $item->product_sku ?? 'N/A' }}</td>
                                    <td>{{ getPriceFormat($item->unit_price) }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td class="font-weight-bold">{{ getPriceFormat($item->unit_price * $item->quantity) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Order Timeline -->
            @if($order->statusHistories->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @foreach($order->statusHistories->sortByDesc('created_at') as $history)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-{{ $history->status == 'delivered' ? 'success' : ($history->status == 'cancelled' ? 'danger' : 'primary') }}"></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">{{ ucfirst(str_replace('_', ' ', $history->status)) }}</h6>
                                <p class="text-muted mb-1">{{ $history->created_at->format('M d, Y H:i') }}</p>
                                @if($history->notes)
                                    <p class="mb-0">{{ $history->notes }}</p>
                                @endif
                                @if($history->changedBy)
                                    <small class="text-muted">by {{ $history->changedBy->display_name }}</small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Order Actions & Info -->
        <div class="col-lg-4">
            <!-- Status Management -->
            @can('order status update')
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Status Management</h5>
                </div>
                <div class="card-body">
                    <form id="status-update-form">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <div class="form-group">
                            <label class="form-label">Update Status</label>
                            <select name="status" class="form-control" id="order-status">
                                <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                                <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>{{ __('messages.confirmed') }}</option>
                                <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>{{ __('messages.processing') }}</option>
                                <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>{{ __('messages.shipped') }}</option>
                                <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>{{ __('messages.delivered') }}</option>
                                <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('messages.status_update_notes') }} ({{ __('messages.optional') }})</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="{{ __('messages.add_notes_about_this_status_change') }}..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-xs btn-primary">
                            <i class="fa fa-save"></i> {{ __('messages.update_status') }}
                        </button>
                    </form>
                </div>
            </div>
            @else
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label class="form-label">Current Status</label>
                        <div class="status-display">
                            <span class="badge badge-{{ $order->status_color }}">{{ ucfirst($order->status) }}</span>
                        </div>
                    </div>
                    <small class="text-muted">You don't have permission to update order status.</small>
                </div>
            </div>
            @endcan

            <!-- Order Totals -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Order Totals</h5>
                </div>
                <div class="card-body">
                    <div class="order-total-item">
                        <span>Subtotal</span>
                        <span>{{ getPriceFormat($order->subtotal) }}</span>
                    </div>
                    @if($order->tax_amount > 0)
                    <div class="order-total-item">
                        <span>Tax</span>
                        <span>{{ getPriceFormat($order->tax_amount) }}</span>
                    </div>
                    @endif
                    @if($order->delivery_fee > 0)
                    <div class="order-total-item">
                        <span>Delivery Fee</span>
                        <span>{{ getPriceFormat($order->delivery_fee) }}</span>
                    </div>
                    @endif
                    @if($order->discount_amount > 0)
                    <div class="order-total-item text-success">
                        <span>Discount</span>
                        <span>-{{ getPriceFormat($order->discount_amount) }}</span>
                    </div>
                    @endif
                    <hr>
                    <div class="order-total-item font-weight-bold h5">
                        <span>Total</span>
                        <span class="text-primary">{{ getPriceFormat($order->total_amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            @if($order->customer)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <div class="customer-info">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-50 bg-soft-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                <i class="fa fa-user text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $order->customer->display_name }}</h6>
                                <small class="text-muted">{{ $order->customer->email }}</small>
                            </div>
                        </div>
                        @if($order->customer->contact_number)
                        <p class="mb-2">
                            <i class="fa fa-phone text-muted me-2"></i>
                            {{ $order->customer->contact_number }}
                        </p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Delivery Information -->
            @if($order->delivery_address)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Delivery Information</h5>
                </div>
                <div class="card-body">
                    @php
                        $address = $order->delivery_address;

                        // Handle different address formats
                        if (is_string($address)) {
                            // Try to decode JSON string
                            $decoded = json_decode($address, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $address = $decoded;
                            }
                        }
                    @endphp

                    @if(is_array($address) && !empty($address))
                    <!-- Simple Address Display -->
                    @if(!empty($address['name']))
                    <div class="delivery-info-item">
                        <label>Recipient Name</label>
                        <p>{{ $address['name'] }}</p>
                    </div>
                    @endif

                    @if(!empty($address['address']))
                    <div class="delivery-info-item">
                        <label>Street Address</label>
                        <p>{{ $address['address'] }}</p>
                    </div>
                    @endif

                    <div class="row mb-3">
                        @if(!empty($address['city']))
                        <div class="col-md-4">
                            <div class="delivery-info-item">
                                <label>City</label>
                                <p>{{ $address['city'] }}</p>
                            </div>
                        </div>
                        @endif
                        @if(!empty($address['state']))
                        <div class="col-md-4">
                            <div class="delivery-info-item">
                                <label>State</label>
                                <p>{{ $address['state'] }}</p>
                            </div>
                        </div>
                        @endif
                        @if(!empty($address['zip']))
                        <div class="col-md-4">
                            <div class="delivery-info-item">
                                <label>ZIP Code</label>
                                <p>{{ $address['zip'] }}</p>
                            </div>
                        </div>
                        @endif
                    </div>

                    @if(!empty($address['country']))
                    <div class="delivery-info-item">
                        <label>Country</label>
                        <p>{{ $address['country'] }}</p>
                    </div>
                    @endif
                    @else
                    <!-- Fallback for non-structured address -->
                    <div class="delivery-info-item">
                        <label>Delivery Address</label>
                        <p>{{ $order->delivery_address }}</p>
                    </div>
                    @endif

                    <!-- Contact Information -->
                    @if($order->delivery_phone)
                    <div class="delivery-info-item">
                        <label>Phone Number</label>
                        <p>
                            <i class="fa fa-phone text-muted me-2"></i>
                            {{ $order->delivery_phone }}
                        </p>
                    </div>
                    @endif

                    @if($order->delivery_notes)
                    <div class="delivery-info-item">
                        <label>Delivery Notes</label>
                        <p>
                            <i class="fa fa-sticky-note text-muted me-2"></i>
                            {{ $order->delivery_notes }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>

@section('bottom_script')
<style>
.avatar-40 {
    width: 40px;
    height: 40px;
    min-width: 40px;
}
.avatar-50 {
    width: 50px;
    height: 50px;
    min-width: 50px;
}
.bg-soft-primary { background-color: rgba(108, 117, 125, 0.1); }
.bg-soft-success { background-color: rgba(40, 167, 69, 0.1); }
.bg-soft-info { background-color: rgba(23, 162, 184, 0.1); }
.bg-soft-warning { background-color: rgba(255, 193, 7, 0.1); }
.bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }

.order-info-item {
    margin-bottom: 1rem;
}
.order-info-item label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: block;
}
.order-info-item p {
    margin-bottom: 0;
    font-size: 0.95rem;
}

.order-total-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}
.timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
}
.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
}
.timeline-content h6 {
    margin-bottom: 0.25rem;
}

/* Simple Delivery Info Styling - Matches Admin Theme */
.delivery-info-item {
    margin-bottom: 1rem;
}

.delivery-info-item:last-child {
    margin-bottom: 0;
}

.delivery-info-item label {
    display: block;
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.delivery-info-item p {
    margin-bottom: 0;
    color: #212529;
    font-size: 0.9rem;
}

/* Extra Small Buttons */
.btn-xs {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}

.btn-xs i {
    font-size: 0.7rem;
}

.status-display {
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
    text-align: center;
}

.status-display .badge {
    font-size: 14px;
    padding: 8px 16px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status update form
    const statusForm = document.getElementById('status-update-form');

    if (!statusForm) {
        console.log('Status update form not found - user may not have permission');
        return;
    }

    const updateButton = statusForm.querySelector('button[type="submit"]');
    const originalButtonText = updateButton.innerHTML;

    console.log('Status update form initialized');

    statusForm.addEventListener('submit', function(e) {
        e.preventDefault();

        console.log('Status update form submitted');

        // Disable button and show loading state
        updateButton.disabled = true;
        updateButton.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';

        const formData = new FormData(this);

        // Log form data for debugging
        for (let [key, value] of formData.entries()) {
            console.log(`${key}: ${value}`);
        }

        fetch('{{ route("order.update-status") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.status) {
                toastr.success(data.message || 'Order status updated successfully');
                setTimeout(() => location.reload(), 1500);
            } else {
                toastr.error(data.message || 'Failed to update order status');
                // Re-enable button
                updateButton.disabled = false;
                updateButton.innerHTML = originalButtonText;
            }
        })
        .catch(error => {
            console.error('Error updating order status:', error);
            toastr.error('An error occurred while updating the order status. Please try again.');
            // Re-enable button
            updateButton.disabled = false;
            updateButton.innerHTML = originalButtonText;
        });
    });
});

function printOrder() {
    window.print();
}

function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        const formData = new FormData();
        formData.append('order_id', orderId);
        formData.append('status', 'cancelled');
        formData.append('notes', 'Order cancelled by admin');

        fetch('{{ route("order.update-status") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status) {
                toastr.success('Order cancelled successfully');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(data.message);
            }
        });
    }
}
</script>
@endsection
</x-master-layout>
