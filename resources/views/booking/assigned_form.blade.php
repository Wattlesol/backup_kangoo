<!-- Modal -->

<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">@php $isAr = app()->getLocale() === 'ar'; @endphp</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
       {{ Form::open(['route' => 'booking.assigned','method' => 'post','data-toggle'=>"validator"]) }}
        <div class="modal-body">

           {{ Form::hidden('id',$bookingdata->id) }}
            <div class="row">
                @php
                    $partner = $bookingdata->provider;
                    $partnerActiveOrders = $partner ? \App\Models\Booking::where('provider_id', $partner->id)->whereNotIn('sanad_stage', ['completed', 'closed'])->where('status', '!=', 'cancelled')->count() : 0;
                    $partnerCompletedOrders = $partner ? \App\Models\Booking::where('provider_id', $partner->id)->whereIn('sanad_stage', ['completed', 'closed'])->count() : 0;
                    $partnerServiceExperience = $partner ? \App\Models\Booking::where('provider_id', $partner->id)->where('service_id', $bookingdata->service_id)->whereIn('sanad_stage', ['completed', 'closed'])->count() : 0;
                    $servicePerformance = $partner ? \App\Models\SanadPartnerServicePerformance::where('provider_id', $partner->id)->where('service_id', $bookingdata->service_id)->first() : null;
                @endphp
                @if($partner)
                    <div class="col-md-12 mb-3">
                        <div class="border rounded p-3 bg-light">
                            <strong>{{ __('messages.partner_information') }}</strong>
                            <div class="row mt-2">
                                <div class="col-md-3"><small>Partner</small><br>{{ $partner->display_name }}</div>
                                <div class="col-md-2"><small>Availability</small><br>{{ $partner->sanad_employee_status ?: 'available' }}</div>
                                <div class="col-md-2"><small>Partner Score</small><br>{{ $servicePerformance?->quality_score ?? $partner->sanad_quality_score ?? '-' }}</div>
                                <div class="col-md-2"><small>Active Orders</small><br>{{ $partnerActiveOrders }}</div>
                                <div class="col-md-2"><small>Capacity</small><br>{{ $partner->sanad_daily_capacity ?? '-' }}</div>
                                <div class="col-md-2"><small>Completed Orders</small><br>{{ $partnerCompletedOrders }}</div>
                                <div class="col-md-2"><small>SLA Compliance</small><br>{{ ($servicePerformance?->sla_compliance_rate ?? $partner->sanad_sla_compliance_rate) !== null ? ($servicePerformance?->sla_compliance_rate ?? $partner->sanad_sla_compliance_rate).'%' : '-' }}</div>
                                <div class="col-md-2"><small>Avg Completion</small><br>{{ ($servicePerformance?->average_completion_minutes ?? $partner->sanad_average_completion_minutes) ? ($servicePerformance?->average_completion_minutes ?? $partner->sanad_average_completion_minutes).' min' : '-' }}</div>
                                <div class="col-md-2"><small>Service Experience</small><br>{{ $servicePerformance?->completed_orders ?? $partnerServiceExperience }}</div>
                                <div class="col-md-2"><small>Last Activity</small><br>{{ optional($partner->updated_at)->diffForHumans() ?: '-' }}</div>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="col-md-6 form-group">
                    {{ Form::label('partner_id', 'Partner <span class="text-danger">*</span>', ['class'=>'form-control-label'], false) }}
                    {{ Form::select('partner_id', $partner ? [$partner->id => $partner->display_name] : [], $partner ? $partner->id : null, [
                        'class'=>'select2js form-control',
                        'id'=>'partner_id',
                        'data-placeholder'=>'Select Partner',
                        'data-ajax--url'=>route('ajax-list', ['type'=>'provider']),
                    ]) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('assignment_mode', __('messages.assignment_mode'), ['class'=>'form-control-label']) }}
                    {{ Form::select('assignment_mode', [
                        'suggested' => __('messages.suggested_assignment'),
                        'auto' => __('messages.auto_assignment'),
                        'manual' => __('messages.manual_assignment'),
                    ], $bookingdata->assignment_mode ?: 'suggested', ['class'=>'form-control', 'required']) }}
                </div>
                <div class="col-md-6 form-group">
                    {{ Form::label('assignment_reason', __('messages.assignment_reason'), ['class'=>'form-control-label']) }}
                    {{ Form::text('assignment_reason', $bookingdata->assignment_reason, [
                        'class'=>'form-control',
                        'maxlength'=>2000,
                        'placeholder' => 'Required when moving an order from one partner to another',
                    ]) }}
                </div>
                
                <div class="col-md-12 form-group ">
                    {{ Form::label('handyman_id', __('messages.select_name',[ 'select' => __('messages.handyman') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                    <br />
                    @php
                        if($bookingdata->booking_address_id != null)
                        {
                            $route = route('ajax-list', ['type' => 'handyman', 'provider_id' => $bookingdata->provider_id, 'booking_id' => $bookingdata->id ]);
                        } else {
                            $route = route('ajax-list', ['type' => 'handyman', 'provider_id' => $bookingdata->provider_id ]);
                        }
                        $assigned_handyman = $bookingdata->handymanAdded->mapWithKeys(function ($item) {
                            return [$item->handyman_id => optional($item->handyman)->display_name];
                        });
                    @endphp
                    {{ Form::select('handyman_id[]', $assigned_handyman, $bookingdata->handymanAdded->pluck('handyman_id'), [
                            'class' => 'select2js handyman',
                            'id' => 'handyman_id',
                            'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.handyman') ]),
                            'data-ajax--url' => $route,
                        ]) }}
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-md btn-secondary" data-dismiss="modal">{{ trans('messages.close') }}</button>
            <button type="submit" class="btn btn-md btn-primary" id="btn_submit" data-form="ajax" >{{ trans('messages.save') }}</button>
        </div>
        {{ Form::close() }}
    </div>
</div>
<script>
    $('#partner_id').select2({
        width: '100%',
        placeholder: 'Select Partner',
        allowClear: true,
    });
    $('#handyman_id').select2({
        width: '100%',
        placeholder: "{{ __('messages.select_name',['select' => __('messages.handyman')]) }}",
    });
</script>
