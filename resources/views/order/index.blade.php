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
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="exportOrders('pdf')">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="exportOrders('excel')">
                                    <i class="fa fa-file-excel"></i> Excel
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
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="avatar-60 bg-soft-primary rounded">
                                    <i class="fa fa-shopping-cart fa-2x text-primary"></i>
                                </div>
                            </div>
                            <h4 class="mb-1" id="total-orders">{{ $statistics['total'] ?? 0 }}</h4>
                            <p class="mb-0 text-muted">Total Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="avatar-60 bg-soft-warning rounded">
                                    <i class="fa fa-clock fa-2x text-warning"></i>
                                </div>
                            </div>
                            <h4 class="mb-1" id="pending-orders">{{ $statistics['pending'] ?? 0 }}</h4>
                            <p class="mb-0 text-muted">Pending Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="avatar-60 bg-soft-success rounded">
                                    <i class="fa fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                            <h4 class="mb-1" id="delivered-orders">{{ $statistics['delivered'] ?? 0 }}</h4>
                            <p class="mb-0 text-muted">Delivered Orders</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="avatar-60 bg-soft-info rounded">
                                    <i class="fa fa-dollar-sign fa-2x text-info"></i>
                                </div>
                            </div>
                            <h4 class="mb-1" id="total-revenue">{{ getPriceFormat($statistics['revenue'] ?? 0) }}</h4>
                            <p class="mb-0 text-muted">Total Revenue</p>
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
                                    <th>Order Number</th>
                                    <th>Customer</th>
                                    <th>Store</th>
                                    <th>Total Amount</th>
                                    <th>Order Status</th>
                                    <th>Payment Status</th>
                                    <th>Order Date</th>
                                    <th>Action</th>
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

    @can('order edit')
    <div class="modal fade" id="reassignPartnerModal" tabindex="-1" aria-labelledby="reassignPartnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" id="reassign-partner-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="reassignPartnerModalLabel">Reassign Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="reassign-order-id">
                    <div class="form-group">
                        <label class="form-label">New Partner</label>
                        <select name="store_id" class="form-control" required>
                            <option value="">Select partner</option>
                            @foreach($partners as $store)
                                <option value="{{ $store->id }}">
                                    {{ optional($store->provider)->display_name ?: $store->name }} - {{ $store->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Example: previous partner delayed the service"></textarea>
                    </div>
                    <p class="text-muted mb-0">The same order record is moved, so its documents, items, timeline, and chats stay attached.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Reassign</button>
                </div>
            </form>
        </div>
    </div>
    @endcan

@section('bottom_script')
<style>
.avatar-60 {
    width: 60px;
    height: 60px;
    min-width: 60px;
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
.table td {
    vertical-align: middle;
}

/* Action Buttons Styling */
.btn-group .dropdown-menu {
    min-width: 160px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #dee2e6;
    border-radius: 6px;
}

.btn-group .dropdown-item {
    padding: 8px 16px;
    font-size: 13px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}

.btn-group .dropdown-item:hover {
    background-color: #f8f9fa;
    color: #495057;
}

.btn-group .dropdown-item i {
    width: 16px;
    margin-right: 8px;
}

.btn-group .dropdown-divider {
    margin: 4px 0;
}

/* Ensure dropdowns work properly */
.btn-group {
    position: relative;
}

.btn-group .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    list-style: none;
    text-align: left;
    background-color: #fff;
    background-clip: padding-box;
    margin: 2px 0 0;
}

.btn-group.show .dropdown-menu {
    display: block;
}
</style>
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

            // Initialize dropdowns after table draw
            initializeDropdowns();
        }
    });

    $('#column_status, #column_payment_status, #column_store').on('change', function() {
        window.renderedDataTable.ajax.reload();
    });

    // Initialize dropdowns function
    function initializeDropdowns() {
        // Initialize Bootstrap dropdowns
        $('.dropdown-toggle').dropdown();

        // Handle dropdown clicks
        $(document).off('click.dropdown').on('click.dropdown', '.dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Close all other dropdowns
            $('.btn-group').removeClass('show');
            $('.dropdown-menu').hide();

            // Toggle current dropdown
            const $btnGroup = $(this).closest('.btn-group');
            const $dropdownMenu = $btnGroup.find('.dropdown-menu');

            if ($btnGroup.hasClass('show')) {
                $btnGroup.removeClass('show');
                $dropdownMenu.hide();
            } else {
                $btnGroup.addClass('show');
                $dropdownMenu.show();
            }
        });

        // Close dropdowns when clicking outside
        $(document).off('click.dropdown-outside').on('click.dropdown-outside', function(e) {
            if (!$(e.target).closest('.btn-group').length) {
                $('.btn-group').removeClass('show');
                $('.dropdown-menu').hide();
            }
        });

        // Handle dropdown item clicks
        $(document).off('click.dropdown-item').on('click.dropdown-item', '.dropdown-item', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Close dropdown after click
            $(this).closest('.btn-group').removeClass('show');
            $(this).closest('.dropdown-menu').hide();
        });
    }

    // Initial dropdown initialization
    initializeDropdowns();
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

function exportOrders(format) {
    const filters = {
        status: $('#column_status').val(),
        payment_status: $('#column_payment_status').val(),
        store_id: $('#column_store').val(),
        format: format || 'excel'
    };

    const queryString = new URLSearchParams(filters).toString();
    window.open('{{ route("order.export") }}?' + queryString, '_blank');
}

window.openReassignPartnerModal = function(orderId) {
    $('#reassign-order-id').val(orderId);
    $('#reassign-partner-form')[0].reset();
    $('#reassign-order-id').val(orderId);
    $('#reassignPartnerModal').modal('show');
};

$('#reassign-partner-form').on('submit', function(e) {
    e.preventDefault();
    const orderId = $('#reassign-order-id').val();

    $.ajax({
        url: '{{ url("order") }}/' + orderId + '/reassign-partner',
        type: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            if (response.status) {
                $('#reassignPartnerModal').modal('hide');
                if (typeof window.renderedDataTable !== 'undefined') {
                    window.renderedDataTable.ajax.reload();
                }
                if (typeof showAlert === 'function') {
                    showAlert('success', response.message);
                } else {
                    alert(response.message);
                }
            } else if (typeof showAlert === 'function') {
                showAlert('error', response.message);
            } else {
                alert(response.message);
            }
        },
        error: function(xhr) {
            const message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '{{ __("messages.something_went_wrong") }}';
            if (typeof showAlert === 'function') {
                showAlert('error', message);
            } else {
                alert(message);
            }
        }
    });
});
</script>
@endsection
</x-master-layout>
