<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>
    <div class="container-fluid">
        <div class="row">
            @include('partials._handyman')

            <div class="col-lg-12">
                <div class="card card-block card-stretch">
                    <div class="card-body p-0">
                        <div class="d-flex justify-content-between align-items-center p-3 flex-wrap gap-3">
                            <h5 class="font-weight-bold"> المستندات</h5>
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#createDocumentModal">
                                Create
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="createDocumentModal" tabindex="-1" aria-labelledby="createDocumentModalLabel" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="createDocumentModalLabel">Add New Document</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="{{ route('handymandata.document_store',$id) }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label for="name">اسم المستند</label>
                                                    <input type="text" name="name" class="form-control" id="name" required>
                                                </div>

                                                <div class="form-group">
                                                    <label for="file"> المستند</label>
                                                    <input type="file" name="file" class="form-control" id="file">
                                                </div>

                                                <div class="form-group">
                                                    <label for="expired_date"> تاريخ الانتهاء</label>
                                                    <input type="date" name="expired_date" class="form-control" id="expired_date">
                                                </div>

                                                <div class="form-group">
                                                    <label for="note">ملاحظات</label>
                                                    <textarea name="note" class="form-control" id="note"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">حفظ</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">


        <br>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>اسم المستند </th>
                <th>المستند</th>
                <th>تاريخ الانتهاء</th>
                <th>ملاحظات </th>
            </tr>
            </thead>
            <tbody>
            @forelse($data as $key=>$item)
                <tr>
                    <td>{{$key+1}}</td>
                    <td>{{$item->name}}</td>
                    <td>   @if($item->file != "")
                            <a class="btn btn-warning" href="{{asset($item->file)}}">File</a>
                        @endif</td>
                    <td>{{$item->expired_date}}</td>
                    <td>{{$item->note}}</td>
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
