
 @if(request()->route()->getName() != "service.detail")
<script src="{{ asset('js/landing-app.min.js') }}"></script>
 @endif
 <script src="{{ asset('js/bootstrap.bundle.js')}}"></script>
 <script src="{{ asset('js/backend-bundle.min/resources_js_handyman_js.js')}}"></script>

<script src="{{ asset('js/app.js') }}"></script>

@yield('bottom_script')
