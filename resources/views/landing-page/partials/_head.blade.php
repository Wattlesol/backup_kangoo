<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>{{ config('sanad.brand.name', 'Quick') }} | {{ config('sanad.brand.tagline_ar') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="shortcut icon" class="favicon_preview" href="{{ getSingleMedia(imageSession('get'),'favicon',null) }}" />
<link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/landing-page.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/landing-page-rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/landing-page-custom.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/custom.css?v=' . time()) }}">
<link rel="stylesheet" href="{{ asset('css/store-dark-mode.css?v=' . time()) }}">
<link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css')}}">
@if(in_array(app()->getLocale(), ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa']))
<style>
    html[dir="rtl"] body,
    html[dir="rtl"] .main-content {
        direction: rtl;
        text-align: right;
    }

    html[dir="rtl"] .landing-header {
        direction: rtl;
        flex-direction: row-reverse;
    }

    html[dir="rtl"] .landing-header > .d-flex:first-child {
        flex-direction: row-reverse;
    }

    html[dir="rtl"] .landing-header .right-panel {
        margin-right: auto;
        margin-left: 0;
    }

    html[dir="rtl"] .iq-nav-menu {
        margin-right: 0;
        margin-left: auto;
        padding-right: 0;
        text-align: right;
    }

    html[dir="rtl"] .horizontal-nav .navbar-nav {
        align-items: flex-start;
    }

    html[dir="rtl"] .top-header-left {
        justify-content: flex-end;
    }

    html[dir="rtl"] .top-header .text-md-end {
        text-align: left !important;
    }

    html[dir="rtl"] .dropdown-menu-end {
        right: auto;
        left: 0;
        text-align: right;
    }

    html[dir="rtl"] .iq-title-box,
    html[dir="rtl"] .iq-title-desc,
    html[dir="rtl"] .categories-desc,
    html[dir="rtl"] .card-text {
        text-align: right;
    }

    html[dir="rtl"] .center,
    html[dir="rtl"] .text-center {
        text-align: center !important;
    }

    html[dir="rtl"] .search-form .form-control {
        text-align: right;
    }

    html[dir="rtl"] .search-form .search-icon {
        right: auto;
        left: 1rem;
    }

    html[dir="rtl"] .input-group > .form-control {
        border-radius: var(--bs-border-radius) !important;
    }

    html[dir="rtl"] .mobile-offcanvas {
        right: 0;
        left: auto;
    }

    html[dir="rtl"] .btn-close {
        margin-right: auto;
        margin-left: 0;
    }
</style>
@endif

<!-- Dynamic Theme CSS Integration -->
@php
    $userRole = auth()->check() ? auth()->user()->user_type : 'customer';
    // Check for theme mode from various sources
    $themeMode = 'light'; // default
    if (request()->cookie('data-bs-theme')) {
        $themeMode = request()->cookie('data-bs-theme');
    } elseif (request()->cookie('theme_mode')) {
        $themeMode = request()->cookie('theme_mode');
    } elseif (session('theme_mode')) {
        $themeMode = session('theme_mode');
    }
@endphp
<link rel="stylesheet" href="{{ route('theme.css', ['role' => $userRole, 'theme' => $themeMode]) }}?v={{ time() }}" id="dynamic-theme-css">
<link rel="stylesheet" href="{{ route('landing.theme.css', ['theme' => $themeMode]) }}?v={{ time() }}" id="dynamic-landing-css">

<!-- Theme Configuration Script -->
<script>
window.themeConfig = {
    role: '{{ $userRole }}',
    mode: '{{ $themeMode }}',
    version: '{{ time() }}'
};
window.currentLocale = '{{ app()->getLocale() }}';
</script>

<meta name="csrf-token" content="{{ csrf_token() }}">

<meta name="assert_url" content="{{ URL::to('') }}" />

<meta name="baseUrl" content="{{env('APP_URL')}}" />
@php
        $currentLang = app()->getLocale();
        $langFolderPath = resource_path("lang/$currentLang");
        $filePaths = \File::files($langFolderPath);
    @endphp

    @foreach ($filePaths as $filePath)
        @php
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        @endphp
        <script>
            window.localMessagesUpdate = {
                ...window.localMessagesUpdate,
                "{{ $fileName }}": @json(require($filePath))
            };
        </script>
    @endforeach
