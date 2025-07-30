<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.my_products') }}</h5>
                            <a href="{{ route('provider.product.create') }}" class="btn btn-sm btn-primary">
                                <i class="fa fa-plus-circle"></i> {{ __('messages.add_product') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row justify-content-between mb-3">
                <div class="col-md-6">
                    <div class="d-flex gap-3 align-items-center">
                        <div class="form-group mb-0">
                            <select class="form-control select2" id="column_category">
                                <option value="">{{ __('messages.all') }} {{ __('messages.categories') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ $filter['category_id'] == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-control select2" id="column_status">
                                <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                                <option value="0" {{ $filter['status'] == '0' ? 'selected' : '' }}>{{ __('messages.inactive') }}</option>
                                <option value="1" {{ $filter['status'] == '1' ? 'selected' : '' }}>{{ __('messages.active') }}</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-control select2" id="column_approval">
                                <option value="">{{ __('messages.all') }} {{ __('messages.approval_status') }}</option>
                                <option value="pending">{{ __('messages.pending') }}</option>
                                <option value="approved">{{ __('messages.approved') }}</option>
                                <option value="rejected">{{ __('messages.rejected') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <div class="alert alert-info alert-sm mb-0">
                        <i class="fas fa-info-circle"></i> {{ __('messages.products_need_approval_message') }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.product') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.sku') }}</th>
                            <th>{{ __('messages.price') }}</th>
                            <th>{{ __('messages.stock') }}</th>
                            <th>{{ __('messages.approval_status') }}</th>
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

@section('bottom_script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.renderedDataTable = $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('provider.product.index_data') }}",
                data: function(d) {
                    d.filter = {
                        category_id: $('#column_category').val(),
                        status: $('#column_status').val(),
                        approval_status: $('#column_approval').val()
                    };
                }
            },
            columns: [
                {data: 'name', name: 'name'},
                {data: 'category', name: 'category'},
                {data: 'sku', name: 'sku'},
                {data: 'price', name: 'price'},
                {data: 'stock', name: 'stock'},
                {data: 'approval_status', name: 'approval_status'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_category, #column_status, #column_approval').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
