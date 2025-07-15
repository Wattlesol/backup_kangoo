@extends('landing-page.layouts.default')

@section('title', $pageTitle)

@section('content')
<div class="section-padding">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 mb-1">{{ $pageTitle }}</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('frontend.index') }}">{{__('landingpage.home')}}</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('products.index') }}">{{__('landingpage.store')}}</a></li>
                                <li class="breadcrumb-item active">{{__('landingpage.search')}}</li>
                            </ol>
                        </nav>
                    </div>
                    <div>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>{{__('landingpage.back_to_store')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <form id="searchForm" class="row g-3">
                            <div class="col-md-4">
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-lg" id="searchQuery"
                                           placeholder="{{__('landingpage.search_products_stores')}}" value="{{ $query }}">
                                    <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="categoryFilter">
                                    <option value="">{{__('landingpage.all_categories')}}</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ $selectedCategory && $selectedCategory->id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="locationFilter">
                                    <option value="">{{__('landingpage.all_locations')}}</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc }}" {{ $location == $loc ? 'selected' : '' }}>
                                            {{ $loc }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="sortFilter">
                                    <option value="name" {{ $sort == 'name' ? 'selected' : '' }}>{{__('landingpage.sort_by_name')}}</option>
                                    <option value="price_low" {{ $sort == 'price_low' ? 'selected' : '' }}>{{__('landingpage.price_low_high')}}</option>
                                    <option value="price_high" {{ $sort == 'price_high' ? 'selected' : '' }}>{{__('landingpage.price_high_low')}}</option>
                                    <option value="newest" {{ $sort == 'newest' ? 'selected' : '' }}>{{__('landingpage.newest_first')}}</option>
                                    <option value="popular" {{ $sort == 'popular' ? 'selected' : '' }}>{{__('landingpage.most_popular')}}</option>
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

        <!-- Search Results Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <ul class="nav nav-tabs nav-fill" id="searchTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">
                            <i class="fas fa-box me-2"></i>{{__('landingpage.products')}} <span class="badge bg-primary ms-2" id="productsCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="stores-tab" data-bs-toggle="tab" data-bs-target="#stores" type="button" role="tab">
                            <i class="fas fa-store me-2"></i>{{__('landingpage.stores')}} <span class="badge bg-primary ms-2" id="storesCount">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                            <i class="fas fa-tags me-2"></i>{{__('landingpage.categories')}} <span class="badge bg-primary ms-2" id="categoriesCount">0</span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center py-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">{{__('landingpage.loading')}}</span>
            </div>
            <p class="mt-3 text-muted">{{__('landingpage.searching')}}</p>
        </div>

        <!-- Search Results Content -->
        <div class="tab-content" id="searchTabsContent">
            <!-- Products Tab -->
            <div class="tab-pane fade show active" id="products" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div id="productsResultsInfo" class="text-muted"></div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary active" id="productsGridView">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="productsListView">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="productsContainer">
                    <!-- Products will be loaded here -->
                </div>
                <div id="productsNoResults" class="text-center py-5" style="display: none;">
                    <i class="fas fa-box fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">{{__('landingpage.no_products_found')}}</h4>
                    <p class="text-muted">{{__('landingpage.try_different_search')}}</p>
                </div>
            </div>

            <!-- Stores Tab -->
            <div class="tab-pane fade" id="stores" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <div id="storesResultsInfo" class="text-muted"></div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-secondary active" id="storesGridView">
                                    <i class="fas fa-th"></i>
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="storesListView">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="storesContainer">
                    <!-- Stores will be loaded here -->
                </div>
                <div id="storesNoResults" class="text-center py-5" style="display: none;">
                    <i class="fas fa-store fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">{{__('landingpage.no_stores_found')}}</h4>
                    <p class="text-muted">{{__('landingpage.try_different_search')}}</p>
                </div>
            </div>

            <!-- Categories Tab -->
            <div class="tab-pane fade" id="categories" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-12">
                        <div id="categoriesResultsInfo" class="text-muted"></div>
                    </div>
                </div>
                <div class="row" id="categoriesContainer">
                    <!-- Categories will be loaded here -->
                </div>
                <div id="categoriesNoResults" class="text-center py-5" style="display: none;">
                    <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">{{__('landingpage.no_categories_found')}}</h4>
                    <p class="text-muted">{{__('landingpage.try_different_search')}}</p>
                </div>
            </div>
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
    let currentTab = 'products';
    let currentPage = 1;
    let isGridView = { products: true, stores: true };

    // Initialize search
    performSearch();

    // Search form submission
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        currentPage = 1;
        performSearch();
    });

    // Filter changes
    $('#categoryFilter, #locationFilter, #sortFilter').on('change', function() {
        currentPage = 1;
        performSearch();
    });

    // Tab changes
    $('#searchTabs button').on('click', function() {
        currentTab = $(this).attr('data-bs-target').replace('#', '');
        currentPage = 1;
        performSearch();
    });

    // View toggles
    $('[id$="GridView"]').on('click', function() {
        const type = $(this).attr('id').replace('GridView', '');
        isGridView[type] = true;
        $(this).addClass('active').siblings().removeClass('active');
        performSearch();
    });

    $('[id$="ListView"]').on('click', function() {
        const type = $(this).attr('id').replace('ListView', '');
        isGridView[type] = false;
        $(this).addClass('active').siblings().removeClass('active');
        performSearch();
    });

    function performSearch() {
        showLoading();

        const params = {
            q: $('#searchQuery').val(),
            category: $('#categoryFilter').val(),
            location: $('#locationFilter').val(),
            sort: $('#sortFilter').val(),
            page: currentPage,
            view: isGridView[currentTab] ? 'grid' : 'list',
            type: currentTab
        };

        const endpoint = currentTab === 'products' ? '{{ route("api.products") }}' :
                        currentTab === 'stores' ? '{{ route("api.stores") }}' :
                        '/api/product-categories';

        $.get(endpoint, params)
            .done(function(response) {
                hideLoading();
                renderResults(response);
                updateCounts(response);
            })
            .fail(function() {
                hideLoading();
                showError('{{__("landingpage.search_error")}}');
            });
    }

    function renderResults(response) {
        const container = $(`#${currentTab}Container`);
        const noResults = $(`#${currentTab}NoResults`);
        const resultsInfo = $(`#${currentTab}ResultsInfo`);

        container.empty();

        if (response.data && response.data.length > 0) {
            response.data.forEach(item => {
                if (currentTab === 'products') {
                    container.append(renderProductCard(item));
                } else if (currentTab === 'stores') {
                    container.append(renderStoreCard(item));
                } else {
                    container.append(renderCategoryCard(item));
                }
            });

            noResults.hide();

            // Update results info
            if (response.pagination) {
                const { current_page, last_page, total } = response.pagination;
                resultsInfo.text(`{{__('landingpage.showing_results')}} ${((current_page - 1) * response.pagination.per_page) + 1}-${Math.min(current_page * response.pagination.per_page, total)} {{__('landingpage.of')}} ${total}`);
                renderPagination(response.pagination);
            }
        } else {
            noResults.show();
            resultsInfo.text('');
            $('#paginationContainer').empty();
        }
    }

    function renderProductCard(product) {
        const cardClass = isGridView.products ? 'col-lg-3 col-md-4 col-sm-6 mb-4' : 'col-12 mb-3';
        const layoutClass = isGridView.products ? '' : 'd-flex';

        return `
            <div class="${cardClass}">
                <div class="card h-100 shadow-sm border-0 product-card ${layoutClass}">
                    ${isGridView.products ? `
                        <img src="${product.image_url || '/images/default-product.png'}" class="card-img-top" alt="${product.name}" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">${product.name}</h5>
                            <p class="card-text text-muted small">${product.category_name || ''}</p>
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
        const cardClass = isGridView.stores ? 'col-lg-4 col-md-6 mb-4' : 'col-12 mb-3';
        const layoutClass = isGridView.stores ? '' : 'd-flex';

        return `
            <div class="${cardClass}">
                <div class="card h-100 shadow-sm border-0 store-card ${layoutClass}">
                    ${isGridView.stores ? `
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

    function renderCategoryCard(category) {
        return `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100 shadow-sm border-0 category-card">
                    <div class="card-body text-center">
                        ${category.image ?
                            `<img src="${category.image_url}" alt="${category.name}" class="img-fluid rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">` :
                            `<div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                <i class="fas fa-tags text-white fa-2x"></i>
                            </div>`
                        }
                        <h5 class="card-title">${category.name}</h5>
                        <p class="card-text text-muted small">${category.products_count} {{__('landingpage.products')}}</p>
                        <a href="/products/category/${category.slug}" class="btn btn-outline-primary btn-sm">
                            {{__('landingpage.view_products')}}
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    function updateCounts(response) {
        if (response.counts) {
            $('#productsCount').text(response.counts.products || 0);
            $('#storesCount').text(response.counts.stores || 0);
            $('#categoriesCount').text(response.counts.categories || 0);
        }
    }

    function renderPagination(pagination) {
        if (!pagination || pagination.last_page <= 1) {
            $('#paginationContainer').empty();
            return;
        }

        let paginationHtml = '<nav><ul class="pagination">';

        if (pagination.current_page > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page - 1})">{{__('landingpage.previous')}}</a></li>`;
        }

        for (let i = Math.max(1, pagination.current_page - 2); i <= Math.min(pagination.last_page, pagination.current_page + 2); i++) {
            const activeClass = i === pagination.current_page ? 'active' : '';
            paginationHtml += `<li class="page-item ${activeClass}"><a class="page-link" href="#" onclick="changePage(${i})">${i}</a></li>`;
        }

        if (pagination.current_page < pagination.last_page) {
            paginationHtml += `<li class="page-item"><a class="page-link" href="#" onclick="changePage(${pagination.current_page + 1})">{{__('landingpage.next')}}</a></li>`;
        }

        paginationHtml += '</ul></nav>';
        $('#paginationContainer').html(paginationHtml);
    }

    window.changePage = function(page) {
        currentPage = page;
        performSearch();
        $('html, body').animate({ scrollTop: $('#searchTabsContent').offset().top - 100 }, 500);
    };

    window.quickView = function(type, id) {
        $('#quickViewModal').modal('show');
        $('#quickViewContent').html('<div class="text-center"><div class="spinner-border" role="status"></div></div>');

        $.get(`/api/${type}s/${id}`)
            .done(function(response) {
                $('#quickViewContent').html(renderQuickView(response.data));
            })
            .fail(function() {
                $('#quickViewContent').html('<div class="alert alert-danger">{{__("landingpage.error_loading")}}</div>');
            });
    };

    function renderQuickView(item) {
        return `<div class="text-center"><h5>${item.name}</h5><p>${item.description || ''}</p></div>`;
    }

    function showLoading() {
        $('#loadingIndicator').show();
        $('#searchTabsContent').hide();
    }

    function hideLoading() {
        $('#loadingIndicator').hide();
        $('#searchTabsContent').show();
    }

    function showError(message) {
        console.error(message);
    }
});
</script>
@endsection
