@extends('components.website.agency.layout.app')

@section('title', __('Reports'))

@section('main')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Reports &amp; Analytics</h4>
        @if(Route::has('agency.ai.summary'))
            <a href="{{ route('agency.ai.summary') }}" class="btn btn-sm btn-outline-primary">
                <i class="ph-sparkle"></i> AI Performance Summary
            </a>
        @endif
    </div>

    <div class="row g-3">
        @foreach($types as $key => $label)
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-semibold mb-2">{{ $label }}</h6>
                        <p class="text-muted small flex-grow-1">
                            @switch($key)
                                @case('recruitment-status')
                                    Breakdown of all applications by their current recruitment status.
                                    @break
                                @case('job-posting')
                                    Views, applicants and lifecycle for every job you have posted.
                                    @break
                                @case('applicant-tracking')
                                    Funnel view of candidates moving from applied to selected.
                                    @break
                                @case('visa-medical')
                                    Visa case and Protector clearance status distribution.
                                    @break
                                @case('payment-commission')
                                    Commission ledger with amounts, currency and payment status.
                                    @break
                            @endswitch
                        </p>
                        <a href="{{ route('agency.reports.show', $key) }}" class="btn btn-sm btn-primary mt-auto">
                            View Report
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
