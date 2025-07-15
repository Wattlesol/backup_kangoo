@extends('landing-page.layouts.default')


@section('content')





<div class="section-padding bg-light our-service">
    <div class="container">
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="iq-title-box mb-0">
                    <h3 class="text-capitalize line-count-1">Packagee List
                        <div class="highlighted-text">
                            <span class="highlighted-text-swipe"></span>
                            <span class="highlighted-image">
                            <svg xmlns="http://www.w3.org/2000/svg" width="155" height="12" viewBox="0 0 155 12"
                                 fill="none">
                                <path d="M2.5 9.5C3.16964 9.26081 78.8393 -2.45948 152.5 4.9554" stroke="currentColor"
                                      stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        </div>
                    </h3>
                </div>

            </div>

        </div>
        <div class="row">

            @forelse($servicePack as $vaule)
                <div class="col-lg-3 col-md-6 col-sm-6">
                    <div class="card text-center iq-rmb-30">
                        <div class="card-body">
                            <h5 class="card-title">{{ $vaule->name }}</h5>
                            <p class="card-text">{{ $vaule->description }}</p>
                            <a href="{{ route('service-package.detail',$vaule->id) }}" class="btn btn-primary">Buy Now</a>
                        </div>
                    </div>
                </div>
            @empty
            @endforelse
        </div>
    </div>
</div>

@endsection
