@extends('components.website.candidate.layout.app')

@section('title', 'Visa Processing')

@section('css')
<link rel="stylesheet" href="{{ asset('css/company-visa-case.css') }}?v={{ @filemtime(public_path('css/company-visa-case.css')) ?: '1' }}">
<style>
.cw-visa-page-header {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.15rem 1.25rem;
    margin-bottom: 1.25rem;
}
.cw-visa-page-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 0.35rem;
}
.cw-visa-page-header p {
    margin: 0;
    color: #64748b;
    font-size: 0.9rem;
}
.cw-visa-case-block + .cw-visa-case-block {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e2e8f0;
}
</style>
@endsection

@section('main')
<div class="dashboard-wrapper">
    <div class="container-fluid px-3 py-3">
        <div class="cw-visa-page-header">
            <h1>Visa Processing</h1>
            <p>Track your visa steps, upload documents when it is your turn, and see who is responsible for each stage.</p>
        </div>

        @forelse($cases as $case)
            <div class="cw-visa-case-block">
                @include('frontend.pages.company.visa-processing.partials.case-panel', [
                    'case' => $case,
                    'vpRoutePrefix' => 'candidate.visa-processing',
                    'actorRole' => 'seeker',
                    'caseIdPrefix' => 'candidate-case-'.$case->id,
                    'heroTitle' => $case->country_name,
                    'heroSubtitle' => $case->job?->title,
                    'hideJobMeta' => true,
                    'canDownloadFiles' => false,
                    'cancelledMessage' => 'This case was cancelled: '.$case->cancel_reason.'. Please wait for your employer to start the process again.',
                    'rejectionPrefix' => 'Please fix',
                    'wrapperClass' => 'cw-visa-case-block-inner',
                ])
            </div>
        @empty
            <div class="cw-visa-step">
                <div class="cw-visa-step-body">
                    <h3 class="cw-visa-step-title mb-2">No visa cases yet</h3>
                    <p class="cw-visa-waiting mb-0">When an employer selects you and starts visa processing, your country steps and document uploads will appear here.</p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    document.querySelectorAll('.cw-visa-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-filename-target');
            var target = targetId ? document.getElementById(targetId) : null;
            if (target && input.files && input.files[0]) {
                target.textContent = 'Selected: ' + input.files[0].name;
            }
        });
    });
})();
</script>
@endsection
