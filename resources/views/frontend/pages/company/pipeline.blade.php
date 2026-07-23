@extends('components.website.company.layout.app')

@section('title','Candidate Pipeline')

@section('main')

<div class="container py-4 seeker-module-page">

    <x-website.company.employer-page-header
        title="Candidate Pipeline"
        subtitle="Manage recruitment, contracts and hiring workflow."
    >
        <x-slot:actions>
            <span class="profile-badge">{{ count($candidates) }} candidates</span>
        </x-slot:actions>
    </x-website.company.employer-page-header>

    <div class="glass-card">
    <div class="glass-card-body">

    {{-- FILTER CARD --}}
    <div class="inner-panel mb-4">

        <div class="card-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Filter By Status

                        </label>

                        <select name="status"
                                class="form-control">

                            <option value="">
                                All Status
                            </option>

                            <option value="shortlisted">
                                Shortlisted
                            </option>

                            <option value="interview">
                                Interview
                            </option>

                            <option value="medical">
                                Medical
                            </option>

                            <option value="underprocess">
                                Under Process
                            </option>

                            <option value="selected">
                                Selected
                            </option>

                            <option value="rejected">
                                Rejected
                            </option>

                        </select>

                    </div>

                    <div class="col-md-4 mb-3">

                        <label class="form-label fw-bold">

                            Search Candidate

                        </label>

                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Search candidate">

                    </div>

                    <div class="col-md-4 mb-3 d-flex align-items-end">

                        <button class="btn btn-primary w-100">

                            Filter Results

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- TABLE --}}
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="70">

                                #

                            </th>

                            <th>

                                Candidate

                            </th>

                            <th>

                                Applied By

                            </th>

                            <th>

                                Job

                            </th>

                            <th>

                                Recruitment Status

                            </th>

                            <th>

                                Hiring Process

                            </th>

                            <th width="260">

                                Update Status

                            </th>

                            <th width="250">

                                Actions

                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($candidates as $candidate)

                            <tr>

                                {{-- ID --}}
                                <td>

                                    #{{ $candidate->id }}

                                </td>

                                {{-- CANDIDATE --}}
                                <td>

                                    <div class="fw-bold mb-1">

                                        {{ $candidate->first_name }}
                                        {{ $candidate->last_name }}

                                    </div>

                                    <div class="small text-muted">

                                        {{ $candidate->email }}

                                    </div>

                                </td>

                                {{-- APPLIED BY --}}
                                <td>

                                    <span class="badge bg-info">

                                        {{ $candidate->agency_name ?? 'Direct Candidate' }}

                                    </span>

                                </td>

                                {{-- JOB --}}
                                <td>

                                    <div class="fw-bold">

                                        Job ID:
                                        {{ $candidate->job_id }}

                                    </div>

                                </td>

                                {{-- STATUS --}}
                                <td>

                                    @if($candidate->status == 'shortlisted')

                                        <span class="badge bg-primary px-3 py-2">

                                            Shortlisted

                                        </span>

                                    @elseif($candidate->status == 'interview')

                                        <span class="badge bg-info px-3 py-2">

                                            Interview

                                        </span>

                                    @elseif($candidate->status == 'medical')

                                        <span class="badge bg-warning text-dark px-3 py-2">

                                            Medical

                                        </span>

                                    @elseif($candidate->status == 'underprocess')

                                        <span class="badge bg-warning text-dark px-3 py-2">

                                            Under Process

                                        </span>

                                    @elseif($candidate->status == 'selected')

                                        <span class="badge bg-success px-3 py-2">

                                            Selected

                                        </span>

                                    @elseif($candidate->status == 'rejected')

                                        <span class="badge bg-danger px-3 py-2">

                                            Rejected

                                        </span>

                                    @else

                                        <span class="badge bg-secondary px-3 py-2">

                                            {{ ucfirst($candidate->status) }}

                                        </span>

                                    @endif

                                </td>

                                {{-- HIRING STATUS --}}
                                <td>

                                    @if($candidate->hiring_status == 'contract_sent')

                                        <span class="badge bg-warning text-dark">

                                            Contract Sent

                                        </span>

                                    @elseif($candidate->hiring_status == 'contract_accepted')

                                        <span class="badge bg-success">

                                            Contract Accepted

                                        </span>

                                    @elseif($candidate->hiring_status == 'medical')

                                        <span class="badge bg-info">

                                            Medical Process

                                        </span>

                                    @elseif($candidate->hiring_status == 'visa_process')

                                        <span class="badge bg-primary">

                                            Visa Process

                                        </span>

                                    @elseif($candidate->hiring_status == 'ticket_process')

                                        <span class="badge bg-dark">

                                            Ticket Process

                                        </span>

                                    @elseif($candidate->hiring_status == 'deployment')

                                        <span class="badge bg-success">

                                            Deployment

                                        </span>

                                    @elseif($candidate->hiring_status == 'completed')

                                        <span class="badge bg-success">

                                            Completed

                                        </span>

                                    @else

                                        <span class="badge bg-secondary">

                                            Not Started

                                        </span>

                                    @endif

                                </td>

                                {{-- STATUS UPDATE --}}
                                <td>

                                    <form method="POST"
                                          action="{{ route('company.pipeline.status',$candidate->id) }}">

                                        @csrf

                                        <select name="status"
                                                class="form-control form-control-sm mb-2">

                                            <option value="shortlisted"
                                                {{ $candidate->status == 'shortlisted' ? 'selected' : '' }}>

                                                Shortlisted

                                            </option>

                                            <option value="interview"
                                                {{ $candidate->status == 'interview' ? 'selected' : '' }}>

                                                Interview

                                            </option>

                                            <option value="medical"
                                                {{ $candidate->status == 'medical' ? 'selected' : '' }}>

                                                Medical

                                            </option>

                                            <option value="underprocess"
                                                {{ $candidate->status == 'underprocess' ? 'selected' : '' }}>

                                                Under Process

                                            </option>

                                            <option value="selected"
                                                {{ $candidate->status == 'selected' ? 'selected' : '' }}>

                                                Selected

                                            </option>

                                            <option value="rejected"
                                                {{ $candidate->status == 'rejected' ? 'selected' : '' }}>

                                                Rejected

                                            </option>

                                        </select>

                                        <button class="btn btn-primary btn-sm w-100">

                                            Update Status

                                        </button>

                                    </form>

                                </td>

                                {{-- ACTIONS --}}
                                <td>

                                    <div class="d-grid gap-2">

                                        {{-- VIEW CONTRACT --}}
                                        @if($candidate->contract_id)

                                            <a href="{{ route('contract.view',$candidate->contract_id) }}"
                                               class="btn btn-outline-primary btn-sm">

                                                View Contract

                                            </a>

                                        @endif

                                        {{-- CREATE CONTRACT --}}
                                        <button class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#contractModal{{ $candidate->id }}">

                                            Create Contract

                                        </button>

                                        {{-- FINAL HIRE --}}
                                        <button class="btn btn-success btn-sm">

                                            Final Hire

                                        </button>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8">

                                    <div class="alert alert-info text-center mb-0">

                                        No candidates found in pipeline.

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    </div>{{-- glass-card-body --}}
    </div>{{-- glass-card --}}

</div>

@endsection