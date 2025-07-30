@extends('landing-page.layouts.default')

@section('content')
<div class="section-padding">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('/store') }}">Store</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order #{{ $order->order_number }}</li>
            </ol>
        </nav>

        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <!-- Order Header -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Order #{{ $order->order_number }}
                            </h4>
                            <span class="badge bg-light text-dark fs-6">
                                {{ ucfirst($order->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>Order Date:</strong><br>
                                    {{ $order->created_at->format('M d, Y \a\t H:i') }}
                                </div>
                                <div class="mb-3">
                                    <strong>Payment Method:</strong><br>
                                    {{ ucfirst($order->payment_method) }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <strong>Payment Status:</strong><br>
                                    <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'warning' }}">
                                        {{ ucfirst($order->payment_status) }}
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <strong>Total Amount:</strong><br>
                                    <span class="h5 text-primary">{{ getPriceFormat($order->total_amount) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-box me-2"></i>
                            Order Items ({{ $order->items->count() }})
                        </h5>
                    </div>
                    <div class="card-body">
                        @foreach($order->items as $item)
                        <div class="d-flex align-items-center p-3 border rounded mb-3 {{ $loop->last ? '' : 'border-bottom' }}">
                            <div class="flex-shrink-0 me-3">
                                @if($item->product && $item->product->getFirstMediaUrl('product_images'))
                                    <img src="{{ $item->product->getFirstMediaUrl('product_images') }}" 
                                         alt="{{ $item->product_name }}" 
                                         class="rounded shadow-sm"
                                         style="width: 80px; height: 80px; object-fit: cover;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center shadow-sm"
                                         style="width: 80px; height: 80px;">
                                        <i class="fas fa-image text-muted fa-2x"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 fw-bold">{{ $item->product_name }}</h6>
                                @if($item->product && $item->product->category)
                                    <p class="text-muted mb-1 small">{{ $item->product->category->name }}</p>
                                @endif
                                @if($item->product_sku)
                                    <p class="text-muted mb-1 small">SKU: {{ $item->product_sku }}</p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="text-muted">Qty: {{ $item->quantity }}</span>
                                        <span class="text-muted mx-2">×</span>
                                        <span class="text-muted">{{ getPriceFormat($item->unit_price) }}</span>
                                    </div>
                                    <span class="fw-bold text-primary h6 mb-0">{{ getPriceFormat($item->total_price) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Delivery Information -->
                @if($order->delivery_address)
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Delivery Information
                        </h5>
                    </div>
                    <div class="card-body">
                        @php
                            $address = is_array($order->delivery_address) ? $order->delivery_address : json_decode($order->delivery_address, true);
                        @endphp
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Delivery Address:</strong><br>
                                {{ $address['name'] ?? 'N/A' }}<br>
                                {{ $address['address'] ?? 'N/A' }}<br>
                                {{ $address['city'] ?? 'N/A' }}, {{ $address['state'] ?? 'N/A' }} {{ $address['zip'] ?? 'N/A' }}
                            </div>
                            <div class="col-md-6">
                                <strong>Contact Information:</strong><br>
                                Email: {{ $address['email'] ?? 'N/A' }}<br>
                                Phone: {{ $address['phone'] ?? $order->delivery_phone ?? 'N/A' }}
                            </div>
                        </div>
                        @if($order->delivery_notes)
                        <div class="mt-3">
                            <strong>Delivery Notes:</strong><br>
                            <p class="text-muted mb-0">{{ $order->delivery_notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Order Summary Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-calculator me-2"></i>
                            Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Subtotal:</span>
                            <span class="fw-semibold">{{ getPriceFormat($order->subtotal) }}</span>
                        </div>
                        @if($order->tax_amount > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Tax:</span>
                            <span class="fw-semibold">{{ getPriceFormat($order->tax_amount) }}</span>
                        </div>
                        @endif
                        @if($order->delivery_fee > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Delivery Fee:</span>
                            <span class="fw-semibold">{{ getPriceFormat($order->delivery_fee) }}</span>
                        </div>
                        @endif
                        @if($order->discount_amount > 0)
                        <div class="d-flex justify-content-between py-2 border-bottom text-success">
                            <span>Discount:</span>
                            <span class="fw-semibold">-{{ getPriceFormat($order->discount_amount) }}</span>
                        </div>
                        @endif
                        <div class="d-flex justify-content-between py-3 bg-light rounded mt-3 px-3">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-5 text-primary">{{ getPriceFormat($order->total_amount) }}</span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4">
                            <a href="{{ route('order.print', $order->id) }}" 
                               class="btn btn-outline-primary w-100 mb-2" target="_blank">
                                <i class="fas fa-print me-2"></i>
                                Print Receipt
                            </a>
                            @if($order->can_be_cancelled)
                            <button type="button" class="btn btn-outline-danger w-100 mb-2" 
                                    onclick="cancelOrder({{ $order->id }})">
                                <i class="fas fa-times me-2"></i>
                                Cancel Order
                            </button>
                            @endif
                            <a href="{{ url('/store') }}" class="btn btn-primary w-100">
                                <i class="fas fa-shopping-bag me-2"></i>
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Order Status Timeline -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-clock me-2"></i>
                            Order Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item {{ $order->status === 'pending' ? 'active' : 'completed' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Order Placed</h6>
                                    <small class="text-muted">{{ $order->created_at->format('M d, Y H:i') }}</small>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Order Confirmed</h6>
                                    <small class="text-muted">Pending confirmation</small>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['processing', 'shipped', 'delivered']) ? 'active' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Processing</h6>
                                    <small class="text-muted">Preparing your order</small>
                                </div>
                            </div>
                            <div class="timeline-item {{ in_array($order->status, ['shipped', 'delivered']) ? 'active' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Shipped</h6>
                                    <small class="text-muted">On the way</small>
                                </div>
                            </div>
                            <div class="timeline-item {{ $order->status === 'delivered' ? 'active' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Delivered</h6>
                                    <small class="text-muted">Order completed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -23px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #e9ecef;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e9ecef;
}

.timeline-item.active .timeline-marker {
    background: #007bff;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-size: 14px;
}

.timeline-item.active .timeline-content h6 {
    color: #007bff;
    font-weight: bold;
}

.timeline-item.completed .timeline-content h6 {
    color: #28a745;
    font-weight: bold;
}
</style>

<script>
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        // Add cancel order functionality here
        alert('Order cancellation feature will be implemented');
    }
}
</script>
@endsection
