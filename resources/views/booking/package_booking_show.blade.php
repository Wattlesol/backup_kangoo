<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>
    <div class="section-padding service-detail">
        <div class="container" style="background: white !important;">



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
            <h3 class="text-capitalize mb-2">{{ @$package->Serverdecimal->name }}</h3>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">

            </div>
            <img src="{{ asset('images/default.png') }}" alt="" class="img-fluid object-cover rounded-3 mt-4 w-100"/>
            @if(!empty(@$package->Serverdecimal->description))
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3">{{__('landingpage.description')}}</h5>
                    <p class="m-0">
                        {{ @$package->Serverdecimal->description }}
                    </p>
                </div>
            @endif
            <div class="mt-5 pt-lg-5 pt-3">
                <h5 class="mb-3">Duration</h5>
                <p class="m-0" style="color: red">
                    Start At   : {{ $package->start_at }} <br>
                    End At : {{ $package->end_at }} <br>

                </p>
            </div>
            <div class="row">
                <br>
                <h3>Service </h3>

                <div class="table-responsive">

                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">service</th>
                            <th scope="col">count</th>
                            <th scope="col">Duration of use before next time</th>
                            <th scope="col">Price</th>
                            <th scope="col">Action</th>
                        </tr>
                        </thead>
                        <tbody >
                        @forelse($package->service as $items)
                            <tr>
                                <td>{{ $items->service->name }}</td>
                                <td>  @if($items->service_type_data == "limited")
                                        {{ $items->count }}

                                    @else
                                        Unlimited
                                    @endif</td>
                                <td>{{ $items->duration_of_use }} Days</td>
                                <td>{{ $items->price }}</td>
                                <td>
                                    @if( $items->count != 0)
                                    @if($package->package_type == "Breaks" && $package->date_breaks == null)
                                            <form action="{{ route('booking.booking_breaks_data', [ $package->id, $items->service_id]) }}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="booking_date_{{ $items->id }}">Date & Time</label>
                                                    <input type="datetime-local" id="booking_date_{{ $items->id }}" name="booking_date" class="form-control" required>
                                                </div>
                                                <button type="submit" class="btn btn-danger mt-2">Book</button>
                                            </form>
                                        @elseif($package->package_type == "Breaks" && $package->date_breaks != null)
                                        <h3>{{@$package->date_breaks}}</h3>
                                        <br>
                                            <a href="{{ route('booking.booking_breaks_data_with_out_Data' ,[$package->id,$items->service_id]) }}" class="btn btn-danger">Booking</a>

                                        @else
                                            <a href="{{ route('booking.booking_service_data' ,[$items->service_id,$package->id]) }}" class="btn btn-danger">Booking</a>

                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                        @endforelse

                        </tbody>
                    </table>
                </div>
            </div>



        @if(in_array($package->package_type,['single','family','specific_place']))
        <h3>Car information</h3>
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">Car number</th>
                            <th scope="col">car Year</th>
                            <th scope="col">car Model</th>
                        </tr>
                        </thead>
                        <tbody >
                        @forelse($package->Cars as $items)
                            <tr>
                                <td>{{ $items->car_number }}</td>
                                <td>{{ $items->car_year }}</td>
                                <td>{{ $items->car_model }} </td>
                            </tr>
                        @empty
                        @endforelse

                        </tbody>
                    </table>

            @if(count($package->Cars) != $package->car_number)
                <form method="post" action="{{route('booking.add_new_car',$package->id)}}">
                    @csrf
                    <div class="form-group">
                        <label for="car_number">Car Number</label>
                        <input type="text" class="form-control" id="car_number" name="car_number" required>
                    </div>
                    <div class="form-group">
                        <label for="car_year">Car Year</label>
                        <input type="text" class="form-control" id="car_year" name="car_year" required>
                    </div>
                    <div class="form-group">
                        <label for="car_model">Car Model</label>
                        <input type="text" class="form-control" id="car_model" name="car_model" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Car</button>
                </form>
            @endif


        @endif

        @if(in_array($package->package_type,['Breaks','specific_place']))
<h3>Address</h3>
                    <table class="table">
                        <thead>
                        <tr>
                            <th scope="col">City</th>
                            <th scope="col">Region</th>
                            <th scope="col">Districts</th>
                            <th scope="col">Address</th>
                            <th scope="col">Google map location</th>
                        </tr>
                        </thead>
                        <tbody >
                        @forelse($package->address_data as $items)
                            <tr>
                                <td>{{ $items->CityData->name }}</td>
                                <td>{{ $items->RegionData->name }}</td>
                                <td>{{ $items->AreaData->name }}</td>
                                <td>{{ $items->address }}</td>


                            </tr>
                        @empty
                        @endforelse

                        </tbody>
                    </table>

            @if(count($package->address_data) != 1)
                <form method="post" action="{{route('booking.add_new_address',$package->id)}}">
                    @csrf
                    <div class="form-group">
                        <label for="car_number">City</label>
                        <br>
                      <h3>  {{@$package->CityData->name}}</h3>
                    </div>
                    <div class="form-group">
                        <label for="car_year">Region</label>
<h3>                        {{@$package->RegionData->name}}</h3>
                    </div>
                    <div class="form-group">
                        <label for="car_year">Districts</label>
                        <select id="area_id" name="area_id" required class="form-control">
                            <option value="">Please Select Districts</option>
                            @foreach(\App\Models\Districts::where('region_id',$package->region_id)->get() as $cities)
                                <option value="{{ $cities->id }}">{{ $cities->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="car_model">Address</label>
                        <br>
                        <textarea name="address" style="height: 200px; width: 100%"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="car_model">Google map location</label>
                        <br>
                        <textarea name="address" style="height: 200px; width: 100%"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Address</button>
                </form>
            @endif


        @endif

<hr>
                <h3>Booking</h3>
                <div class="table-responsive">
                    <table id="datatable" class="table table-striped border">
                        <thead>
                        <tr>
                            <th>date</th>
                            <th>Status</th>
                            <th>Car/Address</th>
                            <th>Action</th>

                        </tr>
                        @foreach ($package_service as $package)
                            <tr>
                                <td>{{ $package->date }}</td>
                                <td><span class="btn {{Labels($package->status)}}">{{ trans('messages.'.\App\Enums\BookingEnums::GetById($package->status)) }}</span></td>
                                <td>
                                    @if($package->car)
                                        <span>car number :  {{ @$items->car_number }}</span><br>
                                        <span>car year :{{ @$items->car_year }}</span><br>
                                        <span>car model :{{ @$items->car_model }} </span><br>
                                    @endif
                                    @if($package->address)
                                        <span>City : {{@$package->address->CityData->name}}</span><br>
                                        <span>Region :{{ @$package->address->RegionData->name }}</span><br>
                                        <span>Districts : {{ @$package->address->AreaData->name }}</span><br>
                                        <span>Address : {{ @$package->address->address }}</span><br>
                                    @endif
                                </td>
                                <td>
                                    @if(\App\Enums\BookingEnums::reschedule == $package->status)
                                        <form action="{{route('servicepackasge.user_change',$package->id)}}" method="POST">
                                            @csrf
                                            <div class="form-group">
                                                <label for="booking_date_{{ $package->id }}">Date & Time</label>
                                                <input type="datetime-local" id="booking_date_{{ $package->id }}" name="booking_date" class="form-control" required>
                                            </div>
                                            <button type="submit" class="btn btn-danger mt-2">Change Date</button>
                                        </form>

                                        <br>
                                        <a href="{{route('user_change.change_status',$package->id)}}" class="btn btn-primary mt-2">Confirm</a>
                                    @endif
                                    @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type ==  "user" && count($package->UsersFeedback) == 0 )

                                        <button type="button" class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#rateModal_{{ $package->id }}">
                                            Rate
                                        </button>

                                    @endif
                                        @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type ==  "user"  && !isset($package->PackageComplaint))

                                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#complaintModal_{{ $package->id }}">
                                                Make Complaint
                                            </button>

                                        @endif
                                </td>
                            </tr>
                            <!-- Rate Modal -->

                        @endforeach
                        </thead>
                    </table>
                </div>
<hr>



            @if($package->price > 0)
                <div class="mt-5 pt-lg-5 pt-3">
                    <h5 class="mb-3 text-capitalize">{{__('landingpage.servicespackage')}}</h5>
                    <div class="p-5 border rounded-3">


                            <div class="mt-5 border-top">
                                <div class="table-responsive">
                                    <table class="table mb-5">
                                        <tbody>
                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{ __('messages.price') }}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary" id="check_price_data" style="color: red">{{$package->price}}</h6>
                                            </td>
                                        </tr>


                                        <tr>
                                            <td class="ps-0 py-2">
                                                <label class="text-capitalize"><h6>{{__('messages.type')}}</h6></label>
                                            </td>
                                            <td class="pe-0 py-2 text-end">
                                                <h6 class="text-primary">{{$package->package_type }}</h6>
                                            </td>
                                        </tr>







                                        </tbody>
                                    </table>
                                </div>

                            </div>

                    </div>
                </div>

            @endif

    </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    @foreach ($package_service as $package)
        @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type ==  "user" && count($package->UsersFeedback) == 0 )
            <div class="modal fade" id="rateModal_{{ $package->id }}" tabindex="-1" aria-labelledby="rateModalLabel_{{ $package->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rateModalLabel_{{ $package->id }}">Rate Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('servicepackage.user_booking_service', $package->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="rating_{{ $package->id }}">Rating:</label>
                                    <div id="star-rating-{{ $package->id }}" class="star-rating">
                                        <i class="far fa-star" data-value="1"></i>
                                        <i class="far fa-star" data-value="2"></i>
                                        <i class="far fa-star" data-value="3"></i>
                                        <i class="far fa-star" data-value="4"></i>
                                        <i class="far fa-star" data-value="5"></i>
                                    </div>
                                    <input type="hidden" id="rating_{{ $package->id }}" name="rating" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="feedback_{{ $package->id }}">Feedback:</label>
                                    <textarea id="feedback_{{ $package->id }}" name="feedback" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Submit Rating</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
            @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type ==  "user"  && !isset($package->PackageComplaint))


            <div class="modal fade" id="complaintModal_{{ $package->id }}" tabindex="-1" aria-labelledby="complaintModalLabel_{{ $package->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="complaintModalLabel_{{ $package->id }}">Submit a Complaint</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('user_change.complaint_post', $package->id) }}" enctype="multipart/form-data" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="complaint_type_{{ $package->id }}">Complaint Type:</label>
                                    <select id="complaint_type_{{ $package->id }}" name="complaint_type" class="form-control" required>
                                        <option value="service">Service Issue</option>
                                        <option value="billing">Billing Issue</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group mt-3">
                                    <label for="complaint_file_{{ $package->id }}">File:</label>
                                    <input type="file" id="complaint_file_{{ $package->id }}" name="complaint_file" class="form-control" rows="4" />
                                </div>
                                <div class="form-group mt-3">
                                    <label for="complaint_details_{{ $package->id }}">Details:</label>
                                    <textarea id="complaint_details_{{ $package->id }}" name="complaint_details" class="form-control" rows="4" required></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Submit Complaint</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
    @endforeach
    <script>
        document.querySelectorAll('.star-rating i').forEach(function(star) {
            star.addEventListener('click', function() {
                let value = this.getAttribute('data-value');
                let stars = this.parentElement.querySelectorAll('i');

                // Reset all stars
                stars.forEach(function(s) {
                    s.classList.remove('fas');
                    s.classList.add('far');
                });

                // Highlight selected stars
                for (let i = 0; i < value; i++) {
                    stars[i].classList.remove('far');
                    stars[i].classList.add('fas');
                }

                // Update the hidden input value
                this.parentElement.nextElementSibling.value = value;
            });
        });
    </script>
</x-master-layout>
