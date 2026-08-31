<!DOCTYPE html>
@php
    // Use the same persisted preference as the authenticated Quick shell.
    $themeMode = 'light';
    if (in_array(request()->cookie('quick_theme'), ['light', 'dark'], true)) {
        $themeMode = request()->cookie('quick_theme');
    } elseif (request()->cookie('data-bs-theme')) {
        $themeMode = request()->cookie('data-bs-theme');
    } elseif (request()->cookie('theme_mode')) {
        $themeMode = request()->cookie('theme_mode');
    } elseif (session('quick_theme')) {
        $themeMode = session('quick_theme');
    } elseif (session('theme_mode')) {
        $themeMode = session('theme_mode');
    }

    $userRole = auth()->check() ? auth()->user()->user_type : 'customer';
    $currentLocale = app()->getLocale();
    $isRtl = in_array($currentLocale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']);
@endphp
<html lang="{{ $currentLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-bs-theme="{{ $themeMode }}" data-quick-theme="{{ $themeMode }}">
<head>
    @yield('before_head')
    @include('landing-page.partials._head')
      @include('landing-page.partials._currencyscripts')

    @yield('after_head')
</head>
<body class="body-bg quick-public-page {{ $themeMode === 'dark' ? 'dark' : '' }} theme-{{ $userRole }} {{ $themeMode }}-theme {{ request()->routeIs('frontend.index') ? 'quick-landing-page' : '' }}">


    <span class="screen-darken"></span>

    <div id="loading">
        @include('landing-page.partials.loading')
    </div>


    <main class="main-content" id="landing-app">
        <div class="position-relative">

            @include('landing-page.partials._header')
        </div>
        @yield('content')
    </main>

    @include('landing-page.partials._footer')

    @include('landing-page.partials.cookie')

    @include('landing-page.partials.back-to-top')



  @yield('before_script')
    @include('landing-page.partials._scripts')
    @yield('after_script')


</body>
</html>
