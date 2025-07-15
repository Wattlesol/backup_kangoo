<!-- Horizontal Menu Start -->
<nav id="navbar_main" class="mobile-offcanvas nav navbar navbar-expand-xl hover-nav horizontal-nav py-xl-0">
    <div class="container-fluid p-lg-0">
        <div class="offcanvas-header px-0">
            <div class="navbar-brand ms-3">
                @include('landing-page.components.widgets.logo')
            </div>
            <button class="btn-close float-end px-3"></button>
        </div>
        @php
                $headerSection = App\Models\FrontendSetting::where('key', 'heder-menu-setting')->first();
                $sectionData = $headerSection ? json_decode($headerSection->value, true) : null;
                $settings = App\Models\Setting::whereIn('type', ['service-configurations','OTHER_SETTING'])
                ->whereIn('key', ['service-configurations', 'OTHER_SETTING'])
                ->get()
                ->keyBy('type');

                $serviceconfig = $settings->has('service-configurations') ? json_decode($settings['service-configurations']->value) : null;
                $othersetting = $settings->has('OTHER_SETTING') ? json_decode($settings['OTHER_SETTING']->value) : null;
        @endphp
         @if ($sectionData && isset($sectionData['header_setting']) && $sectionData['header_setting'] == 1)
        <ul class="navbar-nav iq-nav-menu list-unstyled" id="header-menu">
            @if($sectionData['home'] == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('frontend.index') ? 'active' : '' }}" href="{{ route('frontend.index') }}">{{__('landingpage.home')}}</a>
            </li>
            @endif
            @if($sectionData['categories'] == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('category.*') ? 'active' : '' }}" href="{{ route('category.list') }}">{{__('landingpage.categories')}}</a>
            </li>
            @endif
            @if($sectionData['service'] == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('service.*') ? 'active' : '' }}" href="{{ route('service.list') }}">{{__('landingpage.services')}}</a>
            </li>
            @endif
                @if($sectionData['service'] == 1)
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('service.*') ? 'active' : '' }}" href="{{ route('servicepackage.list') }}">{{__('landingpage.servicespackage')}}</a>
                    </li>
                @endif
            @if(optional($othersetting)->blog  == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('blog.*') ? 'active' : '' }}" href="{{ route('blog.list') }}">{{__('landingpage.blogs')}}</a>
            </li>
            @endif
            @if($sectionData['provider'] == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('frontend.provider.*') ? 'active' : '' }}" href="{{ route('frontend.provider') }}">{{__('landingpage.providers')}}</a>
            </li>
            @endif

            <!-- Store Menu with Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('products.*') || request()->routeIs('stores.*') ? 'active' : '' }}"
                   href="#" id="storeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    {{__('landingpage.store')}}
                </a>
                <ul class="dropdown-menu" aria-labelledby="storeDropdown">
                    <li><a class="dropdown-item {{ request()->routeIs('products.category.*') ? 'active' : '' }}" href="{{ route('products.index') }}?view=categories">
                        <i class="fas fa-tags me-2"></i>{{__('landingpage.product_categories')}}
                    </a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('products.index') ? 'active' : '' }}" href="{{ route('products.index') }}">
                        <i class="fas fa-box me-2"></i>{{__('landingpage.products')}}
                    </a></li>
                    <li><a class="dropdown-item {{ request()->routeIs('stores.*') ? 'active' : '' }}" href="{{ route('stores.index') }}">
                        <i class="fas fa-store me-2"></i>{{__('landingpage.stores')}}
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="{{ route('products.search') }}">
                        <i class="fas fa-search me-2"></i>{{__('landingpage.search_products')}}
                    </a></li>
                </ul>
            </li>
            @if(auth()->check() && auth()->user()->user_type == 'user' && $sectionData['bookings'] == 1)
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('booking.*') ? 'active' : '' }}" href="{{ route('booking.list') }}">{{__('landingpage.bookings')}}</a>
                </li>
            @endif
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('service.*') ? 'active' : '' }}" href="{{ route('user.help_support') }}">{{__('landingpage.help_support')}}</a>
                </li>
            {{-- @if(auth()->check() && auth()->user()->user_type == 'user' && optional($serviceconfig)->post_services == 1)
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('post.job.*') ? 'active' : '' }}" href="{{ route('post.job.list') }}">{{__('landingpage.job_request')}}</a>
            </li>
            @endif --}}
        </ul>
    @endif
    </div>
    <!-- container-fluid.// -->
</nav>
<!-- Sidebar Menu End -->
