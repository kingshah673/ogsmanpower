@extends('components.website.agency.layout.app')

@section('title', __('Application Details'))

@section('main')
<div class="container my-5">

    <!-- ===== Candidate Header (Professional CV Style) ===== -->
    <div class="cv-header card shadow-sm p-4 mb-5 d-flex flex-wrap align-items-start justify-content-between">
        <!-- Photo + Info + Actions in flex -->
        <div class="d-flex align-items-start w-100 gap-4">

            <!-- Candidate Photo -->
            <div class="position-relative">
                <img src="{{ $candidate->photo }}" class="rounded-circle border border-3 border-primary" width="100" height="100" alt="candidate_photo">
            </div>

            <!-- Candidate Info -->
            <div class="flex-grow-1">
                <h1 class="fw-bold">{{ $candidate->user->name }}</h1>
                <p class="text-muted mb-1">{{ $candidate->profession ? $candidate->profession->name : '-' }}</p>
                <p class="text-secondary small">{{ $candidate->exact_location ?? $candidate->full_address }}</p>

                <div class="mt-2 d-flex gap-2 flex-wrap">
                    <a href="{{ route('agency.download.applicant.resume', ['candidate_id' => $candidate->id, 'job_id' => $candiateJob->job_id]) }}" class="btn btn-primary btn-sm">
                        <x-svg.download-icon class="me-1"/> {{ __('Download CV') }}
                    </a>
                    @if ($user->contactInfo?->phone)
                        <a href="tel:{{ $user->contactInfo->phone }}" class="btn btn-outline-secondary btn-sm">
                            <x-svg.details-phone-call class="me-1"/> {{ __('Call') }}
                        </a>
                    @endif
                    @if ($user->contactInfo?->email)
                        <a href="mailto:{{ $user->contactInfo->email }}" class="btn btn-outline-secondary btn-sm">
                            <x-svg.details-envelop class="me-1"/> {{ __('Email') }}
                        </a>
                    @endif
                   <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#forwardCandidateModal">
                   📤 Forward CV to Email
                    </button>
                     
                </div>

                <!-- Social Media -->
                @if ($user->socialInfo && $candidate->user->socialInfo->count() > 0)
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        @foreach ($user->socialInfo as $contact)
                        <a target="_blank" href="{{ $contact->url }}" class="social-icon">
                            @switch($contact->social_media)
                                @case('facebook') <x-svg.facebook-icon /> @break
                                @case('twitter') <x-svg.twitter-icon /> @break
                                @case('instagram') <x-svg.instagram-icon /> @break
                                @case('youtube') <x-svg.youtube-icon /> @break
                                @case('linkedin') <x-svg.linkedin-icon /> @break
                                @case('pinterest') <x-svg.pinterest-icon /> @break
                                @case('reddit') <x-svg.reddit-icon /> @break
                                @case('github') <x-svg.github-icon /> @break
                                @default <x-svg.link-icon /> 
                            @endswitch
                        </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="d-flex flex-column gap-2">
                <button class="btn btn-success btn-sm">✅ {{ __('Shortlist') }}</button>
                <button class="btn btn-danger btn-sm">❌ {{ __('Reject') }}</button>
                <button class="btn btn-info btn-sm">📅 {{ __('Interview') }}</button>
                <button class="btn btn-secondary btn-sm">📝 {{ __('Notes') }}</button>
            </div>

        </div>
    </div>

    <!-- ===== About & Summary ===== -->
    <div class="card shadow-sm p-3 mb-4 border-start border-4 border-success">
        <h4 class="text-success mb-2">{{ __('Professional Summary') }}</h4>
        <p>{!! nl2br($candidate->bio) !!}</p>
    </div>

    <div class="row g-4">
        <!-- Left Column: Personal Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm p-3 border-start border-4 border-primary">
                <h5 class="text-primary mb-3">{{ __('Personal Details') }}</h5>
                <ul class="list-unstyled small">
                    <li><strong>{{ __('Experience') }}:</strong> {{ $candidate->experience?->name ?? '-' }}</li>
                    <li><strong>{{ __('Education') }}:</strong> {{ $candidate->education?->name ?? '-' }}</li>
                    <li><strong>{{ __('Marital Status') }}:</strong> {{ __($candidate->marital_status) }}</li>
                    <li><strong>{{ __('Gender') }}:</strong> {{ ucfirst($candidate->gender) }}</li>
                    <li><strong>{{ __('Birth Date') }}:</strong> {{ date('d M Y', strtotime($candidate->birth_date)) }}</li>
                </ul>
            </div>
        </div>

        <!-- Right Column: Skills, Languages, Contact, Location -->
        <div class="col-lg-8">
            <!-- Skills & Languages -->
            <div class="card shadow-sm p-3 mb-4 border-start border-4 border-warning">
                <h5 class="text-warning mb-2">{{ __('Skills') }}</h5>
                <div class="mb-3">
                    @foreach ($candidate->skills as $skill)
                        <span class="badge bg-info text-dark me-1 mb-1">{{ $skill->name }}</span>
                    @endforeach
                </div>

                <h5 class="text-warning mb-2">{{ __('Languages') }}</h5>
                <div>
                    @foreach ($candidate->languages as $language)
                        <span class="badge bg-secondary me-1 mb-1">{{ $language->name }}</span>
                    @endforeach
                </div>
            </div>

            <!-- Contact & Website -->
            <div class="card shadow-sm p-3 mb-4 border-start border-4 border-info">
                <h5 class="text-info mb-2">{{ __('Contact Information') }}</h5>
                <ul class="list-unstyled small">
                    @if ($candidate->website)
                        <li><strong>{{ __('Website') }}:</strong> <a href="{{ $candidate->website }}" target="_blank">{{ $candidate->website }}</a></li>
                    @endif
                    @if ($user->contactInfo?->phone)
                        <li><strong>{{ __('Phone') }}:</strong> {{ $user->contactInfo->phone }}</li>
                    @endif
                    @if ($user->contactInfo?->email)
                        <li><strong>{{ __('Email') }}:</strong> {{ $user->contactInfo->email }}</li>
                    @endif
                </ul>
            </div>

            <!-- Location Map -->
            <div class="card shadow-sm p-3 border-start border-4 border-danger">
                <h5 class="text-danger mb-2">{{ __('Location') }}</h5>
                <p>{{ $candidate->exact_location ?? $candidate->full_address }}</p>
                <div id="leaflet-map" style="height: 260px;"></div>
            </div>
        </div>
    </div>
</div>
<!-- Forward Email Modal -->
<!-- Forward Candidate Modal -->
<div class="modal fade" id="forwardCandidateModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">

<form action="{{ route('agency.forward.candidate.email') }}" method="POST">
@csrf

<input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
<input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">

<div class="modal-header">
<h5 class="modal-title">Send Candidate to Client</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="mb-3">
<label class="form-label">Client Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<label><strong>Select Documents to Send</strong></label>

<div class="form-check">
<input class="form-check-input" type="checkbox" name="docs[]" value="cv" checked>
<label class="form-check-label">Candidate CV (PDF)</label>
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox" name="docs[]" value="photo">
<label class="form-check-label">Profile Picture</label>
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox" name="docs[]" value="passport">
<label class="form-check-label">Passport</label>
</div>

<div class="form-check">
<input class="form-check-input" type="checkbox" name="docs[]" value="video">
<label class="form-check-label">Video Introduction</label>
</div>

<div class="mt-3">
<label>Message (optional)</label>
<textarea name="message" class="form-control"></textarea>
</div>

</div>

<div class="modal-footer">
<button class="btn btn-primary">Send Candidate</button>
</div>

</form>

</div>
</div>
</div>
@endsection

@section('style')
<style>
    .badge { font-size: 0.85rem; padding: 0.5em 0.7em; transition: transform 0.2s; }
    .badge:hover { transform: scale(1.05); }
    .social-icon svg { width: 22px; height: 22px; transition: transform 0.2s; }
    .social-icon:hover svg { transform: scale(1.3); }
    .card { border-radius: 12px; }
    .cv-header h1 { font-size: 1.9rem; }
    .cv-header .btn-sm { font-size: 0.75rem; padding: 0.3rem 0.5rem; }
</style>
<script>
document.getElementById('forwardCandidateModal').addEventListener('shown.bs.modal', function () {
    document.querySelector('#forwardCandidateModal input[name="email"]').focus();
});
</script>
@endsection
