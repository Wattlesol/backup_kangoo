<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                   <div class="card-body p-0">
                       <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                        @php $isAr = app()->getLocale() === 'ar'; @endphp
                        <h5 class="font-weight-bold">{{ !empty($serviceaddon->id) ? ($isAr ? 'تحديث خدمة إضافية' : 'Update Additional Service') : ($isAr ? 'إنشاء خدمة إضافية' : 'Create Additional Service') }}</h5>
                       <a href="{{ route('serviceaddon.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        @if($auth_user->can('service list'))
                       
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                {{ Form::model($serviceaddon,['method' => 'POST','route'=>'serviceaddon.store', 'enctype'=>'multipart/form-data','data-toggle'=>"validator" ,'id'=>'serviceaddon'] ) }}
                        {{ Form::hidden('id') }}
                        <div class="row">
                           <div class="form-group col-md-4">
                                {{ Form::label('name', __('messages.english_name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('name', old('name'), ['placeholder' => __('messages.english_name'), 'class' => 'form-control', 'required']) }}
                               <small class="help-block with-errors text-danger"></small>
                           </div>
                           <div class="form-group col-md-4">
                                {{ Form::label('name_ar', __('messages.arabic_name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('name_ar', old('name_ar'), ['placeholder' => __('messages.arabic_name'), 'class' => 'form-control', 'dir' => 'rtl', 'required']) }}
                               <small class="help-block with-errors text-danger"></small>
                           </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('price', __('messages.price').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('price', null, [ 'placeholder' => __('messages.price'), 'class' =>'form-control', 'required', 'pattern' => '^\\d+(\\.\\d{1,2})?$']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                           <div class="form-group col-md-6">
                                {{ Form::label('category_ids', $isAr ? 'عرض حسب الفئات' : 'Show For Categories', ['class' => 'form-control-label']) }}
                               {{ Form::select('category_ids[]', $categories, $selectedCategoryIds, [
                                   'class' => 'form-control select2js',
                                   'multiple' => 'multiple',
                                    'data-placeholder' => $isAr ? 'اختر فئة واحدة أو أكثر' : 'Select one or more categories',
                               ]) }}
                                <small class="help-block text-muted">{{ $isAr ? 'اتركه فارغاً ليظهر لجميع الفئات.' : 'Leave empty to avoid category targeting.' }}</small>
                           </div>

                           <div class="form-group col-md-6">
                                {{ Form::label('service_ids', $isAr ? 'عرض حسب الخدمات' : 'Show For Services', ['class' => 'form-control-label']) }}
                               {{ Form::select('service_ids[]', $services, $selectedServiceIds, [
                                   'class' => 'form-control select2js',
                                   'multiple' => 'multiple',
                                    'data-placeholder' => $isAr ? 'اختر خدمة واحدة أو أكثر' : 'Select one or more services',
                               ]) }}
                                <small class="help-block text-muted">{{ $isAr ? 'إذا كان كلا الحقلين فارغين، فستظهر هذه الخدمة الإضافية لجميع الخدمات.' : 'If both fields are empty, this add-on appears for all services.' }}</small>
                           </div>

                            <div class="form-group col-md-4">
                                <label class="form-control-label" for="serviceaddon_image">{{ __('messages.image') }}</label>
                                <div class="custom-file">
                                    <input type="file" name="serviceaddon_image" class="custom-file-input" accept="image/*">
                                    <label class="custom-file-label upload-label">{{  __('messages.choose_file',['file' =>  __('messages.image') ]) }}</label>
                                </div>
                            </div>

                                @if(getMediaFileExit($serviceaddon, 'serviceaddon_image'))
                                    <div class="col-md-2 mb-2">
                                        @php
                                            $extention = imageExtention(getSingleMedia($serviceaddon,'serviceaddon_image'));
                                        @endphp
                                        <img id="serviceaddon_image_preview" src="{{getSingleMedia($serviceaddon,'serviceaddon_image')}}" alt="#" class="attachment-image mt-1">
                                            <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $serviceaddon->id, 'type' => 'serviceaddon_image']) }}"
                                                data--submit="confirm_form"
                                                data--confirmation='true'
                                                data--ajax="true"
                                                title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                                data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                                data-message='{{ __("messages.remove_file_msg") }}'>
                                                <i class="ri-close-circle-line"></i>
                                            </a>
                                    </div>
                                @endif
                            <!-- <div class="form-group col-md-4">
                                {{ Form::label('quantity', __('messages.quantity').' ', ['class' => 'form-control-label'], false) }}
                                {{ Form::number('quantity',null, [ 'min' => 1, 'step' => 'any' , 'placeholder' => __('messages.quantity'),'class' =>'form-control']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>  -->
                            <div class="form-group col-md-4">
                                {{ Form::label('status',__('messages.status').' ',['class'=>'form-control-label'],false) }}
                                {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'id' => 'role' ,'class' =>'form-control select2js','required']) }}
                            </div>

                        </div>

                        {{ Form::submit( __('messages.save'), ['class'=>'btn btn-md btn-primary float-right']) }}
                        {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>


</x-master-layout>
