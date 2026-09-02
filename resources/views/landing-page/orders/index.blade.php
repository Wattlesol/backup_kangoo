@extends('landing-page.layouts.default')

@section('content')

<div class="section-padding">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3 mb-0">{{ __('messages.my_orders') }}</h2>
                    <a href="{{ route('store.unified') }}" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-2"></i>
                        Continue Shopping
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <order-list :user_id="{{ $user_id }}"></order-list>
            </div>
        </div>
    </div>
</div>

@endsection
