@if($product)
    <div class="d-flex justify-content-end align-items-center">
        <a href="{{ route('provider.product.show', $product->id) }}" class="btn btn-sm btn-info me-2" title="{{ __('messages.view') }}">
            <i class="fas fa-eye"></i>
        </a>
        
        <a href="{{ route('provider.product.edit', $product->id) }}" class="btn btn-sm btn-primary me-2" title="{{ __('messages.edit') }}">
            <i class="fas fa-edit"></i>
        </a>
        
        <button type="button" class="btn btn-sm btn-danger" onclick="deleteProviderProduct({{ $product->id }})" title="{{ __('messages.delete') }}">
            <i class="fas fa-trash"></i>
        </button>
    </div>

    <script>
        function deleteProviderProduct(id) {
            if (confirm('{{ __("messages.confirm_delete_product") }}')) {
                $.ajax({
                    url: `/provider/product/${id}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            showAlert('success', response.message || '{{ __("messages.product_deleted_successfully") }}');
                        } else {
                            showAlert('error', response.message || '{{ __("messages.something_went_wrong") }}');
                        }
                    },
                    error: function() {
                        showAlert('error', '{{ __("messages.something_went_wrong") }}');
                    }
                });
            }
        }
    </script>
@endif
