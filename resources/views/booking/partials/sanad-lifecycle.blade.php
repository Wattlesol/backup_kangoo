@php
    $sanadStages = config('sanad.request_lifecycle', []);
    $sanadPriorities = ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High', 'urgent' => 'Urgent'];
    $currentStage = $bookingdata->sanad_stage ?: 'submitted';
    $currentPriority = $bookingdata->sanad_priority ?: 'normal';
    $slaValue = $bookingdata->sla_due_at ? \Carbon\Carbon::parse($bookingdata->sla_due_at)->format('Y-m-d\TH:i') : '';
@endphp

<div class="sanad-lifecycle-panel c1-light-bg radius-10 py-3 px-4 mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
        <div>
            <h4 class="mb-1">Quick Request Lifecycle</h4>
            <p class="mb-0 opacity-75">
                Reference: <strong>{{ $bookingdata->quick_reference }}</strong>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <span class="badge badge-primary">{{ Str::headline($currentStage) }}</span>
            <span class="badge badge-light">{{ Str::headline($currentPriority) }} priority</span>
        </div>
    </div>

    <form method="POST" action="{{ route('sanad.requests.lifecycle.update', $bookingdata->id) }}">
        @csrf
        <div class="row align-items-end">
            <div class="col-md-4 mb-3">
                <label class="form-control-label">Lifecycle Stage</label>
                <select name="sanad_stage" class="form-control">
                    @foreach($sanadStages as $stage)
                        <option value="{{ $stage }}" {{ $currentStage === $stage ? 'selected' : '' }}>{{ Str::headline($stage) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-control-label">Priority</label>
                <select name="sanad_priority" class="form-control">
                    @foreach($sanadPriorities as $value => $label)
                        <option value="{{ $value }}" {{ $currentPriority === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-control-label">SLA Due</label>
                <input type="datetime-local" name="sla_due_at" value="{{ $slaValue }}" class="form-control">
            </div>
            <div class="col-md-2 mb-3">
                <button type="submit" class="btn btn-primary w-100">Update</button>
            </div>
        </div>
    </form>
</div>

@once
    <style>
        .sanad-lifecycle-panel {
            border: 1px solid rgba(0, 0, 0, 0.08);
        }

        .sanad-lifecycle-panel .form-control-label {
            font-weight: 600;
            font-size: 13px;
        }
    </style>
@endonce
