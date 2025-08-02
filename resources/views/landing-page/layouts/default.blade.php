<!DOCTYPE html>
@php
    // Determine theme mode from various sources
    $themeMode = 'light'; // default
    if (request()->cookie('data-bs-theme')) {
        $themeMode = request()->cookie('data-bs-theme');
    } elseif (request()->cookie('theme_mode')) {
        $themeMode = request()->cookie('theme_mode');
    } elseif (session('theme_mode')) {
        $themeMode = session('theme_mode');
    }

    $userRole = auth()->check() ? auth()->user()->user_type : 'customer';
@endphp
<html lang="en" data-bs-theme="{{ $themeMode }}">
<head>
    @yield('before_head')
    @include('landing-page.partials._head')
      @include('landing-page.partials._currencyscripts')

    @yield('after_head')
</head>
<body class="body-bg {{ $themeMode === 'dark' ? 'dark' : '' }} theme-{{ $userRole }} {{ $themeMode }}-theme">


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
