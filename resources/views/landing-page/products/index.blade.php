@extends('landing-page.layouts.default')
@section('content')

<!-- Page Header -->
<div class="iq-breadcrumb-one iq-bg-over iq-over-dark-50" style="background-image: url('{{ asset('images/breadcrumb/01.jpg') }}'); background-size: cover;">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <nav aria-label="breadcrumb" class="text-center iq-breadcrumb-two">
                    <h2 class="title text-white">{{ $pageTitle }}</h2>
                    <ol class="breadcrumb main-bg">
                        <li class="breadcrumb-item"><a href="{{ route('frontend.index') }}">Home</a></li>
                        <li class="breadcrumb-item active">Products</li>
                        @if($selectedCategory)
                            <li class="breadcrumb-item active">{{ $selectedCategory->name }}</li>
                        @endif
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Products Section -->
<div class="section-padding">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="iq-sidebar-widget">
                    <!-- Search -->
                    <div class="widget">
                        <h5 class="widget-title">Search Products</h5>
                        <form method="GET" action="{{ route('products.search') }}">
                            <div class="input-group">
                                <input type="text" class="form-control" name="q" placeholder="Search products..." value="{{ $search }}">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Categories -->
                    <div class="widget">
                        <h5 class="widget-title">Categories</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="{{ route('products.index') }}" class="d-flex justify-content-between align-items-center {{ !$selectedCategory ? 'text-primary fw-bold' : '' }}">
                                    All Products
                                </a>
                            </li>
                            @foreach($categories as $category)
                                <li class="mb-2">
                                    <a href="{{ route('products.category', $category->slug) }}" class="d-flex justify-content-between align-items-center {{ $selectedCategory && $selectedCategory->id == $category->id ? 'text-primary fw-bold' : '' }}">
                                        {{ $category->name }}
                                        <span class="badge bg-light text-dark">{{ $category->products_count ?? 0 }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Location Filter -->
                    <div class="widget">
                        <h5 class="widget-title">Location</h5>
                        <div class="location-filter">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="use-location">
                                <i class="fas fa-map-marker-alt"></i> Use My Location
                            </button>
                            <small class="text-muted d-block mt-2">Find products available near you</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Filter Bar -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="results-info">
                        <span id="results-count">Loading...</span>
                    </div>
                    <div class="sort-options">
                        <select class="form-select" id="sort-select" style="width: auto;">
                            <option value="created_at-desc">Newest First</option>
                            <option value="created_at-asc">Oldest First</option>
                            <option value="name-asc">Name A-Z</option>
                            <option value="name-desc">Name Z-A</option>
                            <option value="price-asc">Price Low to High</option>
                            <option value="price-desc">Price High to Low</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div id="products-container">
                    <div class="row" id="products-grid">
                        <!-- Products will be loaded here via AJAX -->
                    </div>
                    
                    <!-- Loading Spinner -->
                    <div class="text-center py-5" id="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-5" id="pagination-container">
                    <!-- Pagination will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('after_script')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let currentFilters = {
        category_id: {{ $selectedCategory ? $selectedCategory->id : 'null' }},
        search: '{{ $search }}',
        latitude: null,
        longitude: null,
        sort_by: 'created_at',
        sort_order: 'desc'
    };

    // Load products on page load
    loadProducts();

    // Sort change handler
    $('#sort-select').on('change', function() {
        const sortValue = $(this).val().split('-');
        currentFilters.sort_by = sortValue[0];
        currentFilters.sort_order = sortValue[1];
        currentPage = 1;
        loadProducts();
    });

    // Use location button
    $('#use-location').on('click', function() {
        if (navigator.geolocation) {
            $(this).html('<i class="fas fa-spinner fa-spin"></i> Getting Location...');
            navigator.geolocation.getCurrentPosition(function(position) {
                currentFilters.latitude = position.coords.latitude;
                currentFilters.longitude = position.coords.longitude;
                currentPage = 1;
                loadProducts();
                $('#use-location').html('<i class="fas fa-check"></i> Location Set');
            }, function() {
                $('#use-location').html('<i class="fas fa-map-marker-alt"></i> Use My Location');
                alert('Unable to get your location');
            });
        }
    });

    function loadProducts() {
        $('#loading-spinner').show();
        
        const params = {
            page: currentPage,
            per_page: 12,
            ...currentFilters
        };

        $.get('{{ route("api.products") }}', params)
            .done(function(response) {
                if (response.status) {
                    renderProducts(response.data.data);
                    renderPagination(response.data);
                    updateResultsCount(response.data.total);
                }
            })
            .fail(function() {
                $('#products-grid').html('<div class="col-12 text-center"><p>Error loading products</p></div>');
            })
            .always(function() {
                $('#loading-spinner').hide();
            });
    }

    function renderProducts(products) {
        let html = '';
        
        if (products.length === 0) {
            html = '<div class="col-12 text-center py-5"><h4>No products found</h4><p>Try adjusting your search or filters</p></div>';
        } else {
            products.forEach(function(product) {
                html += `
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <img src="${product.main_image || '{{ asset("images/default-product.jpg") }}'}" 
                                     class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                                ${product.is_featured ? '<span class="badge bg-warning position-absolute top-0 end-0 m-2">Featured</span>' : ''}
                                ${!product.is_in_stock ? '<span class="badge bg-danger position-absolute top-0 start-0 m-2">Out of Stock</span>' : ''}
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h6 class="card-title">${product.name}</h6>
                                <p class="card-text text-muted small">${product.category ? product.category.name : ''}</p>
                                <p class="card-text flex-grow-1">${product.short_description || ''}</p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 text-primary mb-0">${product.price_format}</span>
                                        <a href="/product/${product.slug}" class="btn btn-primary btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        
        $('#products-grid').html(html);
    }

    function renderPagination(data) {
        if (data.last_page <= 1) {
            $('#pagination-container').html('');
            return;
        }

        let html = '<nav><ul class="pagination">';
        
        // Previous button
        if (data.current_page > 1) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">Previous</a></li>`;
        }
        
        // Page numbers
        for (let i = 1; i <= data.last_page; i++) {
            if (i === data.current_page) {
                html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
            } else {
                html += `<li class="page-item"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
            }
        }
        
        // Next button
        if (data.current_page < data.last_page) {
            html += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">Next</a></li>`;
        }
        
        html += '</ul></nav>';
        $('#pagination-container').html(html);
        
        // Pagination click handlers
        $('#pagination-container a').on('click', function(e) {
            e.preventDefault();
            currentPage = parseInt($(this).data('page'));
            loadProducts();
            $('html, body').animate({scrollTop: $('#products-container').offset().top - 100}, 500);
        });
    }

    function updateResultsCount(total) {
        $('#results-count').text(`Showing ${total} product${total !== 1 ? 's' : ''}`);
    }
});
</script>

<style>
.product-card {
    transition: transform 0.2s;
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.widget {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.widget-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
}

.location-filter button {
    width: 100%;
}
</style>
@endsection
