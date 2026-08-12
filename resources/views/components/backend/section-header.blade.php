<div {{ $attributes->merge(['class' => 'd-flex justify-content-between align-items-center flex-wrap gap-2']) }}>
    <div>
        {{ $slot }}
        @isset($subtitle)
            <div class="small text-muted">{{ $subtitle }}</div>
        @endisset
    </div>
    @isset($toolbar)
        <div class="d-flex align-items-center gap-2">{{ $toolbar }}</div>
    @endisset
</div>
