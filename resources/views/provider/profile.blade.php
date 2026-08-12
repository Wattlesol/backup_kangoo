<x-master-layout>
    <div class="container-fluid">
        <div class="card"><div class="card-body"><h5 class="font-weight-bold mb-1">{{ $pageTitle }}</h5><span class="text-muted">Office, registration, licenses, bank, hours, contact, supported services, and branch information.</span></div></div>
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Office Details</h5></div>
                    <div class="card-body">
                        <p><strong>Name:</strong> {{ $provider->display_name }}</p>
                        <p><strong>Email:</strong> {{ $provider->email }}</p>
                        <p><strong>Phone:</strong> {{ $provider->contact_number ?: '-' }}</p>
                        <p><strong>Contact Information:</strong> {{ $provider->description ?: '-' }}</p>
                        <p><strong>Working Hours:</strong> {{ $provider->sanad_working_hours ?: '-' }}</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Bank Account / IBAN</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerbank as $bank)
                            <p><strong>{{ $bank->bank_name ?? 'Bank' }}:</strong> {{ $bank->account_no ?? '-' }} {{ !empty($bank->ifsc_no) ? ' / IBAN '.$bank->ifsc_no : '' }}</p>
                        @empty
                            <p class="text-muted">No bank account configured.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Partner Verification Requirements</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerDocument as $document)
                            <div class="border-bottom pb-2 mb-2">
                                @php($hasUpload = getMediaFileExit($document, 'provider_document'))
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                    <div>
                                        <strong>{{ optional($document->document)->name ?? 'Document' }}</strong>
                                        @if($hasUpload)
                                            <span class="badge badge-{{ $document->is_verified ? 'success' : 'warning' }}">{{ $document->is_verified ? 'Approved' : 'Pending Review' }}</span>
                                        @else
                                            <span class="badge badge-danger">Missing</span>
                                        @endif
                                        @if($hasUpload)
                                            <a class="d-block mt-1" href="{{ getSingleMedia($document, 'provider_document') }}" target="_blank">Preview / Download</a>
                                        @endif
                                    </div>
                                    @if(!$document->is_verified)
                                        <form method="POST" action="{{ route('provider.profile.documents.upload', $document->id) }}" enctype="multipart/form-data" class="verification-upload-form">
                                            @csrf
                                            <input type="file" name="provider_document" class="form-control form-control-sm mb-1" required>
                                            <button class="btn btn-sm btn-primary">{{ $hasUpload ? 'Replace' : 'Upload' }}</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted">No partner verification requirements assigned by Sanad admin.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Supported Services</h5></div>
                    <div class="card-body">
                        @forelse($services as $availability)
                            <div class="border-bottom pb-2 mb-2">
                                <strong>{{ optional($availability->service)->name_en ?: optional($availability->service)->name }}</strong>
                                <span class="badge badge-{{ $availability->is_enabled ? 'success' : 'secondary' }}">{{ $availability->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                                <div class="text-muted small">{{ $availability->availability }} | {{ $availability->estimated_execution_time }}</div>
                            </div>
                        @empty
                            <p class="text-muted">No supported services configured.</p>
                        @endforelse
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Branch Information</h5></div>
                    <div class="card-body">
                        @forelse($provider->providerslotsmapping as $slot)
                            <p>{{ $slot->days ?? 'Working day' }}: {{ $slot->start_at ?? '-' }} - {{ $slot->end_at ?? '-' }}</p>
                        @empty
                            <p class="text-muted">No branch/working-hour records configured.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-master-layout>
