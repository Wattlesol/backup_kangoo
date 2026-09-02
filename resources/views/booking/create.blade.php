<x-master-layout>
@php
    $isAr = app()->getLocale() === 'ar';
    $bookingDate = $bookingDate ?? (isset($bookingdata->date) ? date('Y-m-d H:i', strtotime($bookingdata->date)) : date('Y-m-d H:i'));
@endphp
    <div class="container-fluid sanad-order-create">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <div>
                                <h5 class="font-weight-bold mb-1">{{ !empty($bookingdata->id) ? ($isAr ? 'تحديث الطلب' : 'Update Request') : ($isAr ? 'إنشاء طلب جديد' : 'Create Request') }}</h5>
                                <span class="text-muted">{{ $isAr ? "تسجيل بيانات العميل، معلومات التواصل، الخدمة، وملاحظات الطلب في مكان واحد." : "Capture the customer, contact details, service, and request notes in one place." }}</span>
                            </div>
                            <a href="{{ route('booking.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        {{ Form::model($bookingdata, ['method' => 'POST', 'route' => 'booking.store', 'data-toggle' => 'validator', 'id' => 'booking']) }}
                        {{ Form::hidden('id') }}

                        <div class="sanad-order-section">
                            <h6>{{ $isAr ? "معلومات وبيانات العميل" : "Customer Information" }}</h6>
                            @if(auth()->user()->hasAnyRole(['admin', 'demo_admin', 'employee', 'handyman']))
                                <div class="form-group">
                                    <div class="btn-group btn-group-toggle" data-toggle="buttons">
                                        <label class="btn btn-outline-primary active" id="existing-customer-toggle">
                                            <input type="radio" name="customer_mode" value="existing" autocomplete="off" checked> {{ $isAr ? "عميل حالي" : "Existing Customer" }}
                                        </label>
                                        <label class="btn btn-outline-primary" id="new-customer-toggle">
                                            <input type="radio" name="customer_mode" value="new" autocomplete="off"> {{ $isAr ? "عميل جديد" : "New Customer" }}
                                        </label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group col-md-4 existing-customer-field">
                                        {{ Form::label('customer_id', $isAr ? 'عميل حالي' : 'Existing Customer', ['class' => 'form-control-label']) }}
                                        <br>
                                        {{ Form::select('customer_id', [optional($bookingdata->customer)->id => optional($bookingdata->customer)->display_name], optional($bookingdata->customer)->id, [
                                            'class' => 'select2js form-group customer',
                                            'id' => 'customer_id',
                                            'data-placeholder' => $isAr ? 'البحث باسم العميل أو رقم الهاتف' : 'Search by customer name or phone',
                                            'data-ajax--url' => route('ajax-list', ['type' => 'user']),
                                        ]) }}
                                        <small class="text-muted">{{ $isAr ? "اختر عميلاً مسجلاً مسبقاً، أو املأ الحقول أدناه لإنشاء حساب جديد." : "Select an existing customer, or fill the contact fields below to create one." }}</small>
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_name', ($isAr ? 'اسم العميل' : 'Customer Name') . ' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                        {{ Form::text('customer_name', old('customer_name', optional($bookingdata->customer)->display_name), ['class' => 'form-control', 'placeholder' => $isAr ? 'الاسم الكامل للعميل' : 'Full customer name']) }}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_phone', ($isAr ? 'رقم التواصل' : 'Contact Number') . ' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                        {{ Form::text('customer_phone', old('customer_phone', optional($bookingdata->customer)->contact_number), ['class' => 'form-control', 'placeholder' => '+966...']) }}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_email', ($isAr ? 'البريد الإلكتروني' : 'Email') . ' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                        {{ Form::email('customer_email', old('customer_email', optional($bookingdata->customer)->email), ['class' => 'form-control', 'placeholder' => 'customer@example.com']) }}
                                    </div>
                                    <div class="form-group col-md-8">
                                        {{ Form::label('customer_address', $isAr ? 'عنوان العميل' : 'Customer Address', ['class' => 'form-control-label']) }}
                                        {{ Form::text('customer_address', old('customer_address', optional($bookingdata->customer)->address), ['class' => 'form-control', 'placeholder' => $isAr ? 'عنوان العميل أو موقع التواصل المفضل' : 'Customer address or preferred contact location']) }}
                                    </div>
                                    <div class="form-group col-md-4 new-customer-credential-field d-none">
                                        {{ Form::label('customer_password', $isAr ? 'كلمة مرور الحساب' : 'Login Password', ['class' => 'form-control-label']) }}
                                        {{ Form::password('customer_password', ['class' => 'form-control', 'placeholder' => $isAr ? 'اتركه فارغاً للتوليد التلقائي' : 'Leave blank to generate']) }}
                                        <small class="text-muted">{{ $isAr ? "سيستخدم العميل البريد الإلكتروني وكلمة المرور هذه لرفع المستندات ومتابعة الطلب." : "Customer will use the email and this password to upload documents." }}</small>
                                    </div>
                                    <div class="form-group col-md-4 new-customer-credential-field d-none">
                                        {{ Form::label('customer_password_confirmation', $isAr ? 'تأكيد كلمة المرور' : 'Confirm Password', ['class' => 'form-control-label']) }}
                                        {{ Form::password('customer_password_confirmation', ['class' => 'form-control', 'placeholder' => $isAr ? 'إعادة إدخال كلمة المرور' : 'Repeat password']) }}
                                    </div>
                                </div>
                            @else
                                <input type="hidden" name="customer_id" value="{{ auth()->id() }}">
                                <div class="sanad-customer-card">
                                    <strong>{{ auth()->user()->display_name ?: auth()->user()->first_name }}</strong>
                                    <span>{{ auth()->user()->contact_number ?: 'No phone number saved' }}</span>
                                    <span>{{ auth()->user()->email }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="sanad-order-section">
                            <h6>{{ $isAr ? "تفاصيل الطلب" : "Request Details" }}</h6>
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('service_id', __('messages.select_name', ['select' => __('messages.service')]).' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    <br>
                                    {{ Form::select('service_id', [optional($bookingdata->service)->id => optional($bookingdata->service)->name], optional($bookingdata->service)->id, [
                                        'class' => 'select2js form-group service',
                                        'required',
                                        'data-placeholder' => __('messages.select_name', ['select' => __('messages.service')]),
                                        'data-ajax--url' => route('ajax-list', ['type' => 'service']),
                                    ]) }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ Form::label('date', ($isAr ? 'تاريخ الموعد المطلوب' : 'Requested Date') . ' <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('date', old('date', $bookingDate), ['placeholder' => __('messages.date'), 'class' => 'form-control min-datetimepicker', 'required']) }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ Form::label('sanad_priority', $isAr ? 'الأولوية' : 'Priority', ['class' => 'form-control-label']) }}
                                    {{ Form::select('sanad_priority', $isAr ? ['normal' => 'عادية', 'high' => 'عالية', 'urgent' => 'عاجلة جداً'] : ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'], old('sanad_priority', $bookingdata->sanad_priority ?: 'normal'), ['class' => 'form-control']) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ Form::label('description', $isAr ? 'ملاحظات الطلب' : 'Request Notes', ['class' => 'form-control-label']) }}
                                    {{ Form::textarea('description', old('description', $bookingdata->description), ['class' => 'form-control textarea', 'rows' => 4, 'placeholder' => $isAr ? 'حالة العميل، المستندات المتوفرة، تعليمات خاصة، الموعد النهائي، أو وقت التواصل المفضل' : 'Customer situation, documents already available, special instructions, deadline, or preferred contact time']) }}
                                </div>
                            </div>
                        </div>

                        <div class="sanad-order-actions">
                            {{ Form::submit(__('messages.save'), ['class' => 'btn btn-md btn-primary']) }}
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @section('bottom_script')
        <style>
            .sanad-order-section {
                border: 1px solid #edf1f7;
                border-radius: 8px;
                padding: 18px;
                margin-bottom: 18px;
                background: #fff;
            }
            .sanad-order-section h6 {
                font-weight: 700;
                margin-bottom: 16px;
                color: #111827;
            }
            .sanad-customer-card {
                display: grid;
                gap: 4px;
                border: 1px solid #edf1f7;
                background: #f8fafc;
                border-radius: 8px;
                padding: 14px;
            }
            .sanad-customer-card span {
                color: #64748b;
            }
            .sanad-order-actions {
                display: flex;
                justify-content: flex-end;
            }
        </style>

        <script>
            (function () {
                const customerDetailsBaseUrl = '{{ url("booking/customer") }}';

                function setCustomerMode(mode) {
                    const isNew = mode === 'new';
                    $('.existing-customer-field').toggleClass('d-none', isNew);
                    $('.new-customer-credential-field').toggleClass('d-none', !isNew);

                    if (isNew) {
                        $('#customer_id').val(null).trigger('change');
                        $('#existing-customer-toggle').removeClass('active');
                        $('#new-customer-toggle').addClass('active');
                        $('input[name="customer_mode"][value="new"]').prop('checked', true);
                    } else {
                        $('#new-customer-toggle').removeClass('active');
                        $('#existing-customer-toggle').addClass('active');
                        $('input[name="customer_mode"][value="existing"]').prop('checked', true);
                    }
                }

                $('input[name="customer_mode"]').on('change', function () {
                    setCustomerMode(this.value);
                });

                $('#customer_id').on('select2:select change', function () {
                    const customerId = $(this).val();
                    if (!customerId) {
                        return;
                    }

                    $.get(customerDetailsBaseUrl + '/' + customerId)
                        .done(function (customer) {
                            $('input[name="customer_name"]').val(customer.display_name || '');
                            $('input[name="customer_phone"]').val(customer.contact_number || '');
                            $('input[name="customer_email"]').val(customer.email || '');
                            $('input[name="customer_address"]').val(customer.address || '');
                        });
                });

                @if(!$bookingdata->id && old('customer_mode') === 'new')
                    setCustomerMode('new');
                @endif
            })();
        </script>
    @endsection
</x-master-layout>
