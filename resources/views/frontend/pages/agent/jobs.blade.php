{{-- @extends('frontend.layouts.app') --}}
@extends('components.website.agent.new-sidebar')

@section('title')
    {{ __('settings') }}
@endsection
@php
    $isAgencyJob = !empty($job->agency_id);
@endphp
@section('main')

<div class="container py-4">

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">Available Jobs</h3>
        <span class="badge bg-primary">
            Total: {{ $jobs->total() }}
        </span>
    </div>

    {{-- JOB LIST --}}
    <div class="row">

        @forelse($jobs as $job)

        <div class="col-md-6 mb-4">

            <div class="card shadow-sm border-0 h-100">
                <!-- jobs display -->

                <div class="card mb-3">
    <div class="card-body">

        {{-- TITLE --}}
        <h5 class="fw-bold text-dark mb-1">
            {{ $job->title ?? '-' }}
        </h5>

        <p class="text-muted mb-2 d-flex align-items-center">

    @php
        $isAgencyJob = !is_null($job->agency_id) && $job->agency_id != 0;
    @endphp

    {{-- LOGO --}}
    <img 
        src="{{ 
            $isAgencyJob 
                ? ($job->agency && $job->agency->logo 
                    ? asset($job->agency->logo) 
                    : asset('images/company.png')) 
                : ($job->company && $job->company->logo 
                    ? asset($job->company->logo) 
                    : asset('images/company.png')) 
        }}"
        class="company-logo me-2"
        style="width:20px;height:20px;object-fit:cover;"
    >

    {{-- NAME --}}
    {{ 
        $isAgencyJob 
            ? optional(optional($job->agency)->user)->name 
            : optional(optional($job->company)->user)->name 
    }}

    {{-- BADGE --}}
    @if($isAgencyJob)
        <span class="badge bg-info ms-2">Agency Job</span>
    @else
        <span class="badge bg-secondary ms-2">Company Job</span>
    @endif

</p>

        {{-- DETAILS --}}
        <div class="mb-2">

            <span class="badge bg-light text-dark me-2">
                💰 {{ $job->min_salary ?? 0 }} - {{ $job->max_salary ?? 0 }}
            </span>

            <span class="badge bg-light text-dark me-2">
                📍 {{ $job->country ?? 'N/A' }}
            </span>

            <span class="badge bg-light text-dark">
                🕒 {{ \Carbon\Carbon::parse($job->created_at)->diffForHumans() }}
            </span>

        </div>

        {{-- STATUS --}}
        <div class="mb-3">
            @if($job->status == 'active')
                <span class="badge bg-success">Active</span>
            @elseif($job->status == 'pending')
                <span class="badge bg-warning text-dark">Pending</span>
            @else
                <span class="badge bg-danger">Expired</span>
            @endif
        </div>

        {{-- ACTIONS --}}
        <div>

            <a href="{{ $job->slug ? route('website.job.details',$job->slug) : '#' }}" 
               class="btn btn-sm btn-outline-primary mb-2 w-100">
                View Details
            </a>

            {{-- APPLY FORM --}}
            <form method="POST" action="{{ route('agent.apply.candidate') }}">
                @csrf

                <input type="hidden" name="job_id" value="{{ $job->id }}">

                <select name="candidate_id" class="form-control mb-2" required>
                    <option value="">Select Candidate</option>

                    @foreach($candidates as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->first_name }} {{ $c->last_name }}
                        </option>
                    @endforeach
                </select>

                <button class="btn btn-success btn-sm w-100">
                    Apply Candidate
                </button>
            </form>

        </div>

    </div>
</div>
                <!-- agency jobs---->
                
                <!---end agency jobs ---->

            </div>

        </div>


        @empty

        <div class="col-12">
            <div class="alert alert-info text-center">
                No jobs available at the moment
            </div>
        </div>

        @endforelse

    </div>

    {{-- PAGINATION --}}
    <div class="mt-4 d-flex justify-content-center">
        {{ $jobs->links() }}
    </div>

</div>

@endsection