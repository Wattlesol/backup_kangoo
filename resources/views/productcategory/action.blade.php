@if($productCategory)
    <div class="d-flex justify-content-end align-items-center">
        @if(auth()->user()->can('product_category edit'))
            <a href="{{ route('productcategory.edit', $productCategory->id) }}" class="btn btn-sm btn-primary me-2" title="{{ __('messages.edit') }}">
                <i class="fas fa-edit"></i>
            </a>
        @endif
        
        @if(auth()->user()->can('product_category view'))
            <button type="button" class="btn btn-sm btn-info me-2" onclick="viewCategory({{ $productCategory->id }})" title="{{ __('messages.view') }}">
                <i class="fas fa-eye"></i>
            </button>
        @endif
        
        @if(auth()->user()->can('product_category delete'))
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteCategory({{ $productCategory->id }})" title="{{ __('messages.delete') }}">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>

    <script>
        function viewCategory(id) {
            // You can implement a modal view or redirect to a view page
            window.location.href = `/productcategory/${id}`;
        }

        function deleteCategory(id) {
            if (confirm('{{ __("messages.confirm_delete") }}')) {
                $.ajax({
                    url: `/productcategory/${id}`,
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
