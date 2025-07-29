<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? 'Pending Product Approvals' }}</h5>
                            <a href="{{ route('product-approval.rejected') }}" class="btn btn-sm btn-danger">
                                <i class="fa fa-times"></i> View Rejected
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between">
                <div>
                    <div class="col-md-12">
                        <form action="{{ route('product-approval.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="bulk-approve">Bulk Approve</option>
                                <option value="bulk-reject">Bulk Reject</option>
                            </select>

                            <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                    data-size="small" data-type="form" data-container="#quick-action-form"
                                    data-title="{{ __('messages.are_you_sure') }}" disabled>{{ __('messages.apply') }}</button>
                        </form>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="form-group">
                        <select class="form-control select2" id="column_provider">
                            <option value="">All Providers</option>
                            @foreach($providers ?? [] as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                            <th>Product</th>
                            <th>Provider</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@section('bottom_script')
<style>
.avatar-40 {
    width: 40px;
    height: 40px;
    min-width: 40px;
}
.table td {
    vertical-align: middle;
}
.font-size-14 {
    font-size: 14px;
}
.font-weight-medium {
    font-weight: 500;
}
.bg-soft-primary {
    background-color: rgba(108, 117, 125, 0.1);
}
.bg-soft-success {
    background-color: rgba(40, 167, 69, 0.1);
}
.bg-soft-info {
    background-color: rgba(23, 162, 184, 0.1);
}
.bg-soft-warning {
    background-color: rgba(255, 193, 7, 0.1);
}
.bg-soft-danger {
    background-color: rgba(220, 53, 69, 0.1);
}
.bg-soft-secondary {
    background-color: rgba(108, 117, 125, 0.1);
}
</style>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.renderedDataTable = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('product-approval.pending') }}",
                data: function(d) {
                    d.filter = {
                        provider_id: $('#column_provider').val()
                    };
                }
            },
            columns: [
                {data: 'check', name: 'check', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'provider', name: 'provider'},
                {data: 'category', name: 'category'},
                {data: 'price', name: 'price'},
                {data: 'submitted', name: 'submitted'},
                {data: 'status', name: 'status'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_provider').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
