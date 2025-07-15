<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if($auth_user->can('product_category add'))
                                <a href="{{ route('productcategory.create') }}" class="float-right mr-1 btn btn-sm btn-primary">
                                    <i class="fa fa-plus-circle"></i> {{ trans('messages.add_form_title',['form' => trans('messages.product_category')]) }}
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
                        <form action="{{ route('productcategory.action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="change-status">{{ __('messages.status') }}</option>
                                <option value="change-featured">{{ __('messages.featured') }}</option>
                                <option value="delete">{{ __('messages.delete') }}</option>
                                <option value="restore">{{ __('messages.restore') }}</option>
                            </select>

                            <div class="select-status d-none quick-action-field" id="change-status-action" style="width:100%">
                                <select name="status" class="form-control select2" id="status">
                                    <option value="1">{{ __('messages.active') }}</option>
                                    <option value="0">{{ __('messages.inactive') }}</option>
                                </select>
                            </div>

                            <div class="select-status d-none quick-action-featured" id="change-featured-action" style="width:100%">
                                <select name="is_featured" class="form-control select2" id="is_featured">
                                    <option value="1">{{ __('messages.yes') }}</option>
                                    <option value="0">{{ __('messages.no') }}</option>
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
                        <select class="form-control select2" id="column_status">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="0" {{$filter['status'] == '0' ? "selected='selected'" : '' }}>{{ __('messages.inactive') }}</option>
                            <option value="1" {{$filter['status'] == '1' ? "selected='selected'" : '' }}>{{ __('messages.active') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_featured">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="0" {{$filter['is_featured'] == '0' ? "selected='selected'" : '' }}>{{ __('messages.regular') }}</option>
                            <option value="1" {{$filter['is_featured'] == '1' ? "selected='selected'" : '' }}>{{ __('messages.featured') }}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                            <th>{{ __('messages.name') }}</th>
                            <th>{{ __('messages.description') }}</th>
                            <th>{{ __('messages.products_count') }}</th>
                            <th>{{ __('messages.featured') }}</th>
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
                url: "{{ route('productcategory.index_data') }}",
                data: function(d) {
                    d.filter = {
                        status: $('#column_status').val(),
                        is_featured: $('#column_featured').val()
                    };
                }
            },
            columns: [
                {data: 'check', name: 'check', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'description', name: 'description'},
                {data: 'products_count', name: 'products_count', orderable: false},
                {data: 'is_featured', name: 'is_featured'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_status, #column_featured').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
