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
                            <a href="{{ route('pricecity.create') }}" class="float-right mr-1 btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> {{ trans('messages.add_form_title',['form' => trans('messages.price_city')  ]) }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form class="mt-4" action="{{route('pricecity.store')}}" method="post" enctype="multipart/form-data">
        {{csrf_field()}}
{{--        <div class="row">--}}
{{--            <div class="col-md-6">--}}
{{--                <div class="form-group">--}}
{{--                    <label for="exampleInputEmail1" class="form-label">{{ __('messages.price_list') }} <span style="color: red">*</span></label>--}}
{{--                    <select name="price_list_id" class="form-control" >--}}
{{--                        @foreach($price_list as $price)--}}
{{--                            <option value=" {{ $price->id }}"> {{ $price->name }}</option>--}}
{{--                        @endforeach--}}
{{--                    </select>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
        <div class="table-responsive">
        <table class="table table-hover">
            <thead>
            <tr>
                <div class="row">
                    <div class="col-md-4">
                    </div>
                    <div class="col-md-4">
                        <th>{{ __('messages.city') }}</th>
                    </div>
                    <div class="col-md-4">
                        <th>{{ __('messages.price') }}</th>
                    </div>
                </div>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <div class="row">
                        <div class="col-md-4">
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="form-label">{{ __('messages.city') }} <span style="color: red">*</span></label>
                                <select name="price_list_id" class="form-control" >
                                    @foreach($city as $cities)
                                        <option value=" {{ $cities->id }}"> {{ $cities->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="exampleInputEmail1" class="form-label">@lang('messages.price') <span style="color: red">*</span></label>
                                <input type="text" class="form-control" value="{{@old('price')}}"  name="price" aria-describedby="emailHelp"
                                       placeholder="@lang('messages.price')">
                            </div>
                        </div>
                    </div>
                </tr>

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
        <button type="submit" class="btn btn-success">{{ __('messages.create') }}</button>
    </form>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
