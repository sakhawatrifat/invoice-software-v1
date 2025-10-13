@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)

@section('content')
<div class="d-flex justify-content-center flex-column-fluid flex-lg-row">
    <div class="d-flex flex-center w-lg-50 p-10">
        <div class="card border rounded-3 w-md-550px">
            <div class="card-body p-10 p-lg-20">
                <form class="form w-100" method="post" action="{{ route('register.confirm') }}">
                    @csrf

                    <div class="text-center mb-11">
                        <h1 class="text-dark fw-bolder mb-3">Register</h1>
                        <div class="text-gray-500 fw-semibold fs-6"></div>
                    </div>

                    @include('auth.message')

                    <div class="fv-row mb-8">
                        <input type="text" name="company_name" placeholder="Company Name" class="form-control bg-transparent" required value="{{ old('company_name') }}">
                        @error('company_name')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="fv-row mb-8">
                        <input type="text" name="name" placeholder="User Full Name" class="form-control bg-transparent" required value="{{ old('name') }}">
                        @error('name')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- <div class="fv-row mb-8">
                        <input type="text" name="phone" placeholder="Phone" class="form-control bg-transparent" required value="{{ old('phone') }}" autocomplete="new-phone">
                        @error('phone')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div> --}}

                    <div class="fv-row mb-8">
                        <input type="email" name="email" placeholder="Email" class="form-control bg-transparent" required value="{{ old('email') }}" autocomplete="new-email">
                        @error('email')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="fv-row mb-8">
                        <div class="position-relative">
                            <input type="password" name="password" placeholder="Password" class="form-control bg-transparent password" required autocomplete="new-password">
                            <span class="toggle-password" minlength="8" data-show="f06e" data-hide="f070"></span>
                        </div>
                        @error('password')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="fv-row mb-8">
                        <div class="position-relative">
                            <input type="password" name="password_confirmation" placeholder="Confirm Password" class="form-control bg-transparent password" minlength="8" required>
                            <span class="toggle-password" data-show="f06e" data-hide="f070"></span>
                        </div>
                        @error('password_confirmation')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-grid mb-10">
                        <button type="submit" class="btn btn-primary">Register</button>
                    </div>

                    {{-- <div class="d-flex justify-content-end flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8"> --}}
                    <div class="text-gray-500 text-center fw-semibold fs-6">
                        Already have account?
                        <a href="{{ route('login') }}" class="link-primary">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
