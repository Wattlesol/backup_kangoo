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
                            @if($store)
                                <a href="{{ route('store.edit', $store->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit Store
                                </a>
                                <a href="{{ url('/store') }}" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fas fa-external-link-alt"></i> View Store
                                </a>
                            @else
                                <a href="{{ route('store.create') }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-plus"></i> Create Store
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($store)
        <!-- Store Statistics -->
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
                                <p class="mb-2 text-secondary">Pending Products</p>
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
                            <div class="icon iq-icon-box-2 bg-primary-light">
                                <i class="fas fa-users text-primary"></i>
                            </div>
                            <div>
                                <p class="mb-2 text-secondary">Total Providers</p>
                                <h4 class="font-weight-bold">{{ $stats['total_providers'] }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Information -->
        <div class="row">
            <div class="col-lg-8">
                <div class="card card-block card-stretch">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Store Information</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>Store Name:</strong></label>
                                    <p class="mb-0">{{ $store->name }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>Status:</strong></label>
                                    <p class="mb-0">
                                        <span class="badge badge-{{ $store->is_active ? 'success' : 'danger' }}">
                                            {{ $store->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label"><strong>Description:</strong></label>
                                    <p class="mb-0">{{ $store->description }}</p>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label"><strong>Address:</strong></label>
                                    <p class="mb-0">{{ $store->address }}</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>Delivery Radius:</strong></label>
                                    <p class="mb-0">{{ $store->delivery_radius }} km</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label"><strong>Delivery Fee:</strong></label>
                                    <p class="mb-0">${{ $store->delivery_fee }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card card-block card-stretch">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">Quick Actions</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('product.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-box"></i> Manage Products
                            </a>
                            <a href="{{ route('productcategory.index') }}" class="btn btn-outline-info">
                                <i class="fas fa-tags"></i> Manage Categories
                            </a>
                            <a href="{{ route('order.index') }}" class="btn btn-outline-success">
                                <i class="fas fa-shopping-cart"></i> View Orders
                            </a>
                            <a href="{{ url('/store') }}" target="_blank" class="btn btn-outline-secondary">
                                <i class="fas fa-external-link-alt"></i> Preview Store
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- No Store Created -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-store fa-5x text-muted"></i>
                        </div>
                        <h4 class="mb-3">No Store Created</h4>
                        <p class="text-muted mb-4">You need to create the main store before providers can add products.</p>
                        <a href="{{ route('store.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Create Main Store
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
