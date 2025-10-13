@php
    $layout = 'frontend.layouts.website';
@endphp

@extends($layout)
@section('content')
<div class="d-flex justify-content-center flex-column-fluid flex-lg-row">
    <div class="d-flex flex-center w-lg-50 p-10">
        <div class="card rounded-3 w-md-550px">
            <div class="card-body p-10 p-lg-20">
                <div class="text-center mb-11">
                    <h1 class="text-dark fw-bolder mb-3">Account Activation</h1>
                    <div class="text-gray-500 fw-semibold fs-6"></div>
                </div>

                @include('auth.message')
                @php
                    $timeRemainingInSeconds = 0;
                    $minutes = 0;
                    $seconds = 0;

                    if(Session::has('verify_token_enable_at')) {
                        $createdAt = Carbon\Carbon::parse(Session::get('verify_token_enable_at'));
                        $now = Carbon\Carbon::now();

                        if ($now->lessThan($createdAt)) {
                            $timeRemainingInSeconds = max($now->diffInSeconds($createdAt, false), 0);
                        }

                        $minutes = floor($timeRemainingInSeconds / 60);
                        $seconds = $timeRemainingInSeconds % 60;
                    }
                @endphp

                <div class="d-grid mb-5">
                    <style>
                        .disabled-button {
                            pointer-events: none;
                            opacity: 0.6;
                            cursor: not-allowed;
                        }
                    </style>
                    <a href="{{ route('account.verify.resend') }}"
                    class="btn btn-primary {{ $timeRemainingInSeconds > 0 ? 'disabled-button' : '' }}"
                    id="resetPasswordBtn">
                        Resend Verification Link
                    </a>
                </div>

                <div class="text-center text-gray-500 fw-semibold fs-6" id="timeRemaining" style="display: {{ $timeRemainingInSeconds > 0 ? 'block' : 'none' }}">
                    Try Again After: <span id="minutes">{{ intval($minutes) }}</span>:<span id="seconds">{{ intval($seconds) }}</span>
                </div>

                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                    <div></div>
                    <a class="link-primary" onclick="document.getElementById('userLogoutForm').submit()">Logout</a>

                    {{-- Logout form --}}
                    <form id="userLogoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
                        @csrf
                        <button type="submit">Logout</button>
                    </form> 
                </div>
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

                var btn = document.getElementById("resetPasswordBtn");
                btn.classList.remove("disabled-link"); // remove CSS disabling
                btn.style.pointerEvents = "auto";
                btn.style.opacity = "1";

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
        var btn = document.getElementById("resetPasswordBtn");
        btn.classList.add("disabled-link");
        btn.style.pointerEvents = "none";
        btn.style.opacity = "0.6";

        startCountdown();
    }

</script>
@endsection
