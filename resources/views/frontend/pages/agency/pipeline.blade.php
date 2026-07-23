@extends('components.website.agency.layout.app')

@section('title','Candidate Pipeline')

@section('main')

@php

    $agency = auth()->user()->agency;

    $isMainAgency = is_null(
        $agency->parent_agency_id
    );

@endphp

<div class="container py-4">

    {{-- HEADER --}}

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h3 class="fw-bold mb-1">

                Candidate Pipeline

            </h3>

            <p class="text-muted mb-0">

                Manage contracts and recruitment workflow.

            </p>

        </div>

        <span class="badge bg-primary px-3 py-2">

            Total:
            {{ count($candidates) }}

        </span>

    </div>

    {{-- MAIN CARD --}}

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead class="table-light">

                        <tr>

                            <th width="70">

                                ID

                            </th>

                            <th>

                                Candidate

                            </th>

                            <th>

                                Job

                            </th>

                            <th>

                                Pipeline Status

                            </th>

                            <th>

                                Contract

                            </th>

                            <th>

                                Hiring Status

                            </th>

                            <th width="260">

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

                                    <div class="fw-bold">

                                        Candidate ID:
                                        {{ $candidate->candidate_id }}

                                    </div>

                                </td>

                                {{-- JOB --}}
                                <td>

                                    <div class="fw-bold">

                                        Job ID:
                                        {{ $candidate->job_id }}

                                    </div>

                                </td>

                                {{-- PIPELINE STATUS --}}
                                <td>

                                    @if($candidate->status == 'selected')

                                        <span class="badge bg-success">

                                            Selected

                                        </span>

                                    @elseif($candidate->status == 'shortlisted')

                                        <span class="badge bg-primary">

                                            Shortlisted

                                        </span>

                                    @elseif($candidate->status == 'underprocess')

                                        <span class="badge bg-warning text-dark">

                                            Under Process

                                        </span>

                                    @elseif($candidate->status == 'rejected')

                                        <span class="badge bg-danger">

                                            Rejected

                                        </span>

                                    @elseif($candidate->status == 'cancelled')

                                        <span class="badge bg-dark">

                                            Cancelled

                                        </span>

                                    @elseif($candidate->status == 'approved_by_subagency')

                                        <span class="badge bg-info">

                                            Approved By Sub Agency

                                        </span>

                                    @elseif($candidate->status == 'approved_by_agency')

                                        <span class="badge bg-success">

                                            Approved By Agency

                                        </span>

                                    @elseif($candidate->status == 'submitted_by_agent')

                                        <span class="badge bg-secondary">

                                            Submitted By Agent

                                        </span>

                                    @else

                                        <span class="badge bg-light text-dark">

                                            {{ ucfirst($candidate->status) }}

                                        </span>

                                    @endif

                                </td>

                                {{-- CONTRACT --}}
                                <td>

                                    @if($candidate->contract_id)

                                        <div class="mb-2">

                                            <span class="badge bg-success">

                                                Contract Available

                                            </span>

                                        </div>

                                        <div class="small fw-bold mb-2">

                                            {{ $candidate->contract_title }}

                                        </div>

                                        @if($candidate->contract_status == 'accepted')

                                            <span class="badge bg-primary">

                                                Accepted

                                            </span>

                                        @elseif($candidate->contract_status == 'rejected')

                                            <span class="badge bg-danger">

                                                Rejected

                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">

                                                Pending

                                            </span>

                                        @endif

                                        <div class="mt-2">

                                            <a href="{{ route('contract.view',$candidate->contract_id) }}"
                                               class="btn btn-outline-primary btn-sm">

                                                View Contract

                                            </a>

                                        </div>

                                    @else

                                        <span class="text-muted">

                                            No Contract

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

                                            Medical

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

                                {{-- ACTIONS --}}
                                <td>

                                    {{-- MAIN AGENCY --}}
                                    @if($isMainAgency)

                                        {{-- STATUS UPDATE --}}
                                        <form method="POST"
                                              action="{{ route('agency.pipeline.status',$candidate->id) }}">

                                            @csrf

                                            <select name="status"
                                                    class="form-control form-control-sm mb-2">

                                                <option value="shortlisted"
                                                    {{ $candidate->status == 'shortlisted' ? 'selected' : '' }}>

                                                    Shortlisted

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

                                                <option value="cancelled"
                                                    {{ $candidate->status == 'cancelled' ? 'selected' : '' }}>

                                                    Cancelled

                                                </option>

                                            </select>

                                            <button class="btn btn-primary btn-sm w-100 mb-2">

                                                Update Status

                                            </button>

                                        </form>

                                    @endif

                                    {{-- CONTRACT ACTIONS --}}
                                    @if($candidate->contract_id)

                                        @if($candidate->contract_status == 'sent')

                                            {{-- ACCEPT --}}
                                            <form method="POST"
                                                  action="{{ route('agency.contract.accept',$candidate->contract_id) }}">

                                                @csrf

                                                <button class="btn btn-success btn-sm w-100 mb-2">

                                                    Accept Contract

                                                </button>

                                            </form>

                                            {{-- REJECT --}}
                                            <form method="POST"
                                                  action="{{ route('agency.contract.reject',$candidate->contract_id) }}">

                                                @csrf

                                                <button class="btn btn-danger btn-sm w-100">

                                                    Reject Contract

                                                </button>

                                            </form>

                                        @elseif($candidate->contract_status == 'accepted')

                                            <span class="badge bg-success w-100 py-2">

                                                Contract Accepted

                                            </span>

                                        @elseif($candidate->contract_status == 'rejected')

                                            <span class="badge bg-danger w-100 py-2">

                                                Contract Rejected

                                            </span>

                                        @endif

                                    @else

                                        <span class="text-muted">

                                            Waiting For Contract

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7">

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

</div>

@endsection