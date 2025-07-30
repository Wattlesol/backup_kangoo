<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? __('messages.my_orders') }}</h5>
                            <div class="alert alert-info alert-sm mb-0">
                                <i class="fas fa-info-circle"></i> {{ __('messages.orders_containing_your_products') }}
                            </div>
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
                            <select class="form-control select2" id="column_status">
                                <option value="">{{ __('messages.all') }} {{ __('messages.status') }}</option>
                                <option value="pending" {{ $filter['status'] == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                                <option value="confirmed" {{ $filter['status'] == 'confirmed' ? 'selected' : '' }}>{{ __('messages.confirmed') }}</option>
                                <option value="processing" {{ $filter['status'] == 'processing' ? 'selected' : '' }}>{{ __('messages.processing') }}</option>
                                <option value="shipped" {{ $filter['status'] == 'shipped' ? 'selected' : '' }}>{{ __('messages.shipped') }}</option>
                                <option value="delivered" {{ $filter['status'] == 'delivered' ? 'selected' : '' }}>{{ __('messages.delivered') }}</option>
                                <option value="cancelled" {{ $filter['status'] == 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
                            </select>
                        </div>
                        <div class="form-group mb-0">
                            <select class="form-control select2" id="column_payment_status">
                                <option value="">{{ __('messages.all') }} {{ __('messages.payment_status') }}</option>
                                <option value="pending" {{ $filter['payment_status'] == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                                <option value="paid" {{ $filter['payment_status'] == 'paid' ? 'selected' : '' }}>{{ __('messages.paid') }}</option>
                                <option value="failed" {{ $filter['payment_status'] == 'failed' ? 'selected' : '' }}>{{ __('messages.failed') }}</option>
                                <option value="refunded" {{ $filter['payment_status'] == 'refunded' ? 'selected' : '' }}>{{ __('messages.refunded') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="datatable" class="table table-striped" data-toggle="data-table">
                    <thead>
                        <tr>
                            <th>{{ __('messages.order_number') }}</th>
                            <th>{{ __('messages.customer') }}</th>
                            <th>{{ __('messages.your_items') }}</th>
                            <th>{{ __('messages.your_total') }}</th>
                            <th>{{ __('messages.order_total') }}</th>
                            <th>{{ __('messages.status') }}</th>
                            <th>{{ __('messages.payment_status') }}</th>
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
                url: "{{ route('provider.order.index_data') }}",
                data: function(d) {
                    d.filter = {
                        status: $('#column_status').val(),
                        payment_status: $('#column_payment_status').val()
                    };
                }
            },
            columns: [
                {data: 'order_number', name: 'order_number'},
                {data: 'customer', name: 'customer'},
                {data: 'provider_items', name: 'provider_items', orderable: false},
                {data: 'provider_total', name: 'provider_total', orderable: false},
                {data: 'total_amount', name: 'total_amount'},
                {data: 'status', name: 'status'},
                {data: 'payment_status', name: 'payment_status'},
                {data: 'created_at', name: 'created_at'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ]
        });

        $('#column_status, #column_payment_status').on('change', function() {
            window.renderedDataTable.ajax.reload();
        });
    });
</script>
@endsection
</x-master-layout>
