@extends('landing-page.layouts.default')

@section('title', $pageTitle)

@section('content')
<div class="py-5" style="background-color: #f8f9fa; min-height: 70vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="text-center">
                    <div class="mb-4">
                        <i class="fas fa-store-slash fa-5x text-muted"></i>
                    </div>
                    <h1 class="h2 fw-bold text-dark mb-3">Store Unavailable</h1>
                    <p class="text-muted mb-4">{{ $message }}</p>
                    <a href="{{ url('/') }}" class="btn btn-primary">
                        <i class="fas fa-home"></i> Go Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
