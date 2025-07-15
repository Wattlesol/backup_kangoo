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
<div class="section-padding">
    <div class="container-fluid">
        <div class="row">
            <!-- Product Images -->
            <div class="col-lg-6">
                <div class="product-gallery">
                    <div class="main-image mb-3">
                        <img src="{{ $product->main_image ?: asset('images/default-product.jpg') }}" 
                             class="img-fluid rounded" alt="{{ $product->name }}" id="main-product-image">
                    </div>
                    @if($product->gallery && count($product->gallery) > 1)
                        <div class="thumbnail-images">
                            <div class="row">
                                @foreach($product->gallery as $index => $image)
                                    <div class="col-3 mb-2">
                                        <img src="{{ $image }}" 
                                             class="img-fluid rounded thumbnail-img {{ $index === 0 ? 'active' : '' }}" 
                                             alt="{{ $product->name }}" 
                                             onclick="changeMainImage('{{ $image }}', this)">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-lg-6">
                <div class="product-info">
                    <h1 class="product-title">{{ $product->name }}</h1>
                    
                    @if($product->category)
                        <p class="text-muted mb-2">
                            <i class="fas fa-tag"></i> {{ $product->category->name }}
                        </p>
                    @endif

                    <div class="product-price mb-3">
                        <span class="h2 text-primary">{{ getPriceFormat($product->effective_price) }}</span>
                        @if($product->admin_price_active && $product->admin_override_price != $product->base_price)
                            <span class="text-muted text-decoration-line-through ms-2">{{ getPriceFormat($product->base_price) }}</span>
                        @endif
                    </div>

                    @if($product->short_description)
                        <p class="product-short-desc">{{ $product->short_description }}</p>
                    @endif

                    <!-- Stock Status -->
                    <div class="stock-status mb-3">
                        @if($product->is_in_stock)
                            <span class="badge bg-success">
                                <i class="fas fa-check"></i> In Stock ({{ $product->stock_quantity }} available)
                            </span>
                        @else
                            <span class="badge bg-danger">
                                <i class="fas fa-times"></i> Out of Stock
                            </span>
                        @endif
                    </div>

                    <!-- Product Variants -->
                    @if($product->variants && count($product->variants) > 0)
                        <div class="product-variants mb-4">
                            <h6>Available Options:</h6>
                            <div class="variants-container">
                                @foreach($product->variants as $variant)
                                    <div class="variant-option" data-variant-id="{{ $variant->id }}" data-price="{{ $variant->final_price }}">
                                        <input type="radio" name="variant" value="{{ $variant->id }}" id="variant-{{ $variant->id }}" class="variant-radio">
                                        <label for="variant-{{ $variant->id }}" class="variant-label">
                                            {{ $variant->name }}
                                            <span class="variant-price">{{ getPriceFormat($variant->final_price) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Add to Cart Form -->
                    @auth
                        <form id="add-to-cart-form" class="mb-4">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="product_variant_id" id="selected-variant-id">
                            <input type="hidden" name="store_id" id="selected-store-id">
                            
                            <div class="row align-items-end">
                                <div class="col-md-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" class="form-control" name="quantity" id="quantity" value="1" min="1" max="{{ $product->stock_quantity }}">
                                </div>
                                <div class="col-md-9">
                                    <button type="submit" class="btn btn-primary btn-lg" {{ !$product->is_in_stock ? 'disabled' : '' }}>
                                        <i class="fas fa-shopping-cart"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <a href="{{ route('login') }}">Login</a> to add products to cart
                        </div>
                    @endauth

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

    // Add to cart form submission
    $('#add-to-cart-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
        
        $.post('/api/cart/add', formData)
            .done(function(response) {
                if (response.status) {
                    // Show success message
                    showAlert('success', 'Product added to cart successfully!');
                    // Update cart count if exists
                    updateCartCount();
                } else {
                    showAlert('error', response.message || 'Failed to add product to cart');
                }
            })
            .fail(function() {
                showAlert('error', 'Failed to add product to cart');
            })
            .always(function() {
                submitBtn.html(originalText).prop('disabled', false);
            });
    });

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

    function updateCartCount() {
        $.get('/api/cart/count')
            .done(function(response) {
                if (response.status && $('.cart-count').length) {
                    $('.cart-count').text(response.data.count);
                }
            });
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
</style>
@endsection
