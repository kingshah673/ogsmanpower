@extends('frontend.auth.layouts.auth')

@section('title', 'Accept Agent Invitation')

@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background:#f8fafc">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">

                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10"
                             style="width:64px;height:64px;font-size:28px">
                            <i class="ph ph-handshake text-primary"></i>
                        </div>
                    </div>

                    <h4 class="fw-bold mb-1">You're Invited!</h4>
                    <p class="text-muted mb-1">
                        <strong>{{ $agency->name ?? 'An Agency' }}</strong> has invited
                        <strong>{{ $invite->agent_name }}</strong> to join their recruitment team.
                    </p>
                    <p class="text-muted small mb-4">
                        This invitation was sent to <strong>{{ $invite->agent_email }}</strong>.
                        It expires on {{ $invite->expires_at->format('d M Y') }}.
                    </p>

                    <div class="d-grid gap-2">
                        {{-- New user: register with email pre-filled and token stored in session --}}
                        <a href="{{ route('register', ['type' => 'agent', 'invite_token' => $token, 'email' => $invite->agent_email]) }}"
                           class="btn btn-primary rounded-3 fw-semibold"
                           onclick="storeToken()">
                            Create My Agent Account
                        </a>

                        {{-- Existing agent user: log in and the link will happen via session --}}
                        <a href="{{ route('login', ['invite_token' => $token]) }}"
                           class="btn btn-outline-secondary rounded-3 fw-semibold"
                           onclick="storeToken()">
                            I Already Have an Account — Log In
                        </a>
                    </div>

                    <p class="text-muted small mt-4 mb-0">
                        If you weren't expecting this invitation, you can safely ignore this page.
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function storeToken() {
    // Store token in sessionStorage so it survives the redirect to the register/login page
    sessionStorage.setItem('agent_invite_token', '{{ $token }}');
}
</script>

@php
// Also flash to Laravel session so RegisterController can pick it up on POST
session(['agent_invite_token' => $token]);
@endphp
@endsection
