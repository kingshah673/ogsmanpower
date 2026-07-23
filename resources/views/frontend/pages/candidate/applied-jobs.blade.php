@extends('components.website.candidate.layout.app')

@section('title')
{{ __('applied_jobs') }}
@endsection

@section('css')
<style>
.cw-app-tabs { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1rem; }
.cw-app-tab {
    display:inline-flex; align-items:center; gap:.35rem;
    padding:.45rem .8rem; border-radius:8px; font-size:.85rem; font-weight:600;
    text-decoration:none; border:1px solid #e2e8f0; background:#fff; color:#0f172a;
}
.cw-app-tab .count { background:#f1f5f9; border-radius:999px; padding:.05rem .4rem; font-size:.75rem; }
.cw-app-tab.is-active { background:#2563eb; border-color:#2563eb; color:#fff; }
.cw-app-tab.is-active .count { background:rgba(255,255,255,.2); color:#fff; }
.cw-app-tab.tab-shortlisted.is-active { background:#16a34a; border-color:#16a34a; }
.cw-app-tab.tab-selected.is-active { background:#d97706; border-color:#d97706; }
.cw-app-tab.tab-rejected.is-active { background:#dc2626; border-color:#dc2626; }

.cw-app-card { border: 1px solid #e2e8f0; border-radius: 12px; border-left: 3px solid #2563eb; margin-bottom: 0.75rem; overflow:hidden; background:#fff; }
.cw-app-card.is-shortlisted { border-left-color: #16a34a; }
.cw-app-card.is-selected { border-left-color: #d97706; }
.cw-app-card.is-rejected { border-left-color: #dc2626; }
.cw-app-card-head { display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; align-items:center; padding:1.25rem; }
.cw-app-card-body { background: #f8fafc; border-top: 1px solid #e2e8f0; padding:1.25rem; }

.cw-status-badge {
    display:inline-block; font-size:.72rem; font-weight:700; letter-spacing:.03em;
    text-transform:uppercase; padding:.3rem .55rem; border-radius:6px; color:#fff;
}
.cw-status-badge.pending { background:#64748b; }
.cw-status-badge.shortlisted { background:#16a34a; }
.cw-status-badge.selected { background:#d97706; }
.cw-status-badge.rejected { background:#dc2626; }

.cw-shortlist-banner {
    background:#f0fdf4; border:1px solid #bbf7d0; color:#166534;
    border-radius:10px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.9rem;
}
</style>
@endsection

@section('main')
@php
    $activeStatus = request('status', 'all');
    $counts = $statusCounts ?? ['all' => $appliedJobs->total(), 'pending' => 0, 'shortlisted' => 0, 'selected' => 0, 'rejected' => 0];
@endphp

<div class="dashboard-wrapper seeker-settings-page">
<div class="container">
<div class="dashboard-right">

<x-website.candidate.seeker-page-header
    :title="__('applied_jobs') . ' (' . ($counts['all'] ?? $appliedJobs->total()) . ')'"
    :subtitle="__('Track jobs you have applied to and your application status.')"
/>

@if(($counts['shortlisted'] ?? 0) > 0)
<div class="cw-shortlist-banner">
    You have been <strong>shortlisted</strong> for {{ $counts['shortlisted'] }} {{ $counts['shortlisted'] == 1 ? 'job' : 'jobs' }}.
    <a href="{{ route('candidate.appliedjob', ['status' => 'shortlisted']) }}" class="ms-1 fw-semibold">View shortlisted</a>
</div>
@endif

@if(($counts['interview'] ?? 0) > 0)
<div class="cw-shortlist-banner" style="background:#f5f3ff;border-color:#ddd6fe;color:#5b21b6;">
    You have <strong>{{ $counts['interview'] }}</strong> interview {{ $counts['interview'] == 1 ? 'invitation' : 'invitations' }}.
    <a href="{{ route('candidate.appliedjob', ['status' => 'interview']) }}" class="ms-1 fw-semibold">View interviews</a>
</div>
@endif

<div class="cw-app-tabs">
    <a class="cw-app-tab {{ $activeStatus === 'all' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'all']) }}">
        All <span class="count">{{ $counts['all'] ?? 0 }}</span>
    </a>
    <a class="cw-app-tab {{ $activeStatus === 'pending' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'pending']) }}">
        Pending <span class="count">{{ $counts['pending'] ?? 0 }}</span>
    </a>
    <a class="cw-app-tab tab-shortlisted {{ $activeStatus === 'shortlisted' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'shortlisted']) }}">
        Shortlisted <span class="count">{{ $counts['shortlisted'] ?? 0 }}</span>
    </a>
    <a class="cw-app-tab {{ $activeStatus === 'interview' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'interview']) }}" style="{{ $activeStatus === 'interview' ? 'background:#7c3aed;border-color:#7c3aed;color:#fff;' : '' }}">
        Interview <span class="count">{{ $counts['interview'] ?? 0 }}</span>
    </a>
    <a class="cw-app-tab tab-selected {{ $activeStatus === 'selected' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'selected']) }}">
        Selected <span class="count">{{ $counts['selected'] ?? 0 }}</span>
    </a>
    <a class="cw-app-tab tab-rejected {{ $activeStatus === 'rejected' ? 'is-active' : '' }}" href="{{ route('candidate.appliedjob', ['status' => 'rejected']) }}">
        Rejected <span class="count">{{ $counts['rejected'] ?? 0 }}</span>
    </a>
</div>

@if($appliedJobs->count() > 0)
<div class="glass-card"><div class="glass-card-body">

@foreach($appliedJobs as $application)
@php
    $job = $application->job;
    $status = $application->status ?: 'pending';
    $companyName = $job?->company?->user?->name
        ?? $job?->company?->name
        ?? 'Company';
    $logoRaw = $job?->company?->logo_url;
    $logo = $logoRaw
        ? (str_starts_with($logoRaw, 'http') ? $logoRaw : asset($logoRaw))
        : asset('frontend/assets/images/company.png');
    $collapseId = 'collapseApp'.$application->id;
@endphp

<div class="cw-app-card is-{{ $status }}">
    <div class="cw-app-card-head">
        <div class="d-flex gap-3 align-items-center">
            <img src="{{ $logo }}" width="56" height="56" class="rounded" alt="" style="object-fit:cover;">
            <div>
                @if($job)
                    <a href="{{ route('website.job.details', $job->slug) }}" class="fw-semibold text-decoration-none">
                        {{ $job->title }}
                    </a>
                    <div class="tw-text-sm text-muted">{{ $companyName }}@if($job->country) · {{ $job->country }}@endif</div>
                @else
                    <span class="fw-semibold">Job unavailable</span>
                @endif
            </div>
        </div>

        <div class="tw-text-sm text-muted">
            Applied {{ optional($application->created_at)->format('M d, Y') }}
        </div>

        <div>
            <span class="cw-status-badge {{ $status }}">{{ $status }}</span>
            @if($job && $job->deadline_active)
                <div class="tw-text-xs text-muted mt-1">Job listing open</div>
            @elseif($job)
                <div class="tw-text-xs text-danger mt-1">Job listing expired</div>
            @endif
        </div>

        <div>
            <button type="button"
                class="btn btn-sm btn-outline-primary"
                data-open-label="{{ __('view_details') }}"
                data-close-label="Hide Details"
                onclick="(function(btn){var p=document.getElementById('{{ $collapseId }}');if(!p)return;var open=p.classList.toggle('show');btn.setAttribute('aria-expanded',open?'true':'false');btn.textContent=open?(btn.getAttribute('data-close-label')||'Hide Details'):(btn.getAttribute('data-open-label')||'View Details');})(this)"
                aria-expanded="false"
                aria-controls="{{ $collapseId }}">
                {{ __('view_details') }}
            </button>
        </div>
    </div>

    <div id="{{ $collapseId }}" class="collapse">
        <div class="cw-app-card-body">

            @if($status === 'shortlisted')
                <div class="alert alert-success py-2 mb-3">
                    Good news — the employer has <strong>shortlisted</strong> your application. They may contact you for next steps.
                </div>
            @elseif($status === 'interview')
                <div class="alert alert-primary py-2 mb-3">
                    You have been invited to an <strong>interview</strong>.
                    @if($application->interview_date)
                        Date: {{ optional($application->interview_date)->format('M d, Y') }}.
                    @endif
                    @if($application->interview_location)
                        Location: {{ $application->interview_location }}.
                    @endif
                    Check your email for details.
                </div>
            @elseif($status === 'selected')
                <div class="alert alert-warning py-2 mb-3">
                    You have been <strong>selected</strong> for this role. Check your messages and email for updates.
                </div>
            @elseif($status === 'rejected')
                <div class="alert alert-secondary py-2 mb-3">
                    This application was not selected. Keep applying — other roles may be a better fit.
                </div>
            @endif

            <h5>{{ __('cover_letter') }}</h5>
            {!! $application->cover_letter ? nl2br(e($application->cover_letter)) : '<p class="text-muted mb-0">No cover letter submitted.</p>' !!}

            @if($application->resume)
                <div class="mt-3">
                    <a href="{{ route('website.candidate.download.cv', $application->resume) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Download Resume{{ $application->resume->name ? ' ('.$application->resume->name.')' : '' }}
                    </a>
                    <div class="small text-muted mt-1">The resume file you submitted with this application.</div>
                </div>
            @elseif(!empty($application->cv_path))
                <div class="mt-3">
                    <a href="{{ route('website.download.applicant.cv', $application->id) }}"
                       class="btn btn-sm btn-outline-secondary">
                        Download Resume
                    </a>
                </div>
            @endif

            @if($job)
                <div class="mt-3">
                    <a href="{{ route('website.job.details', $job->slug) }}" class="btn btn-sm btn-primary">View Job</a>
                </div>
            @endif

        </div>
    </div>
</div>

@endforeach

<div class="mt-4">
{{ $appliedJobs->links('vendor.pagination.frontend') }}
</div>

</div></div>

@else

<div class="glass-card"><div class="glass-card-body text-center py-5">
    <h4>No Applied Jobs Yet</h4>
    <p class="text-muted mb-0">
        @if($activeStatus !== 'all')
            No applications with status “{{ $activeStatus }}”.
            <a href="{{ route('candidate.appliedjob') }}">View all</a>
        @else
            Once you apply for jobs, they will appear here with your status (pending, shortlisted, selected, or rejected).
        @endif
    </p>
</div></div>
@endif

</div>
</div>
</div>

@endsection
