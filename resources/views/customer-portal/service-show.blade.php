<x-master-layout>
@include('customer-portal.partials.styles')
@php
    $docs = is_array($service->required_documents) ? $service->required_documents : [];
    $instructionSteps = [];
    if (!empty($service->service_instructions)) {
        $decodedInstructions = json_decode($service->service_instructions, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedInstructions)) {
            $instructionSteps = $decodedInstructions;
        } else {
            $instructionSteps = collect(preg_split('/\r\n|\r|\n/', $service->service_instructions))->filter()->map(function ($line, $index) {
                return ['title' => 'Step '.($index + 1), 'instruction' => trim($line)];
            })->values()->all();
        }
    }
@endphp
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">{{ $service->name_en ?? $service->name }}</h1><div class="sanad-muted">{{ $service->government_entity ?? 'Sanad Service' }}</div></div>
        <a class="sanad-btn" href="{{ route('customer-portal.requests.create', ['service_id' => $service->id]) }}">Start Service</a>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="sanad-card mb-3"><div class="sanad-card-header">Service Description</div><div class="sanad-card-body">{!! $service->description ?: '<span class="sanad-muted">No description configured.</span>' !!}</div></div>
            <div class="sanad-card mb-3"><div class="sanad-card-header">Requirements</div><div class="sanad-card-body">@forelse($docs as $doc)<span class="sanad-badge mr-2 mb-2">{{ is_array($doc) ? ($doc['name'] ?? $doc['document_name'] ?? $doc['key'] ?? 'Document') : $doc }}</span>@empty<span class="sanad-muted">No required documents configured.</span>@endforelse</div></div>
            <div class="sanad-card"><div class="sanad-card-header">Instructions and Terms</div><div class="sanad-card-body"><h6>Service Instructions</h6>@forelse($instructionSteps as $step)<div class="mb-2"><strong>{{ $step['title'] ?? 'Step' }}</strong><div class="sanad-muted">{{ $step['instruction'] ?? '' }}</div></div>@empty<p>-</p>@endforelse<h6>Terms & Conditions</h6><p>{{ $service->terms_and_conditions ?? '-' }}</p></div></div>
        </div>
        <div class="col-lg-4">
            <div class="sanad-card mb-3"><div class="sanad-card-header">Processing and Fees</div><div class="sanad-card-body"><p><strong>Estimated Processing Time:</strong> {{ $service->estimated_completion_time ?? '-' }}</p><p><strong>Government Fee:</strong> {{ getPriceFormat($service->government_fee ?? 0) }}</p><p><strong>Service Fee:</strong> {{ getPriceFormat($service->service_fee ?? $service->price ?? 0) }}</p></div></div>
            <div class="sanad-card"><div class="sanad-card-header">Related Services</div><div class="sanad-card-body">@forelse($relatedServices as $related)<div class="mb-2"><a href="{{ route('customer-portal.catalog.show', $related->id) }}">{{ $related->name_en ?? $related->name }}</a></div>@empty<span class="sanad-muted">No related services.</span>@endforelse</div></div>
        </div>
    </div>
</div>
</x-master-layout>
