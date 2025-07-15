
<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold">{{ __('messages.price_list') }}</h5>
                            <a href="{{ route('pricelist.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <form class="mt-4" action="{{route('pricelist.update',$data->id)}}" method="post" enctype="multipart/form-data">
            {{csrf_field()}}
            @method('put')
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">{{ __('messages.name') }} <span style="color: red">*</span></label>
                        <input type="text" class="form-control" value="{{@$data->name}}"  name="name" aria-describedby="emailHelp"
                               placeholder="@lang('messages.name')">
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <div class="row">
                                    <div class="col-md-4">
                                    </div>
                                    <div class="col-md-4">
                                        <th>{{ __('messages.region') }}</th>
                                    </div>
                                    <div class="col-md-4">
                                        <th>{{ __('messages.price') }}</th>
                                    </div>
                                </div>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($PriceCity as $cities2)
                                <tr>


                                    <td >
                                        <select name="city_id[]" class="form-control" >
                                            @foreach($city as $cities)
                                                <option value=" {{ $cities->id }}" @if($cities->id == $cities2->city_id)selected @endif > {{ $cities->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td >
                                        <input type="text" class="form-control" value="{{@old('price',$cities2->price)}}"  name="price[]" aria-describedby="emailHelp"
                                               placeholder="@lang('messages.price')">
                                    </td>

                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        <br>

                    </div>
                </div>

            </div>

            <button type="submit" class="btn btn-success">{{ __('messages.edit') }}</button>
        </form>

    </div>

</x-master-layout>
