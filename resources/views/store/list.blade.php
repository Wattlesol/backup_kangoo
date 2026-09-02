<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.list_form_title',['form' => __('messages.store')]) }}</h5>
                            @if($auth_user->can('store add'))
                                <a href="{{ route('store.create') }}" class="float-right btn btn-sm btn-primary">
                                    <i class="fa fa-plus-circle"></i> {{ __('messages.add_form_title',['form' => __('messages.store')]) }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="form-group mr-3">
                                    <select name="column_status" id="column_status" class="form-control select2js">
                                        <option value="">{{ __('messages.all') }}</option>
                                        <option value="approved">{{ __('messages.approved') }}</option>
                                        <option value="pending">{{ __('messages.pending') }}</option>
                                        <option value="rejected">{{ __('messages.rejected') }}</option>
                                    </select>
                                </div>
                                <div class="form-group mr-3">
                                    <select name="column_store_type" id="column_store_type" class="form-control select2js">
                                        <option value="">{{ __('messages.all') }} Types</option>
                                        <option value="main">Main Store</option>
                                        <option value="provider">Provider Store</option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="form-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-modal="export">
                                        <i class="fa fa-download"></i> {{ __('messages.export') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="datatable" class="table table-striped" data-toggle="data-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                                        <th>{{ __('messages.name') }}</th>
                                        <th>{{ __('messages.email') }}</th>
                                        <th>{{ __('messages.products_count') }}</th>
                                        <th>Store Type</th>
                                        <th>{{ __('messages.status') }}</th>
                                        <th>{{ __('messages.created_at') }}</th>
                                        <th>{{ __('messages.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
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
                    url: "{{ route('store.index_data') }}",
                    data: function(d) {
                        d.filter = {
                            column_status: $('#column_status').val(),
                            store_type: $('#column_store_type').val()
                        };
                    }
                },
                columns: [
                    {data: 'check', name: 'check', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'products_count', name: 'products_count', orderable: false},
                    {data: 'store_type', name: 'store_type'},
                    {data: 'status', name: 'status'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ]
            });

            $('#column_status, #column_store_type').on('change', function() {
                window.renderedDataTable.ajax.reload();
            });
        });
    </script>
    @endsection
</x-master-layout>
