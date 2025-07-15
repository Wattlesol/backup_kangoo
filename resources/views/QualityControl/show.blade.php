<x-master-layout>
    <head>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    </head>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">

            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
        <div class="row justify-content-between">

              <div class="table-responsive">
                  <table id="datatable" class="table table-striped border">
                      <thead>
                      <tr>
                          <th>مزود الخدمه</th>
                          <th>الحاله</th>
                          <th>انشاء بواسطه</th>
                      </tr>
                          <tr>
                              <td>{{$data->provider->display_name}}</td>
                              <td>{{trans('messages.'.\App\Enums\ComplaintEnums::GetById($data->status))}}</td>
                              <td>{{$data->createdby->display_name}}</td>

                          </tr>
                      </thead>
                  </table>
                  <hr>
                  <hr>

                <table id="datatable" class="table table-striped border">
                    <thead>
                        <tr>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>file</th>

                            <th>Added by</th>

                        </tr>
                        @foreach ($data->complaints_comment as $package)
                        <tr>
                            <td>{{ @$package->comment }}</td>

                            <td>{{ $package->created_at }}</td>
                            <td>
                                @if($package->file != "")
                                <a class="btn btn-warning" href="{{asset($package->file)}}">File</a>
                                @endif

                            </td>

                            <td>{{@$package->user->first_name}}  {{@$package->user->last_name}}  </td>


                        </tr>
                        @endforeach
                    </thead>
                </table>
<hr>
                  <h3>Reply</h3>
                  <br>
                  <form action="{{ route('complaint.reply_submitComplaint', $data->id) }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      <div class="form-group">
                          <textarea placeholder="Reply" name="reply" class="form-control" rows="2" required></textarea>
                      </div>
                      <br>
                      <div class="form-group">
                          <input type="file" name="file" class="form-control">
                      </div>
                      <br>
                      <button type="submit" class="btn btn-primary mt-2">Send Reply</button>
                  </form>
              </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</x-master-layout>
