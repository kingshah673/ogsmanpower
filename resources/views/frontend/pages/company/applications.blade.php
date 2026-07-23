@extends('components.website.company.layout.app')

@section('title', __('Applications').' — '.$job->title)

@section('css')
<link rel="stylesheet" href="{{ asset('css/company-applicants.css') }}?v={{ @filemtime(public_path('css/company-applicants.css')) ?: '1' }}">
@endsection

@section('main')
@php
    $activeStatus = request('status', 'all');
    $baseParams = request()->except(['page', 'status']);
    $baseParams['job'] = $job->id;
@endphp

<div class="container-fluid px-3">
    <div class="cw-apps-header">
        <div>
            <h1>{{ $job->id }}/ {{ $job->title }}</h1>
            <div class="cw-apps-meta">
                @if($job->featured)<span>Featured</span>@endif
                <span>{{ (int) ($job->total_views ?? 0) }} Views</span>
                <span>{{ $counts['all'] ?? 0 }} Applied</span>
                <span>Status: {{ ucfirst($job->status ?? '—') }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('company.myjob') }}" class="btn btn-outline-secondary btn-sm">Back to My Jobs</a>
            @if(($job->status ?? '') === 'active')
                <form method="POST" action="{{ route('company.job.make.expire', $job) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger btn-sm">Deactivate Job</button>
                </form>
            @endif
        </div>
    </div>

    <div class="cw-apps-tabs">
        <a class="cw-apps-tab {{ $activeStatus === 'all' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'all'])) }}">
            Applicants <span class="count">{{ $counts['all'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab tab-shortlisted {{ $activeStatus === 'shortlisted' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'shortlisted'])) }}">
            Shortlisted <span class="count">{{ $counts['shortlisted'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab {{ $activeStatus === 'interview' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'interview'])) }}"
           style="{{ $activeStatus === 'interview' ? 'background:#7c3aed;border-color:#7c3aed;color:#fff;' : '' }}">
            Interview <span class="count">{{ $counts['interview'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab tab-selected {{ $activeStatus === 'selected' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'selected'])) }}">
            Selected <span class="count">{{ $counts['selected'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab tab-rejected {{ $activeStatus === 'rejected' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'rejected'])) }}">
            Rejected <span class="count">{{ $counts['rejected'] ?? 0 }}</span>
        </a>
        <a class="cw-apps-tab {{ $activeStatus === 'pending' ? 'is-active' : '' }}"
           href="{{ route('company.job.application', array_merge($baseParams, ['status' => 'pending'])) }}">
            Pending <span class="count">{{ $counts['pending'] ?? 0 }}</span>
        </a>
    </div>

    <div class="cw-apps">
        <aside class="cw-apps-filters">
            <h3>Applicant Filters</h3>
            <form method="GET" action="{{ route('company.job.application') }}">
                <input type="hidden" name="job" value="{{ $job->id }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}">

                <div class="cw-apps-filter-group" style="border-top:0;padding-top:0;">
                    <label class="cw-apps-filter-label">Keyword</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q', request('name')) }}" placeholder="Name">
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Any</option>
                        <option value="male" @selected(request('gender')==='male')>Male</option>
                        <option value="female" @selected(request('gender')==='female')>Female</option>
                    </select>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Country</label>
                    <select name="country" class="form-select">
                        <option value="">Any</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->name }}" @selected(request('country')===$country->name)>{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Education</label>
                    <select name="education" class="form-select">
                        <option value="">Any</option>
                        @foreach($educations as $education)
                            <option value="{{ $education->id }}" @selected((string)request('education')===(string)$education->id)>{{ $education->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Skill</label>
                    <select name="skill" class="form-select">
                        <option value="">Any</option>
                        @foreach($skills as $skill)
                            <option value="{{ $skill->id }}" @selected((string)request('skill')===(string)$skill->id)>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Experience (years)</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="experience_from" class="form-control" placeholder="From" value="{{ request('experience_from') }}" min="0">
                        <input type="number" name="experience_to" class="form-control" placeholder="To" value="{{ request('experience_to') }}" min="0">
                    </div>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Age</label>
                    <div class="d-flex gap-2">
                        <input type="number" name="age_from" class="form-control" placeholder="From" value="{{ request('age_from') }}" min="0">
                        <input type="number" name="age_to" class="form-control" placeholder="To" value="{{ request('age_to') }}" min="0">
                    </div>
                </div>

                <div class="cw-apps-filter-group">
                    <label class="cw-apps-filter-label">Applied date</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>

                <button type="submit" class="btn-filter">Apply Filters</button>
                <a class="btn-reset" href="{{ route('company.job.application', ['job' => $job->id]) }}">Reset</a>
            </form>
        </aside>

        <div class="cw-apps-main">
            <form method="GET" action="{{ route('company.job.application') }}" class="cw-apps-toolbar">
                <input type="hidden" name="job" value="{{ $job->id }}">
                <input type="hidden" name="status" value="{{ $activeStatus }}">
                @foreach(request()->except(['q','sort','page','job','status']) as $k => $v)
                    @if(is_scalar($v))
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endif
                @endforeach
                <input type="text" name="q" class="form-control" value="{{ request('q', request('name')) }}" placeholder="Search for Job Applicants / Keywords">
                <button type="submit" class="btn-search">Search</button>
                <select name="sort" class="form-select form-select-sm" style="max-width:160px" onchange="this.form.submit()">
                    <option value="date_desc" @selected(request('sort','date_desc')==='date_desc')>Sort: Newest</option>
                    <option value="date_asc" @selected(request('sort')==='date_asc')>Sort: Oldest</option>
                    <option value="name" @selected(request('sort')==='name')>Sort: Name</option>
                </select>
            </form>

            @forelse($applications as $app)
                @include('frontend.pages.company.partials.applicant-card', ['app' => $app, 'showJob' => false])
            @empty
                <div class="cw-apps-empty">No applicants match your filters for this job.</div>
            @endforelse

            <div class="mt-3">
                {{ $applications->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
