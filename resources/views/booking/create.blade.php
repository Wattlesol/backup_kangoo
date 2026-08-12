@php
    $bookingDate = $bookingdata->date ? \Carbon\Carbon::parse($bookingdata->date)->format('Y-m-d H:i') : null;
@endphp

<x-master-layout>
    <div class="container-fluid sanad-order-create">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <div>
                                <h5 class="font-weight-bold mb-1">{{ !empty($bookingdata->id) ? 'Update Sanad Order' : 'Create Sanad Order' }}</h5>
                                <span class="text-muted">Capture the customer, contact details, service, and request notes in one place.</span>
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
                            <h6>Customer Information</h6>
                            @if(auth()->user()->hasAnyRole(['admin', 'demo_admin', 'employee', 'handyman']))
                                <div class="row">
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_id', 'Existing Customer', ['class' => 'form-control-label']) }}
                                        <br>
                                        {{ Form::select('customer_id', [optional($bookingdata->customer)->id => optional($bookingdata->customer)->display_name], optional($bookingdata->customer)->id, [
                                            'class' => 'select2js form-group customer',
                                            'data-placeholder' => 'Search by customer name or phone',
                                            'data-ajax--url' => route('ajax-list', ['type' => 'user']),
                                        ]) }}
                                        <small class="text-muted">Select an existing customer, or fill the contact fields below to create one.</small>
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_name', 'Customer Name <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                        {{ Form::text('customer_name', old('customer_name', optional($bookingdata->customer)->display_name), ['class' => 'form-control', 'placeholder' => 'Full customer name']) }}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_phone', 'Contact Number <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                        {{ Form::text('customer_phone', old('customer_phone', optional($bookingdata->customer)->contact_number), ['class' => 'form-control', 'placeholder' => '+966...']) }}
                                    </div>
                                    <div class="form-group col-md-4">
                                        {{ Form::label('customer_email', 'Email', ['class' => 'form-control-label']) }}
                                        {{ Form::email('customer_email', old('customer_email', optional($bookingdata->customer)->email), ['class' => 'form-control', 'placeholder' => 'customer@example.com']) }}
                                    </div>
                                    <div class="form-group col-md-8">
                                        {{ Form::label('customer_address', 'Customer Address', ['class' => 'form-control-label']) }}
                                        {{ Form::text('customer_address', old('customer_address', optional($bookingdata->customer)->address), ['class' => 'form-control', 'placeholder' => 'Customer address or preferred contact location']) }}
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
                            <h6>Request Details</h6>
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
                                    {{ Form::label('date', 'Requested Date <span class="text-danger">*</span>', ['class' => 'form-control-label'], false) }}
                                    {{ Form::text('date', old('date', $bookingDate), ['placeholder' => __('messages.date'), 'class' => 'form-control min-datetimepicker', 'required']) }}
                                </div>
                                <div class="form-group col-md-4">
                                    {{ Form::label('sanad_priority', 'Priority', ['class' => 'form-control-label']) }}
                                    {{ Form::select('sanad_priority', ['normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'], old('sanad_priority', $bookingdata->sanad_priority ?: 'normal'), ['class' => 'form-control']) }}
                                </div>
                                <div class="form-group col-md-12">
                                    {{ Form::label('description', 'Request Notes', ['class' => 'form-control-label']) }}
                                    {{ Form::textarea('description', old('description', $bookingdata->description), ['class' => 'form-control textarea', 'rows' => 4, 'placeholder' => 'Customer situation, documents already available, special instructions, deadline, or preferred contact time']) }}
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

    @push('after-styles')
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
    @endpush
</x-master-layout>
