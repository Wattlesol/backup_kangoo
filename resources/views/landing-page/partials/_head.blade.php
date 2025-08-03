<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="ie=edge">
<title>{{env('APP_NAME')}} Service </title>
<link rel="shortcut icon" class="favicon_preview" href="{{ getSingleMedia(imageSession('get'),'favicon',null) }}" />
<link rel="stylesheet" href="{{ asset('css/swiper-bundle.min.css') }}"/>
<link rel="stylesheet" href="{{ asset('css/landing-page.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/landing-page-rtl.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/landing-page-custom.min.css') }}">
<link rel="stylesheet" href="{{ asset('css/store-dark-mode.css?v=' . time()) }}">
<link rel="stylesheet" href="{{ asset('vendor/@fortawesome/fontawesome-free/css/all.min.css')}}">

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





