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
                            <th>Package Price</th>
                            <th>Package Type</th>
                            <th>Start At</th>
                            <th>end At</th>
                            <th>Days left</th>
                            <th>Action</th>
                        </tr>
                        @foreach ($packages as $package)
                        <tr>
                            <td>{{ @$package->Serverdecimal->name }}</td>
                            <td>{{ $package->price }}</td>
                            <td>{{ $package->package_type }}</td>
                            <td>{{ $package->start_at }}</td>
                            <td>{{ $package->end_at }}</td>
                            <td>
                                @php
                                    $endDate = \Carbon\Carbon::parse($package->end_at);
                                    $currentDate = \Carbon\Carbon::now();
                                    $daysLeft = $endDate->diffInDays($currentDate, false);

                                    echo $daysLeft < 0 ? abs($daysLeft) : 0;
                                @endphp
                            </td>
                            <td>
                                <a href="{{ route('booking.package_service_show', $package->id) }}" class="btn btn-primary">Show</a>
                                @if(abs($daysLeft) < 10)
                                    <a href="{{ route('booking.package_service_renew_data', $package->id) }}" class="btn btn-danger">Renew</a>
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
