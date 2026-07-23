@extends('components.website.agency.layout.app')

@section('title', __('my_jobs'))

@section('main')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body{background:#f5f7fb}

/* KPI */
.analytics-card{
    border-radius:14px;
    padding:18px;
    background:#fff;
    box-shadow:0 10px 25px rgba(0,0,0,0.05);
}
.analytics-card h4{margin:0;font-weight:700}

/* JOB CARD */
.job-card{
    border-radius:16px;
    padding:20px;
    background:#fff;
    box-shadow:0 8px 20px rgba(0,0,0,0.05);
    transition:.3s;
}
.job-card:hover{
    transform:translateY(-4px);
    box-shadow:0 15px 30px rgba(0,0,0,0.08);
}

.job-title{
    font-weight:600;
    font-size:16px;
    color:#111827;
}

/* STATUS */
.badge-status{
    padding:6px 10px;
    border-radius:10px;
    font-size:12px;
}
.active{background:#d1fae5;color:#065f46}
.pending{background:#fef3c7;color:#92400e}
.expired{background:#fee2e2;color:#b91c1c}

/* ACTION BUTTONS */
.action-btn{
    border-radius:8px;
    font-size:12px;
    padding:6px 10px;
}

/* MINI STATS */
.stat-box{
    background:#f8fafc;
    border-radius:10px;
    padding:10px;
    text-align:center;
}
.stat-box strong{display:block}

/* PROMO */
.promo{
    background:linear-gradient(135deg,#6366F1,#4F46E5);
    color:#fff;
    border-radius:16px;
    padding:20px;
}
</style>

<div class="container py-4">

    <!-- TOP ANALYTICS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Total Jobs</small>
                <h4>{{ $myJobs->total() }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Active Jobs</small>
                <h4>{{ $myJobs->where('status','active')->count() }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="analytics-card">
                <small>Total Applications</small>
                <h4>{{ $myJobs->sum('applied_jobs_count') }}</h4>
            </div>
        </div>
        <div class="col-md-3">
            <div class="promo">
                <h6>Boost Hiring ðŸš€</h6>
                <small>Promote jobs to get more candidates</small>
                <div class="mt-2">
                    <a href="#" class="btn btn-light btn-sm">Promote Now</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <form id="status-filter" action="{{ route('agency.myjob') }}" method="GET" class="d-flex gap-2 mb-4">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="expired">Expired</option>
        </select>
    </form>

    <!-- JOB LIST -->
    <div class="row g-4">

        @forelse($myJobs as $job)
        <div class="col-lg-6">
            <div class="job-card">

                <!-- TITLE -->
                <div class="d-flex justify-content-between">
                    <div>
                        <a href="{{ route('website.job.details',$job->slug) }}" class="job-title">
                            {{ $job->title }}
                        </a>
                        <div class="text-muted small">
                            {{ ucfirst($job->job_type->name) }} â€¢ {{ $job->days_remaining }} days left
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
                            <strong>{{ $job->total_views ?? 0 }}</strong>
                            <small>Views</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <strong>{{ $job->applied_jobs_count > 0 ? round($job->selected_jobs_count / $job->applied_jobs_count * 100) : 0 }}%</strong>
                            <small>Conversion</small>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <div class="d-flex flex-wrap gap-2 mt-3">

                    <a href="{{ route('agency.job.application',['job'=>$job->id]) }}" 
                       class="btn btn-dark action-btn">
                        Applications
                    </a>

                    @if(Route::has('agency.ai.candidate-matcher'))
                        <a href="{{ route('agency.ai.candidate-matcher', $job->id) }}"
                           class="btn btn-outline-info action-btn">
                            <i class="ph-magic-wand"></i> Suggest Candidates
                        </a>
                    @endif

                    <a href="{{ route('agency.job.edit',$job->slug) }}" 
                       class="btn btn-outline-primary action-btn">
                        Edit
                    </a>

                    <a href="{{ route('agency.promote',$job->slug) }}" 
                       class="btn btn-outline-warning action-btn">
                        Promote
                    </a>

                    <a href="{{ route('agency.clone',$job->slug) }}" 
                       class="btn btn-outline-secondary action-btn">
                        Clone
                    </a>

                </div>

            </div>
        </div>
        @empty
        <div class="text-center">No Jobs Found</div>
        @endforelse

    </div>

    <!-- PAGINATION -->
    <div class="mt-4">
        {{ $myJobs->links('vendor.pagination.frontend') }}
    </div>

</div>

@endsection