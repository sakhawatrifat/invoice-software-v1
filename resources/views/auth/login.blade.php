@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)

@section('content')
<div class="d-flex justify-content-center flex-column-fluid flex-lg-row">
    <div class="d-flex flex-center w-lg-50 p-10">
        <div class="card border rounded-3 w-md-550px">
            <div class="card-body p-10 p-lg-20">
                <form class="form w-100" method="post" action="{{ route('login.confirm') }}">
                	@csrf
                    <div class="text-center mb-11">
                        <h1 class="text-dark fw-bolder mb-3">Login</h1>
                        <div class="text-gray-500 fw-semibold fs-6"></div>
                    </div>
                    @include('auth.message')
                    <div class="fv-row mb-8">
                        <input type="text" placeholder="Email" name="email" autocomplete="off" class="form-control bg-transparent" required/>
                        @error('email')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="fv-row mb-3">
                        <input type="password" placeholder="Password" name="password" autocomplete="off" class="form-control bg-transparent" required/>
                        @error('password')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div class="form-check d-flex pl-2">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label ps-1 cursor-pointer usn" for="remember">Remember Me</label>
                        </div>
                        <a href="{{ route('password.forget.form') }}" class="link-primary">Forgot Password ?</a>
                    </div>
                    <div class="d-grid mb-10">
                        <button type="submit" class="btn btn-primary">
                            Login
                        </button>
                    </div>
                    
                    @if(homepageData()->is_registration_enabled == 1)
                        <div class="text-gray-500 text-center fw-semibold fs-6">
                            Not a member yet?
                            <a href="{{ route('register') }}" class="link-primary">Create Account</a>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
@endsection