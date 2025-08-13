@extends('landing-page.layouts.default')
@section('content')

<!-- Page Header -->
<div class="iq-breadcrumb-one iq-bg-over iq-over-dark-50" style="background-image: url('{{ asset('images/breadcrumb/01.jpg') }}'); background-size: cover;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb" class="text-center iq-breadcrumb-two">
                    <h2 class="title text-white">{{ $product->name }}</h2>
                    <ol class="breadcrumb main-bg">
                        <li class="breadcrumb-item"><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('products.index') }}">Products</a></li>
                        @if($product->category)
                            <li class="breadcrumb-item"><a href="{{ route('products.category', $product->category->slug) }}">{{ $product->category->name }}</a></li>
                        @endif
                        <li class="breadcrumb-item active">{{ $product->name }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Product Details Section -->
<div class="section-padding bg-light">
    <div class="container">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6 mb-4">
                <div class="product-gallery sticky-top" style="top: 100px;">
                    <div class="main-image mb-3 position-relative">
                        <img src="{{ $product->main_image ?: asset('images/default-product.jpg') }}"
                             class="img-fluid rounded shadow-sm w-100"
                             alt="{{ $product->name }}"
                             id="main-product-image"
                             style="height: 400px; object-fit: cover;">
                        @if($product->is_featured)
                            <span class="badge bg-warning position-absolute top-0 start-0 m-3">
                                <i class="fas fa-star me-1"></i>Featured
                            </span>
                        @endif
                        @if(!$product->is_in_stock)
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <span class="badge bg-danger fs-6 px-3 py-2">Out of Stock</span>
                            </div>
                        @endif
                    </div>
                    @if($product->gallery && count($product->gallery) > 1)
                        <div class="thumbnail-images">
                            <div class="row g-2">
                                @foreach($product->gallery as $index => $image)
                                    <div class="col-3">
                                        <img src="{{ $image }}"
                                             class="img-fluid rounded thumbnail-img {{ $index === 0 ? 'active border-primary' : '' }}"
                                             alt="{{ $product->name }}"
                                             onclick="changeMainImage('{{ $image }}', this)"
                                             style="height: 80px; object-fit: cover; cursor: pointer; border: 2px solid transparent;">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info bg-white rounded shadow-sm p-4 h-100">
                    <!-- Product Header -->
                    <div class="product-header mb-4">
                        <h1 class="product-title h2 fw-bold text-dark mb-2">{{ $product->name }}</h1>

                        @if($product->category)
                            <div class="mb-3">
                                <span class="badge bg-light text-dark border px-3 py-2">
                                    <i class="fas fa-tag me-1"></i>{{ $product->category->name }}
                                </span>
                            </div>
                        @endif

                        <!-- Price Section -->
                        <div class="price-section mb-4 p-3 bg-light rounded">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="h3 text-primary fw-bold mb-0">{{ getPriceFormat($product->effective_price) }}</span>
                                    @if($product->selling_price && $product->selling_price != $product->base_price)
                                        <span class="text-muted text-decoration-line-through ms-2 fs-5">{{ getPriceFormat($product->base_price) }}</span>
                                        <span class="badge bg-success ms-2">Sale</span>
                                    @endif
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">Price per unit</small>
                                    <small class="text-success">✓ Best Price Guaranteed</small>
                                </div>
                            </div>
                        </div>

                        @if($product->short_description)
                            <div class="product-description mb-4">
                                <h6 class="fw-semibold text-dark mb-2">Description</h6>
                                <p class="text-muted mb-0">{{ $product->short_description }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Stock Status -->
                    <!-- Stock & Availability -->
                    <div class="availability-section mb-4 p-3 border rounded">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-semibold mb-1">Availability</h6>
                                @if($product->is_in_stock)
                                    <span class="badge bg-success fs-6 px-3 py-2">
                                        <i class="fas fa-check me-1"></i>In Stock
                                    </span>
                                    <small class="text-muted ms-2">{{ $product->stock_quantity }} units available</small>
                                @else
                                    <span class="badge bg-danger fs-6 px-3 py-2">
                                        <i class="fas fa-times me-1"></i>Out of Stock
                                    </span>
                                @endif
                            </div>
                            @if($product->is_in_stock)
                                <div class="text-end">
                                    <small class="text-success d-block">✓ Ready to ship</small>
                                    <small class="text-muted">Fast delivery available</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Product Variants -->
                    @if($product->variants && count($product->variants) > 0)
                        <div class="product-variants mb-4">
                            <h6 class="fw-semibold mb-3">Available Options</h6>
                            <div class="variants-container">
                                @foreach($product->variants as $variant)
                                    <div class="variant-option mb-2" data-variant-id="{{ $variant->id }}" data-price="{{ $variant->final_price }}">
                                        <div class="form-check border rounded p-3">
                                            <input type="radio" name="variant" value="{{ $variant->id }}" id="variant-{{ $variant->id }}" class="form-check-input variant-radio">
                                            <label for="variant-{{ $variant->id }}" class="form-check-label w-100 d-flex justify-content-between">
                                                <span>{{ $variant->name }}</span>
                                                <span class="fw-bold text-primary">{{ getPriceFormat($variant->final_price) }}</span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Order Now Section -->
                    <div class="order-section mb-4">
                        @if($product->is_in_stock)
                            @auth
                                @if(auth()->user()->user_type == 'user')
                                    <!-- Logged in customer - can order -->
                                    <div class="d-grid gap-2">
                                        <a href="{{ route('products.checkout', ['product_id' => $product->id, 'quantity' => 1]) }}" class="btn btn-primary btn-lg py-3">
                                            <i class="fas fa-shopping-bag me-2"></i>
                                            Buy Now - {{ getPriceFormat($product->effective_price) }}
                                        </a>
                                        <small class="text-center text-muted">
                                            <i class="fas fa-shield-alt me-1"></i>
                                            Secure checkout • Fast delivery • Money-back guarantee
                                        </small>
                                    </div>
                                @else
                                    <!-- Logged in but not customer (provider/admin) -->
                                    <div class="alert alert-info border-0 text-center">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Customer Account Required</strong><br>
                                        <small>Only customer accounts can place orders. Please switch to a customer account or create one.</small>
                                        <div class="mt-2">
                                            <a href="{{ route('register') }}" class="btn btn-outline-primary btn-sm">Create Customer Account</a>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <!-- Not logged in - redirect to login -->
                                <div class="d-grid gap-2">
                                    <a href="{{ route('login') }}?redirect={{ urlencode(route('products.checkout', ['product_id' => $product->id, 'quantity' => 1])) }}" class="btn btn-primary btn-lg py-3">
                                        <i class="fas fa-sign-in-alt me-2"></i>
                                        Login to Buy Now - {{ getPriceFormat($product->effective_price) }}
                                    </a>
                                    <div class="text-center">
                                        <small class="text-muted">
                                            Don't have an account?
                                            <a href="{{ route('register') }}" class="text-primary text-decoration-none">Sign up free</a>
                                        </small>
                                    </div>
                                    <small class="text-center text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Secure checkout • Fast delivery • Money-back guarantee
                                    </small>
                                </div>
                            @endauth
                        @else
                            <!-- Out of stock -->
                            <div class="d-grid">
                                <button class="btn btn-outline-secondary btn-lg py-3" disabled>
                                    <i class="fas fa-times-circle me-2"></i>
                                    Currently Out of Stock
                                </button>
                                <small class="text-center text-muted mt-2">
                                    <i class="fas fa-bell me-1"></i>
                                    We'll notify you when this item is back in stock
                                </small>
                            </div>
                        @endif
                    </div>

                    <!-- Additional Product Info -->
                    <div class="product-features mb-4">
                        <div class="row g-3">
                            <div class="col-4 text-center">
                                <div class="feature-item p-2">
                                    <i class="fas fa-shipping-fast text-primary fs-4 mb-2"></i>
                                    <small class="d-block text-muted">Fast Delivery</small>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="feature-item p-2">
                                    <i class="fas fa-undo text-primary fs-4 mb-2"></i>
                                    <small class="d-block text-muted">Easy Returns</small>
                                </div>
                            </div>
                            <div class="col-4 text-center">
                                <div class="feature-item p-2">
                                    <i class="fas fa-headset text-primary fs-4 mb-2"></i>
                                    <small class="d-block text-muted">24/7 Support</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available Stores -->
                    @if($availableStores->count() > 0)
                        <div class="available-stores mb-4">
                            <h6>Available from nearby stores:</h6>
                            <div class="stores-list">
                                @foreach($availableStores as $store)
                                    @php
                                        $storeProduct = $store->storeProducts->first();
                                    @endphp
                                    <div class="store-option" data-store-id="{{ $store->id }}">
                                        <input type="radio" name="store" value="{{ $store->id }}" id="store-{{ $store->id }}" class="store-radio">
                                        <label for="store-{{ $store->id }}" class="store-label">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong>{{ $store->name }}</strong>
                                                    <small class="text-muted d-block">{{ $store->address }}</small>
                                                    @if($store->distance)
                                                        <small class="text-muted">{{ number_format($store->distance, 1) }} km away</small>
                                                    @endif
                                                </div>
                                                <div class="text-end">
                                                    <span class="store-price">{{ getPriceFormat($storeProduct->final_price) }}</span>
                                                    <small class="text-muted d-block">{{ $storeProduct->stock_quantity }} in stock</small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Product Details -->
                    <div class="product-details">
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab">Description</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="specifications-tab" data-bs-toggle="tab" data-bs-target="#specifications" type="button" role="tab">Specifications</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="productTabsContent">
                            <div class="tab-pane fade show active" id="description" role="tabpanel">
                                <div class="p-3">
                                    {!! $product->description ?: 'No description available.' !!}
                                </div>
                            </div>
                            <div class="tab-pane fade" id="specifications" role="tabpanel">
                                <div class="p-3">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>SKU:</strong></td>
                                            <td>{{ $product->sku }}</td>
                                        </tr>
                                        @if($product->weight)
                                            <tr>
                                                <td><strong>Weight:</strong></td>
                                                <td>{{ $product->weight }} kg</td>
                                            </tr>
                                        @endif
                                        @if($product->dimensions)
                                            <tr>
                                                <td><strong>Dimensions:</strong></td>
                                                <td>
                                                    @if(is_array($product->dimensions))
                                                        {{ implode(' x ', $product->dimensions) }} cm
                                                    @else
                                                        {{ $product->dimensions }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td><strong>Category:</strong></td>
                                            <td>{{ $product->category ? $product->category->name : 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
            <div class="row mt-5">
                <div class="col-12">
                    <h3 class="mb-4">Related Products</h3>
                    <div class="row">
                        @foreach($relatedProducts as $relatedProduct)
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card product-card h-100">
                                    <div class="position-relative">
                                        <img src="{{ $relatedProduct->main_image ?: asset('images/default-product.jpg') }}" 
                                             class="card-img-top" alt="{{ $relatedProduct->name }}" style="height: 200px; object-fit: cover;">
                                        @if($relatedProduct->is_featured)
                                            <span class="badge bg-warning position-absolute top-0 end-0 m-2">Featured</span>
                                        @endif
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title">{{ $relatedProduct->name }}</h6>
                                        <p class="card-text text-muted small">{{ $relatedProduct->category ? $relatedProduct->category->name : '' }}</p>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="h6 text-primary mb-0">{{ getPriceFormat($relatedProduct->effective_price) }}</span>
                                                <a href="{{ route('products.show', $relatedProduct->slug) }}" class="btn btn-primary btn-sm">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
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
    // Variant selection
    $('.variant-radio').on('change', function() {
        const variantId = $(this).val();
        const price = $(this).closest('.variant-option').data('price');
        
        $('#selected-variant-id').val(variantId);
        $('.product-price .h2').text('$' + price.toFixed(2));
    });

    // Store selection
    $('.store-radio').on('change', function() {
        const storeId = $(this).val();
        $('#selected-store-id').val(storeId);
    });

    // Product ordering functionality
    // Order button click is handled by the href link to order.product route

    function changeMainImage(src, element) {
        $('#main-product-image').attr('src', src);
        $('.thumbnail-img').removeClass('active');
        $(element).addClass('active');
    }

    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert alert at the top of the product info section
        $('.product-info').prepend(alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }



    // Make changeMainImage function global
    window.changeMainImage = changeMainImage;
});
</script>

<style>
.product-gallery .thumbnail-img {
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.3s;
}

.product-gallery .thumbnail-img.active,
.product-gallery .thumbnail-img:hover {
    opacity: 1;
}

.variant-option, .store-option {
    margin-bottom: 10px;
}

.variant-label, .store-label {
    display: block;
    padding: 10px 15px;
    border: 2px solid #e9ecef;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.variant-radio:checked + .variant-label,
.store-radio:checked + .store-label {
    border-color: var(--bs-primary);
    background-color: rgba(var(--bs-primary-rgb), 0.1);
}

.variant-price, .store-price {
    font-weight: 600;
    color: var(--bs-primary);
}

.product-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

/* Enhanced Product Detail Page Styles */
.product-gallery .thumbnail-img {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.product-gallery .thumbnail-img:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.product-gallery .thumbnail-img.active {
    border-color: #007bff;
}

.product-info {
    animation: fadeInUp 0.6s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.price-section {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
}

.availability-section {
    background: #f8f9fa;
    border-left: 4px solid #28a745;
}

.variant-option .form-check {
    transition: all 0.3s ease;
    cursor: pointer;
}

.variant-option .form-check:hover {
    background-color: #f8f9fa;
    border-color: #007bff;
}

.variant-option .form-check-input:checked + .form-check-label {
    color: #007bff;
    font-weight: 600;
}

.feature-item {
    transition: transform 0.3s ease;
}

.feature-item:hover {
    transform: translateY(-5px);
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
}

@media (max-width: 991px) {
    .product-gallery {
        position: relative !important;
        top: auto !important;
    }
}

/* Badge animations */
.badge {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}
</style>
@endsection
