@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)

@section('content')
<div class="d-flex justify-content-center flex-column-fluid flex-lg-row">
    <div class="d-flex flex-center w-lg-50 p-10">
        <div class="card border rounded-3 w-md-550px">
            <div class="card-body p-10 p-lg-20">
                <form class="form w-100" method="post" action="{{ route('password.reset') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ Request::route('token') }}">

                    <div class="text-center mb-11">
                        <h1 class="text-dark fw-bolder mb-3">Forgot Password</h1>
                        <div class="text-gray-500 fw-semibold fs-6"></div>
                    </div>

                    @include('auth.message')

                    <div class="fv-row mb-8">
                        <input type="email" name="email" placeholder="Enter your email" class="form-control bg-transparent" required value="{{ $reset->email }}">
                        @error('email')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="fv-row mb-8">
                        <input type="password" name="password" placeholder="Enter new password" class="form-control bg-transparent" required>
                        @error('password')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="fv-row mb-8">
                        <input type="password" name="password_confirmation" placeholder="Confirm new password" class="form-control bg-transparent" required>
                        @error('password_confirmation')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div></div>
                        <a href="{{ route('password.forget.form') }}" class="link-primary">Back To Resend Link</a>
                    </div>

                    <div class="d-grid mb-5">
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
