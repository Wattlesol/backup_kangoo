<x-master-layout>
@include('customer-portal.partials.styles')
@php $selectedService = $service; $docs = $selectedService && is_array($selectedService->required_documents) ? $selectedService->required_documents : []; @endphp
<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">Create New Request</h1><div class="sanad-muted">Select service, provide information, upload documents, review fees, and submit.</div></div></div>
    <form class="sanad-card" method="post" action="{{ route('customer-portal.requests.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="sanad-card-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>Select Service</label>
                    <select class="sanad-form-control" name="service_id" onchange="window.location='{{ route('customer-portal.requests.create') }}?service_id='+this.value" required>
                        <option value="">Choose service</option>
                        @foreach($services as $item)
                            @php $serviceLabel = $item->name_en ?: $item->name ?: 'Service #'.$item->id; @endphp
                            <option value="{{ $item->id }}" {{ optional($selectedService)->id == $item->id ? 'selected' : '' }}>{{ $serviceLabel }}</option>
                        @endforeach
                    </select>
                    @if($selectedService)
                        <div class="mt-2 sanad-badge">
                            Selected: {{ $selectedService->name_en ?: $selectedService->name ?: 'Service #'.$selectedService->id }}
                        </div>
                    @endif
                </div>
                <div class="col-md-6 form-group"><label>Estimated Completion</label><input class="sanad-form-control" value="{{ optional($selectedService)->estimated_completion_time ?? '-' }}" disabled></div>
                <div class="col-md-12 form-group"><label>Required Information</label><textarea class="sanad-form-control" name="description" rows="4" placeholder="Add information required for this service"></textarea></div>
            </div>
            <h5 class="mt-3">Upload Required Documents</h5>
            <div class="row">
                @forelse($docs as $doc)
                    @php $name = is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc; $key = is_array($doc) ? ($doc['key'] ?? Str::slug($name, '_')) : Str::slug($name, '_'); @endphp
                    <div class="col-md-6 form-group"><label>{{ $name }}</label><input class="sanad-form-control" type="file" name="required_documents[{{ $key }}]"></div>
                @empty
                    <div class="col-12"><p class="sanad-muted">No required documents configured for the selected service.</p></div>
                @endforelse
            </div>
            @if($vaultDocuments->count())
                <h5 class="mt-3">Reuse From Document Vault</h5>
                <div class="row">@foreach($vaultDocuments as $document)<div class="col-md-4"><label><input type="checkbox" name="vault_document_ids[]" value="{{ $document->id }}"> {{ $document->document_type }}</label></div>@endforeach</div>
            @endif
            <h5 class="mt-3">Review and Payment</h5>
            <div class="sanad-grid mb-3">
                <div><span class="sanad-muted">Government Fee</span><strong class="d-block">{{ getPriceFormat(optional($selectedService)->government_fee ?? 0) }}</strong></div>
                <div><span class="sanad-muted">Service Fee</span><strong class="d-block">{{ getPriceFormat(optional($selectedService)->service_fee ?? optional($selectedService)->price ?? 0) }}</strong></div>
                <div><span class="sanad-muted">Request Number</span><strong class="d-block">Generated on submit</strong></div>
            </div>
            <button class="sanad-btn" type="submit">Submit Request</button>
        </div>
    </form>
</div>
</x-master-layout>
