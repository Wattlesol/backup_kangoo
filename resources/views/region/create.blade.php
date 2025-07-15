
<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                    <div class="card card-block card-stretch">
                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                                <h5 class="font-weight-bold">{{ __('messages.region') }}</h5>
                                    <a href="{{ route('region.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        <form class="mt-4" action="{{route('region.store')}}" method="post" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">@lang('messages.name') <span style="color: red">*</span></label>
                        <input type="text" class="form-control" value="{{@old('name')}}"  name="name" aria-describedby="emailHelp"
                               placeholder="@lang('messages.name')">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">@lang('messages.phone') <span style="color: red">*</span></label>
                        <input type="text" class="form-control" value="{{@old('phone')}}"  name="phone" aria-describedby="emailHelp"
                               placeholder="@lang('messages.phone')">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">{{ __('messages.city') }} <span style="color: red">*</span></label>
                        <select name="city_id" class="form-control" >
                            @foreach($city as $cities)
                                <option value=" {{ $cities->id }}"> {{ $cities->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">{{ __('messages.time') }} <span style="color: red">*</span></label>
                        <select name="time_id" class="form-control" >
                            @foreach($time as $times)
                                <option value=" {{ $times->id }}"> {{ $times->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">{{ __('messages.create') }}</button>
        </form>

        </div>
    </div>
</x-master-layout>
