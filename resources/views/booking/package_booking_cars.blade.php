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

<h3>Please Select Car</h3>


                <table class="table">
                    <thead>
                    <tr>
                        <th scope="col">Car number</th>
                        <th scope="col">car Year</th>
                        <th scope="col">car Model</th>
                        <th scope="col">action</th>
                    </tr>
                    </thead>
                    <tbody >
                    @forelse($package->Cars as $items)
                        <tr>
                            <td>{{ $items->car_number }}</td>
                            <td>{{ $items->car_year }}</td>
                            <td>{{ $items->car_model }} </td>
                            <td>
                                <form action="{{ route('booking.booking_car_data', [$items->id, $package->id, $ServicePackage->service_id]) }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="booking_date_{{ $items->id }}">Date & Time</label>
                                        <input type="datetime-local" id="booking_date_{{ $items->id }}" name="booking_date" class="form-control" required>
                                    </div>
                                    <button type="submit" class="btn btn-danger mt-2">Book</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                    @endforelse

                    </tbody>
                </table>

    </div>
        </div>
    </div>
</x-master-layout>
