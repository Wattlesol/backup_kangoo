<x-master-layout>
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
  </head>
    <div class="container-fluid quick-financial-center-page">
	        <div class="row">
	            @include('payment.partials.sanad-payment-summary')
	        </div>
	    <div class="card quick-financial-transactions-card">
	        <div class="card-body">
        <div class="row justify-content-between">
            @if(($sanadPaymentSummary['role_scope']['can_bulk_manage'] ?? false) === true)
              <div class="col-md-5 mb-3">
                  <form action="{{ route('payment.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                    <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                        <option value="">{{__('messages.no_action')}}</option>
                        <option value="delete">{{__('messages.delete')}}</option>
                    </select>
                    <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                        data--submit="{{ route('payment.bulk-action') }}"
                        data-datatable="reload" data-confirmation='true'
                        data-title="{{ __('payment',['form'=>  __('payment') ]) }}"
                        title="{{ __('payment',['form'=>  __('payment') ]) }}"
                        data-message='{{ __("Do you want to perform this action?") }}' disabled>{{__('messages.apply')}}</button>
                  </form>
              </div>
          @endif
              <div class="d-flex justify-content-end ml-auto mb-3">
              <div class="datatable-filter ml-auto">
                  <select class="select2 form-control" data-filter="select" id="statusSelect" style="width: 100%">
                    <option value="all" data-route="{{ route('payment.index')}}" selected>{{__('messages.all')}}</option>
                    <option value="cash" data-route="{{ route('cash.list') }}">{{__('messages.cash')}}</option>
                  
                  </select>
                </div>
                <div class="input-group ml-2">
                    <span class="input-group-text" id="addon-wrapping"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control dt-search" placeholder="{{ __("messages.search") }}..." aria-label="Search" aria-describedby="addon-wrapping" aria-controls="dataTableBuilder">
                  </div>
              </div>
               
              <div class="table-responsive">
                <table id="datatable" class="table table-striped border">
                </table>
              </div>
            </div>
	        </div>
	    </div>
	    </div>
      @once
        <style>
          .quick-financial-center-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 26px 22px 48px;
          }

          .quick-financial-center-page > .row {
            margin-left: 0;
            margin-right: 0;
          }

          .quick-financial-transactions-card {
            border: 1px solid #dce6f4;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(10, 22, 38, .06);
            overflow: hidden;
          }

          .quick-financial-transactions-card .card-body {
            padding: 24px;
          }

          .quick-financial-transactions-card .form-control,
          .quick-financial-transactions-card .input-group-text {
            min-height: 48px;
            border-color: #dce6f4;
            border-radius: 12px;
          }

          .quick-financial-transactions-card .input-group {
            min-width: 260px;
          }

          .quick-financial-transactions-card .input-group-text {
            background: #f8fbff;
          }

          .quick-financial-transactions-card .btn-primary {
            min-height: 48px;
            border-radius: 12px;
            font-weight: 800;
            padding-left: 24px;
            padding-right: 24px;
          }

          .quick-financial-transactions-card .table-responsive,
          .quick-financial-transactions-card .dataTables_wrapper .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
          }

          .quick-financial-transactions-card table.dataTable,
          .quick-financial-transactions-card #datatable {
            min-width: 980px;
            margin-bottom: 0;
          }

          .quick-financial-transactions-card table.dataTable thead th {
            background: #1f6bff;
            color: #fff;
            border-color: rgba(255, 255, 255, .16);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
            white-space: nowrap;
          }

          .quick-financial-transactions-card table.dataTable tbody td {
            vertical-align: middle;
          }

          .quick-financial-transactions-card .dataTables_length select {
            min-width: 72px;
          }

          .quick-financial-transactions-card .dataTables_paginate {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
          }

          @media (max-width: 899px) {
            .quick-financial-center-page {
              padding: 16px 12px 36px;
            }

            .quick-financial-transactions-card {
              border-radius: 20px;
            }

            .quick-financial-transactions-card .card-body {
              padding: 16px;
            }

            .quick-financial-transactions-card .row.justify-content-between {
              gap: 12px;
            }

            .quick-financial-transactions-card form,
            .quick-financial-transactions-card .d-flex.justify-content-end {
              width: 100%;
              flex-direction: column;
              align-items: stretch !important;
              margin-left: 0 !important;
            }

            .quick-financial-transactions-card .datatable-filter,
            .quick-financial-transactions-card .input-group,
            .quick-financial-transactions-card .btn-primary {
              width: 100%;
              min-width: 0;
              margin-left: 0 !important;
            }

            .quick-financial-transactions-card .table-responsive,
            .quick-financial-transactions-card .dataTables_wrapper .table-responsive {
              border: 1px solid #dce6f4;
              border-radius: 16px;
              background: #fff;
            }

            .quick-financial-transactions-card #datatable {
              min-width: 0;
            }

            .quick-financial-transactions-card #datatable,
            .quick-financial-transactions-card #datatable thead,
            .quick-financial-transactions-card #datatable tbody,
            .quick-financial-transactions-card #datatable tr,
            .quick-financial-transactions-card #datatable th,
            .quick-financial-transactions-card #datatable td {
              display: block;
              width: 100% !important;
            }

            .quick-financial-transactions-card #datatable thead {
              display: none;
            }

            .quick-financial-transactions-card #datatable tbody tr {
              border: 1px solid #dce6f4;
              border-radius: 16px;
              margin: 12px;
              padding: 10px 12px;
              background: #fff;
              box-shadow: 0 10px 24px rgba(10,22,38,.05);
            }

            .quick-financial-transactions-card #datatable tbody td {
              display: flex;
              flex-direction: column;
              align-items: stretch;
              gap: 6px;
              border: 0;
              border-bottom: 1px solid #edf3fb;
              padding: 10px 0;
              text-align: left;
              white-space: normal !important;
              overflow-wrap: anywhere;
            }

            .quick-financial-transactions-card #datatable tbody td > * {
              max-width: 100%;
              min-width: 0;
              white-space: normal !important;
              overflow-wrap: anywhere;
              text-align: left;
            }

            .quick-financial-transactions-card #datatable tbody td:last-child {
              border-bottom: 0;
            }

            .quick-financial-transactions-card #datatable tbody td::before {
              content: attr(data-label);
              color: #64748b;
              font-size: 12px;
              font-weight: 800;
              text-align: left;
              text-transform: uppercase;
              letter-spacing: .02em;
            }

            .quick-financial-transactions-card #datatable tbody td:first-child::before {
              content: "";
            }

            .quick-financial-transactions-card #datatable tbody td:first-child {
              justify-content: flex-start;
            }

            .quick-financial-transactions-card #datatable tbody td:first-child > * {
              max-width: 100%;
            }

            .quick-financial-transactions-card .dataTables_wrapper .row.align-items-center {
              gap: 12px;
            }

            .quick-financial-transactions-card .dataTables_length,
            .quick-financial-transactions-card .dataTables_paginate {
              width: 100%;
              text-align: center;
              justify-content: center;
            }
          }
        </style>
      @endonce
	    @php
	        $canBulkManagePayments = ($sanadPaymentSummary['role_scope']['can_bulk_manage'] ?? false) === true;
        $bulkPaymentHeader = $canBulkManagePayments
            ? '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" onclick="selectAllTable(this)">'
            : '';
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {

        window.renderedDataTable = $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                responsive: true,
                dom: '<"row align-items-center"><"table-responsive my-3" rt><"row align-items-center" <"col-md-6" l><"col-md-6" p>><"clear">',
                ajax: {
                  "type"   : "GET",
                  "url"    : '{{ route("payment.index_data")}}',
                  "data"   : function( d ) {
                    d.search = {
                      value: $('.dt-search').val()
                    };
                    d.filter = {
                      column_status: $('#column_status').val()
                    }
                  },
                },
                columns: [
                    {
                        name: 'check',
                        data: 'check',
                        title: @json($bulkPaymentHeader),
                        exportable: false,
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'booking_id',
                        name: 'booking_id',
                        title: "{{__('messages.service')}}",
                         orderable: false,
                    },
                    {
                        data: 'customer_id',
                        name: 'customer_id',
                        title: "{{__('messages.user')}}"
                    },
                    {
                        data: 'payment_type',
                        name: 'payment_type',
                        title: "{{__('messages.payment_type')}}"
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status',
                        title: "{{__('messages.status')}}"
                    },
                    {
                        data: 'datetime',
                        name: 'datetime',
                        title: "{{__('messages.datetime')}}"
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount',
                        title: "{{__('messages.total_amount')}}"
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{__('messages.action')}}"
                    }
                    
	                ],
                  drawCallback: function () {
                    const labels = this.api().columns().header().toArray().map((header) => $(header).text().trim());
                    $('#datatable tbody tr').each(function () {
                      $(this).find('td').each(function (index) {
                        const label = labels[index] || '';
                        if (label) {
                          $(this).attr('data-label', label);
                        }
                      });
                    });
                  }

	            });
      });

      $(document).ready(function() {
        $('#statusSelect').change(function() {
            var selectedValue = $(this).val();
            var selectedOption = $('#statusSelect option:selected');
            var route = selectedOption.data('route');

            if (selectedValue === 'cash' && route) {
                window.location.href = route;
            }
            window.location.href = route;
        });
    });

    function resetQuickAction () {
    const actionValue = $('#quick-action-type').val();
    console.log(actionValue)
    if (actionValue != '') {
        $('#quick-action-apply').removeAttr('disabled');

        if (actionValue == 'change-status') {
            $('.quick-action-field').addClass('d-none');
            $('#change-status-action').removeClass('d-none');
        } else {
            $('.quick-action-field').addClass('d-none');
        }
    } else {
        $('#quick-action-apply').attr('disabled', true);
        $('.quick-action-field').addClass('d-none');
    }
  }

  $('#quick-action-type').change(function () {
    resetQuickAction()
  });

  $(document).on('update_quick_action', function() {

  })

    $(document).on('click', '[data-ajax="true"]', function (e) {
      e.preventDefault();
      const button = $(this);
      const confirmation = button.data('confirmation');

      if (confirmation === 'true') {
          const message = button.data('message');
          if (confirm(message)) {
              const submitUrl = button.data('submit');
              const form = button.closest('form');
              form.attr('action', submitUrl);
              form.submit();
          }
      } else {
          const submitUrl = button.data('submit');
          const form = button.closest('form');
          form.attr('action', submitUrl);
          form.submit();
      }
  });

    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
