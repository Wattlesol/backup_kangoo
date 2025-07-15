@if($product)
    <div class="d-flex justify-content-end align-items-center">
        @if(auth()->user()->can('product view'))
            <button type="button" class="btn btn-sm btn-info me-2" onclick="viewProduct({{ $product->id }})" title="{{ __('messages.view') }}">
                <i class="fas fa-eye"></i>
            </button>
        @endif
        
        @if(auth()->user()->can('product edit'))
            <a href="{{ route('product.edit', $product->id) }}" class="btn btn-sm btn-primary me-2" title="{{ __('messages.edit') }}">
                <i class="fas fa-edit"></i>
            </a>
        @endif
        
        @if(auth()->user()->can('dynamic_pricing list') && $product->created_by_type === 'admin')
            <button type="button" class="btn btn-sm btn-warning me-2" onclick="managePricing({{ $product->id }})" title="{{ __('messages.dynamic_pricing') }}">
                <i class="fas fa-dollar-sign"></i>
            </button>
        @endif
        
        @if(auth()->user()->can('product delete'))
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteProduct({{ $product->id }})" title="{{ __('messages.delete') }}">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>

    <script>
        function viewProduct(id) {
            window.location.href = `/product/${id}`;
        }

        function managePricing(id) {
            window.location.href = `/dynamic-pricing?product_id=${id}`;
        }

        function deleteProduct(id) {
            if (confirm('{{ __("messages.confirm_delete") }}')) {
                $.ajax({
                    url: `/product/${id}`,
                    type: 'POST',
                    data: {
                        _method: 'DELETE',
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.status) {
                            window.renderedDataTable.ajax.reload();
                            showAlert('success', response.message);
                        } else {
                            showAlert('error', response.message);
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
