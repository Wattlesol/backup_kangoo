@extends('layouts.master')

@section('title')
    {{ $pageTitle ?? trans('messages.product') }}
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <div class="header-title">
                        <h4 class="card-title">{{ $pageTitle ?? trans('messages.product_details') }}</h4>
                    </div>
                    <div class="header-action">
                        <a href="{{ route('product.index') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-left"></i> {{ trans('messages.back') }}
                        </a>
                        @if(auth()->user()->can('product edit'))
                            <a href="{{ route('product.create', ['id' => $product->id]) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> {{ trans('messages.edit') }}
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="product-image">
                                @if($product->getFirstMediaUrl('product_image'))
                                    <img src="{{ $product->getFirstMediaUrl('product_image') }}" 
                                         alt="{{ $product->name }}" 
                                         class="img-fluid rounded" 
                                         style="max-height: 400px;">
                                @else
                                    <div class="no-image-placeholder bg-light d-flex align-items-center justify-content-center rounded" 
                                         style="height: 300px;">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="product-details">
                                <h3>{{ $product->name }}</h3>
                                <p class="text-muted mb-3">{{ $product->description }}</p>
                                
                                <div class="product-info">
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.category') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @if($product->category)
                                                <span class="badge badge-info">{{ $product->category->name }}</span>
                                            @else
                                                <span class="text-muted">{{ trans('messages.no_category') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.sku') }}:</strong></div>
                                        <div class="col-sm-8">{{ $product->sku }}</div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.price') }}:</strong></div>
                                        <div class="col-sm-8">
                                            <span class="h5 text-primary">{{ getPriceFormat($product->base_price) }}</span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.stock') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @if($product->stock_quantity > 0)
                                                <span class="badge badge-success">{{ $product->stock_quantity }} {{ trans('messages.in_stock') }}</span>
                                            @else
                                                <span class="badge badge-danger">{{ trans('messages.out_of_stock') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.creator') }}:</strong></div>
                                        <div class="col-sm-8">
                                            <span class="badge badge-{{ $product->created_by_type == 'admin' ? 'primary' : 'info' }}">
                                                {{ ucfirst($product->created_by_type) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.status') }}:</strong></div>
                                        <div class="col-sm-8">
                                            @if($product->status)
                                                <span class="badge badge-success">{{ trans('messages.active') }}</span>
                                            @else
                                                <span class="badge badge-secondary">{{ trans('messages.inactive') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><strong>{{ trans('messages.created_at') }}:</strong></div>
                                        <div class="col-sm-8">{{ dateAgoFormate($product->created_at, true) }}</div>
                                    </div>
                                    
                                    @if($product->updated_at != $product->created_at)
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>{{ trans('messages.updated_at') }}:</strong></div>
                                            <div class="col-sm-8">{{ dateAgoFormate($product->updated_at, true) }}</div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    @if($product->variants && $product->variants->count() > 0)
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>{{ trans('messages.product_variants') }}</h5>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('messages.variant_name') }}</th>
                                                <th>{{ trans('messages.price') }}</th>
                                                <th>{{ trans('messages.stock') }}</th>
                                                <th>{{ trans('messages.status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($product->variants as $variant)
                                                <tr>
                                                    <td>{{ $variant->name }}</td>
                                                    <td>{{ getPriceFormat($variant->price) }}</td>
                                                    <td>{{ $variant->stock_quantity }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $variant->is_active ? 'success' : 'secondary' }}">
                                                            {{ $variant->is_active ? trans('messages.active') : trans('messages.inactive') }}
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
