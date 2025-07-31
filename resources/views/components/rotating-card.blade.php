@props([
    'index' => 0,
    'class' => '',
    'colorElement' => false
])

@php
    $cardColor = getRotatingCardColor($index);
    $colorKeys = ['yellow', 'red', 'green', 'blue'];
    $colorName = $colorKeys[$index % count($colorKeys)];
@endphp

<div {{ $attributes->merge(['class' => "card {$class}"]) }} data-card-index="{{ $index }}">
    @if($colorElement)
        <div class="card-color-element brand-color-{{ $colorName }}" style="background-color: {{ $cardColor['light'] }};">
            {{ $slot }}
        </div>
    @else
        <div class="card-body">
            {{ $slot }}
        </div>
    @endif
</div>
