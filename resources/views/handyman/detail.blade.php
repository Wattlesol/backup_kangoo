<x-master-layout>
    {{ Form::open(['route' => ['provider.destroy', $handymandata->id], 'method' => 'delete', 'data--submit' => 'provider' . $handymandata->id]) }}
    <main class="main-area">
        <div class="main-content">
            <div class="container-fluid">
                @include('partials._handyman')
                <div class="card">
                <div class="card-body p-30">
                        <div class="provider-details-overview mb-30">
                            <div class="provider-details-overview__collect-cash">
                                <div class="statistics-card statistics-card__collect-cash h-100">
                                    <h3>{{ __('messages.collect_cash') }}</h3>
                                        <a href="{{route('handymanpayout.create',$handymandata->id)}}" class="btn btn--primary text-capitalize btn--lg mw-75">{{ __('messages.collect') }}</a>
                                </div>
                            </div>
                            <div class="provider-details-overview__statistics">
                                <div class="statistics-card statistics-card__style2 statistics-card__pending-withdraw">
                                    <h2>{{ getPriceFormat($handymandata['providerTotEarning']) ?? 0}}</h2>
                                    <h3>{{__('messages.pending_withdraw')}}</h3>
                                </div>

                            <div class="statistics-card statistics-card__style2 statistics-card__already-withdraw">
                                <h2>{{getPriceFormat($handymandata['providerTotWithdrableAmt']) ?? 0}}</h2>
                                <h3>{{__('messages.already_withdraw')}}</h3>
                            </div>

                            <div
                                class="statistics-card statistics-card__style2 statistics-card__withdrawable-amount">
                                <h2>{{getPriceFormat($handymandata['providerAlreadyWithdrawAmt']) ?? 0}}</h2>
                                <h3>{{__('messages.withdrawble_amount')}}</h3>
                            </div>

                            <div class="statistics-card statistics-card__style2 statistics-card__total-earning">
                                <h2>{{getPriceFormat($handymandata['pendWithdrwan']) ?? 0}}</h2>
                                <h3>{{__('messages.total_earning')}}</h3>
                            </div>
                        </div>
                        
                    </div>

                    
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="information-details-box media flex-column flex-sm-row gap-20">
                                <img class="avatar-img radius-5" src="./img/1.png" alt="" />
                                <div class="media-body">
                                    <h2 class="information-details-box__title">
                                        {{ $handymandata->display_name }}
                                    </h2>
                                    <ul class="contact-list">
                                        <li>
                                            <i class="ri-smartphone-line"></i>
                                            <a
                                                href="{{ $handymandata->contact_number }}" class="contact-info-text p-0">{{ !empty($handymandata->contact_number) ? $handymandata->contact_number: '-' }}</a>
                                        </li>
                                        <li>
                                            <i class="ri-mail-line"></i>
                                            <a href="{{ $handymandata->email }}" class="contact-info-text p-0">{{ $handymandata->email }}</a>
                                        </li>
                                        <li>
                                            <i class="ri-map-2-line"></i>
                                            <span class="contact-info-text">{{ !empty($handymandata->address) ?$handymandata->address : '-' }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="sanad-employee-detail-card">
                                <h4>Quick Employee Operations</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <span>Job Title</span>
                                        <strong>{{ $handymandata->sanad_job_title ?: $handymandata->designation ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span>Department</span>
                                        <strong>{{ $handymandata->sanad_department ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span>Status</span>
                                        <strong>{{ Str::headline($handymandata->sanad_employee_status ?: 'available') }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span>Daily Capacity</span>
                                        <strong>{{ $handymandata->sanad_daily_capacity ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span>Working Hours</span>
                                        <strong>{{ $handymandata->sanad_working_hours ?: '-' }}</strong>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <span>Skills</span>
                                        <strong>{{ $handymandata->skills ? str_replace(',', ', ', $handymandata->skills) : '-' }}</strong>
                                    </div>
                                </div>
                                <div>
                                    <span>Permissions</span>
                                    <div class="sanad-permission-tags">
                                        @forelse($handymandata->sanad_permissions ?: [] as $permission)
                                            <small>{{ Str::headline($permission) }}</small>
                                        @empty
                                            <small>None assigned</small>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
{{ Form::close() }}

<style>
    .sanad-employee-detail-card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 8px;
        background: #fff;
        padding: 18px;
        height: 100%;
    }
    .sanad-employee-detail-card h4 {
        font-weight: 700;
        margin-bottom: 16px;
    }
    .sanad-employee-detail-card span {
        display: block;
        color: #6c757d;
        font-size: 13px;
        margin-bottom: 4px;
    }
    .sanad-employee-detail-card strong {
        display: block;
        overflow-wrap: anywhere;
    }
    .sanad-permission-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }
    .sanad-permission-tags small {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 14px;
        background: #f8f9fb;
        padding: 5px 9px;
    }
</style>
</x-master-layout>
