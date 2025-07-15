@extends('landing-page.layouts.default')

@section('title', __('landingpage.product_categories'))

@section('content')
<div class="section-padding">
    <div class="container">
        <!-- Page Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="text-center">
                    <h1 class="display-4 fw-bold text-primary mb-3">{{__('landingpage.product_categories')}}</h1>
                    <p class="lead text-muted">{{__('landingpage.categories_description')}}</p>
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <div class="position-relative">
                                    <input type="text" class="form-control form-control-lg" id="categorySearch" 
                                           placeholder="{{__('landingpage.search_categories')}}" value="{{ request('q') }}">
                                    <i class="fas fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-muted"></i>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select class="form-select form-select-lg" id="sortCategories">
                                    <option value="name">{{__('landingpage.sort_by_name')}}</option>
                                    <option value="products_count">{{__('landingpage.sort_by_products')}}</option>
                                    <option value="newest">{{__('landingpage.newest_first')}}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-primary btn-lg w-100" onclick="searchCategories()">
                                    <i class="fas fa-search me-2"></i>{{__('landingpage.search')}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Categories -->
        @if($featuredCategories->count() > 0)
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="h3 mb-4">{{__('landingpage.featured_categories')}}</h2>
                <div class="row">
                    @foreach($featuredCategories as $category)
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card h-100 shadow-sm border-0 category-card featured-category">
                                <div class="position-relative">
                                    @if($category->image)
                                        <img src="{{ $category->image_url }}" alt="{{ $category->name }}" 
                                             class="card-img-top" style="height: 200px; object-fit: cover;">
                                    @else
                                        <div class="card-img-top bg-gradient-primary d-flex align-items-center justify-content-center" 
                                             style="height: 200px;">
                                            <i class="fas fa-tags text-white fa-4x"></i>
                                        </div>
                                    @endif
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star me-1"></i>{{__('landingpage.featured')}}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body text-center">
                                    <h5 class="card-title">{{ $category->name }}</h5>
                                    @if($category->description)
                                        <p class="card-text text-muted small">{{ Str::limit($category->description, 80) }}</p>
                                    @endif
                                    <p class="card-text">
                                        <span class="badge bg-primary">{{ $category->products_count }} {{__('landingpage.products')}}</span>
                                    </p>
                                    <a href="{{ route('products.category', $category->slug) }}" class="btn btn-primary">
                                        {{__('landingpage.explore_category')}}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- All Categories -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="h3 mb-0">{{__('landingpage.all_categories')}}</h2>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary active" id="gridViewBtn">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="listViewBtn">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Grid/List -->
        <div id="categoriesContainer">
            <div class="row" id="categoriesGrid">
                @foreach($categories as $category)
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4 category-item" data-name="{{ strtolower($category->name) }}">
                        <div class="card h-100 shadow-sm border-0 category-card">
                            <div class="position-relative">
                                @if($category->image)
                                    <img src="{{ $category->image_url }}" alt="{{ $category->name }}" 
                                         class="card-img-top" style="height: 180px; object-fit: cover;">
                                @else
                                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                         style="height: 180px;">
                                        <i class="fas fa-tags text-muted fa-3x"></i>
                                    </div>
                                @endif
                                @if($category->is_featured)
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-star me-1"></i>{{__('landingpage.featured')}}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title">{{ $category->name }}</h5>
                                @if($category->description)
                                    <p class="card-text text-muted small">{{ Str::limit($category->description, 60) }}</p>
                                @endif
                                <p class="card-text">
                                    <small class="text-muted">{{ $category->products_count }} {{__('landingpage.products')}}</small>
                                </p>
                                <a href="{{ route('products.category', $category->slug) }}" class="btn btn-outline-primary">
                                    {{__('landingpage.view_products')}}
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- No Results -->
        <div id="noResults" class="text-center py-5" style="display: none;">
            <i class="fas fa-search fa-5x text-muted mb-4"></i>
            <h3 class="text-muted">{{__('landingpage.no_categories_found')}}</h3>
            <p class="text-muted">{{__('landingpage.try_different_search')}}</p>
            <button class="btn btn-primary" onclick="clearCategorySearch()">{{__('landingpage.clear_search')}}</button>
        </div>

        <!-- Pagination -->
        @if($categories->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Category Quick View Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="categoryModalBody">
                <!-- Category details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('landingpage.close')}}</button>
                <a href="#" class="btn btn-primary" id="viewCategoryProducts">{{__('landingpage.view_products')}}</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('after_script')
<script>
$(document).ready(function() {
    let isGridView = true;

    // Search functionality
    $('#categorySearch').on('input', function() {
        searchCategories();
    });

    $('#sortCategories').on('change', function() {
        sortCategories();
    });

    // View toggles
    $('#gridViewBtn').on('click', function() {
        if (!isGridView) {
            isGridView = true;
            $(this).addClass('active').siblings().removeClass('active');
            switchToGridView();
        }
    });

    $('#listViewBtn').on('click', function() {
        if (isGridView) {
            isGridView = false;
            $(this).addClass('active').siblings().removeClass('active');
            switchToListView();
        }
    });

    function searchCategories() {
        const query = $('#categorySearch').val().toLowerCase();
        const items = $('.category-item');
        let visibleCount = 0;

        items.each(function() {
            const name = $(this).data('name');
            if (name.includes(query)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        if (visibleCount === 0) {
            $('#categoriesContainer').hide();
            $('#noResults').show();
        } else {
            $('#categoriesContainer').show();
            $('#noResults').hide();
        }
    }

    function sortCategories() {
        const sortBy = $('#sortCategories').val();
        const container = $('#categoriesGrid');
        const items = container.children('.category-item').get();

        items.sort(function(a, b) {
            let aVal, bVal;
            
            switch(sortBy) {
                case 'name':
                    aVal = $(a).find('.card-title').text();
                    bVal = $(b).find('.card-title').text();
                    return aVal.localeCompare(bVal);
                    
                case 'products_count':
                    aVal = parseInt($(a).find('.text-muted').text().match(/\d+/)[0]);
                    bVal = parseInt($(b).find('.text-muted').text().match(/\d+/)[0]);
                    return bVal - aVal; // Descending order
                    
                case 'newest':
                    // Assuming newer items are added later in the DOM
                    return $(b).index() - $(a).index();
                    
                default:
                    return 0;
            }
        });

        container.empty().append(items);
    }

    function switchToGridView() {
        $('#categoriesGrid').removeClass('list-view').addClass('row');
        $('.category-item').removeClass('col-12').addClass('col-lg-3 col-md-4 col-sm-6');
        $('.category-card').removeClass('d-flex');
        $('.card-img-top').show();
    }

    function switchToListView() {
        $('#categoriesGrid').removeClass('row').addClass('list-view');
        $('.category-item').removeClass('col-lg-3 col-md-4 col-sm-6').addClass('col-12');
        $('.category-card').addClass('d-flex');
        $('.card-img-top').hide();
    }

    window.clearCategorySearch = function() {
        $('#categorySearch').val('');
        $('.category-item').show();
        $('#categoriesContainer').show();
        $('#noResults').hide();
    };

    // Category quick view
    window.showCategoryDetails = function(categoryId) {
        $.get(`/api/product-categories/${categoryId}`)
            .done(function(response) {
                const category = response.data;
                $('#categoryModalTitle').text(category.name);
                $('#categoryModalBody').html(`
                    <div class="row">
                        <div class="col-md-4">
                            ${category.image ? 
                                `<img src="${category.image_url}" alt="${category.name}" class="img-fluid rounded">` :
                                `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-tags text-muted fa-4x"></i>
                                </div>`
                            }
                        </div>
                        <div class="col-md-8">
                            <h5>${category.name}</h5>
                            <p class="text-muted">${category.description || '{{__("landingpage.no_description")}}'}</p>
                            <p><strong>{{__('landingpage.products')}}:</strong> ${category.products_count}</p>
                            ${category.subcategories && category.subcategories.length > 0 ? 
                                `<p><strong>{{__('landingpage.subcategories')}}:</strong> ${category.subcategories.map(sub => sub.name).join(', ')}</p>` : 
                                ''
                            }
                        </div>
                    </div>
                `);
                $('#viewCategoryProducts').attr('href', `/products/category/${category.slug}`);
                $('#categoryModal').modal('show');
            })
            .fail(function() {
                alert('{{__("landingpage.error_loading_category")}}');
            });
    };
});
</script>

<style>
.category-card {
    transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.featured-category {
    border: 2px solid #ffc107 !important;
}

.list-view .category-card {
    flex-direction: row !important;
}

.list-view .card-body {
    text-align: left !important;
}

.bg-gradient-primary {
    background: linear-gradient(45deg, #007bff, #0056b3);
}

@media (max-width: 768px) {
    .list-view .category-card {
        flex-direction: column !important;
    }
    
    .list-view .card-body {
        text-align: center !important;
    }
}
</style>
@endsection
