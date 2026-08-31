@if(isset($query->id))
<a href="{{ route('provider.show', ['provider' => $query->id]) }}" class="d-flex align-items-center text-decoration-none" style="gap: 10px;">
  <img src="{{ getSingleMedia($query,'profile_image', null) }}" alt="avatar" class="avatar avatar-40" style="width: 38px; height: 38px; border-radius: 10px; object-fit: cover; flex-shrink: 0; border: 1px solid rgba(0,0,0,0.06);">
  <div class="text-start" style="min-width: 0;">
    <h6 class="m-0 font-weight-bold" style="font-size: 13px; color: var(--quick-shell-ink); line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $query->first_name }} {{ $query->last_name }}</h6>
    <span class="text-muted" style="font-size: 11px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">{{ $query->email ?? '--' }}</span>
  </div>
</a>
@else
<div class="align-items-center">
    <h6 class="text-center text-muted mb-0">-</h6>
</div>
@endif

