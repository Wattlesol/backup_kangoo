@extends('layouts.master')

@section('title')
    {{ $pageTitle ?? trans('messages.store') }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">{{ $pageTitle ?? trans('messages.store_details') }}</h4>
                    </div>
                    <div class="header-action">
                        <a href="{{ route('store.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> {{ trans('messages.back') }}
                        </a>
                        @if(auth()->user()->can('store edit'))
                            <a href="{{ route('store.create', ['id' => $store->id]) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> {{ trans('messages.edit') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="store-image">
                                @if($store->getFirstMediaUrl('store_image'))
                                    <img src="{{ $store->getFirstMediaUrl('store_image') }}" 
                                         alt="{{ $store->name }}" 
                                         class="img-fluid rounded" 
                                         style="max-height: 400px;">
                                @else
                                    <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center rounded" 
                                         style="height: 300px;">
                                        <i class="fas fa-store fa-3x text-muted"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="store-details">
                                <h3>{{ $store->name }}</h3>
                                <p class="text-muted mb-3">{{ $store->description }}</p>
                                
                                <div class="store-info">
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.provider') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @if($store->provider)
                                                <span class="badge badge-info">{{ $store->provider->display_name }}</span>
                                            @else
                                                <span class="text-muted">{{ trans('messages.no_provider') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.phone') }}:</strong></div>
                                        <div class="col-sm-8">{{ $store->phone ?? '-' }}</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.address') }}:</strong></div>
                                        <div class="col-sm-8">{{ $store->address ?? '-' }}</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.status') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'warning',
                                                    'approved' => 'success',
                                                    'rejected' => 'danger',
                                                    'suspended' => 'secondary'
                                                ];
                                                $color = $statusColors[$store->status] ?? 'secondary';
                                            @endphp
                                            <span class="badge badge-{{ $color }}">{{ ucfirst($store->status) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.active') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @if($store->is_active)
                                                <span class="badge badge-success">{{ trans('messages.active') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ trans('messages.inactive') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.delivery_radius') }}:</strong></div>
                                        <div class="col-sm-8">{{ $store->delivery_radius ?? '-' }} km</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.minimum_order') }}:</strong></div>
                                        <div class="col-sm-8">{{ $store->minimum_order_amount ? getPriceFormat($store->minimum_order_amount) : '-' }}</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.delivery_fee') }}:</strong></div>
                                        <div class="col-sm-8">{{ $store->delivery_fee ? getPriceFormat($store->delivery_fee) : '-' }}</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.products_count') }}:</strong></div>
                                        <div class="col-sm-8">
                                            <span class="badge badge-primary">{{ $store->products_count ?? $store->products()->count() }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.created_at') }}:</strong></div>
                                        <div class="col-sm-8">{{ dateAgoFormate($store->created_at, true) }}</div>
                                    </div>
                                    
                                    @if($store->approved_at)
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>{{ trans('messages.approved_at') }}:</strong></div>
                                            <div class="col-sm-8">{{ dateAgoFormate($store->approved_at, true) }}</div>
                                        </div>
                                    @endif
                                    
                                    @if($store->updated_at != $store->created_at)
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>{{ trans('messages.updated_at') }}:</strong></div>
                                            <div class="col-sm-8">{{ dateAgoFormate($store->updated_at, true) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($store->business_hours)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>{{ trans('messages.business_hours') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('messages.day') }}</th>
                                                <th>{{ trans('messages.opening_time') }}</th>
                                                <th>{{ trans('messages.closing_time') }}</th>
                                                <th>{{ trans('messages.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($store->business_hours as $day => $hours)
                                                <tr>
                                                    <td>{{ ucfirst($day) }}</td>
                                                    <td>{{ $hours['open'] ?? '-' }}</td>
                                                    <td>{{ $hours['close'] ?? '-' }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ isset($hours['is_open']) && $hours['is_open'] ? 'success' : 'secondary' }}">
                                                            {{ isset($hours['is_open']) && $hours['is_open'] ? trans('messages.open') : trans('messages.closed') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    @if($store->products && $store->products->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>{{ trans('messages.store_products') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('messages.product_name') }}</th>
                                                <th>{{ trans('messages.store_price') }}</th>
                                                <th>{{ trans('messages.stock') }}</th>
                                                <th>{{ trans('messages.availability') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($store->products->take(10) as $product)
                                                <tr>
                                                    <td>{{ $product->name }}</td>
                                                    <td>{{ getPriceFormat($product->pivot->store_price ?? $product->base_price) }}</td>
                                                    <td>{{ $product->pivot->stock_quantity ?? $product->stock_quantity }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $product->pivot->is_available ? 'success' : 'secondary' }}">
                                                            {{ $product->pivot->is_available ? trans('messages.available') : trans('messages.unavailable') }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($store->products->count() > 10)
                                        <p class="text-muted">{{ trans('messages.showing_first_10_products') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
