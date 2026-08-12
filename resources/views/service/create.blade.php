<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ route('service.index') }}" class="float-right btn btn-sm btn-primary"><i
                                    class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @if($auth_user->can('service list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($servicedata,['method' => 'POST','route'=>'service.store', 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'service'] ) }}
                        {{ Form::hidden('id') }}
                        {{ Form::hidden('provider_id', old('provider_id', $servicedata->provider_id ?: admin_id()), ['id' => 'provider_id']) }}
                        {{ Form::hidden('type', old('type', $servicedata->type ?: 'fixed')) }}
                        {{ Form::hidden('price', old('price', $servicedata->price ?: $servicedata->service_fee ?: 0), ['id' => 'price']) }}
                        @php
                            $isPartnerServiceEditor = auth()->user()->hasRole('provider') && !empty($servicedata->id);
                            $sanadReadOnly = $isPartnerServiceEditor ? ['readonly' => true] : [];
                            $requiredSkillsText = old('required_employee_skills', is_array($servicedata->required_employee_skills) ? implode("\n", $servicedata->required_employee_skills) : $servicedata->required_employee_skills);
                            $storedRequiredDocuments = old('required_documents', $servicedata->required_documents);
                            if (is_string($storedRequiredDocuments)) {
                                $decodedRequiredDocuments = json_decode($storedRequiredDocuments, true);
                                $storedRequiredDocuments = json_last_error() === JSON_ERROR_NONE ? $decodedRequiredDocuments : preg_split('/\r\n|\r|\n/', $storedRequiredDocuments);
                            }
                            $requiredDocumentRows = collect($storedRequiredDocuments ?: [])->map(function ($document) {
                                if (is_string($document)) {
                                    $name = trim($document);
                                    return [
                                        'key' => \Illuminate\Support\Str::slug($name, '_'),
                                        'name' => $name,
                                        'required' => true,
                                        'approval_required' => true,
                                        'mime_types' => '',
                                        'max_size_mb' => 10,
                                    ];
                                }
                                $mimeTypes = $document['mime_types'] ?? '';
                                if (is_array($mimeTypes)) {
                                    $mimeTypes = implode(', ', $mimeTypes);
                                }
                                return [
                                    'key' => $document['key'] ?? \Illuminate\Support\Str::slug($document['name'] ?? '', '_'),
                                    'name' => $document['name'] ?? '',
                                    'required' => filter_var($document['required'] ?? true, FILTER_VALIDATE_BOOLEAN),
                                    'approval_required' => filter_var($document['approval_required'] ?? true, FILTER_VALIDATE_BOOLEAN),
                                    'mime_types' => $mimeTypes,
                                    'max_size_mb' => $document['max_size_mb'] ?? 10,
                                ];
                            })->filter(function ($document) {
                                return trim($document['name'] ?? '') !== '';
                            })->values()->all();
                            if (empty($requiredDocumentRows)) {
                                $requiredDocumentRows = [[
                                    'key' => '',
                                    'name' => '',
                                    'required' => true,
                                    'approval_required' => true,
                                    'mime_types' => 'image/jpeg, image/png, application/pdf',
                                    'max_size_mb' => 10,
                                ]];
                            }
                            $storedInstructionSteps = old('service_instructions', $servicedata->service_instructions);
                            if (is_string($storedInstructionSteps)) {
                                $decodedInstructionSteps = json_decode($storedInstructionSteps, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedInstructionSteps)) {
                                    $storedInstructionSteps = $decodedInstructionSteps;
                                } else {
                                    $storedInstructionSteps = preg_split('/\r\n|\r|\n/', $storedInstructionSteps);
                                }
                            }
                            $serviceInstructionRows = collect($storedInstructionSteps ?: [])->map(function ($step, $index) {
                                if (is_string($step)) {
                                    return [
                                        'title' => 'Step '.($index + 1),
                                        'instruction' => trim($step),
                                    ];
                                }
                                return [
                                    'title' => $step['title'] ?? 'Step '.($index + 1),
                                    'instruction' => $step['instruction'] ?? $step['description'] ?? '',
                                ];
                            })->filter(function ($step) {
                                return trim($step['title'] ?? '') !== '' || trim($step['instruction'] ?? '') !== '';
                            })->values()->all();
                            if (empty($serviceInstructionRows)) {
                                $serviceInstructionRows = [[
                                    'title' => 'Step 1',
                                    'instruction' => '',
                                ]];
                            }
                        @endphp
                        <div class="row">
                            <div class="form-group col-md-4">
                                {{ Form::label('name_en', 'English Name <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('name_en', old('name_en', $servicedata->name_en ?: $servicedata->name), array_merge(['placeholder' => 'Service name in English', 'class' => 'form-control', 'required'], $sanadReadOnly)) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('name_ar', 'Arabic Name <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('name_ar', old('name_ar'), array_merge(['class' => 'form-control', 'placeholder' => 'Service name in Arabic', 'required', 'dir' => 'rtl'], $sanadReadOnly)) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.category') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('category_id', [optional($servicedata->category)->id => optional($servicedata->category)->name], optional($servicedata->category)->id, [
                                            'class' => 'select2js form-group category',
                                            'required',
                                            'id' => 'category_id',
                                            'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.category') ]),
                                            'data-ajax--url' => route('ajax-list', ['type' => 'category']),
                                        ]) }}

                            </div>
                            <div class="form-group col-md-4">
                                {{ Form::label('subcategory_id', __('messages.select_name',[ 'select' => __('messages.subcategory') ]),['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('subcategory_id', [], [
                                        'class' => 'select2js form-group subcategory_id',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.subcategory') ]),
                                    ]) }}
                            </div>

                            @if(false && auth()->user()->hasAnyRole(['admin','demo_admin']))
                            <div class="form-group col-md-4 d-none">
                                {{ Form::label('name', 'Select Partner <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('provider_id', [ optional($servicedata->providers)->id => optional($servicedata->providers)->display_name ], optional($servicedata->providers)->id, [
                                            'class' => 'select2js form-group',
                                            'id' => 'provider_id',
                                            'onchange' => 'selectprovider(this)',
                                            'disabled',
                                            'data-placeholder' => 'Select Partner',
                                            'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                                        ]) }}
                            </div>
                            @endif
                            <div class="form-group col-md-4 d-none">
                                {{ Form::label('name', 'Select Partner Address',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('provider_address_id[]', [], old('provider_address_id'), [
                                        'class' => 'select2js form-group provider_address_id',
                                        'id' =>'provider_address_id',
                                        'multiple' => 'multiple',
                                        'disabled',
                                        'data-placeholder' => 'Select Partner Address',
                                    ]) }}
                                   
                               
                                 @if(auth()->user()->hasAnyRole(['provider']))
                                    <a href="{{ route('provideraddress.create', ['provideraddress' => auth()->id()]) }}" id="add_provider_address_link" class=""><i class="fa fa-plus-circle mt-2"></i>
                                 Add Partner Address</a>
                                 @else
                                    <a href="#" id="add_provider_address_link" class=""><i class="fa fa-plus-circle mt-2"></i>
                                 Add Partner Address</a>
                                 @endif
                            </div> 

                            <div class="form-group col-md-4 d-none">
                                {{ Form::label('type',__('messages.price_type').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::select('type',['fixed' => __('messages.fixed') , 'hourly' => __('messages.hourly'), 'free' => __('messages.free') ],old('status'),[ 'class' =>'form-control select2js','disabled' ,'id'=>'price_type']) }}
                            </div>
                            <div class="form-group col-md-4 d-none" id="price_div">
                                {{ Form::label('price',__('messages.price').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::text('price',null, array_merge([ 'min' => 1, 'step' => 'any' , 'placeholder' => __('messages.price'),'class' =>'form-control', 'disabled','id' => 'price_legacy',  'pattern' => '^\\d+(\\.\\d{1,2})?$' ], $sanadReadOnly)) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4 d-none" id="discount_div">
                                {{ Form::label('discount',__('messages.discount').' %', ['class' => 'form-control-label']) }}
                                {{ Form::number('discount',null, [ 'min' => 0,'max' => 99, 'step' => 'any' , 'id' =>'discount','placeholder' => __('messages.discount'),'class' =>'form-control', 'disabled']) }}

                                <span id="discount-error" class="text-danger"></span>
                            </div>


                            <div class="form-group col-md-4 d-none">
                                {{ Form::label('duration', __('messages.duration').' (hours) ', ['class' => 'form-control-label'], false) }}
                                {{ Form::text('duration', old('duration'), ['placeholder' => __('messages.duration'), 'class' => 'form-control min-datetimepicker-time', 'disabled']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('status',__('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'class' =>'form-control select2js','required']) }}
                            </div>
                            
                            <div class="form-group col-md-4 d-none">
                                    {{ Form::label('visit_type', __('messages.visit_type').' ',['class'=>'form-control-label'],false) }}
                                    <br />
                                    {{ Form::select('visit_type',$visittype,old('visit_type'),[ 'id' => 'visit_type' ,'class' =>'form-control select2js','disabled']) }}
                                </div>

                            <div class="form-group col-md-4">
                                <label class="form-control-label" for="service_attachment">{{ __('messages.image') }}
                                </label>
                                <div class="custom-file">
                                    <input type="file" name="service_attachment[]" class="custom-file-input"
                                        data-file-error="{{ __('messages.files_not_allowed') }}" multiple>
                                    <label
                                        class="custom-file-label upload-label">{{ __('messages.choose_file',['file' =>  __('messages.attachments') ]) }}</label>
                                </div>
                            </div>
                        </div>


                        <div class="row service_attachment_div">
                            <div class="col-md-12">


                                @if(getMediaFileExit($servicedata, 'service_attachment'))
                                @php

                                $attchments = $servicedata->getMedia('service_attachment');

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

                                            <div class="col-md-2 pr-10 text-center galary file-gallary-{{$servicedata->id}}"
                                                data-gallery=".file-gallary-{{$servicedata->id}}"
                                                id="service_attachment_preview_{{$attchment->id}}">
                                                @if($extention)
                                                <a id="attachment_files" href="{{ $attchment->getFullUrl() }}"
                                                    class="list-group-item-action attachment-list" target="_blank">
                                                    <img src="{{ $attchment->getFullUrl() }}" class="attachment-image"
                                                        alt="">
                                                </a>
                                                @else
                                                <a id="attachment_files"
                                                    class="video list-group-item-action attachment-list"
                                                    href="{{ $attchment->getFullUrl() }}">
                                                    <img src="{{ asset('images/file.png') }}" class="attachment-file">
                                                </a>
                                                @endif
                                                <a class="text-danger remove-file"
                                                    href="{{ route('remove.file', ['id' => $attchment->id, 'type' => 'service_attachment']) }}"
                                                    data--submit="confirm_form" data--confirmation='true'
                                                    data--ajax="true" data-toggle="tooltip"
                                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.attachments") ] ) }}'
                                                    data-message='{{ __("messages.remove_file_msg") }}'>
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
                            <div class="form-group col-md-12">
                                {{ Form::label('description',__('messages.description'), ['class' => 'form-control-label']) }}
                                {{ Form::textarea('description', null, array_merge(['class'=>"form-control textarea" , 'rows'=>3  , 'placeholder'=> __('messages.description') ], $sanadReadOnly)) }}
                            </div>
                            <div class="form-group col-md-12">
                                <div class="sanad-service-master-data">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div>
                                            <h5 class="font-weight-bold mb-1">Sanad Service Master Data</h5>
                                            <span class="text-muted">Centralized government-service metadata used by web dashboards and mobile apps</span>
                                        </div>
                                        @if($isPartnerServiceEditor)
                                            <span class="badge badge-light">Partner pricing and public details are read-only</span>
                                        @endif
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            {{ Form::label('government_entity', 'Government Entity', ['class' => 'form-control-label']) }}
                                            {{ Form::text('government_entity', null, array_merge(['class' => 'form-control', 'placeholder' => 'Ministry or authority'], $sanadReadOnly)) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('estimated_completion_time', 'Estimated Completion Time', ['class' => 'form-control-label']) }}
                                            {{ Form::text('estimated_completion_time', null, array_merge(['class' => 'form-control', 'placeholder' => 'Example: 3 business days'], $sanadReadOnly)) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('government_fee', 'Government Fees', ['class' => 'form-control-label']) }}
                                            {{ Form::number('government_fee', null, array_merge(['class' => 'form-control', 'min' => 0, 'step' => 'any'], $sanadReadOnly)) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('service_fee', 'Service Fees', ['class' => 'form-control-label']) }}
                                            {{ Form::number('service_fee', null, array_merge(['class' => 'form-control', 'min' => 0, 'step' => 'any'], $sanadReadOnly)) }}
                                        </div>
                                        <div class="form-group col-md-12">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                {{ Form::label('required_documents', 'Required Documents', ['class' => 'form-control-label mb-0']) }}
                                                @if(!$isPartnerServiceEditor)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-required-document">
                                                        <i class="fa fa-plus"></i> Add Document
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="table-responsive sanad-required-documents">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 24%;">Document Name</th>
                                                            <th style="width: 18%;">Key</th>
                                                            <th style="width: 14%;">Requirement</th>
                                                            <th style="width: 16%;">Approval</th>
                                                            <th style="width: 18%;">Accepted File Types</th>
                                                            <th style="width: 8%;">Max MB</th>
                                                            <th style="width: 2%;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="required-document-rows">
                                                        @foreach($requiredDocumentRows as $index => $document)
                                                            <tr class="required-document-row">
                                                                <td>
                                                                    <input type="text" name="required_documents[{{ $index }}][name]" value="{{ $document['name'] }}" class="form-control required-document-name" placeholder="Example: Driving license front" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="required_documents[{{ $index }}][key]" value="{{ $document['key'] }}" class="form-control required-document-key" placeholder="driving_license_front" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>
                                                                </td>
                                                                <td>
                                                                    <select name="required_documents[{{ $index }}][required]" class="form-control" {{ $isPartnerServiceEditor ? 'disabled' : '' }}>
                                                                        <option value="1" {{ $document['required'] ? 'selected' : '' }}>Required</option>
                                                                        <option value="0" {{ !$document['required'] ? 'selected' : '' }}>Optional</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <select name="required_documents[{{ $index }}][approval_required]" class="form-control" {{ $isPartnerServiceEditor ? 'disabled' : '' }}>
                                                                        <option value="1" {{ $document['approval_required'] ? 'selected' : '' }}>Approval Required</option>
                                                                        <option value="0" {{ !$document['approval_required'] ? 'selected' : '' }}>No Approval</option>
                                                                    </select>
                                                                </td>
                                                                <td>
                                                                    <input type="text" name="required_documents[{{ $index }}][mime_types]" value="{{ $document['mime_types'] }}" class="form-control" placeholder="image/jpeg, application/pdf" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="required_documents[{{ $index }}][max_size_mb]" value="{{ $document['max_size_mb'] }}" class="form-control" min="1" max="100" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    @if(!$isPartnerServiceEditor)
                                                                        <button type="button" class="btn btn-link text-danger p-0 remove-required-document" title="Remove document">
                                                                            <i class="ri-close-circle-line"></i>
                                                                        </button>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <small class="text-muted d-block mt-2">These documents will be shown as selectable requirements in customer, partner, and request document upload flows.</small>
                                        </div>
                                        <div class="form-group col-md-12">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                {{ Form::label('service_instructions', 'Service Instructions', ['class' => 'form-control-label mb-0']) }}
                                                @if(!$isPartnerServiceEditor)
                                                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-service-instruction">
                                                        <i class="fa fa-plus"></i> Add Step
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="table-responsive sanad-service-instructions">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th style="width: 24%;">Step Title</th>
                                                            <th style="width: 74%;">Instruction</th>
                                                            <th style="width: 2%;"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="service-instruction-rows">
                                                        @foreach($serviceInstructionRows as $index => $step)
                                                            <tr class="service-instruction-row">
                                                                <td>
                                                                    <input type="text" name="service_instructions[{{ $index }}][title]" value="{{ $step['title'] }}" class="form-control" placeholder="Step {{ $index + 1 }}" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>
                                                                </td>
                                                                <td>
                                                                    <textarea name="service_instructions[{{ $index }}][instruction]" class="form-control" rows="2" placeholder="What should the customer do in this step?" {{ $isPartnerServiceEditor ? 'readonly' : '' }}>{{ $step['instruction'] }}</textarea>
                                                                </td>
                                                                <td class="text-center align-middle">
                                                                    @if(!$isPartnerServiceEditor)
                                                                        <button type="button" class="btn btn-link text-danger p-0 remove-service-instruction" title="Remove step">
                                                                            <i class="ri-close-circle-line"></i>
                                                                        </button>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <small class="text-muted d-block mt-2">These steps are shown to customers as ordered service instructions.</small>
                                        </div>
                                        <div class="form-group col-md-6">
                                            {{ Form::label('terms_and_conditions', 'Terms & Conditions', ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('terms_and_conditions', null, array_merge(['class' => 'form-control', 'rows' => 4], $sanadReadOnly)) }}
                                        </div>
                                        <div class="form-group col-md-6 d-none">
                                            {{ Form::label('required_employee_skills', 'Required Employee Skills', ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('required_employee_skills', $requiredSkillsText, ['class' => 'form-control', 'rows' => 4, 'placeholder' => "One skill per line"]) }}
                                        </div>
                                        <div class="form-group col-md-12 d-none">
                                            {{ Form::label('partner_availability_notes', 'Partner Internal Notes / Availability', ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('partner_availability_notes', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Execution notes, availability, capacity, or partner-only comments']) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if(!empty( $slotservice) && $slotservice == 1)
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('is_slot', $servicedata->is_slot, null, ['class' => 'custom-control-input', 'id' => 'is_slot' ]) }}
                                    <label class="custom-control-label"
                                        for="is_slot">{{ __('messages.slot') }}</label>
                                </div>
                            </div>
                            @endif
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('is_featured', $servicedata->is_featured, null, ['class' => 'custom-control-input', 'id' => 'is_featured' ]) }}
                                    <label class="custom-control-label"
                                        for="is_featured">{{ __('messages.set_as_featured') }}</label>
                                </div>
                            </div>
                            <!-- @if(!empty( $digitalservicedata) && $digitalservicedata->value == 1)
                            <div class="form-group col-md-3">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('digital_service', $servicedata->digital_service, null, ['class' => 'custom-control-input', 'id' => 'digital_service' ]) }}
                                    <label class="custom-control-label"
                                        for="digital_service">{{ __('messages.digital_service') }}</label>
                                </div>
                            </div>
                            @endif -->
                            @if(!empty( $advancedPaymentSetting) && $advancedPaymentSetting == 1)
                            <div class="form-group col-md-3" id="is_enable_advance">
                                <div class="custom-control custom-switch">
                                    {{ Form::checkbox('is_enable_advance_payment', $servicedata->is_enable_advance_payment , null, ['class' => 'custom-control-input' , 'id' => 'is_enable_advance_payment' ]) }}
                                    <label class="custom-control-label"
                                        for="is_enable_advance_payment">{{ __('messages.enable_advanced_payment')  }}
                                    </label>
                                </div>
                            </div>
                            @endif
                            <div class="form-group col-md-4 d-none" id="amount">
                            {{ Form::label('advance_payment_amount', __('messages.advance_payment_amount').' <span class="text-danger"></span> (%)', ['class' => 'form-control-label'], false) }}
                                {{ Form::number('advance_payment_amount',old('advance_payment_amount'),['placeholder' => __('messages.amount'),'class' =>'form-control','id' => 'advance_payment_amount' ,'min' => '1', 'max' => '99']) }}
                                <small class="help-block with-errors text-danger"></small>
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
    $data = $servicedata->providerServiceAddress->pluck('provider_address_id')->implode(',');
    @endphp
    @section('bottom_script')
    <style>
        .sanad-service-master-data {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #f8f9fb;
            padding: 16px;
        }
        .sanad-service-master-data .gap-2 {
            gap: 8px;
        }
        .sanad-required-documents table th,
        .sanad-required-documents table td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .sanad-required-documents .form-control {
            min-width: 140px;
        }
        .sanad-required-documents .required-document-name {
            min-width: 220px;
        }
        .sanad-service-instructions table th,
        .sanad-service-instructions table td {
            vertical-align: top;
        }
        .sanad-service-instructions textarea {
            min-height: 72px;
        }
    </style>
    <script type="text/javascript">
    var discountInput = document.getElementById('discount');
    var discountError = document.getElementById('discount-error');

   
      document.addEventListener('DOMContentLoaded', function () {
        var initialProviderId = document.getElementById('provider_id').value;
        selectprovider({ value: initialProviderId }); 
        document.getElementById('add_provider_address_link').addEventListener('click', function (event) {
            event.preventDefault();
            var providerId = document.getElementById('provider_id').value;
            var providerAddressCreateUrl = "{{ route('provideraddress.create', ['provideraddress' => '']) }}";
            providerAddressCreateUrl = providerAddressCreateUrl.replace('provideraddress=', 'provideraddress=' + providerId);
            window.location.href = providerAddressCreateUrl;
        });


      
   

    });

    function selectprovider(selectElement){

        var providerId = selectElement.value;
        var addProviderAddressLink =  document.getElementById('add_provider_address_link');

        if(providerId){
            addProviderAddressLink.classList.remove('d-none');
        } else {
            addProviderAddressLink.classList.add('d-none');
        }
    }

     
    discountInput.addEventListener('input', function() {
        var discountValue = parseFloat(discountInput.value);
        if (isNaN(discountValue) || discountValue < 0 || discountValue > 99) {
            discountError.textContent = "{{ __('Discount value should be between 0 to 99') }}";
        } else {
            discountError.textContent = "";
        }
    });

    var isEnableAdvancePayment = $("input[name='is_enable_advance_payment']").prop('checked');

    var priceType = $("#price_type").val();

    enableAdvancePayment(priceType);
    checkEnablePayment(isEnableAdvancePayment);

    $("#is_enable_advance_payment").change(function() {
        isEnableAdvancePayment = $(this).prop('checked');
        checkEnablePayment(isEnableAdvancePayment);
        updateAmountVisibility(priceType, isEnableAdvancePayment);
    });

    $("#price_type").change(function() {
        priceType = $(this).val();
        enableAdvancePayment(priceType);
        updateAmountVisibility(priceType, isEnableAdvancePayment);
    });

    function checkEnablePayment(value) {
        $("#amount").toggleClass('d-none', !value);
        $('#advance_payment_amount').prop('required', value);
    }

    function enableAdvancePayment(type) {
        $("#is_enable_advance").toggleClass('d-none', type !== 'fixed');
    }

    function updateAmountVisibility(type, isEnableAdvancePayment) {
        if (type === 'fixed' && !$("#is_enable_advance").hasClass('d-none') && isEnableAdvancePayment) {
            $("#amount").removeClass('d-none');
        } else {
            $("#amount").addClass('d-none');
        }
    }

	    (function($) {
	        "use strict";
	        $(document).ready(function() {
                var requiredDocumentIndex = $('#required-document-rows .required-document-row').length;

                function slugDocumentKey(value) {
                    return (value || '')
                        .toString()
                        .trim()
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '_')
                        .replace(/^_+|_+$/g, '');
                }

                function requiredDocumentRow(index) {
                    return [
                        '<tr class="required-document-row">',
                            '<td><input type="text" name="required_documents[' + index + '][name]" class="form-control required-document-name" placeholder="Example: Driving license front"></td>',
                            '<td><input type="text" name="required_documents[' + index + '][key]" class="form-control required-document-key" placeholder="driving_license_front"></td>',
                            '<td><select name="required_documents[' + index + '][required]" class="form-control"><option value="1" selected>Required</option><option value="0">Optional</option></select></td>',
                            '<td><select name="required_documents[' + index + '][approval_required]" class="form-control"><option value="1" selected>Approval Required</option><option value="0">No Approval</option></select></td>',
                            '<td><input type="text" name="required_documents[' + index + '][mime_types]" class="form-control" value="image/jpeg, image/png, application/pdf" placeholder="image/jpeg, application/pdf"></td>',
                            '<td><input type="number" name="required_documents[' + index + '][max_size_mb]" class="form-control" min="1" max="100" value="10"></td>',
                            '<td class="text-center align-middle"><button type="button" class="btn btn-link text-danger p-0 remove-required-document" title="Remove document"><i class="ri-close-circle-line"></i></button></td>',
                        '</tr>'
                    ].join('');
                }

                $('#add-required-document').on('click', function() {
                    $('#required-document-rows').append(requiredDocumentRow(requiredDocumentIndex));
                    requiredDocumentIndex += 1;
                });

                $(document).on('click', '.remove-required-document', function() {
                    var rows = $('#required-document-rows .required-document-row');
                    if (rows.length <= 1) {
                        $(this).closest('tr').find('input[type="text"]').val('');
                        $(this).closest('tr').find('input[type="number"]').val('10');
                        return;
                    }
                    $(this).closest('tr').remove();
                });

                $(document).on('input', '.required-document-name', function() {
                    var row = $(this).closest('tr');
                    var keyInput = row.find('.required-document-key');
                    if (!keyInput.data('manually-edited')) {
                        keyInput.val(slugDocumentKey($(this).val()));
                    }
                });

                $(document).on('input', '.required-document-key', function() {
                    $(this).data('manually-edited', true);
                    $(this).val(slugDocumentKey($(this).val()));
                });

                var serviceInstructionIndex = $('#service-instruction-rows .service-instruction-row').length;

                function serviceInstructionRow(index) {
                    return [
                        '<tr class="service-instruction-row">',
                            '<td><input type="text" name="service_instructions[' + index + '][title]" class="form-control" value="Step ' + (index + 1) + '" placeholder="Step ' + (index + 1) + '"></td>',
                            '<td><textarea name="service_instructions[' + index + '][instruction]" class="form-control" rows="2" placeholder="What should the customer do in this step?"></textarea></td>',
                            '<td class="text-center align-middle"><button type="button" class="btn btn-link text-danger p-0 remove-service-instruction" title="Remove step"><i class="ri-close-circle-line"></i></button></td>',
                        '</tr>'
                    ].join('');
                }

                $('#add-service-instruction').on('click', function() {
                    $('#service-instruction-rows').append(serviceInstructionRow(serviceInstructionIndex));
                    serviceInstructionIndex += 1;
                });

                $(document).on('click', '.remove-service-instruction', function() {
                    var rows = $('#service-instruction-rows .service-instruction-row');
                    if (rows.length <= 1) {
                        $(this).closest('tr').find('input[type="text"]').val('Step 1');
                        $(this).closest('tr').find('textarea').val('');
                        return;
                    }
                    $(this).closest('tr').remove();
                });

	            var provider_id = "{{ isset($servicedata->provider_id) ? $servicedata->provider_id : '' }}";
            var provider_address_id = "{{ isset($data) ? $data : [] }}";

            var category_id = "{{ isset($servicedata->category_id) ? $servicedata->category_id : '' }}";
            var subcategory_id =
                "{{ isset($servicedata->subcategory_id) ? $servicedata->subcategory_id : '' }}";

            var price_type = "{{ isset($servicedata->type) ? $servicedata->type : '' }}";

            providerAddress(provider_id, provider_address_id)
            getSubCategory(category_id, subcategory_id)
            priceformat(price_type)

            $(document).on('change', '#provider_id', function() {
                var provider_id = $(this).val();
                $('#provider_address_id').empty();
                providerAddress(provider_id, provider_address_id);
            })
            $(document).on('change', '#category_id', function() {
                var category_id = $(this).val();
                $('#subcategory_id').empty();
                getSubCategory(category_id, subcategory_id);
            })
            $(document).on('change', '#price_type', function() {
                var price_type = $(this).val();
                priceformat(price_type);
            })


            $('.galary').each(function(index, value) {
                let galleryClass = $(value).attr('data-gallery');
                $(galleryClass).magnificPopup({
                    delegate: 'a#attachment_files',
                    type: 'image',
                    gallery: {
                        enabled: true,
                        navigateByImgClick: true,
                        preload: [0,
                            1
                        ] // Will preload 0 - before current, and 1 after the current image
                    },
                    callbacks: {
                        elementParse: function(item) {
                            if (item.el[0].className.includes('video')) {
                                item.type = 'iframe',
                                    item.iframe = {
                                        markup: '<div class="mfp-iframe-scaler">' +
                                            '<div class="mfp-close"></div>' +
                                            '<iframe class="mfp-iframe" frameborder="0" allowfullscreen></iframe>' +
                                            '<div class="mfp-title">Some caption</div>' +
                                            '</div>'
                                    }
                            } else {
                                item.type = 'image',
                                    item.tLoading = 'Loading image #%curr%...',
                                    item.mainClass = 'mfp-img-mobile',
                                    item.image = {
                                        tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
                                    }
                            }
                        }
                    }
                })
            })
        })

        function providerAddress(provider_id, provider_address_id = "") {
            var provider_address_route =
                "{{ route('ajax-list', [ 'type' => 'provider_address','provider_id' =>'']) }}" + provider_id;
            provider_address_route = provider_address_route.replace('amp;', '');

            $.ajax({
                url: provider_address_route,
                success: function(result) {
                    $('#provider_address_id').select2({
                        width: '100%',
                        placeholder: "{{ trans('messages.select_name',['select' => trans('messages.provider_address')]) }}",
                        data: result.results
                    });
                    if (provider_address_id != "") {
                        $('#provider_address_id').val(provider_address_id.split(',')).trigger('change');
                    }
                }
            });
        }

        function getSubCategory(category_id, subcategory_id = "") {
            var get_subcategory_list =
                "{{ route('ajax-list', [ 'type' => 'subcategory_list','category_id' =>'']) }}" + category_id;
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
        var price = "{{ isset($servicedata->price) ? $servicedata->price : '' }}";
        var discount = "{{ isset($servicedata->discount) ? $servicedata->discount : '' }}";
        function priceformat(value) {
            if (value == 'free') {
                $('#price').val(0);
                $('#price').attr("readonly", true)

                $('#discount').val(0);
                $('#discount').attr("readonly", true)

            }
            else{
                $('#price').val(price);
                $('#price').attr("readonly", false)
                $('#discount').val(discount);
                $('#discount').attr("readonly", false)
            }
        }
    })(jQuery);
    </script>
    @endsection
</x-master-layout>
