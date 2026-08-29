@php
    $currentLocale = app()->getLocale();
    $isRtl = in_array($currentLocale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']) || session('dir') === 'rtl';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-quick-shell="true">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{env('APP_URL')}}" />

    <title>{{ config('sanad.brand.name', 'Quick') }} | {{ config('sanad.brand.tagline_ar') }}</title>

    @include('partials._head')
    @if(request()->routeIs('customer-portal.*'))
        @include('customer-portal.partials.styles')
    @endif
</head>
<body class="quick-shell-active {{ $userTheme['theme_class'] ?? 'theme-customer' }}" id="app" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<button class="quick-sidebar-scrim" type="button" onclick="toggleQuickMobileNav(false)" aria-label="Close menu"></button>
@include('partials._body')

<script>
    // Theme Management
    function applyQuickTheme(theme) {
        document.documentElement.setAttribute('data-quick-theme', theme);
        const body = document.body;
        if (theme === 'dark') {
            body.classList.add('quick-theme-dark');
            body.classList.remove('quick-theme-light');
        } else {
            body.classList.remove('quick-theme-dark');
            body.classList.add('quick-theme-light');
        }
        localStorage.setItem('quick_theme', theme);
        const sun = document.getElementById('quick-theme-sun');
        const moon = document.getElementById('quick-theme-moon');
        if (sun && moon) {
            if (theme === 'dark') {
                sun.style.display = 'block';
                moon.style.display = 'none';
            } else {
                sun.style.display = 'none';
                moon.style.display = 'block';
            }
        }
    }

    function toggleQuickTheme() {
        const isDark = document.body.classList.contains('quick-theme-dark');
        applyQuickTheme(isDark ? 'light' : 'dark');
    }

    // Sidebar Collapse
    function toggleQuickSidebar() {
        const isCollapsed = document.body.classList.toggle('is-collapsed');
        localStorage.setItem('quick_sidebar_collapsed', String(isCollapsed));
    }

    function toggleQuickMobileNav(open) {
        if (open === undefined) {
            document.body.classList.toggle('is-mobile-open');
        } else if (open) {
            document.body.classList.add('is-mobile-open');
        } else {
            document.body.classList.remove('is-mobile-open');
        }
    }

    // Initialize saved settings
    const savedTheme = localStorage.getItem('quick_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyQuickTheme(savedTheme);

    if (localStorage.getItem('quick_sidebar_collapsed') === 'true') {
        document.body.classList.add('is-collapsed');
    }
</script>
</body>
</html>
