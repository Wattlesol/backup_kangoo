<x-master-layout>
<head>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
  </head>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ __('messages.price_list') }}</h5>
                            <a href="{{ route('pricelist.create') }}" class="float-right mr-1 btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ trans('messages.add_form_title',['form' => trans('messages.price_list')  ]) }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>{{ __('messages.name') }}</th>
                <th>{{ __('messages.action') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $key=>$item)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$item->name}}</td>
                    <td>
                        <a href="{{route('pricelist.edit',['id'=>$item->id])}}" class="btn btn-info"> Edit<i class="ti-pin"></i> </a>
                        <a href="{{route('pricelist.destroy',['id'=>$item->id])}}" class="btn btn-danger"> Delete <i class="ti-trash"></i> </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="200">
                        <h5>{{ __('messages.no_data') }}</h5>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        <br>
        @if($data->count()>0)
            <div class="row">
                <div class="col-md-5 col-sm-3 "> Count {{$data->total()}} </div>
                <div class="col-md-7 col-sm-7">{{$data->appends(\Request::except('_token'))->render()}}</div>
            </div>
        @endif
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
