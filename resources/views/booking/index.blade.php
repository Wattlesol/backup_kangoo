<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
        <style>
            #datatable th, #datatable td {
                vertical-align: middle;
            }
            #datatable th:nth-child(3),
            #datatable td:nth-child(3) {
                min-width: 240px !important;
                white-space: normal !important;
            }
            #datatable th:nth-child(2),
            #datatable td:nth-child(2) {
                white-space: nowrap !important;
                min-width: 130px !important;
            }
            #datatable th:nth-child(4),
            #datatable td:nth-child(4) {
                min-width: 190px !important;
            }
            #datatable th:nth-child(7),
            #datatable td:nth-child(7) {
                min-width: 90px !important;
                white-space: nowrap !important;
            }
        </style>
    </head>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ $pageTitle ?? trans('messages.list') }}</h5>
                            @if($auth_user->can('booking add'))
                            <a href="{{ route('booking.create') }}" class="float-right mr-1 btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ __('messages.create_request') }}</a>
                            @endif
                        </div>
                       
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
        @if(session('created_customer_credentials'))
            @php $credentials = session('created_customer_credentials'); @endphp
            <div class="alert alert-info d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <strong>Customer account created:</strong>
                    {{ $credentials['name'] }} can sign in with
                    <strong>{{ $credentials['email'] }}</strong>
                    and password
                    <strong>{{ $credentials['password'] }}</strong>
                    to upload documents.
                </div>
            </div>
        @endif
        <div class="row justify-content-between">
            <div>
                <div class="col-md-12">
                  <form action="{{ route('booking.bulk-action') }}" id="quick-action-form" class="form-disabled d-flex gap-3 align-items-center">
                    @csrf
                  <select name="action_type" class="form-control select2" id="quick-action-type" style="width:100%" disabled>
                      <option value="">{{__('messages.no_action')}}</option>
                      <option value="delete">{{__('messages.delete')}}</option>
                      <option value="restore">{{__('messages.restore')}}</option>
                      <option value="permanently-delete">{{__('messages.permanent_dlt')}}</option>
                  </select>
                  
                <button id="quick-action-apply" class="btn btn-primary" data-ajax="true"
                data--submit="{{ route('booking.bulk-action') }}"
                data-datatable="reload" data-confirmation='true'
                data-title="{{ __('booking',['form'=>  __('booking') ]) }}"
                title="{{ __('booking',['form'=>  __('booking') ]) }}"
                data-message='{{ __("Do you want to perform this action?") }}' disabled>{{__('messages.apply')}}</button>
            </div>
          
            </form>
          </div>
              <div class="d-flex justify-content-end">
                <a href="{{ route('booking.export', ['format' => 'pdf']) }}" class="btn btn-sm btn-outline-primary mr-2 booking-export-link" data-format="pdf">
                    <i class="ri-file-pdf-line"></i> {{ app()->getLocale() === 'ar' ? 'ملخص PDF' : 'Summary PDF' }}
                </a>
                <a href="{{ route('booking.export', ['format' => 'excel']) }}" class="btn btn-sm btn-outline-success mr-2 booking-export-link" data-format="excel">
                    <i class="ri-file-excel-line"></i> {{ app()->getLocale() === 'ar' ? 'تصدير Excel' : 'Export Excel' }}
                </a>
               
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
                  "url"    : '{{ route("booking.index_data") }}',
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
                        title: '<input type="checkbox" class="form-check-input" name="select_all_table" id="select-all-table" data-type="booking" onclick="selectAllTable(this)">',
                        exportable: false,
                        orderable: false,
                        searchable: false,
                    },
                    {
                        data: 'order_number',
                        name: 'sanad_reference',
                        orderable: false,
                        title: "{{__('messages.order_number')}}"
                    },
                    {
                        data: 'service_id',
                        name: 'service_id',
                        title: "{{__('messages.service')}}",
                        width: '22%'
                    },
                    {
                        data: 'customer_id',
                        name: 'customer_id',
                        title: "{{ __("messages.customer") }}"
                    },
                    {
                        data: 'provider_id',
                        name: 'provider_id',
                        title: "{{ __("messages.provider") }}"
                    },
                    {
                        data: 'handyman_id',
                        name: 'handyman_id',
                        title: "{{ __("messages.handyman") }}"
                    },
                    {
                        data: 'status',
                        name: 'status',
                        title: "{{ __("messages.status") }}"
                    },
                    {
                        data: 'priority',
                        name: 'sanad_priority',
                        orderable: false,
                        title: "{{__('messages.priority')}}"
                    },
                    {
                        data: 'expected_completion_at',
                        name: 'expected_completion_at',
                        title: "{{__('messages.expected_completion_date')}}"
                    },
                    {
                        data: 'date',
                        name: 'created_at',
                        title: "{{__('messages.created_date')}}"
                    },
                    {
                       data: 'payment_id',
                       name: 'payment_id',
                       title: "{{ __('messages.payment_status') }}",
                       orderable: true,
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        title: "{{__('messages.action')}}"
                    }
                    
                ],
                        order: [
            
                         [8, 'desc']
                     ]

                
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

  $('.booking-export-link').on('click', function (event) {
    const search = $('.dt-search').val();
    const url = new URL(this.href);
    if (search) {
        url.searchParams.set('search', search);
    }
    event.preventDefault();
    window.location.href = url.toString();
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
