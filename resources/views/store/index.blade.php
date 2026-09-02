<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? 'Store Management' }}</h5>
                            <div class="d-flex gap-2 align-items-center">
                                @if($store)
                                    <a href="{{ route('store.edit', $store->id) }}" class="btn btn-sm btn-primary">
                                        @php $isAr = app()->getLocale() === 'ar'; @endphp<i class="fa fa-edit"></i> {{ $isAr ? 'تعديل المتجر' : 'Edit Store' }}
                                    </a>
                                    <a href="{{ url('/store') }}" target="_blank" class="btn btn-sm btn-success">
                                        <i class="fa fa-external-link-alt"></i> {{ $isAr ? 'عرض المتجر' : 'View Store' }}
                                    </a>
                                @else
                                    <a href="{{ route('store.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-plus-circle"></i> {{ $isAr ? 'إنشاء متجر' : 'Create Store' }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($store)
        <!-- Store Information Card -->
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Store Name</label>
                                    <p class="form-control-plaintext">{{ $store->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <p class="form-control-plaintext">
                                        @if($store->is_active)
                                            <span class="badge bg-soft-success text-success">Active</span>
                                        @else
                                            <span class="badge bg-soft-danger text-danger">Inactive</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <p class="form-control-plaintext">{{ $store->description ?? 'No description provided' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <p class="form-control-plaintext">{{ $store->email ?? 'Not provided' }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <p class="form-control-plaintext">{{ $store->phone ?? 'Not provided' }}</p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label">Address</label>
                                    <p class="form-control-plaintext">{{ $store->address ?? 'Not provided' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center">
                            <label class="form-label">Store Logo</label>
                            <div class="store-logo-display">
                                @if($store->logo)
                                    <img src="{{ $store->logo }}" alt="Store Logo" class="img-fluid rounded" style="max-height: 200px;">
                                @else
                                    <div class="bg-soft-primary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fa fa-store fa-3x text-primary"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <div class="avatar-60 bg-soft-primary rounded">
                                <i class="fa fa-box fa-2x text-primary"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ $totalProducts ?? 0 }}</h4>
                        <p class="mb-0 text-muted">Total Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <div class="avatar-60 bg-soft-success rounded">
                                <i class="fa fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ $activeProducts ?? 0 }}</h4>
                        <p class="mb-0 text-muted">Active Products</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <div class="avatar-60 bg-soft-warning rounded">
                                <i class="fa fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ $pendingProducts ?? 0 }}</h4>
                        <p class="mb-0 text-muted">Pending Approval</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            <div class="avatar-60 bg-soft-info rounded">
                                <i class="fa fa-shopping-cart fa-2x text-info"></i>
                            </div>
                        </div>
                        <h4 class="mb-1">{{ $totalOrders ?? 0 }}</h4>
                        <p class="mb-0 text-muted">Total Orders</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="{{ route('product.index') }}" class="btn btn-outline-primary btn-block">
                            <i class="fa fa-box"></i> Manage Products
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('productcategory.index') }}" class="btn btn-outline-info btn-block">
                            <i class="fa fa-tags"></i> Manage Categories
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('order.index') }}" class="btn btn-outline-success btn-block">
                            <i class="fa fa-shopping-cart"></i> View Orders
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('product-approval.pending') }}" class="btn btn-outline-warning btn-block">
                            <i class="fa fa-clock"></i> Product Approvals
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Store Created -->
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-4">
                    <i class="fa fa-store fa-5x text-muted"></i>
                </div>
                <h4 class="mb-3">No Store Created</h4>
                <p class="text-muted mb-4">Create your store to start selling products and managing your e-commerce business.</p>
                <a href="{{ route('store.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus-circle"></i> {{ $isAr ? 'إنشاء متجر' : 'Create Store' }} Now
                </a>
            </div>
        </div>
    @endif

@section('bottom_script')
<style>
.avatar-60 {
    width: 60px;
    height: 60px;
    min-width: 60px;
}
.bg-soft-primary {
    background-color: rgba(108, 117, 125, 0.1);
}
.bg-soft-success {
    background-color: rgba(40, 167, 69, 0.1);
}
.bg-soft-info {
    background-color: rgba(23, 162, 184, 0.1);
}
.bg-soft-warning {
    background-color: rgba(255, 193, 7, 0.1);
}
.bg-soft-danger {
    background-color: rgba(220, 53, 69, 0.1);
}
</style>
@endsection
</x-master-layout>
