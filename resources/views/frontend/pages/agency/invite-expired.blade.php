@extends('frontend.auth.layouts.auth')
@section('title', 'Invitation Expired')
@section('content')
<div class="min-vh-100 d-flex align-items-center" style="background:#f8fafc">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 text-center">
                <div class="card border-0 shadow-sm rounded-4 p-5">
                    <div class="mb-3" style="font-size:52px">⏰</div>
                    <h4 class="fw-bold mb-2">Invitation Expired</h4>
                    <p class="text-muted mb-4">
                        This invitation link has expired or is no longer valid.
                        Please ask the agency to send a new invitation.
                    </p>
                    <a href="{{ route('website.home') }}" class="btn btn-primary rounded-3">Go to Homepage</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
