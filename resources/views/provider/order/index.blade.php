<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5>
                            <span class="text-muted">Only orders assigned by Sanad are visible here.</span>
                        </div>
                        <a href="{{ route('provider.kanban.index') }}" class="btn btn-sm btn-primary"><i class="fas fa-columns"></i> Operations Board</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control select2" id="column_stage">
                            <option value="">All stages</option>
                            @foreach(config('sanad.request_lifecycle', []) as $stage)
                                <option value="{{ $stage }}" {{ $filter['sanad_stage'] === $stage ? 'selected' : '' }}>{{ Str::headline($stage) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control select2" id="column_priority">
                            <option value="">All priorities</option>
                            @foreach(['low','normal','high','urgent'] as $priority)
                                <option value="{{ $priority }}" {{ $filter['sanad_priority'] === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control select2" id="column_sla">
                            <option value="">All SLA states</option>
                            <option value="overdue" {{ $filter['sla_state'] === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="datatable" class="table table-striped" data-toggle="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Priority</th>
                                <th>Stage</th>
                                <th>Assigned Employees</th>
                                <th>SLA</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

@section('bottom_script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    window.renderedDataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('provider.order.index_data') }}",
            data: function(d) {
                d.filter = {
                    sanad_stage: $('#column_stage').val(),
                    sanad_priority: $('#column_priority').val(),
                    sla_state: $('#column_sla').val()
                };
            }
        },
        columns: [
            {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
            {data: 'sanad_reference', name: 'sanad_reference'},
            {data: 'customer', name: 'customer', orderable: false},
            {data: 'service', name: 'service', orderable: false},
            {data: 'sanad_priority', name: 'sanad_priority'},
            {data: 'sanad_stage', name: 'sanad_stage'},
            {data: 'assigned_employees', name: 'assigned_employees', orderable: false},
            {data: 'sla', name: 'sla_due_at'},
            {data: 'updated_at', name: 'updated_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ]
    });

    $('#column_stage, #column_priority, #column_sla').on('change', function() {
        window.renderedDataTable.ajax.reload();
    });
});
</script>
@endsection
</x-master-layout>
