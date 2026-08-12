<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div>
            <h1 class="sanad-title">Complaints & Support</h1>
            <div class="sanad-muted">Create complaints linked to a specific active or closed request.</div>
        </div>
    </div>

    <div class="sanad-card mb-3">
        <div class="sanad-card-header">Create Complaint</div>
        <div class="sanad-card-body">
            @if($requests->isEmpty())
                <div class="sanad-muted">No active or closed requests are available for complaints.</div>
            @else
                <form method="post" action="{{ route('customer-portal.support.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Request</label>
                            <select class="sanad-form-control" name="booking_id" required>
                                @foreach($requests as $request)
                                    @php
                                        $serviceName = optional($request->service)->name_en ?? optional($request->service)->name ?? 'Service';
                                        $partnerName = optional($request->provider)->display_name ?? optional($request->provider)->first_name ?? 'Not assigned yet';
                                        $requestStatus = \Illuminate\Support\Str::headline($request->sanad_stage ?? $request->status ?? 'Active');
                                    @endphp
                                    <option value="{{ $request->id }}" {{ (string) old('booking_id') === (string) $request->id ? 'selected' : '' }}>
                                        {{ $request->sanad_reference ?? '#'.$request->id }} - {{ $serviceName }} - Partner: {{ $partnerName }} - {{ $requestStatus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label>Complaint Type</label>
                            <select class="sanad-form-control" name="complaint_type" required>
                                <option value="">Select complaint type</option>
                                @foreach($complaintTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('complaint_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 form-group">
                            <label>Priority</label>
                            <select class="sanad-form-control" name="priority" required>
                                <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                            </select>
                        </div>

                        <div class="col-md-3 form-group">
                            <label>Attachment</label>
                            <input class="sanad-form-control" type="file" name="attachment">
                        </div>

                        <div class="col-md-12 form-group">
                            <label>Description</label>
                            <textarea class="sanad-form-control" name="description" rows="3" required>{{ old('description') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <button class="sanad-btn">Submit Complaint</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <div class="sanad-card">
        <div class="sanad-card-body table-responsive">
            <table class="sanad-table">
                <thead>
                    <tr>
                        <th>Request</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Resolution Timeline</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        @php
                            $booking = $complaint->booking;
                            $serviceName = optional(optional($booking)->service)->name_en ?? optional(optional($booking)->service)->name ?? 'Service';
                            $partnerName = optional(optional($booking)->provider)->display_name ?? optional(optional($booking)->provider)->first_name ?? 'Not assigned yet';
                            $typeLabel = $complaintTypes[$complaint->complaint_type] ?? \Illuminate\Support\Str::headline($complaint->complaint_type);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ optional($booking)->sanad_reference ?? '#'.$complaint->booking_id }}</strong>
                                <div class="sanad-muted">{{ $serviceName }}</div>
                                <div class="sanad-muted">Partner: {{ $partnerName }}</div>
                            </td>
                            <td>{{ $typeLabel }}</td>
                            <td>{{ \Illuminate\Support\Str::headline($complaint->priority) }}</td>
                            <td><span class="sanad-badge">{{ \Illuminate\Support\Str::headline($complaint->status) }}</span></td>
                            <td>{{ $complaint->resolution_notes ?? 'Open with Sanad support.' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No complaints submitted.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $complaints->links() }}</div>
</div>
</x-master-layout>
