<div class="sanad-employee-profile-cell">
    <strong>{{ $query->sanad_job_title ?: $query->designation ?: '-' }}</strong>
    <span>{{ $query->sanad_department ?: '-' }}</span>
    <small>
        {{ Str::headline($query->sanad_employee_status ?: 'available') }}
        @if($query->sanad_daily_capacity)
            &middot; {{ $query->sanad_daily_capacity }} / day
        @endif
    </small>
</div>
