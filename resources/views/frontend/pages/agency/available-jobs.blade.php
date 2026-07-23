@extends('components.website.agency.layout.app')

@section('title', __('Availiable_jobs'))

@section('main')

<div class="container py-4">

    {{-- PAGE HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <h3 class="fw-bold">
            Available Jobs
        </h3>

        <span class="badge bg-primary">
            Total: {{ $jobs->total() }}
        </span>

    </div>

    {{-- JOB LIST --}}
    <div class="row">

        @forelse($jobs as $job)

            <div class="col-md-6 mb-4">

                <div class="card shadow-sm border-0 h-100 rounded-4">

                    <div class="card-body">

                        {{-- TITLE --}}
                        <h5 class="fw-bold text-dark mb-2">

                            {{ $job->title ?? '-' }}

                        </h5>

                        {{-- COMPANY / AGENCY --}}
                        <p class="text-muted mb-3 d-flex align-items-center">

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
                                class="me-2 rounded-circle"
                                style="width:24px;height:24px;object-fit:cover;"
                            >

                            {{-- NAME --}}
                            {{
                                $isAgencyJob
                                    ? optional(optional($job->agency)->user)->name
                                    : optional(optional($job->company)->user)->name
                            }}

                            {{-- BADGE --}}
                            @if($isAgencyJob)

                                <span class="badge bg-info ms-2">

                                    Agency Job

                                </span>

                            @else

                                <span class="badge bg-secondary ms-2">

                                    Company Job

                                </span>

                            @endif

                        </p>

                        {{-- DETAILS --}}
                        <div class="mb-3">

                            <span class="badge bg-light text-dark me-2 mb-2">

                                💰 {{ $job->min_salary ?? 0 }}
                                -
                                {{ $job->max_salary ?? 0 }}

                            </span>

                            <span class="badge bg-light text-dark me-2 mb-2">

                                📍 {{ $job->country ?? 'N/A' }}

                            </span>

                            <span class="badge bg-light text-dark mb-2">

                                🕒
                                {{ \Carbon\Carbon::parse($job->created_at)->diffForHumans() }}

                            </span>

                        </div>

                        {{-- STATUS --}}
                        <div class="mb-3">

                            @if($job->status == 'active')

                                <span class="badge bg-success">
                                    Active
                                </span>

                            @elseif($job->status == 'pending')

                                <span class="badge bg-warning text-dark">
                                    Pending
                                </span>

                            @else

                                <span class="badge bg-danger">
                                    Expired
                                </span>

                            @endif

                            {{-- ASSIGNMENT RESPONSE STATUS --}}
                            @php($assignmentStatus = $job->pivot->status ?? null)
                            @if($assignmentStatus === 'accepted')
                                <span class="badge bg-success">Accepted</span>
                            @elseif($assignmentStatus === 'declined')
                                <span class="badge bg-danger">Declined</span>
                            @elseif($assignmentStatus === 'pending')
                                <span class="badge bg-warning text-dark">Awaiting Your Response</span>
                            @endif

                        </div>

                        {{-- ACCEPT / DECLINE ASSIGNMENT --}}
                        @if(($job->pivot->status ?? null) === 'pending')
                            <div class="d-flex gap-2 mb-2">
                                <form method="POST" action="{{ route('agency.job.respond', $job->id) }}" class="flex-fill">
                                    @csrf
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="btn btn-success btn-sm w-100">✔ Accept</button>
                                </form>
                                <button type="button" class="btn btn-outline-danger btn-sm flex-fill"
                                        data-bs-toggle="modal" data-bs-target="#declineJobModal{{ $job->id }}">
                                    ✖ Decline
                                </button>
                            </div>
                        @endif

                        {{-- ACTIONS --}}
                        <div>

                            {{-- VIEW DETAILS --}}
                            <a href="{{ $job->slug ? route('website.job.details',$job->slug) : '#' }}"
                               class="btn btn-outline-primary btn-sm w-100 mb-2">

                                View Details

                            </a>

                            {{-- ASSIGN JOB BUTTON --}}
                            <button class="btn btn-warning btn-sm w-100 mb-2"
                                    data-bs-toggle="modal"
                                    data-bs-target="#assignJobModal{{ $job->id }}">

                                📤 Assign Job

                            </button>

                            {{-- APPLY CANDIDATE --}}
                            <form method="POST"
                                  action="{{ route('agency.apply.candidate') }}">

                                @csrf

                                <input type="hidden"
                                       name="job_id"
                                       value="{{ $job->id }}">

                                <select name="candidate_id"
                                        class="form-control mb-2"
                                        required>

                                    <option value="">
                                        Select Candidate
                                    </option>

                                    @foreach($candidates as $c)

                                        <option value="{{ $c->id }}">

                                            {{ $c->first_name }}
                                            {{ $c->last_name }}

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

            </div>

            {{-- DECLINE REASON MODAL --}}
            @if(($job->pivot->status ?? null) === 'pending')
                <div class="modal fade" id="declineJobModal{{ $job->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 rounded-4">
                            <form method="POST" action="{{ route('agency.job.respond', $job->id) }}">
                                @csrf
                                <input type="hidden" name="action" value="decline">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Decline Job</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Reason (optional)</label>
                                    <textarea name="reason" class="form-control" rows="3" placeholder="Let the employer know why you're declining"></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger">Decline Job</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            {{-- MODAL --}}

            <div class="modal fade"
                 id="assignJobModal{{ $job->id }}"
                 tabindex="-1">

                <div class="modal-dialog modal-lg">

                    <div class="modal-content border-0 rounded-4">

                        <div class="modal-header">

                            <h5 class="modal-title fw-bold">

                                Assign Job

                            </h5>

                            <button type="button"
                                    class="btn-close"
                                    data-bs-dismiss="modal">
                            </button>

                        </div>

                        <div class="modal-body">

                            {{-- LIMITS --}}

                            <div class="alert alert-info">

                                Your plan allows:

                                <strong>{{ $subAgencyLimit }}</strong>
                                sub agencies

                                and

                                <strong>{{ $agentLimit }}</strong>
                                agents.

                            </div>

                            {{-- TABS --}}

                            <ul class="nav nav-pills mb-4">

                                <li class="nav-item me-2">

                                    <button class="nav-link active"
                                            data-bs-toggle="pill"
                                            data-bs-target="#subagency-tab-{{ $job->id }}">

                                        Sub Agency

                                    </button>

                                </li>

                                <li class="nav-item">

                                    <button class="nav-link"
                                            data-bs-toggle="pill"
                                            data-bs-target="#agent-tab-{{ $job->id }}">

                                        Agent

                                    </button>

                                </li>

                            </ul>

                            <div class="tab-content">

                                {{-- SUB AGENCY TAB --}}

                                <div class="tab-pane fade show active"
                                     id="subagency-tab-{{ $job->id }}">

                                    <form method="POST"
                                          action="{{ route('agency.assign.subagency') }}">

                                        @csrf

                                        <input type="hidden"
                                               name="job_id"
                                               value="{{ $job->id }}">

                                        <div class="mb-3">

                                            <label class="form-label fw-bold">

                                                Select Sub Agency

                                            </label>

                                            <select name="sub_agency_id"
                                                    class="form-control"
                                                    required>

                                                <option value="">
                                                    Choose Sub Agency
                                                </option>

                                                @foreach($subAgencies as $subAgency)

                                                    <option value="{{ $subAgency->id }}">

                                                        {{ optional($subAgency->user)->name }}
                                                        —
                                                        {{ $subAgency->country }}

                                                    </option>

                                                @endforeach

                                            </select>

                                        </div>

                                        {{-- HIDE OPTIONS --}}

                                        <div class="card border-0 bg-light mb-4">

                                            <div class="card-body">

                                                <h6 class="fw-bold mb-3">

                                                    Hide / Display Information

                                                </h6>

                                                <div class="row">

                                                    @php
                                                        $hideOptions = [
                                                            'hide_company_name' => 'Hide Company Name',
                                                            'hide_salary' => 'Hide Salary',
                                                            'hide_city' => 'Hide City',
                                                            'hide_country' => 'Hide Country',
                                                            'hide_company_logo' => 'Hide Company Logo',
                                                            'hide_job_description' => 'Hide Job Description',
                                                        ];
                                                    @endphp

                                                    @foreach($hideOptions as $field => $label)

                                                        <div class="col-md-6 mb-2">

                                                            <div class="form-check">

                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       name="{{ $field }}"
                                                                       value="1">

                                                                <label class="form-check-label">

                                                                    {{ $label }}

                                                                </label>

                                                            </div>

                                                        </div>

                                                    @endforeach

                                                </div>

                                            </div>

                                        </div>

                                        <button class="btn btn-primary w-100">

                                            Assign to Sub Agency

                                        </button>

                                    </form>

                                </div>

                                {{-- AGENT TAB --}}

                                <div class="tab-pane fade"
                                     id="agent-tab-{{ $job->id }}">

                                    <form method="POST"
                                          action="{{ route('agency.assign.agent') }}">

                                        @csrf

                                        <input type="hidden"
                                               name="job_id"
                                               value="{{ $job->id }}">

                                        <div class="mb-3">

                                            <label class="form-label fw-bold">

                                                Select Agent

                                            </label>

                                            <select name="agent_id"
                                                    class="form-control"
                                                    required>

                                                <option value="">
                                                    Choose Agent
                                                </option>

                                                @foreach($agents as $agent)

                                                    <option value="{{ $agent->id }}">

                                                        {{ $agent->name }}

                                                    </option>

                                                @endforeach

                                            </select>

                                        </div>

                                        {{-- HIDE OPTIONS --}}

                                        <div class="card border-0 bg-light mb-4">

                                            <div class="card-body">

                                                <h6 class="fw-bold mb-3">

                                                    Hide / Display Information

                                                </h6>

                                                <div class="row">

                                                    @foreach($hideOptions as $field => $label)

                                                        <div class="col-md-6 mb-2">

                                                            <div class="form-check">

                                                                <input class="form-check-input"
                                                                       type="checkbox"
                                                                       name="{{ $field }}"
                                                                       value="1">

                                                                <label class="form-check-label">

                                                                    {{ $label }}

                                                                </label>

                                                            </div>

                                                        </div>

                                                    @endforeach

                                                </div>

                                            </div>

                                        </div>

                                        <button class="btn btn-dark w-100">

                                            Assign to Agent

                                        </button>

                                    </form>

                                </div>

                            </div>

                        </div>

                    </div>

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