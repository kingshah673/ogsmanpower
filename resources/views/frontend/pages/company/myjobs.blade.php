@extends('components.website.company.layout.app')

@section('title', __('my_jobs'))

@section('main')

<div class="dashboard-wrapper seeker-module-page">
<div class="container">
<div class="dashboard-right">

<x-website.company.employer-page-header
    :title="__('my_jobs')"
    subtitle="Manage your job postings, applications and promotions."
>
    <x-slot:actions>
        <a href="{{ route('company.job.create') }}" class="pv-topbar-btn"><i class="fas fa-plus"></i> {{ __('post_a_job') }}</a>
    </x-slot:actions>
</x-website.company.employer-page-header>

<div class="glass-card">
<div class="glass-card-body">

<div class="container-fluid px-0 py-0">

    <!-- TOP ANALYTICS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Total Jobs</small>
                <h4>{{ $stats['total'] ?? $myJobs->total() }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Active Jobs</small>
                <h4>{{ $stats['active'] ?? 0 }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Total Applications</small>
                <h4>{{ $stats['applications'] ?? 0 }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="promo">
                <h6>Boost Hiring 🚀</h6>
                <small>Promote jobs to get more candidates</small>
                <div class="mt-2">
                    <a href="#" class="btn btn-light btn-sm">Promote Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <form id="status-filter" action="{{ route('company.myjob') }}" method="GET" class="d-flex flex-wrap gap-2 mb-4 align-items-end">
        <div>
            <label class="small text-muted mb-1 d-block">{{ __('job_status') }}</label>
            <select name="status" class="form-select">
                <option value="">{{ __('all') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('active') }}</option>
                <option value="pending" @selected(request('status') === 'pending')>{{ __('pending') }}</option>
                <option value="expired" @selected(request('status') === 'expired')>{{ __('expired') }}</option>
            </select>
        </div>
        <div>
            <label class="small text-muted mb-1 d-block">{{ __('apply_on') }}</label>
            <select name="apply_on" class="form-select">
                <option value="">{{ __('all') }}</option>
                <option value="app" @selected(request('apply_on') === 'app')>{{ __('app') }}</option>
                <option value="email" @selected(request('apply_on') === 'email')>{{ __('email') }}</option>
                <option value="custom_url" @selected(request('apply_on') === 'custom_url')>{{ __('custom_url') }}</option>
            </select>
        </div>
        <button type="submit" class="btn btn-outline-primary">{{ __('filter') }}</button>
        @if(request()->hasAny(['status', 'apply_on']))
            <a href="{{ route('company.myjob') }}" class="btn btn-link">{{ __('clear') }}</a>
        @endif
    </form>

    <form id="bulk-delete-jobs" method="POST" action="{{ route('company.jobs.destroy.selected') }}">
        @csrf
        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
            <label class="mb-0 small">
                <input type="checkbox" id="select-all-jobs"> Select all on this page
            </label>
            <button type="submit" class="btn btn-outline-danger btn-sm" id="bulk-delete-btn" disabled
                onclick="return confirm('Delete selected jobs permanently? Applications for those jobs will also be removed.');">
                Delete selected
            </button>
        </div>

    <!-- JOB LIST -->
    <div class="row g-4">

        @forelse($myJobs as $job)
        <div class="col-lg-6">
            <div class="job-card">

                <!-- TITLE -->
                <div class="d-flex justify-content-between">
                    <div class="d-flex gap-2 align-items-start">
                        <input type="checkbox" class="job-select mt-1" name="ids[]" value="{{ $job->id }}" form="bulk-delete-jobs">
                        <div>
                            <a href="{{ route('website.job.details',$job->slug) }}" class="job-title">
                                {{ $job->title }}
                            </a>
                            <div class="text-muted small">
                                {{ ucfirst($job->job_type->name ?? '—') }} • {{ $job->days_remaining }} days left
                            </div>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <span class="badge-status {{ $job->status }}">
                        {{ ucfirst($job->status) }}
                    </span>
                </div>

                <!-- STATS -->
                <div class="row g-2 mt-3">
                    <div class="col-4">
                        <div class="stat-box">
                            <strong>{{ $job->applied_jobs_count }}</strong>
                            <small>Applicants</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <strong>{{ (int) ($job->total_views ?? 0) }}</strong>
                            <small>Views</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <strong>{{ $job->conversionRate($job->applied_jobs_count) }}%</strong>
                            <small>Conversion</small>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="d-flex flex-wrap gap-2 mt-3">

                    <a href="{{ route('company.job.application',['job'=>$job->id]) }}"
                       class="btn btn-dark action-btn">
                        Applications
                    </a>

                    <a href="{{ route('company.job.edit',$job->slug) }}"
                       class="btn btn-outline-primary action-btn">
                        Edit
                    </a>

                    <a href="{{ route('company.promote',$job->slug) }}"
                       class="btn btn-outline-warning action-btn">
                        Promote
                    </a>
                    <a href="{{ route('company.job.assign.agency', $job->id) }}"
                       class="btn btn-outline-success action-btn">
                       Assign Agency
                     </a>

                    <a href="{{ route('company.clone',$job->slug) }}"
                       class="btn btn-outline-secondary action-btn">
                        Clone
                    </a>

                    <button type="submit" form="delete-job-{{ $job->id }}"
                        class="btn btn-outline-danger action-btn"
                        onclick="return confirm('Delete this job permanently? Its applications will also be removed.');">
                        Delete
                    </button>
                </div>

            </div>
        </div>
        @empty
        <div class="text-center">No Jobs Found</div>
        @endforelse

    </div>
    </form>

    @foreach($myJobs as $job)
    <form id="delete-job-{{ $job->id }}" method="POST" action="{{ route('company.job.destroy', $job) }}" class="d-none">
        @csrf
        @method('DELETE')
    </form>
    @endforeach

    <!-- PAGINATION -->
    <div class="mt-4">
        {{ $myJobs->links('vendor.pagination.frontend') }}
    </div>

</div>

</div>
</div>

</div>
</div>
</div>

@endsection

@section('script')
<script>
    document.getElementById('status-filter')?.addEventListener('change', function (event) {
        if (event.target && event.target.tagName === 'SELECT') {
            this.submit();
        }
    });

    (function () {
        var selectAll = document.getElementById('select-all-jobs');
        var bulkBtn = document.getElementById('bulk-delete-btn');
        var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('.job-select')); };

        function syncBulk() {
            var checked = boxes().filter(function (b) { return b.checked; }).length;
            if (bulkBtn) bulkBtn.disabled = checked === 0;
            if (selectAll) {
                var all = boxes();
                selectAll.checked = all.length > 0 && checked === all.length;
                selectAll.indeterminate = checked > 0 && checked < all.length;
            }
        }

        selectAll?.addEventListener('change', function () {
            boxes().forEach(function (b) { b.checked = selectAll.checked; });
            syncBulk();
        });
        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('job-select')) syncBulk();
        });
        syncBulk();
    })();
</script>
@endsection