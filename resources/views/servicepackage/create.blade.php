<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
@endphp
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block card-stretch">
                <div class="card-body p-0">
                    <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                        <h5 class="font-weight-bold">{{ !empty($servicepackage->id) ? ($isAr ? 'تحديث باقة الخدمات' : 'Update Service Bundle') : ($isAr ? 'إنشاء باقة خدمات' : 'Create Service Bundle') }}</h5>
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
                            {{ Form::label('name', __('messages.english_name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                            {{ Form::text('name', old('name', $servicepackage->name), ['placeholder' => __('messages.english_name'), 'class' => 'form-control', 'required']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('name_ar', __('messages.arabic_name').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                            {{ Form::text('name_ar', old('name_ar', $servicepackage->name_ar), ['placeholder' => __('messages.arabic_name'), 'class' => 'form-control', 'dir' => 'rtl', 'required']) }}
                            <small class="help-block with-errors text-danger"></small>
                        </div>
                        <div class="form-group col-md-4" id="price_div">
                                {{ Form::label('price', ($isAr ? 'سعر الباقة (بعد الخصم)' : 'Bundle Price (After Discount)').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::number('price',null, [ 'min' => 0, 'step' => 'any' , 'placeholder' => 'Enter discounted bundle price','class' =>'form-control','id' => 'price' ]) }}
                                <small class="help-block text-muted">{{ $isAr ? "المبلغ الذي يدفعه العميل. اتركه فارغاً لاستخدام المجموع الأصلي." : "Customer pays this price. Leave empty to use the original total." }}</small>
                            </div>
                        <div class="form-group col-md-4">
                                <label class="form-control-label">{{ $isAr ? "السعر الأصلي (قبل الخصم)" : "Original Price (Before Discount)" }}</label>
                                <input type="text" class="form-control" id="original_price_preview" value="0.00" readonly>
                                <small class="help-block text-muted">{{ $isAr ? "محسوب تلقائياً من مجموع أسعار الخدمات المختارة." : "Auto-calculated from selected service prices." }}</small>
                            </div>
                        <div class="form-group col-md-4">
                            {{ Form::label('status',trans('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                            {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'id' => 'role' ,'class' =>'form-control select2js','required']) }}
                        </div>
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
                            <h3>{{ $isAr ? "تفاصيل خدمات الباقة" : "Bundle Service Details" }}</h3>

                        <div class="table-responsive">
                            <div class="d-flex justify-content-end flex-wrap gap-2">
                                <a href="{{ route('service.create') }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fa fa-plus"></i> {{ $isAr ? "إنشاء خدمة جديدة" : "Create New Service" }}
                                </a>
                                <button type="button" class="btn btn-sm btn-primary" id="add_service"><i class="fa fa-plus-circle"></i> {{ $isAr ? "إضافة خدمة" : "Add Service" }}</button>
                            </div>
                            <br>
                            <br>
                            <br>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">{{ $isAr ? "الخدمة" : "Service" }}</th>
                                    <th scope="col">{{ $isAr ? "سعر الخدمة" : "Service Price" }}</th>
                                    <th scope="col" class="text-right">{{ $isAr ? "الإجراء" : "Action" }}</th>
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
                var total = selectedServiceIds().reduce(function(sum, serviceId) {
                    return sum + parseFloat(servicePrices[serviceId] || 0);
                }, 0);
                $('#original_price_preview').val(formatPrice(total));
                $('#price').attr('placeholder', total > 0 ? formatPrice(total) : 'Enter discounted bundle price');
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
