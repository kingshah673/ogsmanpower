@extends('frontend.auth.layouts.auth')
@section('title', 'Invitation Already Accepted')
@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background:#f8fafc">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <div class="mb-3" style="font-size:52px">✅</div>
                    <h4 class="fw-bold mb-2">Invitation Already Accepted</h4>
                    <p class="text-muted mb-4">
                        This invitation has already been accepted. You can log in to your agent account.
                    </p>
                    <a href="{{ route('login') }}" class="btn btn-primary rounded-3">Log In</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
