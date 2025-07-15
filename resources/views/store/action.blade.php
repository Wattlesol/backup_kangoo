@if($store)
    <div class="d-flex justify-content-end align-items-center">
        @if(auth()->user()->can('store view'))
            <button type="button" class="btn btn-sm btn-info me-2" onclick="viewStore({{ $store->id }})" title="{{ __('messages.view') }}">
                <i class="fas fa-eye"></i>
            </button>
        @endif
        
        @if(auth()->user()->can('store edit'))
            <a href="{{ route('store.edit', $store->id) }}" class="btn btn-sm btn-primary me-2" title="{{ __('messages.edit') }}">
                <i class="fas fa-edit"></i>
            </a>
        @endif
        
        @if(auth()->user()->can('store approve'))
            @if($store->status === 'pending')
                <button type="button" class="btn btn-sm btn-success me-2" onclick="approveStore({{ $store->id }})" title="{{ __('messages.approve') }}">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" class="btn btn-sm btn-danger me-2" onclick="rejectStore({{ $store->id }})" title="{{ __('messages.reject') }}">
                    <i class="fas fa-times"></i>
                </button>
            @elseif($store->status === 'approved')
                <button type="button" class="btn btn-sm btn-warning me-2" onclick="suspendStore({{ $store->id }})" title="{{ __('messages.suspend') }}">
                    <i class="fas fa-pause"></i>
                </button>
            @elseif($store->status === 'suspended')
                <button type="button" class="btn btn-sm btn-success me-2" onclick="approveStore({{ $store->id }})" title="{{ __('messages.reactivate') }}">
                    <i class="fas fa-play"></i>
                </button>
            @endif
        @endif
        
        @if(auth()->user()->can('store delete'))
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteStore({{ $store->id }})" title="{{ __('messages.delete') }}">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>

    <script>
        function viewStore(id) {
            window.location.href = `/store/${id}`;
        }

        function approveStore(id) {
            if (confirm('{{ __("messages.confirm_approve_store") }}')) {
                updateStoreStatus(id, 'approved');
            }
        }

        function rejectStore(id) {
            const reason = prompt('{{ __("messages.rejection_reason") }}:');
            if (reason !== null) {
                updateStoreStatus(id, 'rejected', reason);
            }
        }

        function suspendStore(id) {
            const reason = prompt('{{ __("messages.suspension_reason") }}:');
            if (reason !== null) {
                updateStoreStatus(id, 'suspended', reason);
            }
        }

        function updateStoreStatus(id, status, reason = null) {
            $.ajax({
                url: '{{ route("store.approve") }}',
                type: 'POST',
                data: {
                    store_id: id,
                    status: status,
                    reason: reason,
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

        function deleteStore(id) {
            if (confirm('{{ __("messages.confirm_delete") }}')) {
                $.ajax({
                    url: `/store/${id}`,
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
