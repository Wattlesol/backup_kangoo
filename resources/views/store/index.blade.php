<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            <div class="d-flex gap-2">
                                @if($auth_user->can('store add'))
                                    <a href="{{ route('store.create') }}" class="btn btn-sm btn-primary">
                                        <i class="fa fa-plus-circle"></i> {{ trans('messages.add_form_title',['form' => trans('messages.store')]) }}
                                    </a>
                                @endif
                                @if($auth_user->can('store approve'))
                                    <a href="{{ route('store.pending') }}" class="btn btn-sm btn-warning">
                                        <i class="fa fa-clock"></i> {{ __('messages.pending_approvals') }}
                                    </a>
                                @endif
                            </div>
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
                        <form action="{{ route('store.action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="approve">{{ __('messages.approve') }}</option>
                                <option value="reject">{{ __('messages.reject') }}</option>
                                <option value="suspend">{{ __('messages.suspend') }}</option>
                                <option value="delete">{{ __('messages.delete') }}</option>
                            </select>

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
                            <option value="pending" {{ $filter['status'] == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                            <option value="approved" {{ $filter['status'] == 'approved' ? 'selected' : '' }}>{{ __('messages.approved') }}</option>
                            <option value="rejected" {{ $filter['status'] == 'rejected' ? 'selected' : '' }}>{{ __('messages.rejected') }}</option>
                            <option value="suspended" {{ $filter['status'] == 'suspended' ? 'selected' : '' }}>{{ __('messages.suspended') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_provider">
                            <option value="">{{ __('messages.all') }} {{ __('messages.providers') }}</option>
                            @foreach($providers as $provider)
                                <option value="{{ $provider->id }}" {{ $filter['provider_id'] == $provider->id ? 'selected' : '' }}>
                                    {{ $provider->display_name }}
                                </option>
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
                            <th>{{ __('messages.store') }}</th>
                            <th>{{ __('messages.provider') }}</th>
                            <th>{{ __('messages.address') }}</th>
                            <th>{{ __('messages.phone') }}</th>
                            <th>{{ __('messages.products_count') }}</th>
                            <th>{{ __('messages.orders_count') }}</th>
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
                url: "{{ route('store.index_data') }}",
                data: function(d) {
                    d.filter = {
                        status: $('#column_status').val(),
                        provider_id: $('#column_provider').val(),
                        location: $('#location_filter').val()
                    };
                }
            },
            columns: [
                {data: 'check', name: 'check', orderable: false, searchable: false},
                {data: 'name', name: 'name'},
                {data: 'provider', name: 'provider'},
                {data: 'address', name: 'address'},
                {data: 'phone', name: 'phone'},
                {data: 'products_count', name: 'products_count'},
                {data: 'orders_count', name: 'orders_count'},
                {data: 'status', name: 'status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_status, #column_provider').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
