@extends('layouts.master')

@section('title')
    {{ $pageTitle }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3">
                        <h5 class="font-weight-bold">{{ $pageTitle }}</h5>
                        <div>
                            <a href="{{ route('provider.product.create') }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add New Product
                            </a>
                            <a href="{{ route('provider.store.products') }}" class="btn btn-sm btn-info">
                                <i class="fas fa-list"></i> View All Products
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Store Information -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Store Information</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-info-circle"></i> Single Store System</h5>
                                <p class="mb-0">You are now part of <strong>{{ $mainStore->name }}</strong>. All your products will be available in this unified store. Focus on creating and managing great products!</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h6 class="mb-2">Store Status</h6>
                                <span class="badge badge-{{ $mainStore->is_active ? 'success' : 'danger' }} badge-lg">
                                    {{ $mainStore->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Statistics -->
    <div class="row">
        <div class="col-lg-3 col-md-6">
            <div class="card card-block card-stretch card-height">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4 card-total-sale">
                        <div class="icon iq-icon-box-2 bg-info-light">
                            <i class="fas fa-box text-info"></i>
                        </div>
                        <div>
                            <p class="mb-2 text-secondary">Total Products</p>
                            <h4 class="font-weight-bold">{{ $stats['total_products'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-block card-stretch card-height">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4 card-total-sale">
                        <div class="icon iq-icon-box-2 bg-success-light">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div>
                            <p class="mb-2 text-secondary">Active Products</p>
                            <h4 class="font-weight-bold">{{ $stats['active_products'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-block card-stretch card-height">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4 card-total-sale">
                        <div class="icon iq-icon-box-2 bg-warning-light">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <div>
                            <p class="mb-2 text-secondary">Pending Approval</p>
                            <h4 class="font-weight-bold">{{ $stats['pending_products'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card card-block card-stretch card-height">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4 card-total-sale">
                        <div class="icon iq-icon-box-2 bg-danger-light">
                            <i class="fas fa-exclamation-triangle text-danger"></i>
                        </div>
                        <div>
                            <p class="mb-2 text-secondary">Low Stock</p>
                            <h4 class="font-weight-bold">{{ $stats['low_stock_products'] }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions and Recent Activity -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card card-block card-stretch">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Quick Actions</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('provider.product.create') }}" class="btn btn-outline-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                        <a href="{{ route('provider.store.products') }}" class="btn btn-outline-info">
                            <i class="fas fa-list"></i> Manage My Products
                        </a>
                        <a href="{{ route('provider.order.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-shopping-cart"></i> View My Orders
                        </a>
                        <a href="{{ url('/store') }}" target="_blank" class="btn btn-outline-secondary">
                            <i class="fas fa-external-link-alt"></i> Preview Store
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-block card-stretch">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">Getting Started</h4>
                    </div>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-1">Create Your First Product</h6>
                                <p class="mb-0 text-muted">Add products to start selling in the store</p>
                            </div>
                            @if($stats['total_products'] > 0)
                                <i class="fas fa-check-circle text-success"></i>
                            @else
                                <a href="{{ route('provider.product.create') }}" class="btn btn-sm btn-primary">Start</a>
                            @endif
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-1">Set Product Prices</h6>
                                <p class="mb-0 text-muted">Configure selling prices for your products</p>
                            </div>
                            @if($stats['active_products'] > 0)
                                <i class="fas fa-check-circle text-success"></i>
                            @else
                                <a href="{{ route('provider.store.products') }}" class="btn btn-sm btn-info">Manage</a>
                            @endif
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <h6 class="mb-1">Monitor Your Sales</h6>
                                <p class="mb-0 text-muted">Track orders and manage inventory</p>
                            </div>
                            <a href="{{ route('provider.order.index') }}" class="btn btn-sm btn-success">View</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($stats['pending_products'] > 0)
    <!-- Pending Products Alert -->
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-warning">
                <h5><i class="fas fa-clock"></i> Products Pending Approval</h5>
                <p class="mb-2">You have {{ $stats['pending_products'] }} product(s) waiting for admin approval.</p>
                <a href="{{ route('provider.store.products') }}?status=0" class="btn btn-sm btn-warning">
                    <i class="fas fa-eye"></i> View Pending Products
                </a>
            </div>
        </div>
    </div>
    @endif

    @if($stats['low_stock_products'] > 0)
    <!-- Low Stock Alert -->
    <div class="row">
        <div class="col-lg-12">
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle"></i> Low Stock Alert</h5>
                <p class="mb-2">{{ $stats['low_stock_products'] }} of your products are running low on stock (≤10 items).</p>
                <a href="{{ route('provider.store.products') }}" class="btn btn-sm btn-danger">
                    <i class="fas fa-boxes"></i> Manage Stock
                </a>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
