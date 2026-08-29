<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="baseUrl" content="{{env('APP_URL')}}" />

    <title>{{ config('sanad.brand.name', 'Quick') }}</title>

    @include('partials._head')
</head>
<body class="{{ $userTheme['theme_class'] ?? 'theme-customer' }}" id="app">
@include('partials._body')
<script>
    // Apply role-based theme
    document.documentElement.className += ' {{ $userTheme['theme_class'] ?? 'theme-customer' }}';

    // Set CSS custom properties for dynamic theming
    document.documentElement.style.setProperty('--role-primary-light', '{{ $userTheme['primary_light'] ?? '#4A75FB' }}');
    document.documentElement.style.setProperty('--role-primary-dark', '{{ $userTheme['primary_dark'] ?? '#004CB2' }}');

    // Brand colors for rotating cards
    window.brandColors = @json($brandColors ?? []);
    window.userRole = '{{ $userTheme['role'] ?? 'customer' }}';
</script>
</body>
</html>
