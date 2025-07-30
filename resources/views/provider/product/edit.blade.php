<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.edit_product') }}</h5>
                            <a href="{{ route('provider.product.index') }}" class="btn btn-sm btn-secondary">
                                <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($product, ['method' => 'PUT','route'=>['provider.product.update', $product->id], 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'provider-product'] ) }}
                        
                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.basic_information') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                {{ Form::label('name', __('messages.name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::text('name', old('name'), ['placeholder' => __('messages.name'), 'class' => 'form-control', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('product_category_id', __('messages.category').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('product_category_id', $categories->pluck('name', 'id'), old('product_category_id'), ['class' => 'form-control select2js', 'required', 'placeholder' => __('messages.select_category')]) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-12">
                                                {{ Form::label('description', __('messages.description'), ['class' => 'form-control-label']) }}
                                                {{ Form::textarea('description', old('description'), ['placeholder' => __('messages.description'), 'class' => 'form-control', 'rows' => 3]) }}
                                            </div>
                                            
                                            <div class="form-group col-md-12">
                                                {{ Form::label('short_description', __('messages.short_description'), ['class' => 'form-control-label']) }}
                                                {{ Form::textarea('short_description', old('short_description'), ['placeholder' => __('messages.short_description'), 'class' => 'form-control', 'rows' => 2]) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Pricing & Inventory -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.pricing_inventory') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                {{ Form::label('base_price', __('messages.price').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::number('base_price', old('base_price'), ['placeholder' => __('messages.price'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('weight', __('messages.weight').' (kg)', ['class' => 'form-control-label']) }}
                                                {{ Form::number('weight', old('weight'), ['placeholder' => __('messages.weight'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('stock_quantity', __('messages.stock_quantity').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::number('stock_quantity', old('stock_quantity'), ['placeholder' => __('messages.stock_quantity'), 'class' => 'form-control', 'min' => '0', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('low_stock_threshold', __('messages.low_stock_threshold'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('low_stock_threshold', old('low_stock_threshold'), ['placeholder' => __('messages.low_stock_threshold'), 'class' => 'form-control', 'min' => '0']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Images & Status -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.product_images') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        @if($product->main_image)
                                            <div class="form-group">
                                                <label class="form-control-label">{{ __('messages.current_image') }}</label>
                                                <div class="text-center">
                                                    <img src="{{ $product->main_image }}" alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 200px;">
                                                </div>
                                            </div>
                                        @endif
                                        
                                        <div class="form-group">
                                            <label class="form-control-label" for="product_image">{{ __('messages.main_image') }}</label>
                                            <div class="custom-file">
                                                <input type="file" name="product_image" class="custom-file-input" accept="image/*">
                                                <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.image') ]) }}</label>
                                            </div>
                                            <small class="text-muted">{{ __('messages.recommended_size') }}: 800x800px</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-control-label" for="product_gallery">{{ __('messages.gallery_images') }}</label>
                                            <div class="custom-file">
                                                <input type="file" name="product_gallery[]" class="custom-file-input" accept="image/*" multiple>
                                                <label class="custom-file-label upload-label">{{ __('messages.choose_files') }}</label>
                                            </div>
                                            <small class="text-muted">{{ __('messages.multiple_images_allowed') }}</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Approval Status -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.approval_status') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-{{ $product->approval_status == 'approved' ? 'success' : ($product->approval_status == 'rejected' ? 'danger' : 'warning') }}">
                                            <i class="fas fa-{{ $product->approval_status == 'approved' ? 'check-circle' : ($product->approval_status == 'rejected' ? 'times-circle' : 'clock') }}"></i>
                                            <strong>{{ __('messages.status') }}:</strong> {{ ucfirst($product->approval_status ?? 'pending') }}
                                        </div>
                                        @if($product->approval_status == 'rejected' && $product->rejection_reason)
                                            <div class="alert alert-info">
                                                <strong>{{ __('messages.rejection_reason') }}:</strong><br>
                                                {{ $product->rejection_reason }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="{{ route('provider.product.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                                    <button type="submit" class="btn btn-primary">{{ __('messages.update_product') }}</button>
                                </div>
                            </div>
                        </div>
                        
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
