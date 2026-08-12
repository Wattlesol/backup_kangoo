<x-master-layout>
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                        <h5 class="font-weight-bold">{{ !empty($servicepackage->id) ? 'Update Service Bundle' : 'Create Service Bundle' }}</h5>
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
                        <div class="form-group col-md-4">
                            {{ Form::label('name_ar','Arabic Name <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                            {{ Form::text('name_ar',old('name_ar'),['placeholder' => 'اسم الحزمة بالعربية','class' =>'form-control','dir'=>'rtl','required']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-4 sanad-legacy-field">
                            {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.user') ]).' ',['class'=>'form-control-label',],false) }}
                            <br />
                            {{ Form::select('legacy_user_id', [],"",[
                                'class' => 'select2js form-group category',
                                'id' => 'user_id',
                                'disabled' => true,
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
                        <div class="form-group col-md-4 sanad-legacy-field" id="select_subcategory">
                            {{ Form::label('name', "Price List",['class'=>'form-control-label'],false) }}
                            <br />
                            {{ Form::select('pricelist_id',$PriceList, [
                                'class' => 'select2 form-group pricelist',
                                'id' => 'pricelist',
                                'disabled' => true,
                                'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.pricelist') ]),

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
                        <div class="form-group col-md-4 sanad-legacy-field">
                            {{ Form::label('duration',__('messages.duration').'',['class'=>'form-control-label'], false ) }}
                            {{ Form::text('duration',old('duration'),['placeholder' => __('messages.duration'),'class' =>'form-control']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-4 sanad-legacy-field">
                            {{ Form::label('Car_Number',"Car Number",['class'=>'form-control-label'], false ) }}
                            {{ Form::text('car_number',old('car_number'),['placeholder' => "Car Number",'class' =>'form-control']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>

                        <div class="form-group col-md-4" id="price_div">
                                {{ Form::label('price',__('messages.price').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::number('price',null, [ 'min' => 0, 'step' => 'any' , 'placeholder' => 'Auto-calculated from selected services if left empty','class' =>'form-control','id' => 'price' ]) }}
                                <small class="help-block text-muted">Leave empty to use the total of selected service prices.</small>
                            </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('status',trans('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                            {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'id' => 'role' ,'class' =>'form-control select2js','required']) }}
                        </div>
                        <div class="form-group col-md-4 sanad-legacy-field">
                            {{ Form::label('status','Bundle Type',['class'=>'form-control-label'],false) }}
                            {{ Form::select('package_type', ['single' => 'فردي', 'family' => 'عائلي ','Breaks'=>"استراحات",'specific_place'=>"مكان محدد"], null, [
                        'class' =>'form-control',
                                        'disabled' => true,
                                    ]) }}                        </div>
                        <div class="form-group col-md-4">
                            <label class="form-control-label" for="package_attachment">{{ __('messages.image') }} <span class="text-danger">*</span> </label>
                            <div class="custom-file">
                            <input type="file" name="package_attachment[]" class="custom-file-input"  data-file-error="{{ __('messages.files_not_allowed') }}" multiple {{ empty($servicepackage->id) ? 'required' : '' }}>
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
                            <h3>Bundle Service Details</h3>

                        <div class="table-responsive">
                            <div class="d-flex justify-content-end flex-wrap gap-2">
                                <a href="{{ route('service.create') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa fa-plus"></i> Create New Service
                                </a>
                                <button type="button" class="btn btn-sm btn-primary" id="add_service"><i class="fa fa-plus-circle"></i> Add Service</button>
                            </div>
                            <br>
                            <br>
                            <br>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Service</th>
                                    <th scope="col">Service Price</th>
                                    <th scope="col" class="text-right">Action</th>
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
                var service_id = "{{$servicepackage->packageServices->pluck('service_id')->implode(',')}}"
                getSubCategory(category_id, subcategory_id)
                getService()

                   $(document).on('change', '#package_type', function() {

                    $('#custom_service_id').empty();
                    getService()
                })



                $(document).on('change', '#category_id', function() {
                    var category_id = $(this).val();
                    var subcategory_id = $('#subcategory_id').val();


                    $('#subcategory_id').empty();
                    getSubCategory(category_id, subcategory_id);

                    $('#custom_service_id').empty();
                    getService(category_id,subcategory_id)
                })

                $(document).on('change', '#subcategory_id', function() {
                    var subcategory_id = $(this).val();
                    var category_id = $('#category_id').val();
                    var selectedServiceIds = $('#custom_service_id').val();

                    $('#custom_service_id').empty();
                    getService(category_id,subcategory_id,selectedServiceIds)
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
            function getService(category_id,subcategory_id,service_id=''){
                var selectedServiceId = {!! json_encode($selectedServiceId) !!};
                $.ajax({
                    url: "{{ route('service-list') }}",
                    method:"POST",
                    data : { '_token': $('meta[name=csrf-token]').attr('content'),category_id:category_id,subcategory_id:subcategory_id },

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
            var servicePrices = @json($servicePrices ?? []);
            var existingServiceIds = @json($selectedServiceId ?? []);
            var serviceOptions = `{{ Form::select('service_id_data[]', $services_data, null, [
                'class' => 'form-control bundle-service-select',
                'data-placeholder' => __('messages.select_name', ['select' => __('messages.service')]),
                'required' => 'required',
            ]) }}`;

            function formatPrice(value) {
                var amount = parseFloat(value || 0);
                return amount.toFixed(2);
            }

            function selectedServiceIds() {
                return $('.bundle-service-select').map(function () {
                    return $(this).val();
                }).get().filter(Boolean);
            }

            function refreshRowPrice(row) {
                var serviceId = row.find('.bundle-service-select').val();
                row.find('.bundle-service-price').text(formatPrice(servicePrices[serviceId] || 0));
                row.find('.bundle-service-price-input').val(servicePrices[serviceId] || 0);
            }

            function refreshBundleTotal() {
                if ($('#price').val() !== '') return;
                var total = selectedServiceIds().reduce(function(sum, serviceId) {
                    return sum + parseFloat(servicePrices[serviceId] || 0);
                }, 0);
                $('#price').attr('placeholder', total > 0 ? formatPrice(total) : 'Auto-calculated from selected services if left empty');
            }

            function addServiceRow(serviceId) {
                var row = $(`<tr>
                    <td class="align-middle">${serviceOptions}</td>
                    <td class="align-middle">
                        <strong class="bundle-service-price">0.00</strong>
                        <input type="hidden" name="price_data[]" class="bundle-service-price-input" value="0">
                        <input type="hidden" name="service_type_data[]" value="limited">
                        <input type="hidden" name="count[]" value="1">
                        <input type="hidden" name="usage_times[]" value="1">
                        <input type="hidden" name="duration_of_use[]" value="">
                    </td>
                    <td class="align-middle text-right">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-service-row"><i class="fa fa-times"></i></button>
                    </td>
                </tr>`);
                $("#service_list").append(row);
                row.find('.bundle-service-select').val(serviceId || '');
                refreshRowPrice(row);
                refreshBundleTotal();
            }

            existingServiceIds.forEach(function(serviceId) {
                addServiceRow(serviceId);
            });

            // add new row in Main Dive
            $("#add_service").click(function () {
                addServiceRow();
                i++;
            });

            $("#service_list").on("change", ".bundle-service-select", function () {
                var row = $(this).closest('tr');
                refreshRowPrice(row);
                refreshBundleTotal();
            });

            $("#service_list").on("click", ".remove-service-row", function () {
                $(this).closest('tr').remove();
                refreshBundleTotal();
            });

            $('#price').on('input', refreshBundleTotal);


        });
    </script>
@endsection
</x-master-layout>
<style>.sanad-legacy-field{display:none!important}</style>
