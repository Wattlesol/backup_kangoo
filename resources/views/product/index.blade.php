<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if($auth_user->can('product add'))
                                <a href="{{ route('product.create') }}" class="float-right mr-1 btn btn-sm btn-primary">
                                    <i class="fa fa-plus-circle"></i> {{ trans('messages.add_form_title',['form' => trans('messages.product')]) }}
                                </a>
                            @endif
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
                        <form action="{{ route('product.action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="change-status">{{ __('messages.status') }}</option>
                                <option value="delete">{{ __('messages.delete') }}</option>
                                <option value="restore">{{ __('messages.restore') }}</option>
                            </select>

                            <div class="select-status d-none quick-action-field" id="change-status-action" style="width:100%">
                                <select name="status" class="form-control select2" id="status">
                                    <option value="1">{{ __('messages.active') }}</option>
                                    <option value="0">{{ __('messages.inactive') }}</option>
                                </select>
                            </div>

                            <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                                    data-size="small" data-type="form" data-container="#quick-action-form"
                                    data-title="{{ __('messages.are_you_sure') }}" disabled>{{ __('messages.apply') }}</button>
                        </form>
                    </div>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="form-group">
                        <select class="form-control select2" id="column_category">
                            <option value="">{{ __('messages.all') }} {{ __('messages.categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $filter['category_id'] == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_status">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="0" {{$filter['status'] == '0' ? "selected='selected'" : '' }}>{{ __('messages.inactive') }}</option>
                            <option value="1" {{$filter['status'] == '1' ? "selected='selected'" : '' }}>{{ __('messages.active') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_stock">
                            <option value="">{{ __('messages.all') }} {{ __('messages.stock') }}</option>
                            <option value="in_stock" {{ $filter['stock_status'] == 'in_stock' ? 'selected' : '' }}>{{ __('messages.in_stock') }}</option>
                            <option value="low_stock" {{ $filter['stock_status'] == 'low_stock' ? 'selected' : '' }}>{{ __('messages.low_stock') }}</option>
                            <option value="out_of_stock" {{ $filter['stock_status'] == 'out_of_stock' ? 'selected' : '' }}>{{ __('messages.out_of_stock') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_creator">
                            <option value="">{{ __('messages.all') }} {{ __('messages.creators') }}</option>
                            <option value="admin" {{ $filter['created_by_type'] == 'admin' ? 'selected' : '' }}>{{ __('messages.admin') }}</option>
                            <option value="provider" {{ $filter['created_by_type'] == 'provider' ? 'selected' : '' }}>{{ __('messages.provider') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                            <th>{{ __('messages.product') }}</th>
                            <th>{{ __('messages.category') }}</th>
                            <th>{{ __('messages.sku') }}</th>
                            <th>{{ __('messages.price') }}</th>
                            <th>{{ __('messages.stock') }}</th>
                            <th>{{ __('messages.creator') }}</th>
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
                url: "{{ route('product.index_data') }}",
                data: function(d) {
                    d.filter = {
                        category_id: $('#column_category').val(),
                        status: $('#column_status').val(),
                        stock_status: $('#column_stock').val(),
                        created_by_type: $('#column_creator').val()
                    };
                }
            },
            columns: [
                {data: 'check', name: 'check', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'category', name: 'category'},
                {data: 'sku', name: 'sku'},
                {data: 'price', name: 'price'},
                {data: 'stock', name: 'stock'},
                {data: 'creator', name: 'creator'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_category, #column_status, #column_stock, #column_creator').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
