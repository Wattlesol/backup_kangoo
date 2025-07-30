<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.product_details') }}</h5>
                            <div class="d-flex gap-2">
                                <a href="{{ route('provider.product.index') }}" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                                </a>
                                <a href="{{ route('provider.product.edit', $product->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fa fa-edit"></i> {{ __('messages.edit') }}
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteProduct()">
                                    <i class="fa fa-trash"></i> {{ __('messages.delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Product Information -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.product_information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.name') }}:</label>
                                <p>{{ $product->name }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.sku') }}:</label>
                                <p>{{ $product->sku }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.category') }}:</label>
                                <p>{{ $product->category->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.price') }}:</label>
                                <p class="h5 text-primary">${{ number_format($product->base_price, 2) }}</p>
                            </div>
                        </div>
                        @if($product->weight)
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.weight') }}:</label>
                                <p>{{ $product->weight }} kg</p>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.stock_quantity') }}:</label>
                                <p class="badge badge-{{ $product->stock_quantity > 0 ? 'success' : 'danger' }}">
                                    {{ $product->stock_quantity }} {{ __('messages.units') }}
                                </p>
                            </div>
                        </div>
                        @if($product->description)
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.description') }}:</label>
                                <p>{{ $product->description }}</p>
                            </div>
                        </div>
                        @endif
                        @if($product->short_description)
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="font-weight-bold">{{ __('messages.short_description') }}:</label>
                                <p>{{ $product->short_description }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Status & Images -->
        <div class="col-lg-4">
            <!-- Approval Status -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.approval_status') }}</h5>
                </div>
                <div class="card-body">
                    <div class="text-center">
                        <div class="mb-3">
                            @if($product->approval_status == 'approved')
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                                <h6 class="text-success mt-2">{{ __('messages.approved') }}</h6>
                            @elseif($product->approval_status == 'rejected')
                                <i class="fas fa-times-circle text-danger" style="font-size: 3rem;"></i>
                                <h6 class="text-danger mt-2">{{ __('messages.rejected') }}</h6>
                            @else
                                <i class="fas fa-clock text-warning" style="font-size: 3rem;"></i>
                                <h6 class="text-warning mt-2">{{ __('messages.pending_approval') }}</h6>
                            @endif
                        </div>
                        
                        @if($product->approval_status == 'rejected' && $product->rejection_reason)
                            <div class="alert alert-danger">
                                <strong>{{ __('messages.rejection_reason') }}:</strong><br>
                                {{ $product->rejection_reason }}
                            </div>
                        @endif
                        
                        @if($product->approval_status == 'pending')
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                {{ __('messages.product_pending_approval_message') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Product Images -->
            @if($product->main_image)
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.product_image') }}</h5>
                </div>
                <div class="card-body text-center">
                    <img src="{{ $product->main_image }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 300px;">
                </div>
            </div>
            @endif

            <!-- Product Statistics -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('messages.statistics') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-right">
                                <h4 class="text-primary">{{ $product->created_at->format('M d, Y') }}</h4>
                                <p class="text-muted mb-0">{{ __('messages.created') }}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-{{ $product->status ? 'success' : 'danger' }}">
                                {{ $product->status ? __('messages.active') : __('messages.inactive') }}
                            </h4>
                            <p class="text-muted mb-0">{{ __('messages.status') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@section('bottom_script')
<script>
    function deleteProduct() {
        if (confirm('{{ __("messages.confirm_delete_product") }}')) {
            $.ajax({
                url: "{{ route('provider.product.destroy', $product->id) }}",
                type: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status) {
                        window.location.href = "{{ route('provider.product.index') }}";
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
@endsection
</x-master-layout>
