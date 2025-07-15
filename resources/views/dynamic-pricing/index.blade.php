<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.dynamic_pricing_management') }}</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-info" onclick="showAnalytics()">
                                    <i class="fas fa-chart-bar"></i> {{ __('messages.analytics') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="showPriceComparison()">
                                    <i class="fas fa-balance-scale"></i> {{ __('messages.price_comparison') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between">
                <div>
                    <div class="col-md-12">
                        <form action="{{ route('dynamic-pricing.bulk-update') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="activate">{{ __('messages.activate_pricing') }}</option>
                                <option value="deactivate">{{ __('messages.deactivate_pricing') }}</option>
                                <option value="set_type">{{ __('messages.set_pricing_type') }}</option>
                            </select>

                            <div class="select-status d-none quick-action-field" id="set-type-action" style="width:100%">
                                <select name="price_override_type" class="form-control select2" id="price_override_type">
                                    <option value="lowest">{{ __('messages.lowest_price') }}</option>
                                    <option value="highest">{{ __('messages.highest_price') }}</option>
                                    <option value="fixed">{{ __('messages.fixed_price') }}</option>
                                </select>
                            </div>

                            <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                    data-size="small" data-type="form" data-container="#quick-action-form"
                                    data-title="{{ __('messages.are_you_sure') }}" disabled>{{ __('messages.apply') }}</button>
                        </form>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="form-group">
                        <select class="form-control select2" id="category_filter">
                            <option value="">{{ __('messages.all_categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $filter['category_id'] == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="pricing_status_filter">
                            <option value="">{{ __('messages.all_products') }}</option>
                            <option value="active" {{ $filter['pricing_status'] == 'active' ? 'selected' : '' }}>{{ __('messages.active_pricing') }}</option>
                            <option value="inactive" {{ $filter['pricing_status'] == 'inactive' ? 'selected' : '' }}>{{ __('messages.inactive_pricing') }}</option>
                        </select>
                    </div>
                </div>
            </div>
            </div>

            <!-- Data Table -->
            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                            <th>{{ __('messages.product') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.base_price') }}</th>
                            <th>{{ __('messages.admin_override') }}</th>
                            <th>{{ __('messages.effective_price') }}</th>
                            <th>{{ __('messages.store_prices') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th>{{ __('messages.type') }}</th>
                            <th>{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pricing Modal -->
<div class="modal fade" id="pricingModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dynamic Pricing Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="pricingForm">
                    <input type="hidden" id="product_id" name="product_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 id="product_name"></h6>
                            <p class="text-muted" id="product_category"></p>
                        </div>
                        <div class="col-md-6">
                            <div class="pricing-summary">
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Base Price</small>
                                        <div class="h6" id="base_price"></div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Current Final Price</small>
                                        <div class="h6 text-primary" id="final_price"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="admin_price_active" name="admin_price_active">
                                <label class="form-check-label" for="admin_price_active">
                                    Enable Dynamic Pricing
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="pricing_options" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="admin_override_price" class="form-label">Admin Override Price</label>
                                <input type="number" class="form-control" id="admin_override_price" name="admin_override_price" step="0.01" min="0">
                                <small class="text-muted">Leave empty to use automatic pricing</small>
                            </div>
                            <div class="col-md-6">
                                <label for="price_override_type" class="form-label">Pricing Strategy</label>
                                <select class="form-control" id="price_override_type" name="price_override_type">
                                    <option value="lowest">Always Lowest Price</option>
                                    <option value="highest">Always Highest Price</option>
                                    <option value="fixed">Fixed Override Price</option>
                                </select>
                            </div>
                        </div>

                        <div class="pricing-explanation">
                            <div class="alert alert-info">
                                <h6>Pricing Strategy Explanation:</h6>
                                <ul class="mb-0">
                                    <li><strong>Lowest Price:</strong> Admin price will always be lower than or equal to the lowest store price</li>
                                    <li><strong>Highest Price:</strong> Admin price will always be higher than or equal to the highest store price</li>
                                    <li><strong>Fixed Price:</strong> Admin will use the exact override price specified above</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Store Prices Analysis -->
                    <div class="store-analysis mt-4">
                        <h6>Store Prices Analysis</h6>
                        <div class="row" id="price_analysis">
                            <!-- Will be populated via AJAX -->
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Store</th>
                                        <th>Store Price</th>
                                        <th>Stock</th>
                                        <th>Final Customer Price</th>
                                    </tr>
                                </thead>
                                <tbody id="store_prices_table">
                                    <!-- Will be populated via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="savePricing()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Analytics Modal -->
<div class="modal fade" id="analyticsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dynamic Pricing Analytics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="analytics_content">
                    <!-- Will be populated via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('bottom_script')
<script>
$(document).ready(function() {
    // Initialize DataTable
    window.renderedDataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('dynamic-pricing.index_data') }}",
            data: function(d) {
                d.filter = {
                    category_id: $('#category_filter').val(),
                    pricing_status: $('#pricing_status_filter').val()
                };
            }
        },
        columns: [
            {data: 'check', name: 'check', orderable: false, searchable: false},
            {data: 'name', name: 'name'},
            {data: 'category', name: 'category'},
            {data: 'base_price', name: 'base_price'},
            {data: 'admin_override_price', name: 'admin_override_price'},
            {data: 'effective_price', name: 'effective_price'},
            {data: 'store_prices', name: 'store_prices', orderable: false},
            {data: 'pricing_status', name: 'pricing_status'},
            {data: 'price_override_type', name: 'price_override_type'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    // Show/hide bulk actions based on selection
    $('#datatable').on('change', 'input[type="checkbox"]', function() {
        const checkedCount = $('#datatable input[type="checkbox"]:checked').length;
        if (checkedCount > 0) {
            $('.bulk-actions').show();
        } else {
            $('.bulk-actions').hide();
        }
    });

    // Dynamic pricing toggle
    $('#admin_price_active').on('change', function() {
        if ($(this).is(':checked')) {
            $('#pricing_options').show();
        } else {
            $('#pricing_options').hide();
        }
    });

    $('#category_filter, #pricing_status_filter').on('change', function() {
        window.renderedDataTable.ajax.reload();
    });
});



function openPricingModal(productId) {
    $.get(`/dynamic-pricing/${productId}`)
        .done(function(response) {
            if (response.status) {
                const product = response.data.product;
                const analysis = response.data.analysis;

                // Populate modal
                $('#product_id').val(product.id);
                $('#product_name').text(product.name);
                $('#product_category').text(product.category ? product.category.name : 'No category');
                $('#base_price').text('$' + product.base_price);
                $('#final_price').text('$' + analysis.final_price);

                $('#admin_price_active').prop('checked', product.admin_price_active);
                $('#admin_override_price').val(product.admin_override_price);
                $('#price_override_type').val(product.price_override_type);

                if (product.admin_price_active) {
                    $('#pricing_options').show();
                } else {
                    $('#pricing_options').hide();
                }

                // Populate analysis
                let analysisHtml = '';
                if (analysis.store_count > 0) {
                    analysisHtml = `
                        <div class="col-md-3">
                            <small class="text-muted">Lowest Store Price</small>
                            <div class="h6 text-success">$${analysis.lowest_store_price}</div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Highest Store Price</small>
                            <div class="h6 text-warning">$${analysis.highest_store_price}</div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Average Store Price</small>
                            <div class="h6">$${analysis.average_store_price.toFixed(2)}</div>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted">Stores Count</small>
                            <div class="h6">${analysis.store_count}</div>
                        </div>
                    `;
                } else {
                    analysisHtml = '<div class="col-12"><p class="text-muted">No store prices available</p></div>';
                }
                $('#price_analysis').html(analysisHtml);

                // Populate store prices table
                let storeTableHtml = '';
                product.store_products.forEach(function(sp) {
                    storeTableHtml += `
                        <tr>
                            <td>${sp.store.name}</td>
                            <td>$${sp.store_price}</td>
                            <td>${sp.stock_quantity}</td>
                            <td>$${sp.final_price}</td>
                        </tr>
                    `;
                });
                $('#store_prices_table').html(storeTableHtml);

                $('#pricingModal').modal('show');
            }
        })
        .fail(function() {
            alert('Failed to load product pricing data');
        });
}

function savePricing() {
    const formData = $('#pricingForm').serialize();

    $.post('/dynamic-pricing/update', formData)
        .done(function(response) {
            if (response.status) {
                $('#pricingModal').modal('hide');
                window.renderedDataTable.ajax.reload();
                showAlert('success', response.message);
            } else {
                showAlert('error', response.message);
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to update pricing');
        });
}

function bulkAction(action) {
    const selectedIds = [];
    $('#datatable input[type="checkbox"]:checked').each(function() {
        if ($(this).val() !== 'on') {
            selectedIds.push($(this).val());
        }
    });

    if (selectedIds.length === 0) {
        alert('Please select products to update');
        return;
    }

    $.post('/dynamic-pricing/bulk-update', {
        product_ids: selectedIds,
        action: action,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status) {
            window.renderedDataTable.ajax.reload();
            $('.bulk-actions').hide();
            showAlert('success', response.message);
        } else {
            showAlert('error', response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to perform bulk action');
    });
}

function bulkSetType(type) {
    const selectedIds = [];
    $('#datatable input[type="checkbox"]:checked').each(function() {
        if ($(this).val() !== 'on') {
            selectedIds.push($(this).val());
        }
    });

    if (selectedIds.length === 0) {
        alert('Please select products to update');
        return;
    }

    $.post('/dynamic-pricing/bulk-update', {
        product_ids: selectedIds,
        action: 'set_type',
        price_override_type: type,
        _token: $('meta[name="csrf-token"]').attr('content')
    })
    .done(function(response) {
        if (response.status) {
            window.renderedDataTable.ajax.reload();
            $('.bulk-actions').hide();
            showAlert('success', response.message);
        } else {
            showAlert('error', response.message);
        }
    })
    .fail(function() {
        showAlert('error', 'Failed to update pricing type');
    });
}

function showAnalytics() {
    $.get('/dynamic-pricing/analytics')
        .done(function(response) {
            let analyticsHtml = `
                <div class="row">
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-primary">${response.total_products}</h3>
                                <p class="mb-0">Total Products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-success">${response.active_dynamic_pricing}</h3>
                                <p class="mb-0">Dynamic Pricing Active</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-info">${response.products_with_store_prices}</h3>
                                <p class="mb-0">With Store Prices</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-center">
                            <div class="card-body">
                                <h3 class="text-warning">$${response.average_price_difference}</h3>
                                <p class="mb-0">Avg Price Difference</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Pricing Strategy Distribution</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h4 class="text-info">${response.pricing_types.lowest}</h4>
                                        <p class="mb-0">Lowest Price Strategy</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h4 class="text-warning">${response.pricing_types.highest}</h4>
                                        <p class="mb-0">Highest Price Strategy</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <h4 class="text-primary">${response.pricing_types.fixed}</h4>
                                        <p class="mb-0">Fixed Price Strategy</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('#analytics_content').html(analyticsHtml);
            $('#analyticsModal').modal('show');
        })
        .fail(function() {
            alert('Failed to load analytics data');
        });
}

function showPriceComparison() {
    // This would show a modal for selecting products to compare
    alert('Price comparison tool - to be implemented');
}

function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    $('body').prepend(alertHtml);

    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection
</x-master-layout>
