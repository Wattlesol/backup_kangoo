@extends('landing-page.layouts.default')

@section('title', __('landingpage.store'))

@section('content')
<div class="py-5" style="background-color: #f8f9fa;">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="h2 fw-bold text-primary mb-2">
                        {{__('landingpage.store')}}
                    </h1>
                    <p class="text-muted mb-0">{{__('landingpage.store_description')}}</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="bg-white rounded-3 shadow-sm border-0 sticky-top" style="top: 20px;">
                    <div class="p-4 border-bottom">
                        <h6 class="fw-bold text-primary mb-0">{{__('landingpage.filters')}}</h6>
                    </div>
                    <div class="p-4">
                        <!-- Search -->
                        <div class="mb-4">
                            <label class="form-label text-primary fw-semibold mb-2">{{__('landingpage.search')}}</label>
                            <div class="position-relative">
                                <input type="text" class="form-control border-0 bg-light" id="searchQuery"
                                       placeholder="{{__('landingpage.search_products')}}" value="{{ $search }}">
                                <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-primary"></i>
                            </div>
                        </div>

                        <!-- Categories -->
                        <div class="mb-4">
                            <label class="form-label text-primary fw-semibold mb-2">{{__('landingpage.categories')}}</label>
                            <select class="form-select border-0 bg-light" id="categoryFilter">
                                <option value="">{{__('landingpage.all_categories')}}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }} ({{ $category->products_count }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <label class="form-label text-primary fw-semibold mb-2">{{__('landingpage.price_range')}}</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control border-0 bg-light" id="priceMin"
                                           placeholder="{{__('landingpage.min_price')}}" value="{{ $priceMin }}"
                                           min="{{ $priceRange->min_price ?? 0 }}">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control border-0 bg-light" id="priceMax"
                                           placeholder="{{__('landingpage.max_price')}}" value="{{ $priceMax }}"
                                           max="{{ $priceRange->max_price ?? 10000 }}">
                                </div>
                            </div>
                            <small class="text-muted mt-1">
                                Range: ${{ $priceRange->min_price ?? 0 }} - ${{ $priceRange->max_price ?? 10000 }}
                            </small>
                        </div>

                        <!-- Providers -->
                        <div class="mb-4">
                            <label class="form-label text-primary fw-semibold mb-2">{{__('landingpage.providers')}}</label>
                            <select class="form-select border-0 bg-light" id="providerFilter">
                                <option value="">{{__('landingpage.all_providers')}}</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" {{ $providerId == $provider->id ? 'selected' : '' }}>
                                        {{ $provider->first_name }} {{ $provider->last_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Availability -->
                        <div class="mb-4">
                            <label class="form-label text-primary fw-semibold mb-2">{{__('landingpage.availability')}}</label>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="inStockOnly" {{ $inStockOnly ? 'checked' : '' }}>
                                <label class="form-check-label" for="inStockOnly">
                                    {{__('landingpage.in_stock_only')}}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="featuredOnly" {{ $featuredOnly ? 'checked' : '' }}>
                                <label class="form-check-label" for="featuredOnly">
                                    {{__('landingpage.featured_only')}}
                                </label>
                            </div>
                        </div>

                        <!-- Clear Filters -->
                        <button type="button" class="btn btn-outline-primary w-100" id="clearFilters">
                            {{__('landingpage.clear_filters')}}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="col-lg-9">
                <!-- Toolbar -->
                <div class="bg-white rounded-3 shadow-sm p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <span class="text-muted" id="resultsCount">{{__('landingpage.loading')}}</span>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end align-items-center gap-3">
                                <!-- Sort -->
                                <select class="form-select border-0 bg-light" id="sortFilter" style="width: 200px;">
                                    <option value="name" {{ $sort == 'name' ? 'selected' : '' }}>{{__('landingpage.sort_name')}}</option>
                                    <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>{{__('landingpage.sort_price_low')}}</option>
                                    <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>{{__('landingpage.sort_price_high')}}</option>
                                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>{{__('landingpage.sort_newest')}}</option>
                                    <option value="featured" {{ $sort == 'featured' ? 'selected' : '' }}>{{__('landingpage.sort_featured')}}</option>
                                </select>

                                <!-- View Toggle -->
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm active" id="gridView">
                                        <i class="fas fa-th"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="listView">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="loadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{__('landingpage.loading')}}</span>
                    </div>
                    <p class="mt-3 text-muted">{{__('landingpage.loading_products')}}</p>
                </div>

                <!-- Products Grid -->
                <div id="productsContainer" class="row g-4" style="display: none;">
                    <!-- Products will be loaded here via AJAX -->
                </div>

                <!-- No Results -->
                <div id="noResults" class="bg-white rounded-3 shadow-sm text-center py-5" style="display: none;">
                    <div class="mb-4">
                        <i class="fas fa-search fa-3x text-muted"></i>
                    </div>
                    <h4 class="text-primary">{{__('landingpage.no_products_found')}}</h4>
                    <p class="text-muted">{{__('landingpage.try_different_filters')}}</p>
                    <button type="button" class="btn btn-primary" id="resetFilters">
                        {{__('landingpage.reset_filters')}}
                    </button>
                </div>

                <!-- Pagination -->
                <div id="paginationContainer" class="d-flex justify-content-center mt-4">
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
    let isGridView = true;
    let isLoading = false;

    // Initialize
    loadProducts();

    // Filter change handlers
    $('#searchQuery, #categoryFilter, #priceMin, #priceMax, #providerFilter, #sortFilter').on('change keyup', debounce(function() {
        currentPage = 1;
        loadProducts();
    }, 500));

    $('#inStockOnly, #featuredOnly').on('change', function() {
        currentPage = 1;
        loadProducts();
    });

    // View toggle
    $('#gridView').on('click', function() {
        if (!isGridView) {
            isGridView = true;
            $(this).addClass('active');
            $('#listView').removeClass('active');
            renderCurrentProducts();
        }
    });

    $('#listView').on('click', function() {
        if (isGridView) {
            isGridView = false;
            $(this).addClass('active');
            $('#gridView').removeClass('active');
            renderCurrentProducts();
        }
    });

    // Clear filters
    $('#clearFilters, #resetFilters').on('click', function() {
        $('#searchQuery').val('');
        $('#categoryFilter').val('');
        $('#priceMin').val('');
        $('#priceMax').val('');
        $('#providerFilter').val('');
        $('#sortFilter').val('name');
        $('#inStockOnly').prop('checked', false);
        $('#featuredOnly').prop('checked', false);
        currentPage = 1;
        loadProducts();
    });

    let currentProducts = [];

    function loadProducts() {
        if (isLoading) return;
        
        isLoading = true;
        showLoading();

        const params = {
            q: $('#searchQuery').val(),
            category_id: $('#categoryFilter').val(),
            price_min: $('#priceMin').val(),
            price_max: $('#priceMax').val(),
            provider_id: $('#providerFilter').val(),
            sort_by: getSortBy(),
            sort_order: getSortOrder(),
            in_stock_only: $('#inStockOnly').is(':checked') ? 1 : 0,
            featured_only: $('#featuredOnly').is(':checked') ? 1 : 0,
            page: currentPage,
            per_page: 12
        };

        $.get('{{ route("api.products") }}', params)
            .done(function(response) {
                isLoading = false;
                hideLoading();
                
                if (response.status && response.data && response.data.data.length > 0) {
                    currentProducts = response.data.data;
                    renderCurrentProducts();
                    renderPagination(response.data);
                    updateResultsCount(response.data);
                    $('#noResults').hide();
                } else {
                    $('#productsContainer').hide();
                    $('#paginationContainer').empty();
                    $('#noResults').show();
                    updateResultsCount(null);
                }
            })
            .fail(function() {
                isLoading = false;
                hideLoading();
                showError('{{__("landingpage.error_loading_products")}}');
            });
    }

    function renderCurrentProducts() {
        const container = $('#productsContainer');
        container.empty();

        currentProducts.forEach(product => {
            container.append(renderProductCard(product));
        });

        container.show();
    }

    function renderProductCard(product) {
        const cardClass = isGridView ? 'col-lg-4 col-md-6 col-sm-6 mb-4' : 'col-12 mb-3';
        const layoutClass = isGridView ? '' : 'd-flex';
        const imageClass = isGridView ? 'card-img-top' : 'flex-shrink-0 me-3';
        const bodyClass = isGridView ? 'card-body' : 'card-body flex-grow-1';

        const price = product.effective_price || product.base_price || 0;
        const image = product.main_image || '/images/default-product.png';
        const providerName = product.creator ? `${product.creator.first_name} ${product.creator.last_name}` : 'Unknown Provider';

        return `
            <div class="${cardClass}">
                <div class="card h-100 border-0 shadow-sm product-card ${layoutClass}" style="transition: transform 0.2s;">
                    <div class="${isGridView ? '' : 'd-flex'}">
                        <img src="${image}" class="${imageClass} rounded-top" alt="${product.name}"
                             style="${isGridView ? 'height: 220px;' : 'width: 150px; height: 150px;'} object-fit: cover;">
                        <div class="${bodyClass} p-3">
                            <h6 class="card-title text-dark fw-semibold mb-2">${product.name}</h6>
                            <p class="card-text text-muted small mb-3">${product.short_description || ''}</p>
                            <div class="mb-3">
                                <span class="badge bg-light text-dark border me-1">${product.category ? product.category.name : 'Uncategorized'}</span>
                                <span class="badge bg-primary">${providerName}</span>
                            </div>
                            <div class="d-flex ${isGridView ? 'justify-content-between' : 'justify-content-between'} align-items-center">
                                <span class="h6 text-primary fw-bold mb-0">$${parseFloat(price).toFixed(2)}</span>
                                <a href="/product/${product.slug}" class="btn btn-primary btn-sm">{{__('landingpage.view_details')}}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function getSortBy() {
        const sort = $('#sortFilter').val();
        switch(sort) {
            case 'price_low':
            case 'price_high':
                return 'base_price';
            case 'newest':
                return 'created_at';
            case 'featured':
                return 'is_featured';
            default:
                return 'name';
        }
    }

    function getSortOrder() {
        const sort = $('#sortFilter').val();
        return (sort === 'price_high' || sort === 'newest' || sort === 'featured') ? 'desc' : 'asc';
    }

    function showLoading() {
        $('#loadingState').show();
        $('#productsContainer').hide();
        $('#noResults').hide();
    }

    function hideLoading() {
        $('#loadingState').hide();
    }

    function updateResultsCount(data) {
        if (data && data.total) {
            const from = ((data.current_page - 1) * data.per_page) + 1;
            const to = Math.min(data.current_page * data.per_page, data.total);
            $('#resultsCount').text(`{{__('landingpage.showing')}} ${from}-${to} {{__('landingpage.of')}} ${data.total} {{__('landingpage.products')}}`);
        } else {
            $('#resultsCount').text('{{__('landingpage.no_products')}}');
        }
    }

    function renderPagination(data) {
        // Simple pagination implementation
        const container = $('#paginationContainer');
        container.empty();

        if (data.last_page > 1) {
            let pagination = '<nav><ul class="pagination justify-content-center">';
            
            // Previous
            if (data.current_page > 1) {
                pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page - 1}">{{__('landingpage.previous')}}</a></li>`;
            }

            // Pages
            for (let i = Math.max(1, data.current_page - 2); i <= Math.min(data.last_page, data.current_page + 2); i++) {
                pagination += `<li class="page-item ${i === data.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>`;
            }

            // Next
            if (data.current_page < data.last_page) {
                pagination += `<li class="page-item"><a class="page-link" href="#" data-page="${data.current_page + 1}">{{__('landingpage.next')}}</a></li>`;
            }

            pagination += '</ul></nav>';
            container.html(pagination);

            // Pagination click handlers
            container.find('.page-link').on('click', function(e) {
                e.preventDefault();
                currentPage = parseInt($(this).data('page'));
                loadProducts();
            });
        }
    }

    function showError(message) {
        // Simple error display - you can enhance this
        alert(message);
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endsection
