<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.list') }}</h5>
                            <a href="{{ auth()->user()->user_type === 'provider' ? route('provider.employees.index') : route('handyman.index') }}" class="float-right btn btn-sm btn-primary"><i
                                    class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            @if($auth_user->can('handyman list'))
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($handymandata,['method' => 'POST','route'=>'handyman.store', 'enctype'=>'multipart/form-data', 'data-toggle'=>"validator" ,'id'=>'handyman'] ) }}
                        {{ Form::hidden('id') }}
                        {{ Form::hidden('user_type','handyman') }}
                        {{ Form::hidden('employee_permission_context', $employeePermissionContext ?? 'admin', ['id' => 'employee_permission_context']) }}
                        @php
                            $isAr = app()->getLocale() === 'ar';
                            $sanadSkillsText = old('skills', str_replace(',', "\n", (string) $handymandata->skills));
                            $modulePermissionActions = $isAr
                                ? ['read' => 'قراءة', 'write' => 'تعديل', 'delete' => 'حذف']
                                : ['read' => 'Read', 'write' => 'Write', 'delete' => 'Delete'];
                            $employeePermissionContext = $employeePermissionContext ?? 'admin';
                            $selectedModulePermissions = $selectedModulePermissions ?? [];
                            $visiblePermissionContexts = auth()->user()->user_type === 'provider'
                                ? ['partner' => $partnerPermissionModules]
                                : ['admin' => $adminPermissionModules];
                            $employeeStatuses = $isAr ? [
                                'available' => 'متاح',
                                'busy' => 'مشغول',
                                'offline' => 'غير متصل',
                                'on_leave' => 'في إجازة',
                                'training' => 'قيد التدريب',
                            ] : [
                                'available' => 'Available',
                                'busy' => 'Busy',
                                'offline' => 'Offline',
                                'on_leave' => 'On Leave',
                                'training' => 'Training',
                            ];
                            $saudiCountry = \App\Models\Country::where('code', 'SA')->orWhere('name', 'Saudi Arabia')->first();
                            $selectedCountryId = old('country_id', $handymandata->country_id ?: optional($saudiCountry)->id);
                        @endphp
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

                            @if (!isset($handymandata->id) || $handymandata->id == null)
                            <div class="form-group col-md-4">
                                {{ Form::label('password', __('messages.password').' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('messages.password'), 'required', 'autocomplete' => 'new-password']) }}
                                <small class="help-block with-errors text-danger"></small>
                            </div>
                            @endif

                            <div class="form-group col-md-4">
                                <div class="d-flex align-items-center justify-content-between">
                                    {{ Form::label('handymantype_id', __('messages.select_name',[ 'select' => __('messages.handymantype') ]),['class'=>'form-control-label'],false) }}
                                    @if(auth()->user()->hasAnyRole(['admin','demo_admin']) || auth()->user()->can('handymantype add') || auth()->user()->can('handymantype list'))
                                        <a href="{{ route('handymantype.create') }}" class="btn btn-sm btn-outline-primary mb-1">
                                            <i class="fa fa-plus-circle"></i> {{ __('messages.add_form_title',['form' => __('messages.handymantype')]) }}
                                        </a>
                                    @endif
                                </div>
                                <br />
                                {{ Form::select('handymantype_id', [optional($handymandata->handymantype)->id => optional($handymandata->handymantype)->name], optional($handymandata->handymantype)->id, [
                                        'class' => 'select2js form-group handymantype',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.handymantype') ]),
                                        'data-ajax--url' => route('ajax-list', ['type' => 'handymantype']),
                                    ]) }}
                            </div>
                            @if(auth()->user()->hasAnyRole(['admin','demo_admin']))
                            <div class="form-group col-md-4">
                                {{ Form::label('provider_id', __('messages.select_name',[ 'select' => __('messages.providers') ]),['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('provider_id', [optional($handymandata->providers)->id => optional($handymandata->providers)->display_name], optional($handymandata->providers)->id, [
                                        'class' => 'select2js form-group providers',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.providers') ]),
                                        'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                                    ]) }}
                                <small class="text-muted">Leave empty for a direct Quick admin employee.</small>
                            </div>
                            @endif
                            @if(auth()->user()->user_type !== 'provider')
                            <div class="form-group col-md-4">
                                {{ Form::label('address', __('messages.address'), ['class' => 'form-control-label']) }}
                                {{ Form::text('address', old('address', $handymandata->address), ['class' => 'form-control', 'placeholder' => __('messages.address')]) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('country_id', __('messages.country').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('country_id', [$selectedCountryId => 'Saudi Arabia'], $selectedCountryId, [
                                        'class' => 'form-control country',
                                        'id' => 'country_id',
                                        'required',
                                    ]) }}
                            </div>
                            @endif

                            <div class="form-group col-md-4">
                                {{ Form::label('state_id', __('messages.select_name',[ 'select' => __('messages.state') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('state_id', [], old('state_id', $handymandata->state_id), [
                                        'class' => 'select2js form-group state_id',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.state') ]),
                                    ]) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('city_id', __('messages.select_name',[ 'select' => __('messages.city') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                <br />
                                {{ Form::select('city_id', [], old('city_id'), [
                                        'class' => 'select2js form-group city_id',
                                        'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.city') ]),
                                    ]) }}
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('contact_number',__('messages.contact_number').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                {{ Form::text('contact_number',old('contact_number'),['placeholder' => '+9665XXXXXXXX','class' =>'form-control contact_number',
                                'maxlength' => 13,
                                'pattern' => '^(\\+9665\\d{8}|05\\d{8}|5\\d{8})$',
                                'title' => 'Enter a valid Saudi mobile number, for example +9665XXXXXXXX, 05XXXXXXXX, or 5XXXXXXXX',
                                'required']) }}
                                <small class="help-block with-errors text-danger" id="contact_number_err"></small>
                            </div>

                            <div class="form-group col-md-4">
                                {{ Form::label('status',__('messages.status').' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                {{ Form::select('status',['1' => __('messages.active') , '0' => __('messages.inactive') ],old('status'),[ 'class' =>'form-control select2js','required']) }}
                            </div>

                            <div class="form-group col-md-12">
                                <div class="sanad-employee-operations">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <div>
                                            <h5 class="font-weight-bold mb-1">{{ $isAr ? 'عمليات موظفي كويك' : 'Quick Employee Operations' }}</h5>
                                            <span class="text-muted">{{ $isAr ? 'الملف التشغيلي والصلاحيات وساعات العمل والطاقة الاستيعابية وحالة الموظف' : 'Operational profile, permissions, working hours, capacity, and employee status' }}</span>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            {{ Form::label('sanad_job_title', $isAr ? 'المسمى الوظيفي' : 'Job Title', ['class' => 'form-control-label']) }}
                                            {{ Form::text('sanad_job_title', old('sanad_job_title', $handymandata->sanad_job_title), ['class' => 'form-control', 'placeholder' => $isAr ? 'أخصائي عمليات' : 'Operations Specialist']) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('sanad_department', $isAr ? 'القسم' : 'Department', ['class' => 'form-control-label']) }}
                                            {{ Form::text('sanad_department', old('sanad_department', $handymandata->sanad_department), ['class' => 'form-control', 'placeholder' => $isAr ? 'الشؤون القانونية، المحاسبة، العلاقات الحكومية' : 'Legal, Accounting, Government Relations']) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('sanad_employee_status', $isAr ? 'حالة الموظف' : 'Employee Status', ['class' => 'form-control-label']) }}
                                            {{ Form::select('sanad_employee_status', $employeeStatuses, old('sanad_employee_status', $handymandata->sanad_employee_status ?: 'available'), ['class' => 'form-control select2js']) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('sanad_working_hours', $isAr ? 'ساعات العمل' : 'Working Hours', ['class' => 'form-control-label']) }}
                                            {{ Form::text('sanad_working_hours', old('sanad_working_hours', $handymandata->sanad_working_hours), ['class' => 'form-control', 'placeholder' => $isAr ? 'الأحد - الخميس، 9:00 - 18:00' : 'Sun-Thu, 9:00-18:00']) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('sanad_daily_capacity', $isAr ? 'الطاقة الاستيعابية اليومية' : 'Daily Capacity', ['class' => 'form-control-label']) }}
                                            {{ Form::number('sanad_daily_capacity', old('sanad_daily_capacity', $handymandata->sanad_daily_capacity), ['class' => 'form-control', 'min' => 0, 'max' => 100, 'placeholder' => $isAr ? 'عدد الطلبات يومياً' : 'Orders per day']) }}
                                        </div>
                                        <div class="form-group col-md-4">
                                            {{ Form::label('skills', $isAr ? 'المهارات' : 'Skills', ['class' => 'form-control-label']) }}
                                            {{ Form::textarea('skills', $sanadSkillsText, ['class' => 'form-control', 'rows' => 3, 'placeholder' => $isAr ? 'مهارة واحدة في كل سطر' : 'One skill per line']) }}
                                        </div>
                                        <div class="form-group col-md-12">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                                <div>
                                                    <label class="form-control-label mb-1">{{ $isAr ? 'مصفوفة الصلاحيات' : 'Permission Matrix' }}</label>
                                                    @if(auth()->user()->user_type === 'provider')
                                                        <div class="text-muted small">{{ $isAr ? 'يحصل موظفو الشريك على وحدات الشريك والمهام، وتبقى صلاحياتهم محصورة في أعمال شريكهم.' : 'Partner employees receive partner/task modules and remain scoped to their partner work.' }}</div>
                                                    @else
                                                        <div class="text-muted small">{{ $isAr ? 'يحصل موظفو كويك المباشرون على وحدات لوحة الإدارة وفق الصلاحيات المحددة هنا فقط.' : 'Direct Quick employees receive admin-panel modules limited by the permissions selected here.' }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                            @foreach($visiblePermissionContexts as $context => $permissionModules)
                                                <div class="sanad-permission-matrix" data-permission-context="{{ $context }}">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>{{ $isAr ? 'الوحدة' : 'Module' }}</th>
                                                                    <th>{{ $isAr ? 'الوصف' : 'Description' }}</th>
                                                                    @foreach($modulePermissionActions as $actionLabel)
                                                                        <th class="text-center">{{ $actionLabel }}</th>
                                                                    @endforeach
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($permissionModules as $moduleKey => $module)
                                                                    <tr>
                                                                        <td class="font-weight-bold">{{ $isAr ? ($module['label_ar'] ?? $module['label']) : $module['label'] }}</td>
                                                                        <td class="text-muted">{{ $isAr ? ($module['description_ar'] ?? $module['description']) : $module['description'] }}</td>
                                                                        @foreach($modulePermissionActions as $actionKey => $actionLabel)
                                                                            @php
                                                                                $isAvailable = !empty($module['permissions'][$actionKey] ?? []) || !empty($module['flags'][$actionKey] ?? []);
                                                                                $isChecked = !empty($selectedModulePermissions[$moduleKey][$actionKey]);
                                                                            @endphp
                                                                            <td class="text-center">
                                                                                @if($isAvailable)
                                                                                    <input
                                                                                        type="checkbox"
                                                                                        class="form-check-input sanad-permission-checkbox"
                                                                                        name="module_permissions[{{ $moduleKey }}][{{ $actionKey }}]"
                                                                                        value="1"
                                                                                        {{ $isChecked ? 'checked' : '' }}
                                                                                    >
                                                                                @else
                                                                                    <span class="text-muted">-</span>
                                                                                @endif
                                                                            </td>
                                                                        @endforeach
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
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

                            @if(getMediaFileExit($handymandata, 'profile_image'))
                            <div class="col-md-2 mb-2">
                                <img id="profile_image_preview" src="{{getSingleMedia($handymandata,'profile_image')}}"
                                    alt="#" class="attachment-image mt-1">
                                <a class="text-danger remove-file"
                                    href="{{ route('remove.file', ['id' => $handymandata->id, 'type' => 'profile_image']) }}"
                                    data--submit="confirm_form" data--confirmation='true' data--ajax="true"
                                    data-toggle="tooltip"
                                    title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-title='{{ __("messages.remove_file_title" , ["name" =>  __("messages.image") ]) }}'
                                    data-message='{{ __("messages.remove_file_msg") }}'>
                                    <i class="ri-close-circle-line"></i>
                                </a>
                            </div>
                            @endif

                        </div>
                        {{ Form::submit( __('messages.save'), ['class'=>'btn btn-md btn-primary float-right']) }}
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @section('bottom_script')
    <style>
        .sanad-employee-operations {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #f8f9fb;
            padding: 16px;
        }
        .sanad-employee-operations .gap-2 {
            gap: 8px;
        }
        .sanad-permission-matrix {
            border: 1px solid rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            background: #fff;
            overflow: hidden;
        }
        .sanad-permission-matrix th,
        .sanad-permission-matrix td {
            vertical-align: middle;
        }
        .sanad-permission-matrix th:nth-child(1) {
            width: 220px;
        }
        .sanad-permission-matrix th:nth-child(n+3) {
            width: 86px;
        }
        .sanad-permission-checkbox {
            position: static;
            margin: 0;
        }
    </style>
    <script type="text/javascript">
    (function($) {
        "use strict";
        $(document).ready(function() {
            var country_id = "{{ $selectedCountryId ?: 0 }}";
            var state_id = "{{ isset($handymandata->state_id) ? $handymandata->state_id : 0 }}";
            var city_id = "{{ isset($handymandata->city_id) ? $handymandata->city_id : 0 }}";

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
        $(document).on('keyup blur', '.contact_number', function() {
            var contactNumberInput = document.getElementById('contact_number');
            var inputValue = contactNumberInput.value;
            inputValue = inputValue.replace(/[^0-9+]/g, '');
            if (inputValue.length > 13) {
                inputValue = inputValue.substring(0, 13);
            } else {
                $('#contact_number_err').text('');
            }
            contactNumberInput.value = inputValue;
            if (inputValue === '' || inputValue.match(/^(\+9665\d{8}|05\d{8}|5\d{8})$/)) {
                $('#contact_number_err').text('');
            } else {
                $('#contact_number_err').text('Enter a valid Saudi mobile number');
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
                    if (state != null && state != 0) {
                        $("#state_id").val(state).trigger('change');
                    } else if (result.results && result.results.length > 0) {
                        var firstStateId = result.results[0].id;
                        $("#state_id").val(firstStateId).trigger('change');
                        cityName(firstStateId);
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
                    if (city != null && city != 0) {
                        $("#city_id").val(city).trigger('change');
                    }
                }
            });
        }

    })(jQuery);
    </script>
    @endsection
</x-master-layout>
