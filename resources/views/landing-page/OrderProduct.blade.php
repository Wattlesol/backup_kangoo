@extends('landing-page.layouts.default')

@section('content')

<div class="section-padding">
    <div class="container">
    <order-wizard  :product="{{ json_encode($product) }}" :coupons="{{ json_encode($coupons) }}" :taxes="{{ json_encode($taxes) }}" :user_id="{{$user_id}}" :googlemapkey="'{{$googlemapkey}}'" :wallet_amount="{{$wallet_amount}}"></order-wizard>
    </div>
</div>

@endsection
