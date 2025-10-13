@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)
@section('content')
<div class="d-flex justify-content-center flex-column-fluid flex-lg-row">
    <div class="d-flex flex-center w-lg-50 p-10">
        <div class="card border rounded-3 w-md-550px">
            <div class="card-body p-10 p-lg-20">
                <form class="form w-100" method="post" action="{{ route('password.forget') }}">
                    @csrf

                    @php
                        $timeRemainingInSeconds = 0;
                        $minutes = 0;
                        $seconds = 0;

                        if(Session::has('reset_token_enable_at')) {
                            $createdAt = Carbon\Carbon::parse(Session::get('reset_token_enable_at'));
                            $now = Carbon\Carbon::now();

                            if ($now->lessThan($createdAt)) {
                                $timeRemainingInSeconds = max($now->diffInSeconds($createdAt, false), 0);
                            }

                            $minutes = floor($timeRemainingInSeconds / 60);
                            $seconds = $timeRemainingInSeconds % 60;
                        }
                    @endphp

                    <div class="text-center mb-11">
                        <h1 class="text-dark fw-bolder mb-3">Forgot password</h1>
                        <div class="text-gray-500 fw-semibold fs-6"></div>
                    </div>

                    @include('auth.message')

                    <div class="fv-row mb-8">
                        <input type="email" placeholder="Enter your email" name="email" autocomplete="off" class="form-control bg-transparent" required />
                        @error('email')
                            <span class="text-danger text-sm text-red text-bold">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <div></div>
                        <a href="{{ route('login') }}" class="link-primary">Back To Login</a>
                    </div>

                    <div class="d-grid mb-5">
                        <button type="submit" class="btn btn-primary" id="resetPasswordBtn" {{ $timeRemainingInSeconds > 0 ? 'disabled' : '' }}>
                            Send Password Reset Link
                        </button>
                    </div>

                    <div class="text-center text-gray-500 fw-semibold fs-6" id="timeRemaining" style="display: {{ $timeRemainingInSeconds > 0 ? 'block' : 'none' }}">
                        Try Again After: <span id="minutes">{{ intval($minutes) }}</span>:<span id="seconds">{{ intval($seconds) }}</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    var countdownSeconds = {{ $timeRemainingInSeconds }};
    var countdownInterval;

    function startCountdown() {
        countdownInterval = setInterval(function() {
            if (countdownSeconds <= 0) {
                clearInterval(countdownInterval);
                document.getElementById("resetPasswordBtn").disabled = false;
                document.getElementById("timeRemaining").style.display = "none";
                document.getElementById("minutes").textContent = "0";
                document.getElementById("seconds").textContent = "0";
            } else {
                var minutes = Math.floor(countdownSeconds / 60);
                var seconds = countdownSeconds % 60;
                document.getElementById("minutes").textContent = minutes.toFixed(0);
                document.getElementById("seconds").textContent = seconds.toFixed(0);
                countdownSeconds--;
            }
        }, 1000);
    }

    if (document.getElementById("resetPasswordBtn") && countdownSeconds > 0) {
        document.getElementById("resetPasswordBtn").disabled = true;
        startCountdown();
    }
</script>
@endsection
