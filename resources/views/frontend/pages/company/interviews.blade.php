@extends('components.website.company.layout.app')

@section('title', 'Interviews')

@section('css')
<link rel="stylesheet" href="{{ asset('css/company-applicants.css') }}?v={{ @filemtime(public_path('css/company-applicants.css')) ?: '1' }}">
<style>
.cw-iv-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:1.1rem 1.25rem; margin-bottom:.85rem; border-left:4px solid #7c3aed; }
.cw-iv-meta { color:#64748b; font-size:.85rem; }
.cw-iv-actions { display:flex; flex-wrap:wrap; gap:.4rem; margin-top:.85rem; }
.cw-iv-actions form { display:inline; }
.cw-iv-outcome { display:inline-block; font-size:.7rem; font-weight:700; text-transform:uppercase; padding:.2rem .45rem; border-radius:6px; color:#fff; background:#7c3aed; }
.cw-iv-outcome.rescheduled { background:#d97706; }
.cw-iv-outcome.completed { background:#16a34a; }
.cw-iv-outcome.rejected { background:#dc2626; }
.cw-iv-date-row { display:flex; flex-wrap:wrap; gap:.5rem; align-items:end; margin-top:.5rem; }
.cw-iv-date-row input { max-width:180px; }
</style>
@endsection

@section('main')
@php
    $activeOutcome = request('outcome', 'all');
@endphp

<div class="container-fluid px-3">
    <div class="cw-apps-header">
        <div>
            <h1>Interviews</h1>
            <div class="cw-apps-meta">
                <span>Manage candidates invited to interview. Accept, reject, reschedule, or mark complete — each action emails the seeker.</span>
            </div>
        </div>
        <div>
            <a href="{{ route('company.applicants') }}" class="btn btn-outline-secondary btn-sm">All Applicants</a>
        </div>
    </div>

    <div class="cw-apps-tabs">
        <a class="cw-apps-tab {{ $activeOutcome === 'all' ? 'is-active' : '' }}" href="{{ route('company.interviews', ['outcome' => 'all', 'q' => request('q')]) }}">
            All <span class="count">{{ $counts['all'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab {{ $activeOutcome === 'scheduled' ? 'is-active' : '' }}" href="{{ route('company.interviews', ['outcome' => 'scheduled', 'q' => request('q')]) }}">
            Scheduled <span class="count">{{ $counts['scheduled'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab {{ $activeOutcome === 'rescheduled' ? 'is-active' : '' }}" href="{{ route('company.interviews', ['outcome' => 'rescheduled', 'q' => request('q')]) }}">
            Rescheduled <span class="count">{{ $counts['rescheduled'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab {{ $activeOutcome === 'completed' ? 'is-active' : '' }}" href="{{ route('company.interviews', ['outcome' => 'completed', 'q' => request('q')]) }}">
            Completed <span class="count">{{ $counts['completed'] ?? 0 }}</span>
        </a>
    </div>

    <form method="GET" action="{{ route('company.interviews') }}" class="cw-apps-toolbar mb-3">
        <input type="hidden" name="outcome" value="{{ $activeOutcome }}">
        <input type="text" name="q" class="form-control" style="max-width:280px" value="{{ request('q') }}" placeholder="Search candidate name">
        <button type="submit" class="btn btn-primary btn-sm">Search</button>
    </form>

    @forelse($interviews as $app)
        @php
            $candidate = $app->candidate;
            $user = $candidate?->user;
            $outcome = $app->interview_outcome ?: 'scheduled';
            $detailUrl = ($candidate && $app->job_id)
                ? route('company.application.detail', [$candidate->id, $app->job_id])
                : '#';
        @endphp
        <div class="cw-iv-card">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <h3 class="h5 mb-1">{{ $user->name ?? 'Candidate' }}</h3>
                    <div class="cw-iv-meta">
                        Job: <strong>{{ $app->job->title ?? '—' }}</strong>
                        · Outcome: <span class="cw-iv-outcome {{ $outcome }}">{{ $outcome }}</span>
                        @if($app->interview_date)
                            · Date: {{ optional($app->interview_date)->format('M d, Y') }}
                        @endif
                        @if($app->interview_location)
                            · {{ $app->interview_location }}
                        @endif
                    </div>
                </div>
                <a href="{{ $detailUrl }}" class="btn btn-outline-secondary btn-sm">Preview CV</a>
            </div>

            <div class="cw-iv-actions">
                <form method="POST" action="{{ route('company.update.interview') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $app->id }}">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn btn-success btn-sm">Accept</button>
                </form>

                <form method="POST" action="{{ route('company.update.interview') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $app->id }}">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Reject this candidate?')">Reject</button>
                </form>

                <form method="POST" action="{{ route('company.update.interview') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $app->id }}">
                    <input type="hidden" name="action" value="complete">
                    <button type="submit" class="btn btn-outline-primary btn-sm">Mark Completed</button>
                </form>
            </div>

            <form method="POST" action="{{ route('company.update.interview') }}" class="cw-iv-date-row">
                @csrf
                <input type="hidden" name="id" value="{{ $app->id }}">
                <input type="hidden" name="action" value="reschedule">
                <div>
                    <label class="form-label mb-0 small">Reschedule date</label>
                    <input type="date" name="interview_date" class="form-control form-control-sm" value="{{ optional($app->interview_date)->format('Y-m-d') }}" required>
                </div>
                <div>
                    <label class="form-label mb-0 small">Location / mode</label>
                    <input type="text" name="interview_location" class="form-control form-control-sm" value="{{ $app->interview_location }}" placeholder="Office / Zoom">
                </div>
                <button type="submit" class="btn btn-warning btn-sm">Reschedule &amp; Email</button>
            </form>
        </div>
    @empty
        <div class="glass-card"><div class="glass-card-body text-center py-5">
            <h4>No interviews yet</h4>
            <p class="text-muted mb-0">Invite candidates from <a href="{{ route('company.applicants') }}">Applicants</a> using <strong>Call for Interview</strong>.</p>
        </div></div>
    @endempty

    <div class="mt-3">
        {{ $interviews->links('vendor.pagination.frontend') }}
    </div>
</div>
@endsection
