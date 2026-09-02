<x-master-layout>
    @php $isAr = app()->getLocale() === 'ar'; @endphp
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ route('provider.index') }}" class="float-right btn btn-sm btn-primary"><i
                                    class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @if($auth_user->can('provider list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($providerdata,['method' => 'POST','route'=>'provider.store', 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'provider'] ) }}
                        {{ Form::hidden('id') }}
                        {{ Form::hidden('user_type','provider') }}
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ Form::label('first_name',__('messages.first_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                {{ Form::text('first_name',old('first_name'),['placeholder' => __('messages.first_name'),'class' =>'form-control','required']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('last_name',__('messages.last_name').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                {{ Form::text('last_name',old('last_name'),['placeholder' => __('messages.last_name'),'class' =>'form-control','required']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('username',__('messages.username').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                {{ Form::text('username',old('username'),['placeholder' => __('messages.username'),'class' =>'form-control','required']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('email', __('messages.email').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::email('email', old('email'), ['placeholder' => __('messages.email'), 'class' => 'form-control', 'required', 'pattern' => '[^@]+@[^@]+\.[a-zA-Z]{2,}', 'title' => 'Please enter a valid email address']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            @if (!isset($providerdata->id) || $providerdata->id == null)
                            <div class="form-group col-md-4">
                                {{ Form::label('password', __('messages.password').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('messages.password'), 'required', 'autocomplete' => 'new-password']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                            @endif

                            <div class="form-group col-md-4">
                                {{ Form::label('designation',__('messages.designation'),['class'=>'form-control-label'], false ) }}
                                {{ Form::text('designation',old('designation'),['placeholder' => __('messages.designation'),'class' =>'form-control']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                            <div class="form-group col-md-4">
                                {{ Form::label('providertype_id', __('messages.select_name',[ 'select' => __('messages.providertype') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('providertype_id', [optional($providerdata->providertype)->id => optional($providerdata->providertype)->name], optional($providerdata->providertype)->id, [
                                        'class' => 'select2js form-group providertype',
                                        'required',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.providertype') ]),
                                        'data-ajax--url' => route('ajax-list', ['type' => 'providertype']),
                                    ]) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('region', __('messages.select_name',[ 'select' => __('messages.region') ]),['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('region[]', @$providerdata->Region->pluck('regiondata.name','region_id')->toArray(),@$providerdata->Region->pluck('region_id')->toArray(), [
                                        'class' => 'select2js form-group country',
                                        'multiple'=>"multiple",
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.region') ]),
                                        'data-ajax--url' => route('ajax-list', ['type' => 'country']),
                                    ]) }}
                            </div>




                            <div class="form-group col-md-4">
                                {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.tax') ]),['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('tax_id[]', [], old('tax_id'), [
                                        'class' => 'select2js form-group tax_id',
                                        'id' =>'tax_id',
                                        'multiple' => 'multiple',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.tax') ]),
                                    ]) }}

                            </div>
                            <div class="form-group col-md-4">
                                {{ Form::label('contact_number',__('messages.contact_number').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                {{ Form::text('contact_number',old('contact_number'),['placeholder' => __('messages.contact_number'),'class' =>'form-control contact_number','required']) }}
                                <small class="help-block with-errors text-danger" id="contact_number_err"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('status',__('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'class' =>'form-control select2js','required']) }}
                            </div>

                            <div class="form-group col-md-4">
                                <label class="form-control-label" for="profile_image">{{ __('messages.profile_image') }}
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="profile_image" class="custom-file-input" accept="image/*">
                                    <label
                                        class="custom-file-label upload-label">{{  __('messages.choose_file',['file' =>  __('messages.profile_image') ]) }}</label>
                                </div>
                                <!-- <span class="selected_file"></span> -->
                            </div>

                            @if(getMediaFileExit($providerdata, 'profile_image'))
                            <div class="col-md-2 mb-2">
                                <img id="profile_image_preview" src="{{getSingleMedia($providerdata,'profile_image')}}"
                                    alt="#" class="attachment-image mt-1">
                                <a class="text-danger remove-file"
                                    href="{{ route('remove.file', ['id' => $providerdata->id, 'type' => 'profile_image']) }}"
                                    data--submit="confirm_form" data--confirmation='true' data--ajax="true"
                                    data-toggle="tooltip"
                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-message='{{ __("messages.remove_file_msg") }}'>
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                            @endif

                            <div class="form-group col-md-12">
                                {{ Form::label('address',__('messages.address'), ['class' => 'form-control-label']) }}
                                {{ Form::textarea('address', null, ['class'=>"form-control textarea" , 'rows'=>3  , 'placeholder'=> __('messages.address') ]) }}
                            </div>
                            <div class="form-group col-md-12">
                                <div class="partner-requirements-editor">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div>
                                            <label class="form-control-label font-weight-bold mb-1">{{ $isAr ? 'متطلبات التحقق من الشريك' : 'Partner Verification Requirements' }} <span class="text-danger">*</span></label>
                                            <div class="small text-muted">{{ $isAr ? 'اختر من مكتبة المستندات أو أنشئ متطلبات مخصصة لهذا الشريك.' : 'Select from the document library or create custom requirements for this partner.' }}</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="add-partner-verification-document">
                                            <i class="fa fa-plus"></i> {{ $isAr ? 'إضافة مستند مخصص' : 'Add Custom Document' }}
                                        </button>
                                    </div>

                                    @error('partner_verification_document_ids')
                                        <div class="alert alert-danger py-2">{{ $message }}</div>
                                    @enderror
                                    @error('custom_partner_verification_documents')
                                        <div class="alert alert-danger py-2">{{ $message }}</div>
                                    @enderror
                                    @error('custom_partner_verification_documents.*.text')
                                        <div class="alert alert-danger py-2">{{ $message }}</div>
                                    @enderror

                                    <div class="verification-library mb-3">
                                        <div class="verification-section-title">{{ $isAr ? 'مكتبة المستندات' : 'Document Library' }}</div>
                                        @forelse($partnerVerificationDocuments as $document)
                                            <label class="verification-option">
                                                <input type="checkbox" name="partner_verification_document_ids[]" value="{{ $document->id }}" {{ in_array($document->id, old('partner_verification_document_ids', $selectedVerificationDocumentIds), false) ? 'checked' : '' }}>
                                                <span class="verification-option-copy">
                                                    <strong>{{ $document->localized_name }}</strong>
                                                    @if($document->is_required)
                                                        <small>{{ $isAr ? 'متطلب افتراضي' : 'Default requirement' }}</small>
                                                    @else
                                                        <small>{{ $isAr ? 'متاح للاختيار' : 'Optional library item' }}</small>
                                                    @endif
                                                </span>
                                            </label>
                                        @empty
                                            <div class="text-muted small p-3">{{ $isAr ? 'مكتبة المستندات فارغة. يمكنك إضافة مستندات مخصصة أدناه.' : 'The document library is empty. You can add custom documents below.' }}</div>
                                        @endforelse
                                    </div>

                                    <div class="verification-section-title mb-2">{{ $isAr ? 'المستندات المخصصة لهذا الشريك' : 'Custom Documents for This Partner' }}</div>
                                    <div class="table-responsive">
                                        <table class="table table-sm partner-custom-documents mb-0">
                                            <thead>
                                                <tr>
                                                    <th>{{ $isAr ? 'اسم المستند بالعربية' : 'Document Name' }} <span class="text-danger">*</span></th>
                                                    <th class="verification-remove-column"></th>
                                                </tr>
                                            </thead>
                                            <tbody id="partner-verification-document-rows">
                                                @foreach($customVerificationDocumentRows as $index => $document)
                                                    <tr class="partner-verification-document-row">
                                                        <td><input type="text" name="custom_partner_verification_documents[{{ $index }}][text]" value="{{ $document['text'] ?? '' }}" class="form-control" maxlength="100" dir="{{ $isAr ? 'rtl' : 'ltr' }}" placeholder="{{ $isAr ? 'مثال: شهادة عضوية الغرفة التجارية' : 'Example: Chamber membership certificate' }}"></td>
                                                        <td class="text-center align-middle"><button type="button" class="btn btn-link text-danger p-0 remove-partner-verification-document" title="{{ $isAr ? 'إزالة المستند' : 'Remove document' }}"><i class="ri-close-circle-line"></i></button></td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <small class="text-muted d-block mt-2">{{ $isAr ? 'ستظهر المتطلبات المختارة في ملف الشريك إلى أن يرفع المستندات ويعتمدها فريق كويك.' : 'Selected requirements appear on the partner profile until the documents are uploaded and approved by Quick.' }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <div class="custom-control custom-switch custom-control-inline ">
                                    {{ Form::checkbox('is_featured', $providerdata->is_featured, null, ['class' => 'custom-control-input' , 'id' => 'is_featured' ]) }}
                                    <label class="custom-control-label"
                                        for="is_featured">{{ __('messages.set_as_featured')  }}
                                    </label>
                                </div>
                            </div>
                        </div>
                        {{ Form::submit( __('messages.save'), ['class'=>'btn btn-md btn-primary float-right']) }}
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @php
    $data = $providerdata->providerTaxMapping->pluck('tax_id')->implode(',');
    @endphp
    @section('bottom_script')
    <style>
        .partner-requirements-editor { border: 1px solid #e3e7ee; border-radius: 10px; padding: 16px; background: #fbfcfe; }
        .verification-section-title { color: #344054; font-size: 13px; font-weight: 700; }
        .verification-library { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 8px; max-height: 240px; overflow-y: auto; border: 1px solid #e3e7ee; border-radius: 8px; padding: 10px; background: #fff; }
        .verification-library .verification-section-title { grid-column: 1 / -1; padding: 2px 4px 5px; }
        .verification-option { display: flex; align-items: flex-start; gap: 9px; padding: 10px; margin: 0; border: 1px solid #edf0f5; border-radius: 8px; background: #fff; font-weight: 400; cursor: pointer; }
        .verification-option:hover { border-color: #b9c5ff; background: #f8f9ff; }
        .verification-option input { margin-top: 3px; flex: 0 0 auto; }
        .verification-option-copy { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
        .verification-option-copy strong { color: #344054; font-size: 13px; }
        .verification-option-copy small { color: #98a2b3; }
        .partner-custom-documents th { color: #667085; font-size: 12px; border-top: 0; }
        .partner-custom-documents td { vertical-align: middle; }
        .partner-custom-documents .verification-remove-column { width: 42px; }
        .partner-custom-documents tbody:empty::after { content: '{{ $isAr ? 'لا توجد مستندات مخصصة بعد.' : 'No custom documents added yet.' }}'; display: block; padding: 14px 8px; color: #98a2b3; font-size: 12px; }
    </style>
    <script type="text/javascript">
    (function($) {
        "use strict";
	        $(document).ready(function() {
                var partnerVerificationDocumentIndex = $('#partner-verification-document-rows .partner-verification-document-row').length;

                function partnerVerificationDocumentRow(index) {
                    return [
                        '<tr class="partner-verification-document-row">',
                            '<td><input type="text" name="custom_partner_verification_documents[' + index + '][text]" class="form-control" maxlength="100" dir="{{ $isAr ? 'rtl' : 'ltr' }}" placeholder="{{ $isAr ? 'مثال: شهادة عضوية الغرفة التجارية' : 'Example: Chamber membership certificate' }}" required></td>',
                            '<td class="text-center align-middle"><button type="button" class="btn btn-link text-danger p-0 remove-partner-verification-document" title="{{ $isAr ? 'إزالة المستند' : 'Remove document' }}"><i class="ri-close-circle-line"></i></button></td>',
                        '</tr>'
                    ].join('');
                }

                $('#add-partner-verification-document').on('click', function() {
                    $('#partner-verification-document-rows').append(partnerVerificationDocumentRow(partnerVerificationDocumentIndex));
                    partnerVerificationDocumentIndex += 1;
                });

                $(document).on('click', '.remove-partner-verification-document', function() {
                    $(this).closest('.partner-verification-document-row').remove();
                });

                var country_id = "{{ isset($providerdata->country_id) ? $providerdata->country_id : 0 }}";
            var state_id = "{{ isset($providerdata->state_id) ? $providerdata->state_id : 0 }}";
            var city_id = "{{ isset($providerdata->city_id) ? $providerdata->city_id : 0 }}";

            var provider_id = "{{ isset($providerdata->id) ? $providerdata->id : '' }}";
            var provider_tax_id = "{{ isset($data) ? $data : [] }}";

            getTax(provider_id, provider_tax_id)
            stateName(country_id, state_id);
            $(document).on('change', '#country_id', function() {
                var country = $(this).val();
                $('#state_id').empty();
                $('#city_id').empty();
                stateName(country);
            })
            $(document).on('change', '#state_id', function() {
                var state = $(this).val();
                $('#city_id').empty();
                cityName(state, city_id);
            })
        })

        $(document).on('keyup', '.contact_number', function() {
        var contactNumberInput = document.getElementById('contact_number');
        var inputValue = contactNumberInput.value;
        inputValue = inputValue.replace(/[^0-9+\- ]/g, '');
        if (inputValue.length > 15) {
            inputValue = inputValue.substring(0, 15);
            $('#contact_number_err').text('Contact number should not exceed 15 characters');
        } else {
            $('#contact_number_err').text('');
        }
        contactNumberInput.value = inputValue;
        if (inputValue.match(/^[0-9+\- ]+$/)) {
            $('#contact_number_err').text('');
        } else {
            $('#contact_number_err').text('Please enter a valid mobile number');
        }
    });

        function stateName(country, state = "") {
            var state_route = "{{ route('ajax-list', [ 'type' => 'state','country_id' =>'']) }}" + country;
            state_route = state_route.replace('amp;', '');

            $.ajax({
                url: state_route,
                success: function(result) {
                    $('#state_id').select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name',['select' => trans('messages.state')]) }}",
                        data: result.results
                    });
                    if (state != null) {
                        $("#state_id").val(state).trigger('change');
                    }
                }
            });
        }

        function cityName(state, city = "") {
            var city_route = "{{ route('ajax-list', [ 'type' => 'city' ,'state_id' =>'']) }}" + state;
            city_route = city_route.replace('amp;', '');

            $.ajax({
                url: city_route,
                success: function(result) {
                    $('#city_id').select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name',['select' => trans('messages.city')]) }}",
                        data: result.results
                    });
                    if (city != null || city != 0) {
                        $("#city_id").val(city).trigger('change');
                    }
                }
            });
        }

        function getTax(provider_id, provider_tax_id = "") {
            var provider_tax_route = "{{ route('ajax-list', [ 'type' => 'provider_tax','provider_id' =>'']) }}" +
                provider_id;
            provider_tax_route = provider_tax_route.replace('amp;', '');

            $.ajax({
                url: provider_tax_route,
                success: function(result) {
                    $('#tax_id').select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name',['select' => trans('messages.tax')]) }}",
                        data: result.results
                    });
                    if (provider_tax_id != "") {
                        $('#tax_id').val(provider_tax_id.split(',')).trigger('change');
                    }
                }
            });
        }
    })(jQuery);
    </script>
    @endsection
</x-master-layout>
