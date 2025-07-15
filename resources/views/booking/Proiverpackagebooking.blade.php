<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">

            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between">

                <div class="table-responsive">
                    <table id="datatable" class="table table-striped border">
                        <thead>
                        <tr>
                            <th>Package Name</th>

                            <th>date</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Package Type</th>
                            <th>Status</th>
                            <th>Car/Address</th>
                            <th>Action</th>

                        </tr>
                        @foreach ($package_service_booking as $package)
                            <tr>
                                <td>{{ @$package->subscription->Serverdecimal->name }}</td>
                                <td>{{ $package->date }}</td>
                                <td>{{@$package->user->first_name}}  {{@$package->user->last_name}} \ {{@$package->user->contact_number  }} </td>
                                <td><span class="btn {{Labels($package->status)}}">{{ trans('messages.'.\App\Enums\BookingEnums::GetById($package->status)) }}</span></td>
                                <td>{{ trans('messages.'.$package->subscription->package_type) }}</td>

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
                                    @if(in_array($package->status,[\App\Enums\BookingEnums::WaitingForApproval,\App\Enums\BookingEnums::reschedule]))
                                    <form action="{{route('proiver_booking_servicepackage.ChangeData',$package->id)}}" method="POST">
                                        @csrf
                                        <div class="form-group">
                                            <label for="booking_date_{{ $package->id }}">Date & Time</label>
                                            <input type="datetime-local" id="booking_date_{{ $package->id }}" name="booking_date" class="form-control" required>
                                        </div>
                                        <button type="submit" class="btn btn-danger mt-2">Change Date</button>
                                    </form>

                                    <br>
                                    <a href="{{route('proiver_booking_servicepackage.change_status',[$package->id,\App\Enums\BookingEnums::approved])}}" class="btn btn-primary mt-2">Confirm</a>
                                    @endif

                                    @if($package->status == \App\Enums\BookingEnums::approved)
                                            <form action="{{route('proiver_booking_servicepackage.AssignHandyman',$package->id)}}" method="POST">
                                                @csrf
                                                <div class="form-group">
                                                    <label for="booking_date_{{ $package->id }}">Handyman Selector</label>
                                                    <select id="booking_date_{{ $package->id }}" name="handyman" class="form-control" required>
                                                        @foreach($handyman as $key=>$h)
                                                            <option value="{{$h}}">{{$key}} || {{$h}}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <button type="submit" class="btn btn-danger mt-2">Assign Handyman</button>
                                            </form>
                                        <br>
                                        <a href="{{route('proiver_booking_servicepackage.change_status', [$package->id, \App\Enums\BookingEnums::canceled])}}" class="btn btn-danger mt-2">Cancel</a>
                                    @endif
                                        @if($package->status == \App\Enums\BookingEnums::handyman_assign)

                                            <a href="{{route('proiver_booking_servicepackage.change_status', [$package->id, \App\Enums\BookingEnums::finished])}}" class="btn btn-danger mt-2">Finished</a>
                                        @endif
                                </td>
                            </tr>
                        @endforeach
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
