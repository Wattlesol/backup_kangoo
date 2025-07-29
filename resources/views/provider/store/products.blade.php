<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.store_products') }}</h5>
                            <div class="d-flex gap-2">
                                <a href="{{ route('provider.store.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-arrow-left"></i> {{ __('messages.back_to_store') }}
                                </a>
                                @if($store->status === 'approved')
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                        <i class="fa fa-plus-circle"></i> {{ __('messages.add_product') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($store->status !== 'approved')
            <div class="row">
                <div class="col-lg-12">
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle"></i>
                        {{ __('messages.store_must_be_approved_to_add_products') }}
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <select class="form-control" id="column_is_available">
                                    <option value="">{{ __('messages.all_products') }}</option>
                                    <option value="1">{{ __('messages.available') }}</option>
                                    <option value="0">{{ __('messages.unavailable') }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="datatable" class="table table-striped" data-toggle="data-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('messages.product') }}</th>
                                        <th>{{ __('messages.category') }}</th>
                                        <th>{{ __('messages.base_price') }}</th>
                                        <th>{{ __('messages.store_price') }}</th>
                                        <th>{{ __('messages.stock') }}</th>
                                        <th>{{ __('messages.status') }}</th>
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

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addProductModalLabel">{{ __('messages.add_product_to_store') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addProductForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="form-group col-md-12">
                                <label for="product_id" class="form-label">{{ __('messages.select_product') }} <span class="text-danger">*</span></label>
                                <select class="form-control select2js" id="product_id" name="product_id" required>
                                    <option value="">{{ __('messages.select_product') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="store_price" class="form-label">{{ __('messages.store_price') }} <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="store_price" name="store_price" step="0.01" min="0" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="stock_quantity" class="form-label">{{ __('messages.stock_quantity') }} <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="minimum_order_quantity" class="form-label">{{ __('messages.minimum_order_quantity') }}</label>
                                <input type="number" class="form-control" id="minimum_order_quantity" name="minimum_order_quantity" min="1">
                            </div>
                            <div class="form-group col-md-6">
                                <label for="maximum_order_quantity" class="form-label">{{ __('messages.maximum_order_quantity') }}</label>
                                <input type="number" class="form-control" id="maximum_order_quantity" name="maximum_order_quantity" min="1">
                            </div>
                            <div class="form-group col-md-12">
                                <label for="store_notes" class="form-label">{{ __('messages.store_notes') }}</label>
                                <textarea class="form-control" id="store_notes" name="store_notes" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('messages.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('messages.add_product') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize DataTable
            window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('provider.store.products_data') }}",
                    data: function(d) {
                        d.filter = {
                            is_available: $('#column_is_available').val()
                        };
                    }
                },
                columns: [
                    {data: 'product_name', name: 'product.name'},
                    {data: 'category', name: 'product.category.name'},
                    {data: 'base_price', name: 'product.base_price'},
                    {data: 'store_price', name: 'store_price'},
                    {data: 'stock_quantity', name: 'stock_quantity'},
                    {data: 'is_available', name: 'is_available'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            // Filter change event
            $('#column_is_available').on('change', function() {
                window.renderedDataTable.ajax.reload();
            });

            // Load available products for modal
            $('#addProductModal').on('show.bs.modal', function() {
                $.ajax({
                    url: "{{ route('provider.product.available') }}",
                    type: 'GET',
                    success: function(data) {
                        $('#product_id').empty().append('<option value="">{{ __("messages.select_product") }}</option>');
                        $.each(data, function(key, value) {
                            $('#product_id').append('<option value="' + value.id + '">' + value.name + ' - $' + value.base_price + '</option>');
                        });
                    }
                });
            });

            // Add product form submission
            $('#addProductForm').on('submit', function(e) {
                e.preventDefault();
                
                $.ajax({
                    url: "{{ route('provider.store.add-product') }}",
                    type: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            $('#addProductModal').modal('hide');
                            $('#addProductForm')[0].reset();
                            window.renderedDataTable.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    }
                });
            });
        });

        // Update product function
        function updateProduct(id) {
            // Implementation for updating product
            console.log('Update product:', id);
        }

        // Remove product function
        function removeProduct(id) {
            if (confirm('{{ __("messages.are_you_sure") }}')) {
                $.ajax({
                    url: "{{ route('provider.store.remove-product', '') }}/" + id,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('{{ __("messages.something_went_wrong") }}');
                    }
                });
            }
        }
    </script>
    @endsection
</x-master-layout>
