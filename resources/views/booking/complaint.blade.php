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
                            <th>Date</th>

                            <th>Action</th>
                        </tr>
                        @foreach ($PackageComplaint as $package)
                        <tr>
                            <td>{{ @$package->subscription->Serverdecimal->name }}</td>

                            <td>{{ $package->created_at }}</td>

                            <td>
                                <a href="{{ route('users.complaint_show', $package->id) }}" class="btn btn-primary">Show</a>

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
