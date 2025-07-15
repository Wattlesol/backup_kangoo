@extends('landing-page.layouts.default')

@section('title', __('landingpage.store'))

@section('content')
<div class="section-padding">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-4 fw-bold text-primary mb-3">{{__('landingpage.store')}}</h1>
                    <p class="lead text-muted">{{__('landingpage.store_description')}}</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <div class="col-md-4">
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-lg" id="searchQuery" 
                                           placeholder="{{__('landingpage.search_products_stores')}}" value="{{ request('q') }}">
                                    <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="categoryFilter">
                                    <option value="">{{__('landingpage.all_categories')}}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="locationFilter">
                                    <option value="">{{__('landingpage.all_locations')}}</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location }}" {{ request('location') == $location ? 'selected' : '' }}>
                                            {{ $location }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="sortFilter">
                                    <option value="name">{{__('landingpage.sort_by_name')}}</option>
                                    <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>{{__('landingpage.price_low_high')}}</option>
                                    <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>{{__('landingpage.price_high_low')}}</option>
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{__('landingpage.newest_first')}}</option>
                                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>{{__('landingpage.most_popular')}}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>{{__('landingpage.search')}}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Toggle -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="viewProducts">
                            <i class="fas fa-box me-2"></i>{{__('landingpage.products')}}
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="viewStores">
                            <i class="fas fa-store me-2"></i>{{__('landingpage.stores')}}
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="viewCategories">
                            <i class="fas fa-tags me-2"></i>{{__('landingpage.categories')}}
                        </button>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="gridView">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="listView">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{__('landingpage.loading')}}</span>
            </div>
            <p class="mt-3 text-muted">{{__('landingpage.searching')}}</p>
        </div>

        <!-- Results Section -->
        <div id="resultsSection">
            <!-- Products View -->
            <div id="productsView" class="results-view">
                <div class="row" id="productsContainer">
                    <!-- Products will be loaded here via AJAX -->
                </div>
            </div>

            <!-- Stores View -->
            <div id="storesView" class="results-view" style="display: none;">
                <div class="row" id="storesContainer">
                    <!-- Stores will be loaded here via AJAX -->
                </div>
            </div>

            <!-- Categories View -->
            <div id="categoriesView" class="results-view" style="display: none;">
                <div class="row" id="categoriesContainer">
                    @foreach($categories as $category)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 shadow-sm border-0 category-card">
                                <div class="card-body text-center">
                                    @if($category->image)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" 
                                             class="img-fluid rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                                    @else
                                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" 
                                             style="width: 80px; height: 80px;">
                                            <i class="fas fa-tags text-white fa-2x"></i>
                                        </div>
                                    @endif
                                    <h5 class="card-title">{{ $category->name }}</h5>
                                    <p class="card-text text-muted small">{{ $category->products_count }} {{__('landingpage.products')}}</p>
                                    <a href="{{ route('products.category', $category->slug) }}" class="btn btn-outline-primary btn-sm">
                                        {{__('landingpage.view_products')}}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- No Results -->
        <div id="noResults" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">{{__('landingpage.no_results_found')}}</h3>
            <p class="text-muted">{{__('landingpage.try_different_search')}}</p>
            <button class="btn btn-primary" onclick="clearSearch()">{{__('landingpage.clear_search')}}</button>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="d-flex justify-content-center mt-5">
            <!-- Pagination will be loaded here -->
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="quickViewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('landingpage.quick_view')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="quickViewContent">
                <!-- Quick view content will be loaded here -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('after_script')
<script>
$(document).ready(function() {
    let currentView = 'products';
    let currentPage = 1;
    let isGridView = true;

    // Initialize
    loadResults();

    // Search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        loadResults();
    });

    // Filter changes
    $('#categoryFilter, #locationFilter, #sortFilter').on('change', function() {
        currentPage = 1;
        loadResults();
    });

    // View toggles
    $('#viewProducts').on('click', function() {
        switchView('products');
    });

    $('#viewStores').on('click', function() {
        switchView('stores');
    });

    $('#viewCategories').on('click', function() {
        switchView('categories');
    });

    // Layout toggles
    $('#gridView').on('click', function() {
        isGridView = true;
        $(this).addClass('active').siblings().removeClass('active');
        loadResults();
    });

    $('#listView').on('click', function() {
        isGridView = false;
        $(this).addClass('active').siblings().removeClass('active');
        loadResults();
    });

    function switchView(view) {
        currentView = view;
        currentPage = 1;
        
        // Update button states
        $(`#view${view.charAt(0).toUpperCase() + view.slice(1)}`).addClass('active').siblings().removeClass('active');
        
        // Show/hide views
        $('.results-view').hide();
        $(`#${view}View`).show();
        
        if (view !== 'categories') {
            loadResults();
        }
    }

    function loadResults() {
        if (currentView === 'categories') return;

        showLoading();

        const params = {
            q: $('#searchQuery').val(),
            category: $('#categoryFilter').val(),
            location: $('#locationFilter').val(),
            sort: $('#sortFilter').val(),
            page: currentPage,
            view: isGridView ? 'grid' : 'list'
        };

        const endpoint = currentView === 'products' ? '{{ route("api.products") }}' : '{{ route("api.stores") }}';

        $.get(endpoint, params)
            .done(function(response) {
                hideLoading();
                if (response.data && response.data.length > 0) {
                    renderResults(response.data);
                    renderPagination(response.pagination);
                    $('#noResults').hide();
                } else {
                    $(`#${currentView}Container`).empty();
                    $('#paginationContainer').empty();
                    $('#noResults').show();
                }
            })
            .fail(function() {
                hideLoading();
                showError('{{__("landingpage.search_error")}}');
            });
    }

    function renderResults(data) {
        const container = $(`#${currentView}Container`);
        container.empty();

        data.forEach(item => {
            if (currentView === 'products') {
                container.append(renderProductCard(item));
            } else {
                container.append(renderStoreCard(item));
            }
        });
    }

    function renderProductCard(product) {
        const cardClass = isGridView ? 'col-lg-3 col-md-4 col-sm-6 mb-4' : 'col-12 mb-3';
        const layoutClass = isGridView ? '' : 'd-flex';
        
        return `
            <div class="${cardClass}">
                <div class="card h-100 shadow-sm border-0 product-card ${layoutClass}">
                    ${isGridView ? `
                        <img src="${product.image_url || '/images/default-product.png'}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">${product.name}</h5>
                            <p class="card-text text-muted small">${product.category_name}</p>
                            <p class="card-text"><strong>${product.price_formatted}</strong></p>
                            <div class="d-flex justify-content-between">
                                <button class="btn btn-outline-primary btn-sm" onclick="quickView('product', ${product.id})">
                                    {{__('landingpage.quick_view')}}
                                </button>
                                <a href="${product.url}" class="btn btn-primary btn-sm">{{__('landingpage.view_details')}}</a>
                            </div>
                        </div>
                    ` : `
                        <img src="${product.image_url || '/images/default-product.png'}" alt="${product.name}" style="width: 150px; height: 150px; object-fit: cover;">
                        <div class="card-body flex-grow-1">
                            <h5 class="card-title">${product.name}</h5>
                            <p class="card-text text-muted">${product.description || ''}</p>
                            <p class="card-text"><strong>${product.price_formatted}</strong></p>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="quickView('product', ${product.id})">{{__('landingpage.quick_view')}}</button>
                                <a href="${product.url}" class="btn btn-primary btn-sm">{{__('landingpage.view_details')}}</a>
                            </div>
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    function renderStoreCard(store) {
        const cardClass = isGridView ? 'col-lg-4 col-md-6 mb-4' : 'col-12 mb-3';
        const layoutClass = isGridView ? '' : 'd-flex';
        
        return `
            <div class="${cardClass}">
                <div class="card h-100 shadow-sm border-0 store-card ${layoutClass}">
                    ${isGridView ? `
                        <div class="card-body text-center">
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-store text-white fa-2x"></i>
                            </div>
                            <h5 class="card-title">${store.name}</h5>
                            <p class="card-text text-muted small">${store.address}</p>
                            <p class="card-text"><small class="text-muted">${store.products_count} {{__('landingpage.products')}}</small></p>
                            <a href="${store.url}" class="btn btn-primary btn-sm">{{__('landingpage.visit_store')}}</a>
                        </div>
                    ` : `
                        <div class="bg-primary rounded d-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                            <i class="fas fa-store text-white fa-2x"></i>
                        </div>
                        <div class="card-body flex-grow-1">
                            <h5 class="card-title">${store.name}</h5>
                            <p class="card-text text-muted">${store.address}</p>
                            <p class="card-text"><small class="text-muted">${store.products_count} {{__('landingpage.products')}}</small></p>
                            <a href="${store.url}" class="btn btn-primary btn-sm">{{__('landingpage.visit_store')}}</a>
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.last_page <= 1) {
            $('#paginationContainer').empty();
            return;
        }

        let paginationHtml = '<nav><ul class="pagination">';
        
        // Previous button
        if (pagination.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">{{__('landingpage.previous')}}</a></li>`;
        }
        
        // Page numbers
        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            paginationHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
        }
        
        // Next button
        if (pagination.current_page < pagination.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">{{__('landingpage.next')}}</a></li>`;
        }
        
        paginationHtml += '</ul></nav>';
        $('#paginationContainer').html(paginationHtml);
    }

    window.changePage = function(page) {
        currentPage = page;
        loadResults();
        $('html, body').animate({ scrollTop: $('#resultsSection').offset().top - 100 }, 500);
    };

    window.quickView = function(type, id) {
        $('#quickViewModal').modal('show');
        $('#quickViewContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');
        
        // Load quick view content via AJAX
        $.get(`/api/${type}s/${id}`)
            .done(function(response) {
                $('#quickViewContent').html(renderQuickView(response.data));
            })
            .fail(function() {
                $('#quickViewContent').html('<div class="alert alert-danger">{{__("landingpage.error_loading")}}</div>');
            });
    };

    function renderQuickView(item) {
        // Implement quick view rendering based on item type
        return `<div class="text-center"><h5>${item.name}</h5><p>${item.description || ''}</p></div>`;
    }

    window.clearSearch = function() {
        $('#searchForm')[0].reset();
        currentPage = 1;
        loadResults();
    };

    function showLoading() {
        $('#loadingIndicator').show();
        $('#resultsSection').hide();
        $('#noResults').hide();
    }

    function hideLoading() {
        $('#loadingIndicator').hide();
        $('#resultsSection').show();
    }

    function showError(message) {
        // Implement error display
        console.error(message);
    }
});
</script>
@endsection
