@extends('frontend.auth.layouts.auth')

@section('title', __('login'))

@section('content')
<div class="login-page d-flex flex-column flex-lg-row min-vh-100">

    {{-- LEFT SIDE: VIDEO --}}
    <div class="login-left flex-fill d-none d-lg-flex align-items-center justify-content-center position-relative">

        <div class="video-wrapper w-100 h-100 d-flex align-items-center justify-content-center">

            {{-- IFRAME VIDEO --}}
            

           

            {{-- CENTER CONTENT --}}
            <div class="video-content text-white text-center">
                <h2 class="fw-bold mb-3">Welcome to Career Workforce</h2>
                <p class="mb-4">
                    Your all-in-one platform for global jobs, agencies, and workforce solutions.
                </p>

                <div class="d-flex justify-content-center gap-4 flex-wrap">
                    <div>
                        <h4 class="fw-bold">120+</h4>
                        <small>Live Jobs</small>
                    </div>
                    <div>
                        <h4 class="fw-bold">50+</h4>
                        <small>Companies</small>
                    </div>
                    <div>
                        <h4 class="fw-bold">200+</h4>
                        <small>Candidates</small>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- RIGHT SIDE: LOGIN --}}
    <div class="login-right flex-fill d-flex align-items-center justify-content-center p-4">

        <div class="auth-wrapper w-100 d-flex align-items-center justify-content-center">

            <div class="auth-card glass-card p-5 rounded-4 shadow-lg w-100">

                <h2 class="fw-bold mb-2 text-center">Welcome Back</h2>
                <p class="text-center text-muted mb-4">
                    Login to continue your journey
                </p>

                <form id="dynamicForm" method="POST" action="{{ route('login') }}">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted">Email</label>
                        <input type="email" name="email" id="email"
                            class="form-control form-control-lg input-modern"
                            placeholder="Enter your email"
                            value="{{ old('email') }}">
                        @error('email')<span class="text-danger small">{{ __($message) }}</span>@enderror
                    </div>

                    {{-- Password --}}
                    <div class="mb-3 position-relative">
                        <label class="form-label small text-muted">Password</label>
                        <input type="password" name="password" id="password"
                            class="form-control form-control-lg input-modern"
                            placeholder="Enter your password">

                        <button type="button"
                            class="toggle-password position-absolute end-0 top-50 translate-middle-y me-3"
                            onclick="passToText('password','eyeIcon')">
                            <i id="eyeIcon" class="ph-eye"></i>
                        </button>

                        @error('password')<span class="text-danger small">{{ __($message) }}</span>@enderror
                    </div>

                    {{-- Remember --}}
                    <div class="d-flex justify-content-between mb-4">
                        <div class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input" id="remember">
                            <label class="form-check-label small" for="remember">Remember me</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="small text-primary">
                            Forgot Password?
                        </a>
                    </div>

                    {{-- Button --}}
                    <button type="submit" id="submitButton"
                        class="btn btn-primary w-100 py-3 fw-bold btn-modern"
                        disabled>
                        Sign In
                    </button>

                </form>

                {{-- Divider --}}
                <div class="divider my-4">
                    <span>OR</span>
                </div>

                {{-- Social --}}
                <div class="d-flex gap-2 justify-content-center flex-wrap">
                    <button class="btn btn-outline-dark btn-social">Google</button>
                    <button class="btn btn-outline-dark btn-social">Facebook</button>
                    <button class="btn btn-outline-dark btn-social">LinkedIn</button>
                </div>

                <p class="text-center mt-4 small">
                    Don’t have an account?
                    <a href="{{ route('register') }}" class="fw-bold text-primary">
                        Create Account
                    </a>
                </p>

            </div>
        </div>

    </div>

</div>
@endsection


@section('style')
<style>

.login-page {
    background: linear-gradient(135deg, #f5f7fa, #e4ecf7);
}

/* LEFT SIDE */
.login-left {
    min-height: 100vh;
}

/* VIDEO */
.video-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.video-iframe {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 140%;
    height: 140%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

/* OVERLAY */
.video-overlay {
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.55);
}

/* CONTENT CENTER */
.video-content {
    position: relative;
    z-index: 2;
    max-width: 500px;
    margin: auto;
    text-align: center;
}

/* RIGHT SIDE CENTER */
.login-right {
    min-height: 100vh;
}

.auth-wrapper {
    min-height: 100vh;
}

/* CARD */
.glass-card {
    max-width: 460px;
    margin: auto;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(20px);
    border-radius: 20px;
}

/* INPUT */
.input-modern {
    border-radius: 12px;
    border: 1px solid #e0e0e0;
    padding: 14px;
}

/* BUTTON */
.btn-modern {
    border-radius: 12px;
    background: linear-gradient(135deg, #0d6efd, #0056d2);
    border: none;
}

/* SOCIAL */
.btn-social {
    border-radius: 10px;
    padding: 8px 16px;
}

/* DIVIDER */
.divider {
    text-align: center;
    position: relative;
}
.divider span {
    background: #fff;
    padding: 0 10px;
}
.divider::before {
    content: '';
    position: absolute;
    width: 100%;
    height: 1px;
    background: #ddd;
    top: 50%;
}

/* PASSWORD ICON */
.toggle-password {
    border: none;
    background: none;
    cursor: pointer;
}

</style>
@endsection


@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submitBtn = document.getElementById('submitButton');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const form = document.getElementById('dynamicForm');

    function toggleButton() {
        submitBtn.disabled = !(emailInput.value.trim() && passwordInput.value.trim());
    }

    emailInput.addEventListener('input', toggleButton);
    passwordInput.addEventListener('input', toggleButton);
    toggleButton();

    // Password toggle
    const eyeIcon = document.getElementById('eyeIcon');
    window.passToText = function(id, icon) {
        const input = document.getElementById(id);
        const eye = document.getElementById(icon);
        if(input.type === 'password'){
            input.type = 'text';
            eye.className = 'ph-eye-slash';
        } else {
            input.type = 'password';
            eye.className = 'ph-eye';
        }
    };

    // Role-based login (if you use later)
    document.querySelectorAll('input[name="role"]').forEach(radio => {
        radio.addEventListener('change', () => {
            form.action = radio.value === 'agent' ? "{{ route('admin.login') }}" : "{{ route('login') }}";
        });
    });
});
</script>
@endsection