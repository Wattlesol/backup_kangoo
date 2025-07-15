@extends('landing-page.layouts.default')
@section('content')

<!-- Page Header -->
<div class="iq-breadcrumb-one iq-bg-over iq-over-dark-50" style="background-image: url('{{ asset('images/breadcrumb/01.jpg') }}'); background-size: cover;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb" class="text-center iq-breadcrumb-two">
                    <h2 class="title text-white">Shopping Cart</h2>
                    <ol class="breadcrumb main-bg">
                        <li class="breadcrumb-item"><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        <li class="breadcrumb-item active">Cart</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Cart Section -->
<div class="section-padding">
    <div class="container-fluid">
        @if($cartSummary['items']->count() > 0)
            <div class="row">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-items">
                        <h4 class="mb-4">Cart Items ({{ $cartSummary['total_items'] }})</h4>
                        
                        <!-- Group by store -->
                        @if($cartSummary['admin_products']->count() > 0)
                            <div class="store-group mb-4">
                                <div class="store-header">
                                    <h5 class="mb-3">
                                        <i class="fas fa-store text-primary"></i> 
                                        Admin Store
                                    </h5>
                                </div>
                                
                                @foreach($cartSummary['admin_products'] as $item)
                                    <div class="cart-item" data-item-id="{{ $item->id }}">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <img src="{{ $item->product_image ?: asset('images/default-product.jpg') }}" 
                                                     class="img-fluid rounded" alt="{{ $item->product->name }}">
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-1">{{ $item->product->name }}</h6>
                                                <p class="text-muted small mb-1">{{ $item->product->category ? $item->product->category->name : '' }}</p>
                                                @if($item->product_variant_id)
                                                    <p class="text-muted small mb-0">{{ $item->variant_display_name }}</p>
                                                @endif
                                                <p class="text-muted small mb-0">SKU: {{ $item->product->sku }}</p>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="fw-bold">{{ getPriceFormat($item->unit_price) }}</span>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="quantity-controls">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                                                    <input type="number" class="form-control form-control-sm quantity-input" value="{{ $item->quantity }}" min="1" style="width: 60px; display: inline-block;">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="increase">+</button>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="fw-bold item-total">{{ getPriceFormat($item->total_price) }}</span>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @foreach($cartSummary['stores'] as $storeId => $storeItems)
                            @php $store = $storeItems->first()->store; @endphp
                            <div class="store-group mb-4">
                                <div class="store-header">
                                    <h5 class="mb-3">
                                        <i class="fas fa-store text-primary"></i> 
                                        {{ $store->name }}
                                        <small class="text-muted">{{ $store->address }}</small>
                                    </h5>
                                    @if($store->minimum_order_amount > 0)
                                        <p class="text-info small mb-2">
                                            <i class="fas fa-info-circle"></i> 
                                            Minimum order: {{ getPriceFormat($store->minimum_order_amount) }}
                                        </p>
                                    @endif
                                    @if($store->delivery_fee > 0)
                                        <p class="text-muted small mb-3">
                                            <i class="fas fa-truck"></i> 
                                            Delivery fee: {{ getPriceFormat($store->delivery_fee) }}
                                        </p>
                                    @endif
                                </div>
                                
                                @foreach($storeItems as $item)
                                    <div class="cart-item" data-item-id="{{ $item->id }}">
                                        <div class="row align-items-center">
                                            <div class="col-md-2">
                                                <img src="{{ $item->product_image ?: asset('images/default-product.jpg') }}" 
                                                     class="img-fluid rounded" alt="{{ $item->product->name }}">
                                            </div>
                                            <div class="col-md-4">
                                                <h6 class="mb-1">{{ $item->product->name }}</h6>
                                                <p class="text-muted small mb-1">{{ $item->product->category ? $item->product->category->name : '' }}</p>
                                                @if($item->product_variant_id)
                                                    <p class="text-muted small mb-0">{{ $item->variant_display_name }}</p>
                                                @endif
                                                <p class="text-muted small mb-0">SKU: {{ $item->product->sku }}</p>
                                            </div>
                                            <div class="col-md-2">
                                                <span class="fw-bold">{{ getPriceFormat($item->unit_price) }}</span>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="quantity-controls">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="decrease">-</button>
                                                    <input type="number" class="form-control form-control-sm quantity-input" value="{{ $item->quantity }}" min="1" style="width: 60px; display: inline-block;">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary quantity-btn" data-action="increase">+</button>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <span class="fw-bold item-total">{{ getPriceFormat($item->total_price) }}</span>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-item" title="Remove item">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="col-lg-4">
                    <div class="cart-summary">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Order Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal ({{ $cartSummary['total_items'] }} items)</span>
                                    <span id="cart-subtotal">{{ getPriceFormat($cartSummary['subtotal']) }}</span>
                                </div>
                                
                                @foreach($cartSummary['stores'] as $storeId => $storeItems)
                                    @php $store = $storeItems->first()->store; @endphp
                                    @if($store->delivery_fee > 0)
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Delivery ({{ $store->name }})</span>
                                            <span>{{ getPriceFormat($store->delivery_fee) }}</span>
                                        </div>
                                    @endif
                                @endforeach
                                
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <strong>Total</strong>
                                    <strong id="cart-total">{{ getPriceFormat($cartSummary['subtotal']) }}</strong>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ route('products.checkout') }}" class="btn btn-primary btn-lg">
                                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                                    </a>
                                    <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Continue Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <!-- Empty Cart -->
            <div class="row">
                <div class="col-12 text-center py-5">
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                        <h3>Your cart is empty</h3>
                        <p class="text-muted mb-4">Looks like you haven't added any products to your cart yet.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag"></i> Start Shopping
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@section('after_script')
<script>
$(document).ready(function() {
    // Quantity controls
    $('.quantity-btn').on('click', function() {
        const action = $(this).data('action');
        const quantityInput = $(this).siblings('.quantity-input');
        const cartItem = $(this).closest('.cart-item');
        const itemId = cartItem.data('item-id');
        let currentQuantity = parseInt(quantityInput.val());
        
        if (action === 'increase') {
            currentQuantity++;
        } else if (action === 'decrease' && currentQuantity > 1) {
            currentQuantity--;
        }
        
        quantityInput.val(currentQuantity);
        updateCartItem(itemId, currentQuantity);
    });

    // Quantity input change
    $('.quantity-input').on('change', function() {
        const cartItem = $(this).closest('.cart-item');
        const itemId = cartItem.data('item-id');
        const quantity = parseInt($(this).val());
        
        if (quantity > 0) {
            updateCartItem(itemId, quantity);
        }
    });

    // Remove item
    $('.remove-item').on('click', function() {
        const cartItem = $(this).closest('.cart-item');
        const itemId = cartItem.data('item-id');
        
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            removeCartItem(itemId);
        }
    });

    function updateCartItem(itemId, quantity) {
        $.ajax({
            url: `/api/cart/${itemId}`,
            method: 'PUT',
            data: {
                quantity: quantity,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    // Update item total
                    const cartItem = $(`.cart-item[data-item-id="${itemId}"]`);
                    const unitPrice = parseFloat(response.data.unit_price);
                    const totalPrice = unitPrice * quantity;
                    
                    cartItem.find('.item-total').text('$' + totalPrice.toFixed(2));
                    
                    // Recalculate cart totals
                    recalculateCartTotals();
                } else {
                    alert(response.message || 'Failed to update cart item');
                }
            },
            error: function() {
                alert('Failed to update cart item');
            }
        });
    }

    function removeCartItem(itemId) {
        $.ajax({
            url: `/api/cart/${itemId}`,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    // Remove item from DOM
                    $(`.cart-item[data-item-id="${itemId}"]`).fadeOut(300, function() {
                        $(this).remove();
                        
                        // Check if store group is empty
                        const storeGroup = $(this).closest('.store-group');
                        if (storeGroup.find('.cart-item').length === 0) {
                            storeGroup.remove();
                        }
                        
                        // Check if cart is empty
                        if ($('.cart-item').length === 0) {
                            location.reload();
                        } else {
                            recalculateCartTotals();
                        }
                    });
                } else {
                    alert(response.message || 'Failed to remove cart item');
                }
            },
            error: function() {
                alert('Failed to remove cart item');
            }
        });
    }

    function recalculateCartTotals() {
        let subtotal = 0;
        let totalItems = 0;
        
        $('.cart-item').each(function() {
            const quantity = parseInt($(this).find('.quantity-input').val());
            const itemTotal = parseFloat($(this).find('.item-total').text().replace('$', ''));
            
            subtotal += itemTotal;
            totalItems += quantity;
        });
        
        $('#cart-subtotal').text('$' + subtotal.toFixed(2));
        $('#cart-total').text('$' + subtotal.toFixed(2)); // Add delivery fees calculation if needed
        
        // Update items count
        $('.cart-items h4').text(`Cart Items (${totalItems})`);
        $('.card-body .d-flex:first-child span:first-child').text(`Subtotal (${totalItems} items)`);
    }
});
</script>

<style>
.cart-item {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    transition: box-shadow 0.3s;
}

.cart-item:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.store-group {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    border: 1px solid #e9ecef;
}

.store-header {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 5px;
}

.quantity-controls .form-control {
    text-align: center;
}

.cart-summary .card {
    position: sticky;
    top: 20px;
}

.empty-cart {
    padding: 60px 20px;
}
</style>
@endsection
