@extends('landing-page.layouts.default')

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
            <form id="checkout-form" method="POST" action="{{ route('orders.store') }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" value="{{ $quantity }}">

                <!-- Customer Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user me-2"></i>
                            Customer Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="{{ auth()->user()->first_name ?? '' }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="{{ auth()->user()->last_name ?? '' }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="{{ auth()->user()->email ?? '' }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="{{ auth()->user()->contact_number ?? '' }}" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Delivery Address
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Street Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="address" name="address"
                                       placeholder="Enter your full address" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="state" class="form-label">State <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="state" name="state" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="zip" class="form-label">ZIP Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="zip" name="zip" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="delivery_notes" class="form-label">Delivery Notes (Optional)</label>
                                <textarea class="form-control" id="delivery_notes" name="delivery_notes" rows="2"
                                          placeholder="Any special delivery instructions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>
                            Payment Method
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check p-3 border rounded h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash_on_delivery" value="cash" checked>
                                    <label class="form-check-label w-100" for="cash_on_delivery">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave me-3 text-success fa-2x"></i>
                                            <div>
                                                <div class="fw-bold">Cash on Delivery</div>
                                                <div class="text-muted small">Pay when you receive your order</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check p-3 border rounded h-100">
                                    <input class="form-check-input" type="radio" name="payment_method" id="online_payment" value="online">
                                    <label class="form-check-label w-100" for="online_payment">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-credit-card me-3 text-primary fa-2x"></i>
                                            <div>
                                                <div class="fw-bold">Online Payment</div>
                                                <div class="text-muted small">Pay securely with card or digital wallet</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Order Summary
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Product Item -->
                    <div class="order-items">
                        <div class="order-item d-flex align-items-center p-3 bg-light rounded mb-3">
                            <div class="item-image me-3">
                                @if($product->getFirstMediaUrl('product_images'))
                                    <img src="{{ $product->getFirstMediaUrl('product_images') }}"
                                         alt="{{ $product->name }}"
                                         class="img-fluid rounded shadow-sm"
                                         style="width: 70px; height: 70px; object-fit: cover;">
                                @else
                                    <div class="bg-white rounded d-flex align-items-center justify-content-center shadow-sm"
                                         style="width: 70px; height: 70px;">
                                        <i class="fas fa-mobile-alt text-primary fa-2x"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="item-details flex-grow-1">
                                <h6 class="mb-1 fw-bold">{{ $product->name }}</h6>
                                <small class="text-muted">{{ $product->category->name ?? 'Uncategorized' }}</small>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <span class="badge bg-secondary">Qty: {{ $quantity }}</span>
                                    <span class="fw-bold text-primary">{{ getPriceFormat($product->effective_price * $quantity) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Totals -->
                    @php
                        $subtotal = $product->effective_price * $quantity;
                        $tax = $subtotal * 0.10;
                        $deliveryFee = 5.00;
                        $total = $subtotal + $tax + $deliveryFee;
                    @endphp
                    <div class="order-totals">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Subtotal ({{ $quantity }} {{ $quantity == 1 ? 'item' : 'items' }}):</span>
                            <span class="fw-semibold">{{ getPriceFormat($subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Tax (10%):</span>
                            <span class="fw-semibold" id="tax-amount">{{ getPriceFormat($tax) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span>Delivery Fee:</span>
                            <span class="fw-semibold" id="delivery-fee">{{ getPriceFormat($deliveryFee) }}</span>
                        </div>
                        <div class="d-flex justify-content-between py-3 bg-light rounded mt-3 px-3">
                            <span class="fw-bold fs-5">Total:</span>
                            <span class="fw-bold fs-5 text-primary" id="total-amount">
                                {{ getPriceFormat($total) }}
                            </span>
                        </div>
                    </div>

                    <!-- Security Badge -->
                    <div class="text-center mt-4 p-3 bg-light rounded">
                        <i class="fas fa-shield-alt text-success fa-2x mb-2"></i>
                        <h6 class="text-success mb-1">Secure Checkout</h6>
                        <small class="text-muted">SSL encrypted payment</small>
                    </div>

                    <!-- Place Order Button -->
                    <div class="mt-4">
                        <button type="submit"
                                form="checkout-form"
                                class="btn btn-primary btn-lg w-100 py-3"
                                id="place-order-btn">
                            <i class="fas fa-lock me-2"></i>
                            <span>Place Order - {{ getPriceFormat($total) }}</span>
                        </button>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Money-back guarantee • Free returns
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trust Badges -->
            <div class="card mt-3 border-0">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-shipping-fast text-primary fa-2x mb-2"></i>
                                <div class="small fw-bold">Fast Delivery</div>
                                <div class="text-muted" style="font-size: 0.75rem;">2-3 days</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-undo text-success fa-2x mb-2"></i>
                                <div class="small fw-bold">Easy Returns</div>
                                <div class="text-muted" style="font-size: 0.75rem;">30 days</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <i class="fas fa-headset text-info fa-2x mb-2"></i>
                                <div class="small fw-bold">24/7 Support</div>
                                <div class="text-muted" style="font-size: 0.75rem;">Always here</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('bottom_script')
<script>
// Enhanced checkout functionality
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkout-form');
    const placeOrderBtn = document.getElementById('place-order-btn');

    // Form validation
    function validateForm() {
        const requiredFields = form.querySelectorAll('input[required], select[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });

        return isValid;
    }

    // Real-time validation
    form.addEventListener('input', function(e) {
        if (e.target.hasAttribute('required')) {
            if (e.target.value.trim()) {
                e.target.classList.remove('is-invalid');
                e.target.classList.add('is-valid');
            } else {
                e.target.classList.remove('is-valid');
                e.target.classList.add('is-invalid');
            }
        }
    });

    // Payment method selection styling
    const paymentRadios = document.querySelectorAll('input[name="payment_method"]');
    paymentRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            paymentRadios.forEach(r => {
                const parent = r.closest('.form-check');
                if (r.checked) {
                    parent.classList.add('border-primary', 'bg-light');
                } else {
                    parent.classList.remove('border-primary', 'bg-light');
                }
            });
        });
    });

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateForm()) {
            alert('Please fill in all required fields.');
            return;
        }

        // Show loading state
        placeOrderBtn.disabled = true;
        placeOrderBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Order...';

        // Submit form
        setTimeout(() => {
            form.submit();
        }, 500);
    });

    // Initialize payment method styling
    const checkedRadio = document.querySelector('input[name="payment_method"]:checked');
    if (checkedRadio) {
        checkedRadio.dispatchEvent(new Event('change'));
    }
});

// Initialize checkout app (keeping Vue.js for compatibility)
const { createApp } = Vue;

const CheckoutApp = {
    components: {
        'checkout-process': {
            props: ['product', 'quantity', 'user'],
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
                    
                    // Payment methods (loaded dynamically)
                    paymentMethods: [],
                    loadingPaymentMethods: false
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
                getPaymentMethodName() {
                    const method = this.paymentMethods.find(m => m.id === this.paymentMethod);
                    return method ? method.name : 'Unknown Payment Method';
                },

                async loadPaymentMethods() {
                    this.loadingPaymentMethods = true;
                    try {
                        const response = await axios.get('/api/product-payment-methods');
                        if (response.data.status) {
                            this.paymentMethods = response.data.data.methods;
                            // Set default payment method to first available
                            if (this.paymentMethods.length > 0) {
                                this.paymentMethod = this.paymentMethods[0].id;
                            }
                        }
                    } catch (error) {
                        console.error('Failed to load payment methods:', error);
                        // Fallback to cash only
                        this.paymentMethods = [{
                            id: 'cash',
                            name: 'Cash on Delivery',
                            description: 'Pay when your order is delivered',
                            icon: 'fas fa-money-bill-wave',
                            enabled: true
                        }];
                        this.paymentMethod = 'cash';
                    } finally {
                        this.loadingPaymentMethods = false;
                    }
                },

                nextStep() {
                    if (this.currentStep === 1 && this.canProceedToStep2) {
                        this.currentStep = 2;
                        // Load payment methods when entering payment step
                        if (this.paymentMethods.length === 0) {
                            this.loadPaymentMethods();
                        }
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
                        // First create the order for direct product purchase
                        const response = await axios.post('/api/orders', {
                            product_id: this.product.id,
                            quantity: this.quantity,
                            delivery_address: this.deliveryAddress,
                            delivery_phone: this.deliveryPhone,
                            delivery_notes: this.deliveryNotes,
                            payment_method: this.paymentMethod
                        });

                        if (response.data.status) {
                            const orders = response.data.data.orders;
                            const totalAmount = response.data.data.total_amount;

                            // Handle different payment methods
                            if (this.paymentMethod === 'cash') {
                                // Cash on delivery - redirect to success page
                                window.location.href = '/order-success?orders=' + orders.map(o => o.id).join(',');
                            } else if (this.paymentMethod === 'stripe' || this.paymentMethod === 'card') {
                                // Stripe payment - create payment session
                                await this.processStripePayment(orders[0].id, totalAmount);
                            } else if (this.paymentMethod === 'wallet') {
                                // Wallet payment
                                await this.processWalletPayment(orders[0].id, totalAmount);
                            } else {
                                // Other payment methods - redirect to success for now
                                window.location.href = '/order-success?orders=' + orders.map(o => o.id).join(',');
                            }
                        } else {
                            this.error = response.data.message || 'Failed to place order';
                        }
                    } catch (err) {
                        this.error = 'Failed to place order. Please try again.';
                        console.error('Checkout error:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                async processStripePayment(orderId, totalAmount) {
                    try {
                        const response = await axios.post('/api/create-product-stripe-payment', {
                            order_id: orderId,
                            total_amount: totalAmount,
                            currency_code: 'USD' // You can make this dynamic
                        });

                        if (response.data.status) {
                            // Redirect to Stripe checkout
                            window.location.href = response.data.data.url;
                        } else {
                            this.error = response.data.message || 'Failed to create payment session';
                        }
                    } catch (error) {
                        this.error = 'Failed to process payment. Please try again.';
                        console.error('Stripe payment error:', error);
                    }
                },

                async processWalletPayment(orderId, totalAmount) {
                    try {
                        const response = await axios.post('/api/process-product-wallet-payment', {
                            order_id: orderId,
                            total_amount: totalAmount
                        });

                        if (response.data.status) {
                            // Wallet payment successful - redirect to success page
                            window.location.href = '/order-success?orders=' + orderId;
                        } else {
                            this.error = response.data.message || 'Wallet payment failed';
                        }
                    } catch (error) {
                        this.error = 'Wallet payment failed. Please try again.';
                        console.error('Wallet payment error:', error);
                    }
                }
            },

            mounted() {
                // Load payment methods on component mount
                this.loadPaymentMethods();
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
                                <!-- Loading state for payment methods -->
                                <div v-if="loadingPaymentMethods" class="text-center py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading payment methods...</span>
                                    </div>
                                    <p class="mt-2 text-muted">Loading payment methods...</p>
                                </div>

                                <!-- Payment methods -->
                                <div v-else class="payment-methods">
                                    <div v-if="paymentMethods.length === 0" class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        No payment methods available. Please contact support.
                                    </div>

                                    <div v-for="method in paymentMethods" :key="method.id" class="payment-method mb-3">
                                        <div class="form-check">
                                            <input v-model="paymentMethod" :value="method.id" :id="method.id" type="radio" class="form-check-input" :disabled="!method.enabled">
                                            <label :for="method.id" class="form-check-label w-100" :class="{ 'text-muted': !method.enabled }">
                                                <div class="payment-option">
                                                    <div class="payment-icon">
                                                        <i :class="method.icon"></i>
                                                    </div>
                                                    <div class="payment-details">
                                                        <div class="payment-name" v-text="method.name"></div>
                                                        <div class="payment-description" v-text="method.description"></div>
                                                        <div v-if="!method.enabled" class="text-danger small">
                                                            <i class="fas fa-exclamation-circle me-1"></i>Currently unavailable
                                                        </div>
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
                                    <button @click="nextStep" :disabled="loadingPaymentMethods || !paymentMethod" class="btn btn-primary">
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
                                        <strong v-text="deliveryAddress.name"></strong><br>
                                        <span v-text="deliveryAddress.address"></span><br>
                                        <span v-text="deliveryAddress.city + ', ' + deliveryAddress.state + ' ' + deliveryAddress.zip"></span><br>
                                        <span v-text="deliveryAddress.country"></span><br>
                                        <strong>Phone:</strong> <span v-text="deliveryPhone"></span>
                                    </address>
                                    
                                    <h6>Payment Method:</h6>
                                    <p v-text="getPaymentMethodName()"></p>
                                    
                                    <div v-if="deliveryNotes">
                                        <h6>Delivery Notes:</h6>
                                        <p v-text="deliveryNotes"></p>
                                    </div>
                                </div>
                                
                                <!-- Error Message -->
                                <div v-if="error" class="alert alert-danger">
                                    <span v-text="error"></span>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button @click="prevStep" :disabled="loading" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back
                                    </button>
                                    <button @click="placeOrder" :disabled="!canPlaceOrder || loading" class="btn btn-success btn-lg">
                                        <span v-if="loading" class="spinner-border spinner-border-sm me-2"></span>
                                        <i v-else class="fas fa-shopping-bag me-2"></i>
                                        <span v-text="loading ? 'Placing Order...' : 'Place Order'"></span>
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
