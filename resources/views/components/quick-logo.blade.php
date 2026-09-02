@props(['compact' => false, 'dark' => false])

<span {{ $attributes->merge(['class' => 'quick-brand-lockup']) }} aria-label="Quick كويك">
    <img src="{{ asset('brand/quick-mark.png') }}" alt="" class="quick-brand-mark" aria-hidden="true">
    @unless($compact)
        <span class="quick-brand-copy {{ $dark ? 'is-dark' : '' }}">
            <span class="quick-brand-ar">كويك</span>
            <span class="quick-brand-en">Quick</span>
            <span class="quick-brand-tagline">لإنجاز المعاملات الحكومية</span>
        </span>
    @endunless
</span>
