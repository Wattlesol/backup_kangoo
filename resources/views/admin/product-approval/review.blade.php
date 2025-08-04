<x-master-layout>
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">Review Product: {{ $product->name ?? 'Product' }}</h4>
                            <p class="text-muted mb-0">
                                Submitted by:
                                @if($product->provider)
                                    <strong>{{ $product->provider->display_name }}</strong>
                                @else
                                    <strong>Admin</strong>
                                @endif
                                on {{ $product->created_at->format('M d, Y') }}
                            </p>
                        </div>
                        <div>
                            <a href="{{ route('product-approval.pending') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Pending
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Product Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <!-- Product Images -->
                    @if($product->featured_image || $product->gallery_images)
                    <div class="mb-4">
                        <h6>Product Images</h6>
                        <div class="row">
                            @if($product->featured_image)
                            <div class="col-md-6 mb-3">
                                <div class="border rounded p-2">
                                    <img src="{{ $product->featured_image }}" alt="Featured Image"
                                         class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                                    <small class="text-muted d-block mt-1">Featured Image</small>
                                </div>
                            </div>
                            @endif

                            @if($product->gallery_images)
                                @foreach(json_decode($product->gallery_images, true) ?? [] as $image)
                                <div class="col-md-6 mb-3">
                                    <div class="border rounded p-2">
                                        <img src="{{ $image }}" alt="Gallery Image"
                                             class="img-fluid rounded" style="max-height: 200px; width: 100%; object-fit: cover;">
                                        <small class="text-muted d-block mt-1">Gallery Image</small>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Basic Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Basic Information</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $product->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>SKU:</strong></td>
                                    <td>{{ $product->sku }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Category:</strong></td>
                                    <td>
                                        @if($product->category)
                                            <span class="badge badge-info">{{ $product->category->name }}</span>
                                        @else
                                            <span class="text-muted">No Category</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Price:</strong></td>
                                    <td><strong class="text-success">${{ number_format($product->price, 2) }}</strong></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Inventory & Status</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Stock Quantity:</strong></td>
                                    <td>{{ $product->stock_quantity ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        @if($product->status)
                                            <span class="badge badge-success">Active</span>
                                        @else
                                            <span class="badge badge-secondary">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Approval Status:</strong></td>
                                    <td>
                                        @switch($product->approval_status ?? 'pending')
                                            @case('approved')
                                                <span class="badge badge-success">Approved</span>
                                                @break
                                            @case('rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                                @break
                                            @default
                                                <span class="badge badge-warning">Pending Review</span>
                                        @endswitch
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Description -->
                    @if($product->description)
                    <div class="mb-4">
                        <h6>Description</h6>
                        <div class="border rounded p-3 bg-light">
                            {!! nl2br(e($product->description)) !!}
                        </div>
                    </div>
                    @endif

                    <!-- Additional Details -->
                    @if($product->short_description)
                    <div class="mb-4">
                        <h6>Short Description</h6>
                        <div class="border rounded p-3 bg-light">
                            {!! nl2br(e($product->short_description)) !!}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Approval Actions -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Approval Actions</h5>
                </div>
                <div class="card-body">
                    @if(($product->approval_status ?? 'pending') === 'pending')
                    <!-- Approve Button -->
                    <form action="{{ route('product-approval.approve', $product->id) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-check"></i> Approve Product
                        </button>
                    </form>

                    <!-- Reject Form -->
                    <form action="{{ route('product-approval.reject', $product->id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="rejection_reason">Rejection Reason</label>
                            <textarea class="form-control" id="rejection_reason" name="rejection_reason"
                                      rows="4" required placeholder="Please provide a detailed reason for rejection..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block btn-lg">
                            <i class="fas fa-times"></i> Reject Product
                        </button>
                    </form>
                    @else
                    <div class="alert alert-info">
                        <h6>Product Already Reviewed</h6>
                        <p class="mb-0">
                            This product has been
                            @if($product->approval_status === 'approved')
                                <strong class="text-success">approved</strong>
                            @else
                                <strong class="text-danger">rejected</strong>
                            @endif
                            on {{ $product->approved_at ? $product->approved_at->format('M d, Y') : 'N/A' }}.
                        </p>

                        @if($product->approval_status === 'rejected' && $product->rejection_reason)
                        <hr>
                        <small><strong>Rejection Reason:</strong></small>
                        <p class="small mb-0">{{ $product->rejection_reason }}</p>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Provider Information -->
            @if($product->provider)
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Provider Information</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        @if($product->provider->profile_image)
                            <img src="{{ $product->provider->profile_image }}" alt="Provider"
                                 class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                        @else
                            <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center text-white"
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-lg"></i>
                            </div>
                        @endif
                    </div>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>{{ $product->provider->display_name }}</td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td>{{ $product->provider->email }}</td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>{{ $product->provider->contact_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Joined:</strong></td>
                            <td>{{ $product->provider->created_at->format('M Y') }}</td>
                        </tr>
                    </table>
                    <a href="{{ route('provider.show', $product->provider->id) }}" class="btn btn-sm btn-outline-primary btn-block">
                        View Provider Profile
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
</x-master-layout>
