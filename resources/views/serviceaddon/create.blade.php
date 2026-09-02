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

                           <div class="col-12 mb-3">
                               <section class="quick-addon-availability" aria-labelledby="addon-availability-title">
                                   <div class="quick-addon-availability-copy">
                                       <strong id="addon-availability-title">{{ $isAr ? 'توفر الخدمة الإضافية' : 'Add-on availability' }}</strong>
                                       <span>{{ $isAr ? 'تظهر الخدمة الإضافية فقط أثناء طلب خدمة مؤهلة، ولا تظهر أبداً كمنتج مستقل للعميل.' : 'This add-on is shown only while a customer orders an eligible service. It is never offered as a standalone product.' }}</span>
                                   </div>
                                   <div class="row">
                                       <div class="form-group col-md-6 mb-md-0">
                                           {{ Form::label('service_ids', $isAr ? 'الخدمات المرتبطة' : 'Linked services', ['class' => 'form-control-label']) }}
                                           {{ Form::select('service_ids[]', $services, old('service_ids', $selectedServiceIds), [
                                               'class' => 'form-control select2js',
                                               'multiple' => 'multiple',
                                               'data-placeholder' => $isAr ? 'اختر الخدمات التي يمكنها عرض هذه الإضافة' : 'Select services that can offer this add-on',
                                           ]) }}
                                           <small class="help-block text-muted">{{ $isAr ? 'يمكنك تغيير الخدمات المرتبطة في أي وقت. اتركها فارغة فقط إذا كانت الإضافة متاحة مع كل الخدمات.' : 'Change these links at any time. Leave empty only when the add-on is valid with every service.' }}</small>
                                       </div>

                                       <div class="form-group col-md-6 mb-0">
                                           {{ Form::label('category_ids', $isAr ? 'فئات الخدمات المرتبطة (اختياري)' : 'Linked service categories (optional)', ['class' => 'form-control-label']) }}
                                           {{ Form::select('category_ids[]', $categories, old('category_ids', $selectedCategoryIds), [
                                               'class' => 'form-control select2js',
                                               'multiple' => 'multiple',
                                               'data-placeholder' => $isAr ? 'اختر فئات الخدمات المؤهلة' : 'Select eligible service categories',
                                           ]) }}
                                           <small class="help-block text-muted">{{ $isAr ? 'استخدم هذا الخيار لإتاحة الإضافة لجميع الخدمات ضمن فئات محددة.' : 'Use this to offer the add-on with every service inside selected categories.' }}</small>
                                       </div>
                                   </div>
                               </section>
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


@push('styles')
<style>
    .quick-addon-availability {
        border: 1px solid var(--quick-shell-line);
        border-radius: 16px;
        padding: 18px;
        background: color-mix(in srgb, var(--quick-blue) 4%, var(--quick-shell-surface));
    }
    .quick-addon-availability-copy {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin-bottom: 16px;
    }
    .quick-addon-availability-copy strong { color: var(--quick-shell-ink); font-size: 15px; }
    .quick-addon-availability-copy span { color: var(--quick-shell-muted); font-size: 12px; line-height: 1.6; }
</style>
@endpush

</x-master-layout>
