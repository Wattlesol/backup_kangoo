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
                            <h5 class="font-weight-bold">مراقبه الجوده</h5>
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter">
                                Create
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">مراقبه الجوده</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form class="mt-4" action="{{route('complaint.store')}}" method="post" enctype="multipart/form-data">
                                                {{csrf_field()}}
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="form-group">
                                                            <label for="exampleInputEmail1" class="form-label">عنوان الشكوي<span style="color: red">*</span></label>
                                                            <input type="text" class="form-control" value="{{@old('title')}}"  name="title" aria-describedby="emailHelp"
                                                                   placeholder="عنوان الشكوي">
                                                        </div>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.provider') ]).' <span class="text-danger">*</span>',['class'=>'form-control-label'],false) }}
                                                        <br />
                                                        {{ Form::select('provider_id', [], 0, [
                                                                    'class' => 'select2js form-group',
                                                                    'id' => 'provider_id',
                                                                    'name' => 'provider_id',
                                                                    'onchange' => 'selectprovider(this)',
                                                                    'required',
                                                                    'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.provider') ]),
                                                                    'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                                                                ]) }}
                                                    </div>

                                                </div>

                                                <button type="submit" class="btn btn-success">{{ __('messages.create') }}</button>
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>

                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <form class="row">
                    <!-- Provider Field -->
                    <div class="col-md-6">
                        {{ Form::label('name', __('messages.select_name',[ 'select' => __('messages.provider') ]).' <span class="text-danger">*</span>', ['class'=>'form-control-label'], false) }}
                        {{ Form::select('provider_id', [], "", [
                            'class' => 'select2js form-control',
                            'id' => 'provider_id',
                            'data-placeholder' => __('messages.select_name',[ 'select' => __('messages.provider') ]),
                            'data-ajax--url' => route('ajax-list', ['type' => 'provider']),
                        ]) }}
                    </div>

                    <!-- Status Field -->
                    <div class="col-md-6">
                        <label for="status">Satatus</label>
                        <br />
                        <select name="status" class="form-control">
                            <option value="" selected>Select Status</option>

                            @forelse(\App\Enums\ComplaintEnums::all() as $key => $status)
                                <option value="{{$status}}">{{trans('messages.'.$key)}}</option>
                            @empty
                            @endforelse
                        </select>
                    </div>
                    <!-- Submit Button -->
                    <div class="col-md-12 mt-3 text-end">
                        <button type="submit" class="btn btn-primary">
                            {{ __('messages.filter') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>




    <div class="table-responsive">


        <br>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>مزود الخدمه</th>
                <th>الحاله</th>
                <th>انشاء بواسطه</th>
                <th>عرض</th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $key=>$item)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$item->provider->display_name}}</td>
                    <td>{{trans('messages.'.\App\Enums\ComplaintEnums::GetById($item->status))}}</td>
                    <td>{{$item->createdby->display_name}}</td>

                    <td>
                        <a href="{{route('complaint.show',['id'=>$item->id])}}" class="btn btn-info"> Show<i class="ti-pin"></i> </a>
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
