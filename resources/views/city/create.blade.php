
<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                    <div class="card card-block card-stretch">
                        <div class="card-body p-0">
                            <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                                <h5 class="font-weight-bold">{{ __('messages.city') }}</h5>
                                    <a href="{{ route('city.index') }}" class="float-right btn btn-sm btn-primary"><i class="fa fa-angle-double-left"></i> {{ __('messages.back') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
        <form class="mt-4" action="{{route('city.store')}}" method="post" enctype="multipart/form-data">
            {{csrf_field()}}
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1" class="form-label">@lang('messages.name') <span style="color: red">*</span></label>
                        <input type="text" class="form-control" value="{{@old('name')}}"  name="name" aria-describedby="emailHelp"
                               placeholder="@lang('messages.name')">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-success">{{ __('messages.create') }}</button>
        </form>

        </div>
    </div>
</x-master-layout>
