<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-info" onclick="showStatistics()">
                                    <i class="fa fa-chart-bar"></i> {{ __('messages.statistics') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="exportOrders()">
                                    <i class="fa fa-download"></i> {{ __('messages.export') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 id="total-orders">{{ $statistics['total'] ?? 0 }}</h3>
                                    <p class="mb-0">{{ __('messages.total_orders') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 id="pending-orders">{{ $statistics['pending'] ?? 0 }}</h3>
                                    <p class="mb-0">{{ __('messages.pending_orders') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 id="delivered-orders">{{ $statistics['delivered'] ?? 0 }}</h3>
                                    <p class="mb-0">{{ __('messages.delivered_orders') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 id="total-revenue">{{ getPriceFormat($statistics['revenue'] ?? 0) }}</h3>
                                    <p class="mb-0">{{ __('messages.total_revenue') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

            <div class="row justify-content-between">
                <div>
                    <div class="col-md-12">
                        <form action="{{ route('order.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                            @csrf
                            <select name="action" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                                <option value="">{{ __('messages.no_action') }}</option>
                                <option value="update_status">{{ __('messages.update_status') }}</option>
                                <option value="export">{{ __('messages.export') }}</option>
                            </select>

                            <div class="select-status d-none quick-action-field" id="update-status-action" style="width:100%">
                                <select name="status" class="form-control select2" id="status">
                                    <option value="confirmed">{{ __('messages.confirmed') }}</option>
                                    <option value="processing">{{ __('messages.processing') }}</option>
                                    <option value="shipped">{{ __('messages.shipped') }}</option>
                                    <option value="delivered">{{ __('messages.delivered') }}</option>
                                    <option value="cancelled">{{ __('messages.cancelled') }}</option>
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
                            <option value="pending" {{ $filter['status'] == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                            <option value="confirmed" {{ $filter['status'] == 'confirmed' ? 'selected' : '' }}>{{ __('messages.confirmed') }}</option>
                            <option value="processing" {{ $filter['status'] == 'processing' ? 'selected' : '' }}>{{ __('messages.processing') }}</option>
                            <option value="shipped" {{ $filter['status'] == 'shipped' ? 'selected' : '' }}>{{ __('messages.shipped') }}</option>
                            <option value="delivered" {{ $filter['status'] == 'delivered' ? 'selected' : '' }}>{{ __('messages.delivered') }}</option>
                            <option value="cancelled" {{ $filter['status'] == 'cancelled' ? 'selected' : '' }}>{{ __('messages.cancelled') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_payment_status">
                            <option value="">{{ __('messages.all') }}</option>
                            <option value="pending" {{ $filter['payment_status'] == 'pending' ? 'selected' : '' }}>{{ __('messages.pending') }}</option>
                            <option value="paid" {{ $filter['payment_status'] == 'paid' ? 'selected' : '' }}>{{ __('messages.paid') }}</option>
                            <option value="failed" {{ $filter['payment_status'] == 'failed' ? 'selected' : '' }}>{{ __('messages.failed') }}</option>
                            <option value="refunded" {{ $filter['payment_status'] == 'refunded' ? 'selected' : '' }}>{{ __('messages.refunded') }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select class="form-control select2" id="column_store">
                            <option value="">{{ __('messages.all') }} {{ __('messages.stores') }}</option>
                            <option value="admin" {{ $filter['store_id'] == 'admin' ? 'selected' : '' }}>{{ __('messages.admin_store') }}</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ $filter['store_id'] == $store->id ? 'selected' : '' }}>
                                    {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

                    <!-- Data Table -->
                    <div class="table-responsive">
                        <table id="datatable" class="table table-striped" data-toggle="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" class="form-check-input" id="select-all-table"></th>
                                    <th>{{ __('messages.order_number') }}</th>
                                    <th>{{ __('messages.customer') }}</th>
                                    <th>{{ __('messages.store') }}</th>
                                    <th>{{ __('messages.total_amount') }}</th>
                                    <th>{{ __('messages.order_status') }}</th>
                                    <th>{{ __('messages.payment_status') }}</th>
                                    <th>{{ __('messages.order_date') }}</th>
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

@section('bottom_script')
<script>
$(document).ready(function() {
    // Initialize DataTable
    window.renderedDataTable = $('#datatable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('order.index_data') }}",
            data: function(d) {
                d.filter = {
                    status: $('#column_status').val(),
                    payment_status: $('#column_payment_status').val(),
                    store_id: $('#column_store').val(),
                    date_from: $('#date_from_filter').val(),
                    date_to: $('#date_to_filter').val()
                };
            },
            error: function(xhr, error, thrown) {
                console.log('DataTable Ajax Error:', xhr.responseText);
                console.log('Status:', xhr.status);
                console.log('Error:', error);
                console.log('Thrown:', thrown);
            }
        },
        columns: [
            {data: 'check', name: 'check', orderable: false, searchable: false},
            {data: 'order_number', name: 'order_number'},
            {data: 'customer', name: 'customer'},
            {data: 'store', name: 'store'},
            {data: 'total_amount', name: 'total_amount'},
            {data: 'status', name: 'status'},
            {data: 'payment_status', name: 'payment_status'},
            {data: 'created_at', name: 'created_at'},
            {data: 'action', name: 'action', orderable: false, searchable: false}
        ],
        order: [[7, 'desc']], // Order by created_at desc
        drawCallback: function(settings) {
            // Show empty state if no data
            if (settings.json.recordsTotal === 0) {
                $('#datatable_wrapper').hide();
                $('#empty-state').show();
            } else {
                $('#datatable_wrapper').show();
                $('#empty-state').hide();
            }
        }
    });

    $('#column_status, #column_payment_status, #column_store').on('change', function() {
        window.renderedDataTable.ajax.reload();
    });
});

function showStatistics() {
    $.get('{{ route("order.statistics") }}')
        .done(function(response) {
            // Update statistics cards if they exist
            if (response.total_orders !== undefined) {
                $('#total-orders').text(response.total_orders);
                $('#pending-orders').text(response.pending_orders);
                $('#delivered-orders').text(response.delivered_orders);
                $('#total-revenue').text(response.total_revenue);
            }
        });
}

function exportOrders() {
    const filters = {
        status: $('#column_status').val(),
        payment_status: $('#column_payment_status').val(),
        store_id: $('#column_store').val()
    };

    const queryString = new URLSearchParams(filters).toString();
    window.open('{{ route("order.export") }}?' + queryString, '_blank');
}
</script>
@endsection
</x-master-layout>
