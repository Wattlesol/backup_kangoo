<x-master-layout>
@include('customer-portal.partials.styles')
<div class="container-fluid sanad-page">
    <div class="sanad-header"><div><h1 class="sanad-title">My Requests</h1><div class="sanad-muted">Search, filter, sort, and export invoices.</div></div><a class="sanad-btn" href="{{ route('customer-portal.requests.create') }}">Create New Request</a></div>
    <form class="sanad-card mb-3" method="get"><div class="sanad-card-body row"><div class="col-md-5"><input class="sanad-form-control" name="search" value="{{ request('search') }}" placeholder="Search request number or service"></div><div class="col-md-5"><select class="sanad-form-control" name="status"><option value="">All statuses</option>@foreach(['submitted','pending_review','in_progress','awaiting_customer_action','awaiting_quality_review','completed','closed'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ Str::headline($status) }}</option>@endforeach</select></div><div class="col-md-2"><button class="sanad-btn w-100">Filter</button></div></div></form>
    <div class="sanad-card"><div class="sanad-card-body table-responsive"><table class="sanad-table"><thead><tr><th>Request</th><th>Service</th><th>Status</th><th>SLA</th><th>Payment</th><th>Updated</th><th>Action</th></tr></thead><tbody>
        @forelse($requests as $request)
            <tr><td>{{ $request->sanad_reference ?? '#'.$request->id }}</td><td>{{ optional($request->service)->name_en ?? optional($request->service)->name }}</td><td><span class="sanad-badge">{{ Str::headline($request->sanad_stage ?? $request->status) }}</span></td><td>{{ optional($request->sla_due_at)->format('Y-m-d H:i') ?? '-' }}</td><td>{{ optional($request->payment)->payment_status ?? 'pending' }}</td><td>{{ optional($request->updated_at)->format('Y-m-d H:i') }}</td><td><a class="sanad-btn secondary" href="{{ route('customer-portal.requests.show', $request->id) }}">Open</a> @if($request->payment)<a class="sanad-btn ghost" href="{{ route('invoice_pdf', $request->id) }}">Invoice</a>@endif</td></tr>
        @empty
            <tr><td colspan="7">No requests found.</td></tr>
        @endforelse
    </tbody></table></div></div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
</x-master-layout>
