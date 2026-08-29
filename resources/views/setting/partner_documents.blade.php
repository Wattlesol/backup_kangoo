<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Partner Verification Documents</h5>
    </div>
    <div class="card-body">
        @if($provider->providerDocument->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-bordered partner-documents-table mb-0">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Status</th>
                            <th>Uploaded File</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($provider->providerDocument as $document)
                            @php $hasUpload = getMediaFileExit($document, 'provider_document'); @endphp
                            @php $media = $hasUpload ? $document->getFirstMedia('provider_document') : null; @endphp
                            <tr>
                                <td class="align-middle"><strong>{{ optional($document->document)->localized_name ?? 'Document' }}</strong></td>
                                <td class="align-middle">
                                    @if($hasUpload)
                                        <span class="badge badge-{{ $document->is_verified ? 'success' : 'warning' }}">{{ $document->is_verified ? 'Approved' : 'Pending Review' }}</span>
                                    @else
                                        <span class="badge badge-danger">Missing</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <div class="uploaded-file-cell">
                                        @if($hasUpload)
                                            <form method="POST" action="{{ route('setting.partner-documents.delete', $document->id) }}" class="mb-1">
                                                @csrf
                                                <button class="btn btn-link btn-sm text-danger p-0 document-delete-icon" title="Remove file" aria-label="Remove file" onclick="return confirm('Remove this uploaded document?')">
                                                    <i class="ri-close-circle-line"></i>
                                                </button>
                                            </form>
                                            <a href="{{ getSingleMedia($document, 'provider_document') }}" target="_blank">{{ optional($media)->file_name ?: 'Preview / Download' }}</a>
                                        @else
                                            <span class="text-muted selected-file-name">No file uploaded</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="align-middle">
                                    @if($document->is_verified)
                                        <span class="text-muted">Verified</span>
                                    @elseif($hasUpload)
                                        <button class="btn btn-sm btn-primary" disabled>Save</button>
                                    @else
                                        <form method="POST" action="{{ route('setting.partner-documents.upload', $document->id) }}" enctype="multipart/form-data" class="document-upload-row">
                                            @csrf
                                            <label class="btn btn-sm btn-primary mb-0">
                                                Upload
                                                <input type="file" name="provider_document" class="document-file-input" required>
                                            </label>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-0">No partner verification requirements assigned by Quick admin.</p>
        @endif
    </div>
</div>

<style>
    .partner-documents-table th,
    .partner-documents-table td {
        vertical-align: middle;
    }
    .document-upload-row {
        margin: 0;
    }
    .document-file-input {
        display: none;
    }
    .uploaded-file-cell {
        min-width: 150px;
    }
    .document-delete-icon {
        font-size: 18px;
        line-height: 1;
    }
</style>
<script>
    $(document).on('change', '.document-file-input', function () {
        if (this.files && this.files.length) {
            $(this).closest('form').submit();
        }
    });
</script>
