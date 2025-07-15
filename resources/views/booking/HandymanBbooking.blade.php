<x-master-layout>
    <style>
        .star-rating i {
            font-size: 24px;
            cursor: pointer;
            color: #ffc107; /* Bootstrap's star color */
        }

        .star-rating i.far {
            color: #ccc; /* Inactive star color */
        }
    </style>
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
                            <th>Package Name</th>
                            <th>Date</th>
                            <th>User</th>
                            <th>Status</th>
                            <th>Package Type</th>
                            <th>Car/Address</th>
                            <th>Rate</th>
                            <th>Action</th>
                            <th>Proof Image & Comment</th> <!-- New Column for Proof Image and Comment -->
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($package_service_booking as $package)
                            <tr>
                                <td>{{ @$package->subscription->Serverdecimal->name }}</td>
                                <td>{{ $package->date }}</td>
                                <td>{{ @$package->user->first_name }} {{ @$package->user->last_name }} \ {{ @$package->user->contact_number }}</td>
                                <td><span class="btn {{ Labels($package->status) }}">{{ trans('messages.' . \App\Enums\BookingEnums::GetById($package->status)) }}</span></td>
                                <td>{{ trans('messages.' . $package->subscription->package_type) }}</td>
                                <td>
                                    @if ($package->car)
                                        <span>Car Number: {{ @$items->car_number }}</span><br>
                                        <span>Car Year: {{ @$items->car_year }}</span><br>
                                        <span>Car Model: {{ @$items->car_model }}</span><br>
                                    @endif
                                    @if ($package->address)
                                        <span>City: {{ @$package->address->CityData->name }}</span><br>
                                        <span>Region: {{ @$package->address->RegionData->name }}</span><br>
                                        <span>Districts: {{ @$package->address->AreaData->name }}</span><br>
                                        <span>Address: {{ @$package->address->address }}</span><br>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('servicepackage.rate_view', $package->subscription_id) }}" class="btn btn-success"><i class="fa fa-eye"></i></a>
                                </td>
                                <td>

                                    @if ($package->start_at == null)
                                        <a href="{{ route('handy_man_change.start_service', $package->id) }}" class="btn btn-success mt-2">Start Service</a>
                                    @else
                                        @if ($package->status == \App\Enums\BookingEnums::handyman_assign)
                                            <a href="{{ route('handy_man_change.change_status', [$package->id, \App\Enums\BookingEnums::finished]) }}" class="btn btn-danger mt-2">Finished</a>
                                        @endif
                                        @if ($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type == "handyman" && count($package->rate) == 0)
                                            <button type="button" class="btn btn-success mt-2" data-bs-toggle="modal" data-bs-target="#rateModal_{{ $package->id }}">Rate</button>
                                        @endif
                                    @endif

                                    @if($package->start_at != null && $package->end_at != null)
                                        @php
                                            $to = \Carbon\Carbon::parse( $package->end_at);
    $from = \Carbon\Carbon::parse( $package->start_at);

      $diff_in_minuts = $from->diffInMinutes($to);
      $diff_in_houres = $from->diffInHours($to);
      $diff_in_minutes = str_pad($diff_in_minuts, 2, '0', STR_PAD_LEFT);
$diff_in_hours = str_pad($diff_in_houres, 2, '0', STR_PAD_LEFT);
                                        @endphp
                                        <br>
                                        <br>
                                        <span>Duration</span>
                                        {{$diff_in_houres}} : {{$diff_in_minutes}}

                                    @endif
                                </td>
                                <!-- New TD for Proof Image and Comment -->
                                <td>
                                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#proofModal_{{ $package->id }}">
                                        Proof Image & Comment
                                    </button>
                                </td>
                            </tr>

                            <!-- Modal for Proof Image and Comment -->
                            <div class="modal fade" id="proofModal_{{ $package->id }}" tabindex="-1" aria-labelledby="proofModalLabel_{{ $package->id }}" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="proofModalLabel_{{ $package->id }}">Proof Image & Comment</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form action="{{ route('proof_image_comment.store') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="package_id" value="{{ $package->id }}">
                                                <div class="mb-3">
                                                    <label for="proof_image" class="form-label">Upload Proof Before</label>
                                                    <input type="file" class="form-control" id="proof_image" name="proof_image" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="comment_before" class="form-label">Comment Before</label>
                                                    <textarea class="form-control" id="comment_before" name="comment_before" rows="3"></textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="proof_image" class="form-label">Upload Proof After</label>
                                                    <input type="file" class="form-control" id="proof_image" name="proof_image" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="comment_after" class="form-label">Comment After</label>
                                                    <textarea class="form-control" id="comment_after" name="comment_after" rows="3"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Submit</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    @foreach ($package_service_booking as $package)
        @if($package->status == \App\Enums\BookingEnums::finished && auth()->user()->user_type ==  "handyman" && count($package->rate) == 0 )
            <div class="modal fade" id="rateModal_{{ $package->id }}" tabindex="-1" aria-labelledby="rateModalLabel_{{ $package->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="rateModalLabel_{{ $package->id }}">Rate Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="{{ route('servicepackage.rate', $package->id) }}" method="POST">
                            @csrf
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="rating_{{ $package->id }}">Rating:</label>
                                    <div id="star-rating-{{ $package->id }}" class="star-rating">
                                        <i class="far fa-star" data-value="1"></i>
                                        <i class="far fa-star" data-value="2"></i>
                                        <i class="far fa-star" data-value="3"></i>
                                        <i class="far fa-star" data-value="4"></i>
                                        <i class="far fa-star" data-value="5"></i>
                                    </div>
                                    <input type="hidden" id="rating_{{ $package->id }}" name="rating" value="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="feedback_{{ $package->id }}">Feedback:</label>
                                    <textarea id="feedback_{{ $package->id }}" name="feedback" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary">Submit Rating</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
<script>
    document.querySelectorAll('.star-rating i').forEach(function(star) {
        star.addEventListener('click', function() {
            let value = this.getAttribute('data-value');
            let stars = this.parentElement.querySelectorAll('i');

            // Reset all stars
            stars.forEach(function(s) {
                s.classList.remove('fas');
                s.classList.add('far');
            });

            // Highlight selected stars
            for (let i = 0; i < value; i++) {
                stars[i].classList.remove('far');
                stars[i].classList.add('fas');
            }

            // Update the hidden input value
            this.parentElement.nextElementSibling.value = value;
        });
    });
</script>
</x-master-layout>
