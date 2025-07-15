@extends('landing-page.layouts.default')

@section('content')
@php

@endphp

<div class="section-padding service-detail">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 pe-xxl-5">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                <h3 class="text-capitalize mb-2">{{ $serviceData->name }}</h3>

                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

                </div>
                    <img src="{{ asset('images/default.png') }}" alt="" class="img-fluid object-cover rounded-3 mt-4 w-100"/>
                @if(!empty($serviceData->description))
                    <div class="mt-5 pt-lg-5 pt-3">
                        <h5 class="mb-3">{{__('landingpage.description')}}</h5>
                        <p class="m-0">
                            {{ $serviceData->description }}
                        </p>
                    </div>
                @endif
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3">Duration</h5>
                    <p class="m-0" style="color: red">
                        {{ $serviceData->duration }} Days
                    </p>
                    <br>
                    <a href="https://wa.me/{{optional($generalsetting)->helpline_number}}"><BUTTON class="btn btn-primary">Need to custom package</BUTTON></a>
                </div>

                <div class="row">
                    <h3>Service </h3>

                    <div class="table-responsive">

                        <table class="table">
                            <thead>
                            <tr>
                                <th scope="col">service</th>
                                <th scope="col">count</th>
                                <th scope="col">Duration of use before next time</th>
{{--                                <th scope="col">Price</th>--}}
                            </tr>
                            </thead>
                            <tbody >
                            @forelse($serviceData->package_service_data as $items)
                                <tr>
                                    <td>{{ $items->service->name }}</td>
                                    <td>
                                        @if($items->service_type_data == "limited")
                                            {{ $items->count }}

                                        @else
                                            Unlimited
                                        @endif

                                    </td>
                                    <td>{{ $items->duration_of_use }} Days</td>
{{--                                    <td>{{ $items->price }}</td>--}}
                                </tr>
                            @empty
                            @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
                @if($serviceData->price > 0)
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3 text-capitalize">{{__('landingpage.servicespackage')}}</h5>
                    <div class="p-5 border rounded-3">
                        <h6 class="mb-1">{{__('landingpage.servicespackage')}}</h6>
                        <p class="m-0 text-capitalize">{{ $serviceData->name }}
                        </p>

            <form method="post" action="{{route('buy_package_data')}}">

@csrf
                <input name="service_id" type="hidden" value="{{$serviceData->id}}">

                        <div class="mt-5 border-top">
                            <div class="table-responsive">
                                <table class="table mb-5">
                                    <tbody>
                                    <tr>
                                        <td class="ps-0 py-2">
                                            <label class="text-capitalize"><h6>{{ __('messages.price') }}</h6></label>
                                        </td>
                                        <td class="pe-0 py-2 text-end">
                                            <h6 class="text-primary" id="check_price_data" style="color: red"></h6>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <select id="city_id" name="city_id" required class="form-control">
                                                <option value="">Please Select Area</option>
                                                @foreach($region as $cities)
                                                    <option value="{{ $cities->id }}">{{ $cities->name }}</option>
                                                @endforeach
                                            </select>
                                            <button  type="button" class="btn btn-danger" id="check_price__check_data">Check</button>
                                        </td>
                                    </tr>

                                    <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{__('messages.type')}}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{$serviceData->package_type }}</h6>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{__('messages.duration')}}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{$serviceData->duration }}</h6>
                                            </td>
                                        </tr>
                                        @if($serviceData->car_number != 0)

                                            <tr>
                                                <td class="ps-0 py-2">
                                                    <label class="text-capitalize"><h6>{{__('messages.car_number')}}</h6></label>
                                                </td>
                                                <td class="pe-0 py-2 text-end">
                                                    <h6 class="text-primary">{{$serviceData->car_number }}</h6>
                                                </td>
                                            </tr>

                                        @endif



                                        <div class="modal fade" id="taxModal" aria-labelledby="taxModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-capitalize" id="taxModalLabel">{{__('messages.applied_taxes')}}</h5>
                                                        <span class="text-primary custom-btn-close" data-bs-dismiss="modal" aria-label="Close">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="41" viewBox="0 0 40 41" fill="none">
                                                                <rect x="12" y="11.8381" width="17" height="17" fill="white"></rect>
                                                                <path fill-rule="evenodd" clip-rule="evenodd"
                                                                    d="M12.783 4.17017H27.233C32.883 4.17017 36.6663 8.13683 36.6663 14.0368V27.6552C36.6663 33.5385 32.883 37.5035 27.233 37.5035H12.783C7.13301 37.5035 3.33301 33.5385 3.33301 27.6552V14.0368C3.33301 8.13683 7.13301 4.17017 12.783 4.17017ZM25.0163 25.8368C25.583 25.2718 25.583 24.3552 25.0163 23.7885L22.0497 20.8218L25.0163 17.8535C25.583 17.2885 25.583 16.3552 25.0163 15.7885C24.4497 15.2202 23.533 15.2202 22.9497 15.7885L19.9997 18.7535L17.033 15.7885C16.4497 15.2202 15.533 15.2202 14.9663 15.7885C14.3997 16.3552 14.3997 17.2885 14.9663 17.8535L17.933 20.8218L14.9663 23.7718C14.3997 24.3552 14.3997 25.2718 14.9663 25.8368C15.2497 26.1202 15.633 26.2718 15.9997 26.2718C16.383 26.2718 16.7497 26.1202 17.033 25.8368L19.9997 22.8885L22.9663 25.8368C23.2497 26.1385 23.6163 26.2718 23.983 26.2718C24.3663 26.2718 24.733 26.1202 25.0163 25.8368Z"
                                                                    fill="currentColor">
                                                                </path>
                                                            </svg>
                                                        </span>
                                                    </div>
                                                    <div class="modal-body">

                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </tbody>
                                </table>
                            </div>

                        </div>
                <button class="btn btn-success">Buy</button>
            </form>
                    </div>
                </div>

                @endif

        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="share-modal" tabindex="-1" aria-labelledby="share-modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-capitalize">{{__('landingpage.share_this_service')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="input-group copy-link-form">
                    <input id="copy-link-input" type="text" class="form-control copy-link-input" readonly>
                    <button id="copy-link-btn" class="btn btn-primary copy-link-btn">{{__('landingpage.copy_link')}}</button>
                </div>
                <div class="social-login mt-3">
                    <ul class="list-inline d-flex flex-wrap align-items-center justify-content-center gap-3 m-0">
                        <li>
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                                    fill="none">
                                    <g>
                                        <path
                                            d="M16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 11.993 2.92547 15.3027 6.75 15.9028V10.3125H4.71875V8H6.75V6.2375C6.75 4.2325 7.94438 3.125 9.77172 3.125C10.6467 3.125 11.5625 3.28125 11.5625 3.28125V5.25H10.5538C9.56 5.25 9.25 5.86672 9.25 6.5V8H11.4688L11.1141 10.3125H9.25V15.9028C13.0745 15.3027 16 11.993 16 8Z"
                                            fill="#3F53A5" />
                                    </g>
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="17" viewBox="0 0 16 17"
                                    fill="none">
                                    <path
                                        d="M14.5377 7.19425H14.0007V7.16659H8.00065V9.83325H11.7683C11.2187 11.3856 9.74165 12.4999 8.00065 12.4999C5.79165 12.4999 4.00065 10.7089 4.00065 8.49992C4.00065 6.29092 5.79165 4.49992 8.00065 4.49992C9.02032 4.49992 9.94798 4.88459 10.6543 5.51292L12.54 3.62725C11.3493 2.51759 9.75665 1.83325 8.00065 1.83325C4.31898 1.83325 1.33398 4.81825 1.33398 8.49992C1.33398 12.1816 4.31898 15.1666 8.00065 15.1666C11.6823 15.1666 14.6673 12.1816 14.6673 8.49992C14.6673 8.05292 14.6213 7.61659 14.5377 7.19425Z"
                                        fill="#FFC107" />
                                    <path
                                        d="M2.10156 5.39692L4.2919 7.00325C4.88456 5.53592 6.3199 4.49992 7.99956 4.49992C9.01923 4.49992 9.9469 4.88459 10.6532 5.51292L12.5389 3.62725C11.3482 2.51759 9.75556 1.83325 7.99956 1.83325C5.4389 1.83325 3.21823 3.27892 2.10156 5.39692Z"
                                        fill="#FF3D00" />
                                    <path
                                        d="M7.99945 15.1667C9.72145 15.1667 11.2861 14.5077 12.4691 13.436L10.4058 11.69C9.73645 12.197 8.90445 12.5 7.99945 12.5C6.26545 12.5 4.79312 11.3943 4.23845 9.85132L2.06445 11.5263C3.16779 13.6853 5.40845 15.1667 7.99945 15.1667Z"
                                        fill="#4CAF50" />
                                    <path
                                        d="M14.537 7.19441H14V7.16675H8V9.83341H11.7677C11.5037 10.5791 11.024 11.2221 10.4053 11.6904C10.4057 11.6901 10.406 11.6901 10.4063 11.6897L12.4697 13.4357C12.3237 13.5684 14.6667 11.8334 14.6667 8.50008C14.6667 8.05308 14.6207 7.61675 14.537 7.19441Z"
                                        fill="#1976D2" />
                                </svg>
                            </a>
                        </li>
                        <li>
                            <a href="#">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"
                                    fill="none">
                                    <g>
                                        <path
                                            d="M16 8C16 3.58172 12.4183 0 8 0C3.58172 0 0 3.58172 0 8C0 11.993 2.92547 15.3027 6.75 15.9028V10.3125H4.71875V8H6.75V6.2375C6.75 4.2325 7.94438 3.125 9.77172 3.125C10.6467 3.125 11.5625 3.28125 11.5625 3.28125V5.25H10.5538C9.56 5.25 9.25 5.86672 9.25 6.5V8H11.4688L11.1141 10.3125H9.25V15.9028C13.0745 15.3027 16 11.993 16 8Z"
                                            fill="#3F53A5" />
                                    </g>
                                </svg>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('after_script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.1/dist/sweetalert2.all.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#check_price__check_data').click(function(e) {
                    e.preventDefault();

                    var cityId = $('#city_id').val();

                    if (cityId) {
                        $.ajax({
                            url: '{{route('checkPrice')}}', // Replace with your actual URL
                            method: 'post',
                            data: {
                                city_id: cityId,
                                service_id: "{{$serviceData->id}}",
                                _token: '{{ csrf_token() }}' // Include the CSRF token
                            },
                            success: function(response) {
                                $('#check_price_data').html(response);
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                    } else {
                        alert('Please select a city.');
                    }
                });
            });
        </script>
@endsection
