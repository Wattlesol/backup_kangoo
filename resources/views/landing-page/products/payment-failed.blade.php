@extends('landing-page.layouts.app')

@section('title', 'Payment Failed')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Failure Header -->
            <div class="text-center mb-5">
                <div class="failure-icon mb-4">
                    <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                </div>
                <h1 class="h2 text-danger mb-3">Payment Failed</h1>
                <p class="lead text-muted">
                    We're sorry, but your payment could not be processed. Please try again or use a different payment method.
                </p>
            </div>

            <!-- Error Details -->
            @if(session('error'))
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
            </div>
            @endif

            <!-- Order Information (if available) -->
            @if(isset($order))
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Order {{ $order->formatted_order_number }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-row mb-2">
                                <span class="fw-bold">Order Date:</span>
                                <span>{{ $order->created_at->format('M d, Y H:i') }}</span>
                            </div>
                            <div class="info-row mb-2">
                                <span class="fw-bold">Status:</span>
                                <span class="badge bg-warning">{{ ucfirst($order->status) }}</span>
                            </div>
                            <div class="info-row mb-2">
                                <span class="fw-bold">Payment Status:</span>
                                <span class="badge bg-danger">{{ ucfirst($order->payment_status) }}</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-row mb-2">
                                <span class="fw-bold">Total Amount:</span>
                                <span class="fw-bold text-primary">${{ number_format($order->total_amount, 2) }}</span>
                            </div>
                            <div class="info-row mb-2">
                                <span class="fw-bold">Payment Method:</span>
                                <span>{{ ucfirst($order->payment_method) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="text-center">
                <div class="row justify-content-center">
                    <div class="col-auto">
                        @if(isset($order))
                        <a href="{{ route('products.checkout') }}?retry_order={{ $order->id }}" class="btn btn-primary btn-lg me-3">
                            <i class="fas fa-redo me-2"></i>
                            Try Payment Again
                        </a>
                        @endif
                        
                        <a href="{{ route('products.cart') }}" class="btn btn-outline-secondary btn-lg me-3">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Back to Cart
                        </a>
                        
                        <a href="{{ route('frontend.index') }}" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-home me-2"></i>
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Section -->
            <div class="card mt-5">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Need Help?
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Common Issues:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>Check your card details</li>
                                <li><i class="fas fa-check text-success me-2"></i>Ensure sufficient funds</li>
                                <li><i class="fas fa-check text-success me-2"></i>Verify billing address</li>
                                <li><i class="fas fa-check text-success me-2"></i>Try a different payment method</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Alternative Payment Methods:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-money-bill-wave text-primary me-2"></i>Cash on Delivery</li>
                                <li><i class="fas fa-wallet text-primary me-2"></i>Wallet Payment</li>
                                <li><i class="fas fa-credit-card text-primary me-2"></i>Different Card</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <p class="text-muted mb-2">Still having trouble?</p>
                        <a href="#" class="btn btn-outline-info">
                            <i class="fas fa-headset me-2"></i>
                            Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.failure-icon {
    animation: shake 0.5s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.card {
    border: none;
    border-radius: 12px;
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
}

.btn-lg {
    padding: 12px 30px;
    border-radius: 8px;
}
</style>
@endsection
