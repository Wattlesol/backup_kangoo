<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.add_form_title',['form' => trans('messages.product')]) }}</h5>
                            @if($auth_user->can('product list'))
                                <a href="{{ route('product.index') }}" class="float-right btn btn-sm btn-primary">
                                    <i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($productdata,['method' => 'POST','route'=>'product.store', 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'product'] ) }}
                        {{ Form::hidden('id') }}
                        
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
                                                {{ Form::label('sku', __('messages.sku').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::text('sku', old('sku'), ['placeholder' => __('messages.sku'), 'class' => 'form-control', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-12">
                                                {{ Form::label('description', __('messages.description'), ['class' => 'form-control-label']) }}
                                                {{ Form::textarea('description', old('description'), ['placeholder' => __('messages.description'), 'class' => 'form-control', 'rows' => 3]) }}
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('category_id', __('messages.category').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('category_id', $categories, old('category_id'), ['class' => 'form-control select2js', 'required', 'placeholder' => __('messages.select_category')]) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('status', __('messages.status').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('status', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('status'), ['class' => 'form-control select2js', 'required']) }}
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
                                            <div class="form-group col-md-4">
                                                {{ Form::label('price', __('messages.price').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::number('price', old('price'), ['placeholder' => __('messages.price'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('compare_price', __('messages.compare_price'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('compare_price', old('compare_price'), ['placeholder' => __('messages.compare_price'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('cost_price', __('messages.cost_price'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('cost_price', old('cost_price'), ['placeholder' => __('messages.cost_price'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('stock_quantity', __('messages.stock_quantity').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::number('stock_quantity', old('stock_quantity'), ['placeholder' => __('messages.stock_quantity'), 'class' => 'form-control', 'min' => '0', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('low_stock_threshold', __('messages.low_stock_threshold'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('low_stock_threshold', old('low_stock_threshold'), ['placeholder' => __('messages.low_stock_threshold'), 'class' => 'form-control', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('track_stock', __('messages.track_stock'), ['class' => 'form-control-label']) }}
                                                {{ Form::select('track_stock', ['1' => __('messages.yes'), '0' => __('messages.no')], old('track_stock', 1), ['class' => 'form-control select2js']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Images -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.product_images') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label class="form-control-label" for="product_image">{{ __('messages.main_image') }} <span class="text-danger">*</span></label>
                                            <div class="custom-file">
                                                <input type="file" name="product_image" class="custom-file-input" accept="image/*" required>
                                                <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.image') ]) }}</label>
                                            </div>
                                            <small class="text-muted">{{ __('messages.recommended_size') }}: 800x800px</small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-control-label" for="gallery_images">{{ __('messages.gallery_images') }}</label>
                                            <div class="custom-file">
                                                <input type="file" name="gallery_images[]" class="custom-file-input" accept="image/*" multiple>
                                                <label class="custom-file-label upload-label">{{ __('messages.choose_files') }}</label>
                                            </div>
                                            <small class="text-muted">{{ __('messages.multiple_images_allowed') }}</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SEO Settings -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.seo_settings') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            {{ Form::label('meta_title', __('messages.meta_title'), ['class' => 'form-control-label']) }}
                                            {{ Form::text('meta_title', old('meta_title'), ['placeholder' => __('messages.meta_title'), 'class' => 'form-control']) }}
                                        </div>
                                        
                                        <div class="form-group">
                                            {{ Form::label('meta_description', __('messages.meta_description'), ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('meta_description', old('meta_description'), ['placeholder' => __('messages.meta_description'), 'class' => 'form-control', 'rows' => 3]) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="{{ route('product.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
                                    {{ Form::submit(__('messages.save'), ['class' => 'btn btn-primary']) }}
                                </div>
                            </div>
                        </div>
                        
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
    <script>
        $(document).ready(function() {
            $('.select2js').select2();
            
            // File input labels
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').html(fileName);
            });
        });
    </script>
    @endsection
</x-master-layout>
