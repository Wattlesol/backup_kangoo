@props(['name', 'size' => 20])
<svg {{ $attributes->merge(['class' => 'quick-icon']) }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            @break
        @case('bot')
            <rect x="4" y="7" width="16" height="12" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="M12 7V4m-3 8h.01M15 12h.01M8 16h8M2 11v4m20-4v4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            @break
        @case('car')
            <path d="m5 10 1.7-4h10.6L19 10m-14 0h14a2 2 0 0 1 2 2v5H3v-5a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M6 17v2m12-2v2M6.5 13h.01m11-.01h.01" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
            @break
        @case('briefcase')
            <rect x="3" y="7" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M9 7V5h6v2m-12 5h18m-11 0v2h4v-2" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
            @break
        @case('landmark')
            <path d="M3 9h18L12 3 3 9Zm2 3v6m4-6v6m6-6v6m4-6v6M3 21h18" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('shield')
            <path d="M12 3 5 6v5c0 4.6 2.7 8 7 10 4.3-2 7-5.4 7-10V6l-7-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('check')
            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('refresh')
            <path d="M20 7v5h-5M4 17v-5h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M18.2 10A7 7 0 0 0 6.1 7.5L4 12m16 0-2.1 4.5A7 7 0 0 1 5.8 14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('message')
            <path d="M5 18.5 3.5 21l.7-4A8 8 0 1 1 12 20c-1.8 0-3.5-.5-4.8-1.5H5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M8 12h.01M12 12h.01M16 12h.01" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>
            @break
        @case('arrow')
            <path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            @break
        @case('play')
            <path d="m5 3 15 9L5 21V3Z" fill="currentColor"/>
            @break
        @case('apple')
            <path d="M16.7 12.7c0-2 1.6-3 1.7-3.1a4.2 4.2 0 0 0-3.3-1.8c-1.4-.1-2.7.8-3.4.8-.7 0-1.8-.8-3-.8-1.5 0-3 .9-3.8 2.3-1.7 2.9-.4 7.1 1.2 9.4.8 1.1 1.7 2.4 2.9 2.3 1.2 0 1.6-.7 3.1-.7s1.9.7 3.1.7c1.3 0 2.1-1.1 2.9-2.3.9-1.3 1.3-2.6 1.3-2.7-.1 0-2.5-1-2.7-4.1ZM14.2 6.3c.7-.9 1.2-2.1 1.1-3.3-1.1 0-2.4.7-3.2 1.6-.7.8-1.3 2-1.1 3.2 1.2.1 2.5-.6 3.2-1.5Z" fill="currentColor"/>
            @break
        @default
            <path d="M6 3h9l4 4v14H6V3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M15 3v5h4M9 12h6m-6 4h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
    @endswitch
</svg>
