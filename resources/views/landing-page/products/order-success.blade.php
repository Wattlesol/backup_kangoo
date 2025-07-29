@extends('landing-page.layouts.app')

@section('title', $pageTitle)

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Header -->
            <div class="text-center mb-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                </div>
                <h1 class="h2 text-success mb-3">Order Placed Successfully!</h1>
                <p class="lead text-muted">
                    Thank you for your order. We've received your order and will process it shortly.
                </p>
            </div>

            <!-- Order Details -->
            <div class="row">
                @foreach($orders as $order)
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Order {{ $order->formatted_order_number }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="order-info">
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Order Date:</span>
                                    <span>{{ $order->created_at->format('M d, Y H:i') }}</span>
                                </div>
                                
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Status:</span>
                                    <span class="badge bg-warning">{{ ucfirst($order->status) }}</span>
                                </div>
                                
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Payment:</span>
                                    <span class="badge bg-info">{{ ucfirst($order->payment_method) }}</span>
                                </div>
                                
                                @if($order->store)
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Store:</span>
                                    <span>{{ $order->store->name }}</span>
                                </div>
                                @else
                                <div class="info-row mb-2">
                                    <span class="fw-bold">Store:</span>
                                    <span>Admin Store</span>
                                </div>
                                @endif
                                
                                <div class="info-row mb-3">
                                    <span class="fw-bold">Total:</span>
                                    <span class="text-primary fs-5">{{ getPriceFormat($order->total_amount) }}</span>
                                </div>
                            </div>

                            <!-- Order Items -->
                            <div class="order-items">
                                <h6 class="mb-3">Items ({{ $order->items->count() }}):</h6>
                                @foreach($order->items as $item)
                                <div class="item d-flex align-items-center mb-2">
                                    <div class="item-info flex-grow-1">
                                        <div class="item-name">{{ $item->product_name }}</div>
                                        @if($item->product_variant_name)
                                            <small class="text-muted">{{ $item->product_variant_name }}</small>
                                        @endif
                                    </div>
                                    <div class="item-quantity text-muted me-2">
                                        × {{ $item->quantity }}
                                    </div>
                                    <div class="item-price fw-bold">
                                        {{ getPriceFormat($item->total_price) }}
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <!-- Actions -->
                            <div class="order-actions mt-3">
                                <a href="{{ route('order.print', $order->id) }}" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-sm me-2">
                                    <i class="fas fa-print me-1"></i>
                                    Print Receipt
                                </a>
                                <a href="{{ route('orders.track', $order->id) }}" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-truck me-1"></i>
                                    Track Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Delivery Information -->
            @if($orders->first()->delivery_address)
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-truck me-2"></i>
                        Delivery Information
                    </h5>
                </div>
                <div class="card-body">
                    @php
                        $address = $orders->first()->delivery_address;
                        if (is_string($address)) {
                            $decoded = json_decode($address, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $address = $decoded;
                            }
                        }
                    @endphp
                    
                    <div class="delivery-address">
                        @if(is_array($address))
                            @if(!empty($address['name']))
                                <div class="fw-bold">{{ $address['name'] }}</div>
                            @endif
                            @if(!empty($address['address']))
                                <div>{{ $address['address'] }}</div>
                            @endif
                            <div>
                                @if(!empty($address['city'])){{ $address['city'] }}@endif
                                @if(!empty($address['city']) && !empty($address['state'])), @endif
                                @if(!empty($address['state'])){{ $address['state'] }}@endif
                                @if(!empty($address['zip'])) {{ $address['zip'] }}@endif
                            </div>
                            @if(!empty($address['country']))
                                <div>{{ $address['country'] }}</div>
                            @endif
                        @else
                            <div>{{ $orders->first()->delivery_address }}</div>
                        @endif
                        
                        @if($orders->first()->delivery_phone)
                            <div class="mt-2">
                                <strong>Phone:</strong> {{ $orders->first()->delivery_phone }}
                            </div>
                        @endif
                        
                        @if($orders->first()->delivery_notes)
                            <div class="mt-2">
                                <strong>Notes:</strong> {{ $orders->first()->delivery_notes }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Next Steps -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5 class="card-title">What's Next?</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-clock text-primary fa-2x mb-2"></i>
                            <h6>Order Processing</h6>
                            <small class="text-muted">We'll prepare your order for delivery</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-truck text-primary fa-2x mb-2"></i>
                            <h6>Delivery</h6>
                            <small class="text-muted">Your order will be delivered to your address</small>
                        </div>
                        <div class="col-md-4 mb-3">
                            <i class="fas fa-star text-primary fa-2x mb-2"></i>
                            <h6>Feedback</h6>
                            <small class="text-muted">Rate your experience after delivery</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="text-center mt-5">
                <a href="{{ route('store.unified') }}" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-shopping-bag me-2"></i>
                    Continue Shopping
                </a>
                <a href="{{ route('frontend.index') }}" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-home me-2"></i>
                    Back to Home
                </a>
            </div>

            <!-- Order Confirmation Email Notice -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-envelope me-2"></i>
                <strong>Order Confirmation:</strong> 
                We've sent order confirmation details to your email address. 
                Please check your inbox and spam folder.
            </div>
        </div>
    </div>
</div>
@endsection

@section('bottom_script')
<style>
.success-icon {
    animation: successPulse 1s ease-in-out;
}

@keyframes successPulse {
    0% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.8;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-items .item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.order-items .item:last-child {
    border-bottom: none;
}

.item-name {
    font-weight: 500;
    color: #495057;
}

.card {
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.delivery-address {
    line-height: 1.6;
}

.alert {
    border-left: 4px solid #17a2b8;
}
</style>
@endsection
