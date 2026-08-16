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
                            <h5 class="font-weight-bold">Quality Control</h5>
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
                                                        <label for="exampleInputEmail1" class="form-label">Issue Title<span style="color: red">*</span></label>
                                                            <input type="text" class="form-control" value="{{@old('title')}}"  name="title" aria-describedby="emailHelp"
                                                                   placeholder="عنوان الشكوي">
                                                        </div>
                                                    </div>

                                                    <div class="form-group col-md-12">
                                                        <label for="issue_type" class="form-control-label">Issue Type</label>
                                                        <select name="issue_type" id="issue_type" class="form-control" required>
                                                            <option value="customer_complaint">Customer Complaint</option>
                                                            <option value="escalation">Escalation</option>
                                                            <option value="sla_violation">SLA Violation</option>
                                                            <option value="customer_feedback">Customer Feedback</option>
                                                        </select>
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
                        <label for="issue_type_filter">Issue Type</label>
                        <select name="issue_type" id="issue_type_filter" class="form-control mb-2">
                            <option value="">All Issue Types</option>
                            <option value="customer_complaint">Customer Complaints</option>
                            <option value="escalation">Escalations</option>
                            <option value="sla_violation">SLA Violations</option>
                            <option value="customer_feedback">Customer Feedback</option>
                        </select>
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




    @if(auth()->user()->hasAnyRole(['admin', 'demo_admin']))
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                    <div>
                        <h5 class="font-weight-bold mb-1">Sanad Customer Complaints & Support Tickets</h5>
                        <span class="text-muted">Customer-created complaints linked to active or closed Sanad requests.</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge badge-light border p-2">Total: {{ $sanadComplaintStats['total'] ?? 0 }}</span>
                        <span class="badge badge-info p-2">Open: {{ $sanadComplaintStats['open'] ?? 0 }}</span>
                        <span class="badge badge-danger p-2">Urgent: {{ $sanadComplaintStats['urgent'] ?? 0 }}</span>
                        <span class="badge badge-success p-2">Resolved: {{ $sanadComplaintStats['resolved'] ?? 0 }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Request</th>
                                <th>Customer</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Description</th>
                                <th>Timeline</th>
                                <th>Attachment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sanadComplaints as $key => $complaint)
                                @php
                                    $booking = $complaint->booking;
                                    $attachmentUrl = $complaint->getFirstMediaUrl('sanad_complaint_attachment');
                                @endphp
                                <tr>
                                    <td>{{ method_exists($sanadComplaints, 'firstItem') ? $sanadComplaints->firstItem() + $key : $key + 1 }}</td>
                                    <td>
                                        @if($booking)
                                            <a href="{{ route('sanad.requests.show', $booking->id) }}" class="font-weight-bold">
                                                {{ $booking->sanad_reference ?: '#' . $booking->id }}
                                            </a>
                                            <div class="text-muted small">{{ optional($booking->service)->name ?: optional($booking->service)->name_en ?: '-' }}</div>
                                            <div class="text-muted small">Partner: {{ optional($booking->provider)->display_name ?: 'Not assigned' }}</div>
                                        @else
                                            <span class="text-muted">No linked request</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ optional($complaint->customer)->display_name ?: optional($complaint->customer)->email ?: '-' }}</strong>
                                        <div class="text-muted small">{{ optional($complaint->customer)->email }}</div>
                                    </td>
                                    <td>{{ Str::headline($complaint->complaint_type) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $complaint->priority === 'urgent' ? 'danger' : ($complaint->priority === 'high' ? 'warning' : 'secondary') }}">
                                            {{ Str::headline($complaint->priority) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ in_array($complaint->status, ['resolved', 'closed'], true) ? 'success' : 'info' }}">
                                            {{ Str::headline($complaint->status) }}
                                        </span>
                                    </td>
                                    <td style="min-width: 260px;">{{ Str::limit($complaint->description, 180) }}</td>
                                    <td>
                                        <div class="small">Created: {{ optional($complaint->created_at)->format('Y-m-d H:i') }}</div>
                                        <div class="small text-muted">Resolved: {{ optional($complaint->resolved_at)->format('Y-m-d H:i') ?: 'Open with Sanad support.' }}</div>
                                    </td>
                                    <td>
                                        @if($attachmentUrl)
                                            <a href="{{ $attachmentUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-paperclip mr-1"></i> Open
                                            </a>
                                        @else
                                            <span class="text-muted small">None</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">No Sanad customer complaints found for the selected filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if(method_exists($sanadComplaints, 'hasPages') && $sanadComplaints->hasPages())
                    <div class="mt-3">{{ $sanadComplaints->links() }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="table-responsive">


        <br>
        <table class="table table-hover">
            <thead>
            <tr>
                <th>#</th>
                <th>Partner</th>
                <th>Issue Type</th>
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
                    <td>{{ str_replace('_', ' ', ucfirst($item->issue_type ?: 'customer_complaint')) }}</td>
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
