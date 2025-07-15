<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.add_form_title',['form' => trans('messages.store')]) }}</h5>
                            @if($auth_user->can('store list'))
                                <a href="{{ route('store.index') }}" class="float-right btn btn-sm btn-primary">
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
                        {{ Form::model($storedata,['method' => 'POST','route'=>'store.store', 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'store'] ) }}
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
                                                {{ Form::label('provider_id', __('messages.provider').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('provider_id', $providers->pluck('display_name', 'id'), old('provider_id'), ['class' => 'form-control select2js', 'required', 'placeholder' => __('messages.select_provider')]) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-12">
                                                {{ Form::label('description', __('messages.description'), ['class' => 'form-control-label']) }}
                                                {{ Form::textarea('description', old('description'), ['placeholder' => __('messages.description'), 'class' => 'form-control', 'rows' => 3]) }}
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('phone', __('messages.phone').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::text('phone', old('phone'), ['placeholder' => __('messages.phone'), 'class' => 'form-control', 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('status', __('messages.status').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('status', ['pending' => __('messages.pending'), 'approved' => __('messages.approved'), 'rejected' => __('messages.rejected'), 'suspended' => __('messages.suspended')], old('status', 'pending'), ['class' => 'form-control select2js', 'required']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Location Information -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.location_information') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-12">
                                                {{ Form::label('address', __('messages.address').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::textarea('address', old('address'), ['placeholder' => __('messages.address'), 'class' => 'form-control', 'rows' => 2, 'required']) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('country_id', __('messages.country').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                                {{ Form::select('country_id', $countries->pluck('name', 'id'), old('country_id'), ['class' => 'form-control select2js', 'required', 'placeholder' => __('messages.select_country')]) }}
                                                <small class="help-block with-errors text-danger"></small>
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('state_id', __('messages.state'), ['class' => 'form-control-label']) }}
                                                {{ Form::select('state_id', [], old('state_id'), ['class' => 'form-control select2js', 'placeholder' => __('messages.select_state')]) }}
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('city_id', __('messages.city'), ['class' => 'form-control-label']) }}
                                                {{ Form::select('city_id', [], old('city_id'), ['class' => 'form-control select2js', 'placeholder' => __('messages.select_city')]) }}
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('latitude', __('messages.latitude'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('latitude', old('latitude'), ['placeholder' => __('messages.latitude'), 'class' => 'form-control', 'step' => 'any']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-6">
                                                {{ Form::label('longitude', __('messages.longitude'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('longitude', old('longitude'), ['placeholder' => __('messages.longitude'), 'class' => 'form-control', 'step' => 'any']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Business Settings -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.business_settings') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="form-group col-md-4">
                                                {{ Form::label('delivery_radius', __('messages.delivery_radius'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('delivery_radius', old('delivery_radius'), ['placeholder' => __('messages.delivery_radius'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                                <small class="text-muted">{{ __('messages.in_kilometers') }}</small>
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('minimum_order_amount', __('messages.minimum_order_amount'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('minimum_order_amount', old('minimum_order_amount'), ['placeholder' => __('messages.minimum_order_amount'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-4">
                                                {{ Form::label('delivery_fee', __('messages.delivery_fee'), ['class' => 'form-control-label']) }}
                                                {{ Form::number('delivery_fee', old('delivery_fee'), ['placeholder' => __('messages.delivery_fee'), 'class' => 'form-control', 'step' => '0.01', 'min' => '0']) }}
                                            </div>
                                            
                                            <div class="form-group col-md-12">
                                                {{ Form::label('is_active', __('messages.active_status'), ['class' => 'form-control-label']) }}
                                                {{ Form::select('is_active', ['1' => __('messages.active'), '0' => __('messages.inactive')], old('is_active', 1), ['class' => 'form-control select2js']) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Store Logo -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.store_logo') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label class="form-control-label" for="store_logo">{{ __('messages.logo') }}</label>
                                            <div class="custom-file">
                                                <input type="file" name="store_logo" class="custom-file-input" accept="image/*">
                                                @if($storedata && getMediaFileExit($storedata, 'store_logo'))
                                                    <label class="custom-file-label upload-label">{{ $storedata->getFirstMedia('store_logo')->file_name }}</label>
                                                @else
                                                    <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.logo') ]) }}</label>
                                                @endif
                                            </div>
                                            <small class="text-muted">{{ __('messages.recommended_size') }}: 300x300px</small>
                                        </div>
                                        
                                        @if(getMediaFileExit($storedata, 'store_logo'))
                                            <div class="col-md-12 mb-2">
                                                <img id="store_logo_preview" src="{{ getSingleMedia($storedata,'store_logo') }}" alt="store-logo" class="attachment-image mt-1" style="height: 80px; width: 80px;">
                                                <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $storedata->id, 'type' => 'store_logo']) }}"
                                                   data--submit="confirm_form"
                                                   data--confirmation='true'
                                                   data--ajax="true"
                                                   title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.logo") ]) }}'
                                                   data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.logo") ]) }}'
                                                   data-message='{{ __("messages.remove_file_msg") }}'>
                                                    <i class="ri-close-circle-line"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Additional Information -->
                                <div class="card mt-3">
                                    <div class="card-header">
                                        <h5 class="card-title">{{ __('messages.additional_information') }}</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            {{ Form::label('rejection_reason', __('messages.rejection_reason'), ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('rejection_reason', old('rejection_reason'), ['placeholder' => __('messages.rejection_reason'), 'class' => 'form-control', 'rows' => 3]) }}
                                            <small class="text-muted">{{ __('messages.only_for_rejected_stores') }}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-end gap-3">
                                    <a href="{{ route('store.index') }}" class="btn btn-secondary">{{ __('messages.cancel') }}</a>
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
            
            // Country/State/City cascade
            $('#country_id').on('change', function() {
                var countryId = $(this).val();
                $('#state_id').empty().append('<option value="">{{ __("messages.select_state") }}</option>');
                $('#city_id').empty().append('<option value="">{{ __("messages.select_city") }}</option>');
                
                if (countryId) {
                    $.get('/get-states/' + countryId, function(data) {
                        $.each(data, function(key, value) {
                            $('#state_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    });
                }
            });
            
            $('#state_id').on('change', function() {
                var stateId = $(this).val();
                $('#city_id').empty().append('<option value="">{{ __("messages.select_city") }}</option>');
                
                if (stateId) {
                    $.get('/get-cities/' + stateId, function(data) {
                        $.each(data, function(key, value) {
                            $('#city_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    });
                }
            });
        });
    </script>
    @endsection
</x-master-layout>
