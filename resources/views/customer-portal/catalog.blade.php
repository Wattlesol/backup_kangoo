<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header">
        <div><h1 class="sanad-title">Service Catalog</h1><div class="sanad-muted">Browse Sanad government and business services.</div></div>
        <a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}">Start Service</a>
    </div>
    <form class="sanad-card mb-3" method="get">
        <div class="sanad-card-body row">
            <div class="col-md-5"><input class="sanad-form-control" name="search" value="{{ request('search') }}" placeholder="Search services, requirements, authority"></div>
            <div class="col-md-5"><select class="sanad-form-control" name="category_id"><option value="">All Categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name_en ?? $category->name }}</option>@endforeach</select></div>
            <div class="col-md-2"><button class="sanad-btn w-100" type="submit">Filter</button></div>
        </div>
    </form>
    <div class="sanad-grid">
        @foreach($services as $service)
            <div class="sanad-card">
                <div class="sanad-card-body">
                    <span class="sanad-badge">{{ optional($service->category)->name_en ?? optional($service->category)->name ?? 'Sanad' }}</span>
                    <h5 class="mt-3">{{ $service->name_en ?? $service->name }}</h5>
                    <p class="sanad-muted">{{ Str::limit(strip_tags($service->description), 120) }}</p>
                    <div class="mb-2"><strong>Entity:</strong> {{ $service->government_entity ?? '-' }}</div>
                    <div class="mb-2"><strong>ETA:</strong> {{ $service->estimated_completion_time ?? '-' }}</div>
                    <div class="mb-3"><strong>Fee:</strong> {{ getPriceFormat(($service->service_fee ?? $service->price ?? 0) + ($service->government_fee ?? 0)) }}</div>
                    <div class="sanad-actions">
                        <a class="sanad-btn" href="{{ route('customer-portal.requests.create', ['service_id' => $service->id]) }}">Start Service</a>
                        <a class="sanad-btn secondary" href="{{ route('customer-portal.catalog.show', $service->id) }}">Details</a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="mt-3">{{ $services->links() }}</div>
</div>
</x-master-layout>
