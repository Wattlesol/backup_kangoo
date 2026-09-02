@extends('layouts.master')

@section('title')
    {{ $pageTitle }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">{{ $pageTitle }}</h4>
                    </div>
                    <div class="card-action">
                        <a href="{{ route('store.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Store
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('store.store') }}" enctype="multipart/form-data">
                        @csrf
                        @if($storedata->id)
                            <input type="hidden" name="id" value="{{ $storedata->id }}">
                        @endif

                        <!-- Basic Information -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="name">Store Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="{{ old('name', $storedata->name) }}" required>
                                    @error('name')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="phone">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" 
                                           value="{{ old('phone', $storedata->phone) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="description">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required>{{ old('description', $storedata->description) }}</textarea>
                                    @error('description')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="address">Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required>{{ old('address', $storedata->address) }}</textarea>
                                    @error('address')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Settings -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="delivery_radius">Delivery Radius (km)</label>
                                    <input type="number" class="form-control" id="delivery_radius" name="delivery_radius" 
                                           value="{{ old('delivery_radius', $storedata->delivery_radius ?? 50) }}" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="minimum_order_amount">Minimum Order Amount ($)</label>
                                    <input type="number" class="form-control" id="minimum_order_amount" name="minimum_order_amount" 
                                           value="{{ old('minimum_order_amount', $storedata->minimum_order_amount ?? 0) }}" step="0.01">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label" for="delivery_fee">Delivery Fee ($)</label>
                                    <input type="number" class="form-control" id="delivery_fee" name="delivery_fee" 
                                           value="{{ old('delivery_fee', $storedata->delivery_fee ?? 5) }}" step="0.01">
                                </div>
                            </div>
                        </div>

                        <!-- Store Settings -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Store Settings</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="allow_reviews" 
                                               name="store_settings[allow_reviews]" value="1"
                                               {{ old('store_settings.allow_reviews', $storedata->store_settings['allow_reviews'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="allow_reviews">Allow Reviews</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="auto_approve_products" 
                                               name="store_settings[auto_approve_products]" value="1"
                                               {{ old('store_settings.auto_approve_products', $storedata->store_settings['auto_approve_products'] ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="auto_approve_products">Auto Approve Products</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_active" 
                                               name="is_active" value="1"
                                               {{ old('is_active', $storedata->is_active ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="is_active">Store Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Methods -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Payment Methods</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="cash_on_delivery" 
                                               name="payment_methods[cash_on_delivery]" value="1"
                                               {{ old('payment_methods.cash_on_delivery', $storedata->payment_methods['cash_on_delivery'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="cash_on_delivery">Cash on Delivery</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="credit_card" 
                                               name="payment_methods[credit_card]" value="1"
                                               {{ old('payment_methods.credit_card', $storedata->payment_methods['credit_card'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="credit_card">Credit Card</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="paypal" 
                                               name="payment_methods[paypal]" value="1"
                                               {{ old('payment_methods.paypal', $storedata->payment_methods['paypal'] ?? false) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="paypal">PayPal</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Policies -->
                        <div class="row">
                            <div class="col-md-12">
                                <h5 class="mb-3">Store Policies</h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="form-label" for="terms_and_conditions">Terms and Conditions</label>
                                    <textarea class="form-control" id="terms_and_conditions" name="terms_and_conditions" rows="3">{{ old('terms_and_conditions', $storedata->terms_and_conditions) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="privacy_policy">Privacy Policy</label>
                                    <textarea class="form-control" id="privacy_policy" name="privacy_policy" rows="3">{{ old('privacy_policy', $storedata->privacy_policy) }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="return_policy">Return Policy</label>
                                    <textarea class="form-control" id="return_policy" name="return_policy" rows="3">{{ old('return_policy', $storedata->return_policy) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> {{ $storedata->id ? 'Update Store' : 'Create Store' }}
                                    </button>
                                    <a href="{{ route('store.index') }}" class="btn btn-secondary ml-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
