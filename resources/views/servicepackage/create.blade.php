<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                        <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                        @if($auth_user->can('servicepackage list'))
                        <a href="{{ route('servicepackage.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    {{ Form::model($servicepackage,['method' => 'POST','route'=>'servicepackage.store', 'enctype'=>'multipart/form-data', ] ) }}
                    {{ Form::hidden('id') }}
                    <div class="row">
                        <div class="form-group col-md-4">
                            {{ Form::label('name',trans('messages.name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                            {{ Form::text('name',old('name'),['placeholder' => trans('messages.name'),'class' =>'form-control','required']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        @if(auth()->user()->hasAnyRole(['admin','demo_admin']))
                        <div class="form-group col-md-4">
                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.provider') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                            <br />
                            {{ Form::select('provider_id', [ optional($servicepackage->providers)->id => optional($servicepackage->providers)->display_name ], optional($servicepackage->providers)->id, [
                                    'class' => 'select2js form-group',
                                    'id' => 'provider_id',
                                    'required',
                                    'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.provider') ]),
                                    'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                            ]) }}
                        </div>
                        @endif

                        <div class="form-group col-md-4">
                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.user') ]).' ',['class'=>'form-control-label',],false) }}
                            <br />
                            {{ Form::select('category_id', [],"",[
                                'class' => 'select2js form-group category',
                                'id' => 'user_id',
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.user') ]),
                                'data-ajax--url' => route('ajax-list', ['type' => 'user']),
                            ]) }}
                        </div>
                        <div class="form-group col-md-4 d-none" id="select_category">
                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.category') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label',],false) }}
                            <br />
                            {{ Form::select('category_id', [optional($servicepackage->category)->id => optional($servicepackage->category)->name], optional($servicepackage->category)->id, [
                                'class' => 'select2js form-group category',
                                'id' => 'category_id',
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.category') ]),
                                'data-ajax--url' => route('ajax-list', ['type' => 'category']),
                            ]) }}
                        </div>
                        <div class="form-group col-md-4 d-none" id="select_subcategory">
                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.subcategory') ]).'',['class'=>'form-control-label'],false) }}
                            <br />
                            {{ Form::select('subcategory_id', [optional($servicepackage->subcategory)->id => optional($servicepackage->subcategory)->name], optional($servicepackage->subcategory)->id, [
                                'class' => 'select2js form-group subcategory',
                                'id' => 'subcategory_id',
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.subcategory') ]),

                            ]) }}
                        </div>
                        <div class="form-group col-md-4 " id="select_subcategory">
                            {{ Form::label('name', "Price List",['class'=>'form-control-label'],false) }}
                            <br />
                            {{ Form::select('pricelist_id',$PriceList, [
                                'class' => 'select2 form-group pricelist',
                                'id' => 'pricelist',
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.pricelist') ]),

                            ]) }}
                        </div>

                        <div class="form-group col-md-4 " id="select_subcategory">
                            {{ Form::label('name', "Services",['class'=>'form-control-label'],false) }}
                            <br />
                            {{ Form::select('service_id',$AllServices, [
                                'class' => 'select2 form-group pricelist',
                                'id' => 'services',
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.service') ]),

                            ]) }}
                        </div>
{{--                        <div class="form-group col-md-4">--}}
{{--                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.service') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}--}}
{{--                            <br />--}}
{{--                            {{ Form::select('service_id[]', $services ? $services->pluck('name', 'id') : [], $selectedServiceId, [--}}
{{--                                'class' => 'select2js form-group service_id',--}}
{{--                                'id' =>'custom_service_id',--}}
{{--                                'multiple' => 'multiple',--}}
{{--                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.service') ]),--}}
{{--                                'required' => 'required',--}}
{{--                            ]) }}--}}
{{--                        </div>--}}
                        <div class="form-group col-md-4">
                            {{ Form::label('duration',__('messages.duration').'',['class'=>'form-control-label'], false ) }}
                            {{ Form::text('duration',old('duration'),['placeholder' => __('messages.duration'),'class' =>'form-control']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('Car_Number',"Car Number",['class'=>'form-control-label'], false ) }}
                            {{ Form::text('car_number',old('car_number'),['placeholder' => "Car Number",'class' =>'form-control']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4" id="price_div">
                                {{ Form::label('price',__('messages.price').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::number('price',null, [ 'min' => 1, 'step' => 'any' , 'placeholder' => __('messages.price'),'class' =>'form-control', 'required','id' => 'price' ]) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('status',trans('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                            {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'id' => 'role' ,'class' =>'form-control select2js','required']) }}
                        </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('status','Type',['class'=>'form-control-label'],false) }}
                            {{ Form::select('package_type', ['single' => 'فردي', 'family' => 'عائلي ','Breaks'=>"استراحات",'specific_place'=>"مكان محدد"], null, [
                        'class' =>'form-control',
                                        'required' => 'required',
                                    ]) }}                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-control-label" for="package_attachment">{{ __('messages.image') }} <span class="text-danger">*</span> </label>
                            <div class="custom-file">
                            <input type="file" name="package_attachment[]" class="custom-file-input"  data-file-error="{{ __('messages.files_not_allowed') }}" multiple required>
                                <label class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.attachments') ]) }}</label>
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            {{ Form::label('description',trans('messages.description'), ['class' => 'form-control-label']) }}
                            {{ Form::textarea('description', null, ['class'=>"form-control textarea" , 'rows'=>3  , 'placeholder'=> __('messages.description') ]) }}
                        </div>
                    </div>
                    <div class="row package_attachment_div">
                            <div class="col-md-12">
                                @if(getMediaFileExit($servicepackage, 'package_attachment'))
                                @php
                                $attchments = $servicepackage->getMedia('package_attachment');
                                $file_extention = config('constant.IMAGE_EXTENTIONS');
                                @endphp
                                <div class="border-left-2">
                                    <p class="ml-2"><b>{{ __('messages.attached_files') }}</b></p>
                                    <div class="ml-2 my-3">
                                        <div class="row">
                                            @foreach($attchments as $attchment )
                                            <?php
                                            $extention = in_array(strtolower(imageExtention($attchment->getFullUrl())), $file_extention);
                                            ?>

                                            <div class="col-md-2 pr-10 text-center galary file-gallary-{{$servicepackage->id}}" data-gallery=".file-gallary-{{$servicepackage->id}}" id="package_attachment_preview_{{$attchment->id}}">
                                                @if($extention)
                                                <a id="attachment_files" href="{{ $attchment->getFullUrl() }}" class="list-group-item-action attachment-list" target="_blank">
                                                    <img src="{{ $attchment->getFullUrl() }}" class="attachment-image" alt="">
                                                </a>
                                                @else
                                                <a id="attachment_files" class="video list-group-item-action attachment-list" href="{{ $attchment->getFullUrl() }}">
                                                    <img src="{{ asset('images/file.png') }}" class="attachment-file">
                                                </a>
                                                @endif
                                                <a class="text-danger remove-file" href="{{ route('remove.file', ['id' => $attchment->id, 'type' => 'package_attachment']) }}" data--submit="confirm_form" data--confirmation='true' data--ajax="true" data-toggle="tooltip" title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}' data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}' data-message='{{ __("messages.remove_file_msg") }}'>
                                                    <i class="ri-close-circle-line"></i>
                                                </a>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                    <div class="row">
                     <h3>Service </h3>

                        <div class="table-responsive">
                            <button type="button" class="float-right mr-1 btn btn-sm btn-primary " id="add_service"><i class="fa fa-plus-circle"></i> Add Service</button>
                            <br>
                            <br>
                            <br>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">service</th>
                                    <th scope="col">service Type</th>
                                    <th scope="col">count</th>
                                    <th scope="col">Usage times</th>
                                    <th scope="col">Duration of use before next time</th>
                                    <th scope="col">Price</th>
                                </tr>
                                </thead>
                                <tbody id="service_list">


                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <div class="custom-control custom-switch custom-control-inline">
                                {{ Form::checkbox('is_featured', $servicepackage->is_featured, null, ['class' => 'custom-control-input' , 'id' => 'is_featured' ]) }}
                                <label class="custom-control-label" for="is_featured">{{ __('messages.set_as_featured')  }}
                                </label>
                            </div>
                        </div>
                    </div>
                    {{ Form::submit( trans('messages.save'), ['class'=>'btn btn-md btn-primary float-right']) }}
                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
</div>

@section('bottom_script')
    <script type="text/javascript">
        (function($) {
            "use strict";
            $(document).ready(function(){
                var package_type = $("#package_type").val();
                hideShow(package_type);

                $(document).on('change', '#package_type', function() {
                    var package_type = $(this).val();
                    hideShow(package_type);
                })

                var category_id = "{{ isset($servicepackage->category_id) ? $servicepackage->category_id : '' }}";
                var subcategory_id = "{{ isset($servicepackage->subcategory_id) ? $servicepackage->subcategory_id : '' }}";
                var provider_id = "{{ isset($servicepackage->provider_id) ? $servicepackage->provider_id : '' }}";
                var service_id = "{{$servicepackage->packageServices->pluck('service_id')->implode(',')}}"
                if(service_id !== ''){
                    getService(service_id)
                }
                getSubCategory(category_id, subcategory_id)
                getService(provider_id)
                $(document).on('change', '#provider_id', function() {
                    var provider_id = $(this).val();
                    $('#custom_service_id').empty();
                    getService(provider_id,category_id)
                })

                   $(document).on('change', '#package_type', function() {

                    var provider_id=$('#provider_id').val();

                    $('#custom_service_id').empty();
                    getService(provider_id)
                })



                $(document).on('change', '#category_id', function() {
                    var category_id = $(this).val();
                    var provider_id = $('#provider_id').val();
                    var subcategory_id = $('#subcategory_id').val();


                    $('#subcategory_id').empty();
                    getSubCategory(category_id, subcategory_id);

                    $('#custom_service_id').empty();
                    getService(provider_id,category_id,subcategory_id)
                })

                $(document).on('change', '#subcategory_id', function() {
                    var subcategory_id = $(this).val();
                    var category_id = $('#category_id').val();
                    var provider_id = $('#provider_id').val();
                    var selectedServiceIds = $('#custom_service_id').val();

                    $('#custom_service_id').empty();
                    getService(provider_id,category_id,subcategory_id,selectedServiceIds)
                })
            })

            function hideShow(package_type){
                if(package_type == 'single'){
                    $('#select_category').removeClass('d-none');
                    $('#select_subcategory').removeClass('d-none');
                    $('#category_id').prop('required', true);
                    $('#subcategory_id').prop('required', true);
                }
                else{
                    $('#select_category').addClass('d-none');
                    $('#select_subcategory').addClass('d-none');
                    $('#category_id').prop('required', false);
                    $('#subcategory_id').prop('required', false);
                }
            }
            function getSubCategory(category_id, subcategory_id = "") {
                var get_subcategory_list = "{{ route('ajax-list', [ 'type' => 'subcategory_list','category_id' =>'']) }}" + category_id;
                get_subcategory_list = get_subcategory_list.replace('amp;', '');

                $.ajax({
                    url: get_subcategory_list,
                    success: function(result) {
                        $('#subcategory_id').select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name',['select' => trans('messages.subcategory')]) }}",
                            data: result.results
                        });
                        if (subcategory_id != "") {
                            $('#subcategory_id').val(subcategory_id).trigger('change');
                        }
                    }
                });
            }
            function getService(provider_id,category_id,subcategory_id,service_id=''){
                var selectedServiceId = {!! json_encode($selectedServiceId) !!};
                $.ajax({
                    url: "{{ route('service-list') }}",
                    method:"POST",
                    data : { '_token': $('meta[name=csrf-token]').attr('content'),provider_id : provider_id,category_id:category_id,subcategory_id:subcategory_id },

                    success: function(result) {
                        console.log(result)
                        $('#custom_service_id').select2({
                            width: '100%',
                            placeholder: "{{ trans('messages.select_name',['select' => trans('messages.subcategory')]) }}",
                            data: result.results
                        });
                        selectedServiceId.forEach(function(id) {
                        // Find the option element with the corresponding ID and mark it as selected
                        $('#custom_service_id option[value="' + id + '"]').prop('selected', true);
                    });
                    }
                });
            }
        })(jQuery);

        $(function () {
            var i = 1;
            // add new row in Main Dive
            $("#add_service").click(function () {

                $("#service_list").append(
                    `<tr>
    <td>
        {{ Form::select('service_id_data[]', $services_data, [], [
            'class' => 'select2js form-group',
            'data-placeholder' => __('messages.select_name', ['select' => __('messages.service')]),
            'required' => 'required',
        ]) }}
                    </td>
                             <td>
{{ Form::select('service_type_data[]', ['limited' => 'Limited', 'unlimited' => 'Unlimited'], null, [
            'class' => 'form-group',
            'required' => 'required',
        ]) }}
                    </td>
                    <td>
                        <input required type="number" name="count[]" />
                    </td>
                    <td>
                        <input required type="number" name="usage_times[]" />
                    </td>
                    <td>
                        <input  required type="text" name="duration_of_use[]" />
                    </td>
                    <td>
                        <input required type="number" step="0.01" name="price_data[]" />
                    </td>

                </tr>`
                );
                i++;
            });

            //remove selected Row
            // $("#devices").on("click", ".btn", function () {
            //     $(this).parent().remove();
            //
            // });


        });
    </script>
@endsection
</x-master-layout>
