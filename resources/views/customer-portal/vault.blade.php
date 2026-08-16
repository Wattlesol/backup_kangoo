<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="sanad-title">Document Vault</h1>
            <div class="sanad-muted">Store your essential personal & commercial documents here to easily re-attach them to messages and requests.</div>
        </div>
        <span class="badge badge-info p-2" style="font-size: 14px;"><i class="fas fa-folder-open mr-1"></i> {{ $documents->count() }} Saved Documents</span>
    </div>

    <div class="sanad-card mb-4 shadow-sm">
        <div class="sanad-card-header font-weight-bold"><i class="fas fa-cloud-upload-alt mr-2 text-primary"></i> Add New Document to Vault</div>
        <div class="sanad-card-body">
            <form method="post" action="{{ route('customer-portal.vault.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">Document Name / Type:</label>
                        <input class="sanad-form-control form-control" name="document_type" placeholder="e.g., National ID, Commercial Register, Passport" required>
                    </div>
                    <div class="col-md-5 mb-2">
                        <label class="small font-weight-bold text-muted">Select File:</label>
                        <input class="sanad-form-control form-control" type="file" name="file" accept="image/jpeg,image/png,application/pdf,.doc,.docx" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="sanad-btn btn btn-primary w-100"><i class="fas fa-upload mr-1"></i> Upload</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="sanad-card shadow-sm">
        <div class="sanad-card-header font-weight-bold"><i class="fas fa-folder mr-2 text-warning"></i> Your Saved Vault Documents</div>
        <div class="sanad-card-body table-responsive">
            <table class="sanad-table table align-middle">
                <thead>
                    <tr>
                        <th>Document Type</th>
                        <th>Status</th>
                        <th>File Name</th>
                        <th>Preview / Download</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr>
                            <td><strong>{{ $document->document_type }}</strong></td>
                            <td><span class="sanad-badge ok badge badge-success">{{ Str::headline($document->verification_status ?: 'Stored') }}</span></td>
                            <td><i class="far fa-file-alt text-muted mr-1"></i> {{ $document->file_name }}</td>
                            <td>
                                @if($document->getFirstMediaUrl('sanad_document'))
                                    <a href="{{ $document->getFirstMediaUrl('sanad_document') }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt mr-1"></i> Preview / Download</a>
                                @else
                                    <span class="text-muted small">No file link</span>
                                @endif
                            </td>
                            <td>
                                <form method="post" action="{{ route('customer-portal.vault.delete', $document->id) }}" onsubmit="return confirm('Are you sure you want to delete this document from your vault?');">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                No documents in your vault yet. Upload one above to get started!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-master-layout>
