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
                            <th>Handyman</th>
                            <th>date</th>
                            <th>Rate</th>
                            <th>Feedback</th>
                        </tr>
                        @foreach ($HanyManRateingService as $package)
                            <tr>
                                <td>{{@$package->handyman->first_name}}  {{@$package->handyman->last_name}} \ {{@$package->handyman->contact_number  }} </td>
                                <td>{{ $package->created_at }}</td>
                                    <td>
                                        @for ($i = 1; $i <= 5; $i++)
                                            @if ($i <= $package->rate)
                                                <i class="fas fa-star text-warning"></i> <!-- Filled star -->
                                            @else
                                                <i class="far fa-star text-warning"></i> <!-- Empty star -->
                                            @endif
                                        @endfor
                                    </td>
                                <td>{{ $package->feedback }}</td>

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
