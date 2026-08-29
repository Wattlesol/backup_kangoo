<x-master-layout>
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
  </head>
      <div class="container-fluid quick-service-catalog-page">
        <div class="row">
            @include('service.partials.sanad-service-summary')
        </div>
	    <div class="card quick-service-catalog-table-card">
        <div class="card-body">
        <div class="row justify-content-between">
              <div class="col-md-6 mb-3">
              <form action="{{ route('service.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                  <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                      <option value="">{{ __('messages.no_action') }}</option>
                      <option value="change-status">{{ __('messages.status') }}</option>
                      <option value="delete">{{ __('messages.delete') }}</option>
                      <option value="restore">{{ __('messages.restore') }}</option>
                      <option value="permanently-delete">{{ __('messages.permanent_dlt') }}</option>
                  </select>
                
                <div class="select-status d-none quick-action-field" id="change-status-action" style="width:100%">
                    <select name="status" class="form-control select2" id="status" >
                      <option value="1">{{ __('messages.active') }}</option>
                      <option value="0">{{ __('messages.inactive') }}</option>
                    </select>
                </div>
                <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                data--submit="{{ route('service.bulk-action') }}"
                data-datatable="reload" data-confirmation='true'
                data-title="{{ __('service',['form'=>  __('service') ]) }}"
                title="{{ __('service',['form'=>  __('service') ]) }}"
                data-message='{{ __("Do you want to perform this action?") }}' disabled>{{ __('messages.apply') }}</button>
            </form>
              </div>
              <div class="d-flex justify-content-end ml-auto mb-3">
                <div class="datatable-filter ml-auto">
                  <select name="column_status" id="column_status" class="select2 form-control" data-filter="select" style="width: 100%">
                    <option value="">{{ __('messages.all') }}</option>
                    <option value="0" {{$filter['status'] == '0' ? "selected" : ''}}>{{ __('messages.inactive') }}</option>
                    <option value="1" {{$filter['status'] == '1' ? "selected" : ''}}>{{ __('messages.active') }}</option>
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
          .quick-service-catalog-page {
            max-width: 1180px;
            margin: 0 auto;
            padding: 26px 22px 48px;
          }

          .quick-service-catalog-page > .row {
            margin-left: 0;
            margin-right: 0;
          }

          .quick-service-catalog-table-card {
            border: 1px solid #dce6f4;
            border-radius: 24px;
            box-shadow: 0 18px 50px rgba(10, 22, 38, .06);
            overflow: hidden;
          }

          .quick-service-catalog-table-card .card-body {
            padding: 24px;
          }

          .quick-service-catalog-table-card .form-control,
          .quick-service-catalog-table-card .input-group-text {
            min-height: 48px;
            border-color: #dce6f4;
            border-radius: 12px;
          }

          .quick-service-catalog-table-card .input-group {
            min-width: 260px;
          }

          .quick-service-catalog-table-card .input-group-text {
            background: #f8fbff;
          }

          .quick-service-catalog-table-card .btn-primary {
            min-height: 48px;
            border-radius: 12px;
            font-weight: 800;
            padding-left: 24px;
            padding-right: 24px;
          }

          .quick-service-catalog-table-card .table-responsive,
          .quick-service-catalog-table-card .dataTables_wrapper .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
          }

          .quick-service-catalog-table-card table.dataTable,
          .quick-service-catalog-table-card #datatable {
            min-width: 1120px;
            margin-bottom: 0;
          }

          .quick-service-catalog-table-card table.dataTable thead th {
            background: #1f6bff;
            color: #fff;
            border-color: rgba(255, 255, 255, .16);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .03em;
            white-space: nowrap;
          }

          .quick-service-catalog-table-card table.dataTable tbody td {
            vertical-align: middle;
          }

          @media (max-width: 899px) {
            .quick-service-catalog-page {
              padding: 16px 12px 36px;
            }

            .quick-service-catalog-table-card {
              border-radius: 20px;
            }

            .quick-service-catalog-table-card .card-body {
              padding: 16px;
            }

            .quick-service-catalog-table-card .row.justify-content-between {
              gap: 12px;
            }

            .quick-service-catalog-table-card form,
            .quick-service-catalog-table-card .d-flex.justify-content-end {
              width: 100%;
              flex-direction: column;
              align-items: stretch !important;
              margin-left: 0 !important;
            }

            .quick-service-catalog-table-card .datatable-filter,
            .quick-service-catalog-table-card .input-group,
            .quick-service-catalog-table-card .btn-primary {
              width: 100%;
              min-width: 0;
              margin-left: 0 !important;
            }

            .quick-service-catalog-table-card .table-responsive,
            .quick-service-catalog-table-card .dataTables_wrapper .table-responsive {
              border: 1px solid #dce6f4;
              border-radius: 16px;
              background: #fff;
            }

            .quick-service-catalog-table-card #datatable,
            .quick-service-catalog-table-card #datatable thead,
            .quick-service-catalog-table-card #datatable tbody,
            .quick-service-catalog-table-card #datatable tr,
            .quick-service-catalog-table-card #datatable th,
            .quick-service-catalog-table-card #datatable td {
              display: block;
              width: 100% !important;
              min-width: 0;
            }

            .quick-service-catalog-table-card #datatable thead {
              display: none;
            }

            .quick-service-catalog-table-card #datatable tbody tr {
              border: 1px solid #dce6f4;
              border-radius: 16px;
              margin: 12px;
              padding: 10px 12px;
              background: #fff;
              box-shadow: 0 10px 24px rgba(10,22,38,.05);
            }

            .quick-service-catalog-table-card #datatable tbody td {
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

            .quick-service-catalog-table-card #datatable tbody td > * {
              max-width: 100%;
              min-width: 0;
              white-space: normal !important;
              overflow-wrap: anywhere;
              text-align: left;
            }

            .quick-service-catalog-table-card #datatable tbody td:last-child {
              border-bottom: 0;
            }

            .quick-service-catalog-table-card #datatable tbody td::before {
              content: attr(data-label);
              color: #64748b;
              font-size: 12px;
              font-weight: 800;
              text-align: left;
              text-transform: uppercase;
              letter-spacing: .02em;
            }

            .quick-service-catalog-table-card #datatable tbody td:first-child::before {
              content: "";
            }

            .quick-service-catalog-table-card .dataTables_length,
            .quick-service-catalog-table-card .dataTables_paginate {
              width: 100%;
              text-align: center;
              justify-content: center;
            }
          }
        </style>
      @endonce
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
                  "url"    : '{{ route("service.service-index-data", ["postrequestid" => $postrequestid, "servicepackage" => $servicepackage]) }}',
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
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="service" onclick="selectAllTable(this)">',
                        exportable: false,
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'name',
                        name: 'name',
                        title: "{{ __("messages.english_name") }}"
                    },
                    {
                        data:'name_ar',
                        name:'name_ar',
                        title:"{{ __("messages.arabic_name") }}"
                    },
                    {
                        data:'category_id',
                        name:'category_id',
                        title: "{{ __('messages.category') }}"
                    },
                    {
                        data:'government_entity',
                        name:'government_entity',
                        title:"{{ __("messages.government_entity") }}"
                    },
                    {
                        data:'government_fee',
                        name:'government_fee',
                        title:"{{ __("messages.government_fee") }}"
                    },
                    {
                        data:'service_fee',
                        name:'service_fee',
                        title:"{{ __("messages.service_fee") }}"
                    },
                    {
                        data:'is_featured',
                        name:'is_featured',
                        title:"{{ __('messages.featured') }}"
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __('messages.status') }}"
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{ __('messages.action') }}"
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
