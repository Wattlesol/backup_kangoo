@extends('landing-page.layouts.default')


@section('content')
    <style>
        body {
            background-color: #f8f9fa !important;;
        }
        .contact-form {
            background-color: #343a40 !important;;
            padding: 30px;
            border-radius: 8px;
            color: white;
        }
        .contact-form .form-control {
            background-color: #495057 !important;;
            color: #fff;
            border: 1px solid #6c757d !important;;
        }
        .contact-form .form-control:focus {
            background-color: #495057 !important;;
            color: #fff !important;;
            border-color: #80bdff !important;;
            box-shadow: none;
        }
        .btn-primary {
            background-color: #007bff !important;
            border-color: #007bff !important;;
        }
    </style>
<div class="my-5">
   <h4 class="text-center text-capitalize font-weight-bold my-5">{{__('landingpage.help_support')}}</h4>
   <div class="container">
      {!! $help_support->value !!}
   </div>
    <div class="container d-flex justify-content-center align-items-center vh-100" style="background-color: black !important">
        <div class="contact-form w-50"  style=" background-color: #495057 !important;">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form action="{{ route('contact.submit') }}" method="POST" >
                @csrf
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" required>
                    @error('email')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="4" required>{{ old('message') }}</textarea>
                    @error('message')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-block">Send Message</button>
            </form>
        </div>
    </div>
 </div>


@endsection
