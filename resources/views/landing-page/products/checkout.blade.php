@extends('landing-page.layouts.app')

@section('title', $pageTitle)

@section('content')
<div class="container my-5">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('frontend.index') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('store.unified') }}">Store</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('products.cart') }}">Cart</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="page-header mb-4">
                <h1 class="h2 mb-0">
                    <i class="fas fa-credit-card me-2 text-primary"></i>
                    Checkout
                </h1>
                <p class="text-muted mb-0">Complete your order</p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Checkout Form -->
        <div class="col-lg-8">
            <div id="checkout-app">
                <checkout-process 
                    :cart-summary="{{ json_encode($cartSummary) }}"
                    :user="{{ json_encode(auth()->user()) }}"
                ></checkout-process>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Cart Items -->
                    <div class="order-items">
                        @foreach($cartSummary['items'] as $item)
                        <div class="order-item d-flex align-items-center mb-3">
                            <div class="item-image me-3">
                                @if($item->product && $item->product->getFirstMediaUrl('product_images'))
                                    <img src="{{ $item->product->getFirstMediaUrl('product_images') }}" 
                                         alt="{{ $item->product_name }}" 
                                         class="img-fluid rounded"
                                         style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                         style="width: 50px; height: 50px;">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="item-details flex-grow-1">
                                <h6 class="mb-1">{{ $item->product_name }}</h6>
                                @if($item->productVariant)
                                    <small class="text-muted">{{ $item->productVariant->attribute_display }}</small>
                                @endif
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Qty: {{ $item->quantity }}</span>
                                    <span class="fw-bold">{{ getPriceFormat($item->total_price) }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <hr>

                    <!-- Order Totals -->
                    <div class="order-totals">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal ({{ $cartSummary['total_items'] }} items):</span>
                            <span>{{ getPriceFormat($cartSummary['subtotal']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax:</span>
                            <span id="tax-amount">{{ getPriceFormat($cartSummary['subtotal'] * 0.10) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span id="delivery-fee">{{ getPriceFormat(5.00) }}</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span class="text-primary" id="total-amount">
                                {{ getPriceFormat($cartSummary['subtotal'] + ($cartSummary['subtotal'] * 0.10) + 5.00) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="card mt-3">
                <div class="card-body text-center">
                    <i class="fas fa-shield-alt text-success fa-2x mb-2"></i>
                    <h6>Secure Checkout</h6>
                    <small class="text-muted">
                        Your payment information is encrypted and secure.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bottom_script')
<script>
// Initialize checkout app
const { createApp } = Vue;

const CheckoutApp = {
    components: {
        'checkout-process': {
            props: ['cartSummary', 'user'],
            data() {
                return {
                    currentStep: 1,
                    loading: false,
                    error: '',
                    
                    // Form data
                    deliveryAddress: {
                        name: this.user?.display_name || '',
                        address: '',
                        city: '',
                        state: '',
                        zip: '',
                        country: 'United States'
                    },
                    deliveryPhone: this.user?.contact_number || '',
                    deliveryNotes: '',
                    paymentMethod: 'cash',
                    
                    // Payment methods
                    paymentMethods: [
                        {
                            id: 'cash',
                            name: 'Cash on Delivery',
                            description: 'Pay when your order is delivered',
                            icon: 'fas fa-money-bill-wave'
                        },
                        {
                            id: 'card',
                            name: 'Credit/Debit Card',
                            description: 'Pay securely with your card',
                            icon: 'fas fa-credit-card'
                        },
                        {
                            id: 'wallet',
                            name: 'Wallet',
                            description: 'Pay from your wallet balance',
                            icon: 'fas fa-wallet'
                        }
                    ]
                }
            },
            
            computed: {
                canProceedToStep2() {
                    return this.deliveryAddress.name && 
                           this.deliveryAddress.address && 
                           this.deliveryAddress.city && 
                           this.deliveryAddress.state && 
                           this.deliveryAddress.zip && 
                           this.deliveryPhone;
                },
                
                canPlaceOrder() {
                    return this.canProceedToStep2 && this.paymentMethod;
                }
            },
            
            methods: {
                nextStep() {
                    if (this.currentStep === 1 && this.canProceedToStep2) {
                        this.currentStep = 2;
                    } else if (this.currentStep === 2) {
                        this.currentStep = 3;
                    }
                },
                
                prevStep() {
                    if (this.currentStep > 1) {
                        this.currentStep--;
                    }
                },
                
                async placeOrder() {
                    if (!this.canPlaceOrder) return;
                    
                    this.loading = true;
                    this.error = '';
                    
                    try {
                        const response = await axios.post('/api/checkout/process', {
                            delivery_address: this.deliveryAddress,
                            delivery_phone: this.deliveryPhone,
                            delivery_notes: this.deliveryNotes,
                            payment_method: this.paymentMethod
                        });
                        
                        if (response.data.status) {
                            // Redirect to success page or show success message
                            window.location.href = '/order-success?orders=' + response.data.data.orders.map(o => o.id).join(',');
                        } else {
                            this.error = response.data.message || 'Failed to place order';
                        }
                    } catch (err) {
                        this.error = 'Failed to place order. Please try again.';
                        console.error('Checkout error:', err);
                    } finally {
                        this.loading = false;
                    }
                }
            },
            
            template: `
                <div class="checkout-process">
                    <!-- Progress Steps -->
                    <div class="checkout-steps mb-4">
                        <div class="row">
                            <div class="col-4">
                                <div class="step" :class="{ active: currentStep >= 1, completed: currentStep > 1 }">
                                    <div class="step-number">1</div>
                                    <div class="step-title">Delivery Info</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="step" :class="{ active: currentStep >= 2, completed: currentStep > 2 }">
                                    <div class="step-number">2</div>
                                    <div class="step-title">Payment</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="step" :class="{ active: currentStep >= 3 }">
                                    <div class="step-number">3</div>
                                    <div class="step-title">Review</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 1: Delivery Information -->
                    <div v-if="currentStep === 1" class="checkout-step">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-truck me-2"></i>
                                    Delivery Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Full Name *</label>
                                        <input v-model="deliveryAddress.name" type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone Number *</label>
                                        <input v-model="deliveryPhone" type="tel" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Address *</label>
                                    <input v-model="deliveryAddress.address" type="text" class="form-control" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">City *</label>
                                        <input v-model="deliveryAddress.city" type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">State *</label>
                                        <input v-model="deliveryAddress.state" type="text" class="form-control" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">ZIP Code *</label>
                                        <input v-model="deliveryAddress.zip" type="text" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <input v-model="deliveryAddress.country" type="text" class="form-control" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Delivery Notes (Optional)</label>
                                    <textarea v-model="deliveryNotes" class="form-control" rows="3" placeholder="Any special instructions for delivery..."></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="/cart" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                    </a>
                                    <button @click="nextStep" :disabled="!canProceedToStep2" class="btn btn-primary">
                                        Continue to Payment <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Payment Method -->
                    <div v-if="currentStep === 2" class="checkout-step">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Payment Method
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="payment-methods">
                                    <div v-for="method in paymentMethods" :key="method.id" class="payment-method mb-3">
                                        <div class="form-check">
                                            <input v-model="paymentMethod" :value="method.id" :id="method.id" type="radio" class="form-check-input">
                                            <label :for="method.id" class="form-check-label w-100">
                                                <div class="payment-option">
                                                    <div class="payment-icon">
                                                        <i :class="method.icon"></i>
                                                    </div>
                                                    <div class="payment-details">
                                                        <div class="payment-name">{{ method.name }}</div>
                                                        <div class="payment-description">{{ method.description }}</div>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button @click="prevStep" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </button>
                                    <button @click="nextStep" class="btn btn-primary">
                                        Review Order <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3: Review & Place Order -->
                    <div v-if="currentStep === 3" class="checkout-step">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Review Your Order
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Order Review -->
                                <div class="order-review mb-4">
                                    <h6>Delivery Address:</h6>
                                    <address class="mb-3">
                                        <strong>{{ deliveryAddress.name }}</strong><br>
                                        {{ deliveryAddress.address }}<br>
                                        {{ deliveryAddress.city }}, {{ deliveryAddress.state }} {{ deliveryAddress.zip }}<br>
                                        {{ deliveryAddress.country }}<br>
                                        <strong>Phone:</strong> {{ deliveryPhone }}
                                    </address>
                                    
                                    <h6>Payment Method:</h6>
                                    <p>{{ paymentMethods.find(m => m.id === paymentMethod)?.name }}</p>
                                    
                                    <div v-if="deliveryNotes">
                                        <h6>Delivery Notes:</h6>
                                        <p>{{ deliveryNotes }}</p>
                                    </div>
                                </div>
                                
                                <!-- Error Message -->
                                <div v-if="error" class="alert alert-danger">
                                    {{ error }}
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button @click="prevStep" :disabled="loading" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </button>
                                    <button @click="placeOrder" :disabled="!canPlaceOrder || loading" class="btn btn-success btn-lg">
                                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="fas fa-shopping-bag me-2"></i>
                                        {{ loading ? 'Placing Order...' : 'Place Order' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `
        }
    }
};

createApp(CheckoutApp).mount('#checkout-app');
</script>

<style>
.checkout-steps {
    margin-bottom: 2rem;
}

.step {
    text-align: center;
    position: relative;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.step.active .step-number {
    background: #007bff;
    color: white;
}

.step.completed .step-number {
    background: #28a745;
    color: white;
}

.step-title {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}

.step.active .step-title {
    color: #007bff;
    font-weight: 600;
}

.payment-method {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    transition: all 0.3s ease;
    cursor: pointer;
}

.payment-method:hover {
    border-color: #007bff;
}

.payment-method .form-check-input:checked ~ .form-check-label .payment-option {
    border-color: #007bff;
}

.payment-option {
    display: flex;
    align-items: center;
    border: 1px solid transparent;
    border-radius: 6px;
    padding: 0.5rem;
    transition: all 0.3s ease;
}

.payment-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.2rem;
    color: #007bff;
}

.payment-name {
    font-weight: 600;
    color: #495057;
}

.payment-description {
    font-size: 0.875rem;
    color: #6c757d;
}

.order-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.order-item:last-child {
    border-bottom: none;
}

.checkout-step {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
@endsection
