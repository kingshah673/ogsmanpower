@extends('components.website.agency.layout.app')

@section('title', __('Dashboard'))

@section('main')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body {
        background: #f5f7fb;
    }

    /* GENERAL CARD */
    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        transition: 0.3s ease;
    }

    .card:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
    }

    .glass-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
    }

    /* KPI SMALL */
    .kpi-card-small {
        border-radius: 14px;
        padding: 12px 14px;
        color: #fff;
        font-size: 13px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.05);
        transition: 0.25s ease;
    }

    .kpi-card-small:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }

    .kpi-card-small h6 {
        font-size: 18px;
    }

    .kpi-card-small small {
        font-size: 11px;
    }

    /* GRADIENTS */
    .gradient-primary { background: linear-gradient(135deg, #6366F1, #4F46E5); }
    .gradient-warning { background: linear-gradient(135deg, #F59E0B, #D97706); }
    .gradient-success { background: linear-gradient(135deg, #10B981, #059669); }
    .gradient-info    { background: linear-gradient(135deg, #06B6D4, #0891B2); }

    /* TABLE */
    .table thead {
        background: #f1f5f9;
    }

    .badge {
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
    }

    .header-avatar {
        border: 3px solid #fff;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    /* PROMO CARD */
    .promo-card {
        height: 100%;
        border-radius: 18px;
        padding: 25px;
        background: linear-gradient(135deg, #4F46E5, #6366F1);
        color: #fff;
        position: relative;
        box-shadow: 0 15px 35px rgba(99,102,241,0.25);
        overflow: hidden;
    }

    .promo-content {
        position: relative;
        z-index: 2;
    }

    .promo-img {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 90px;
        opacity: 0.9;
    }

    .circle {
        position: absolute;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
    }

    .circle.one {
        width: 120px;
        height: 120px;
        top: -30px;
        right: -30px;
    }

    .circle.two {
        width: 80px;
        height: 80px;
        bottom: -20px;
        left: -20px;
    }

    .mini-box {
        background: #f8fafc;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        transition: 0.2s;
    }

    .mini-box:hover {
        background: #eef2ff;
        transform: translateY(-3px);
    }

    .mini-box h6 {
        margin: 0;
        font-weight: 700;
    }
    .stat-box{
    background:#f8fafc;
    border-radius:12px;
    padding:20px;
    text-align:center;
    transition:.2s;
}
.stat-box:hover{
    background:#eef2ff;
    transform:translateY(-3px);
}

.activity-list{
    list-style:none;
    padding:0;
    margin:0;
}
.activity-list li{
    padding:8px 0;
    border-bottom:1px solid #eee;
    font-size:14px;
}
.activity-list li:last-child{
    border:none;
}
/* TABLE */
.modern-table thead th{
    font-size:12px;
    text-transform:uppercase;
    color:#6b7280;
    border-bottom:1px solid #e5e7eb;
}

.modern-table tbody tr{
    transition:0.2s;
}

.modern-table tbody tr:hover{
    background:#f9fafb;
}

/* JOB ICON */
.job-icon{
    width:40px;
    height:40px;
    border-radius:10px;
    background:#eef2ff;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#4F46E5;
}

/* STATUS */
.status-badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:500;
}

.status-badge.active{
    background:#dcfce7;
    color:#166534;
}

.status-badge.pending{
    background:#fef3c7;
    color:#92400e;
}

.status-badge.expired{
    background:#fee2e2;
    color:#991b1b;
}

/* APPLICATION COUNT */
.app-count{
    font-weight:600;
    font-size:15px;
}

/* ACTIONS */
.action-group{
    display:flex;
    justify-content:flex-end;
    gap:8px;
}
</style>

<div class="container-fluid py-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold">Hello, {{ ucfirst(auth()->user()->name) }}</h4>
            <p class="text-muted mb-0">{{ __('Your dashboard overview') }}</p>
        </div>

        <div class="d-flex align-items-center gap-4">
            <div class="position-relative">
                <i class="fas fa-bell fa-lg"></i>
                <span class="badge bg-danger position-absolute top-0 start-100 translate-middle">
                    {{ $unreadNotifications ?? 0 }}
                </span>
            </div>
            <img src="{{ asset('images/Admin_logo.jpeg') }}" class="rounded-circle header-avatar" width="50">
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
        @php
            $metrics = [
                ['label'=>__('Open Jobs'), 'value'=>$openJobCount, 'trend'=>0, 'class'=>'gradient-primary'],
                ['label'=>__('Pending Jobs'), 'value'=>$pendingJobCount, 'trend'=>0, 'class'=>'gradient-warning'],
                ['label'=>__('Saved Candidates'), 'value'=>$savedCandidates, 'trend'=>0, 'class'=>'gradient-success'],
                ['label'=>__('Applicants'), 'value'=>$applicants, 'trend'=>$trends['applicants']['percent'] ?? 0, 'class'=>'gradient-info'],
            ];
        @endphp

        @foreach($metrics as $metric)
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card-small {{ $metric['class'] }}">
                <div class="d-flex justify-content-between align-items-center">
                    
                    <div>
                        <h6 class="mb-1 fw-bold">{{ $metric['value'] }}</h6>
                        <small class="opacity-75">{{ $metric['label'] }}</small>

                        <div class="mt-1 small">
                            @if($metric['trend'] >= 0)
                                <i class="fas fa-arrow-up"></i> {{ $metric['trend'] }}%
                            @else
                                <i class="fas fa-arrow-down"></i> {{ abs($metric['trend']) }}%
                            @endif
                        </div>
                    </div>

                    <div style="width:70px;">
                        <canvas id="sparkline{{ $loop->index }}" height="40"></canvas>
                    </div>

                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- PROMO + QUICK OVERVIEW -->
    <div class="row g-4 mb-4">

        <div class="col-lg-4">
            <div class="promo-card">

                <div class="promo-content">
                    <h5 class="fw-bold mb-2">Boost Your Hiring</h5>
                    <p class="small opacity-75 mb-3">
                        Promote your jobs and reach top candidates faster.
                    </p>

                    <a href="#" class="btn btn-light btn-sm fw-semibold">
                        Promote Now
                    </a>
                </div>

                <div class="circle one"></div>
                <div class="circle two"></div>

                <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="promo-img">
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card glass-card p-4 h-100">
                <h6 class="fw-semibold mb-3">Quick Overview</h6>

                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ $openJobCount }}</h6>
                            <small>Active Jobs</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ $applicants }}</h6>
                            <small>Total Applicants</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ $savedCandidates }}</h6>
                            <small>Saved Profiles</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ number_format(($commissionTotals['pending'] ?? 0) + ($commissionTotals['approved'] ?? 0), 0) }}</h6>
                            <small>Commission Owed</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ $visaCaseCounts['in_progress'] ?? 0 }}</h6>
                            <small>Visa Cases In Progress</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mini-box">
                            <h6>{{ $protectorPending ?? 0 }}</h6>
                            <small>Protector Pending</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    @if(Route::has('agency.reports.index'))
                        <a href="{{ route('agency.reports.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="ph-chart-bar"></i> View Reports
                        </a>
                    @endif
                    @if(Route::has('agency.commissions.index'))
                        <a href="{{ route('agency.commissions.index') }}" class="btn btn-sm btn-outline-success">
                            <i class="ph-currency-circle-dollar"></i> Commissions
                        </a>
                    @endif
                </div>

            </div>
        </div>

    </div>

    <!-- PERFORMANCE + INSIGHTS (NO CHARTS) -->
<div class="row g-4 mb-4">

    <!-- MAIN PERFORMANCE -->
    <div class="col-lg-8">
        <div class="card p-4 h-100">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-semibold mb-0">Application Performance</h6>
                <span class="badge bg-light text-dark">Live Stats</span>
            </div>

            <div class="row g-3">

                <div class="col-md-4">
                    <div class="stat-box">
                        <h4>{{ array_sum($chartCounts ?? []) }}</h4>
                        <small>Total Applications</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <h4>{{ count($chartCounts ?? []) }}</h4>
                        <small>Active Days</small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="stat-box">
                        <h4>
                            {{ !empty($chartCounts) ? round(array_sum($chartCounts)/count($chartCounts)) : 0 }}
                        </h4>
                        <small>Avg / Day</small>
                    </div>
                </div>

            </div>

            <!-- RECENT ACTIVITY -->
            <div class="mt-4">
                <h6 class="fw-semibold mb-2">Recent Activity</h6>

                <ul class="activity-list">
                    <li>New application received</li>
                    <li>Job status updated</li>
                    <li>Candidate shortlisted</li>
                    <li>New job posted</li>
                </ul>
            </div>

        </div>
    </div>

    <!-- SIDE INSIGHTS -->
    <div class="col-lg-4">

        <!-- TOP COUNTRIES -->
        <div class="card p-3 mb-4">
            <h6 class="fw-semibold mb-3">Top Countries</h6>

            @foreach(($countryNames ?? []) as $index => $country)
            <div class="d-flex justify-content-between mb-2">
                <span>{{ $country }}</span>
                <strong>{{ $countryApplications[$index] ?? 0 }}</strong>
            </div>
            @endforeach

        </div>

        <!-- GENDER -->
        <div class="card p-3">
            <h6 class="fw-semibold mb-3">Gender Distribution</h6>

            @foreach(($genderLabels ?? []) as $i => $label)
            <div class="d-flex justify-content-between mb-2">
                <span>{{ $label }}</span>
                <strong>{{ $genderCounts[$i] ?? 0 }}</strong>
            </div>
            @endforeach

        </div>

    </div>

</div>

    <!-- PROFESSIONAL JOB TABLE -->
<div class="card p-4">

    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="mb-0">{{ __('Recent Jobs') }}</h5>
            <small class="text-muted">Manage and track your job listings</small>
        </div>

        <div class="d-flex gap-2">
            <input class="form-control form-control-sm" style="width:200px;" placeholder="Search jobs...">
        </div>
    </div>

    <div class="table-responsive">

        <table class="table align-middle modern-table">

            <thead>
                <tr>
                    <th>{{ __('Job') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Applications') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>

            <tbody>

                @forelse($recentJobs as $job)
                <tr>

                    <!-- JOB -->
                    <td>
                        <div class="d-flex align-items-center gap-3">

                            <div class="job-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>

                            <div>
                                <div class="fw-semibold">
                                    {{ Str::limit($job->title, 40) }}
                                </div>
                                <small class="text-muted">
                                    ID: #{{ $job->id }}
                                </small>
                            </div>

                        </div>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <span class="status-badge 
                            {{ $job->status == 'active' ? 'active' : ($job->status=='pending'? 'pending' : 'expired') }}">
                            {{ ucfirst($job->status) }}
                        </span>
                    </td>

                    <!-- APPLICATIONS -->
                    <td>
                        <div class="app-count">
                            {{ $job->applied_jobs_count }}
                        </div>
                    </td>

                    <!-- ACTIONS -->
                    <td class="text-end">
                        <div class="action-group">

                            <a href="{{ route('agency.job.application', ['job' => $job->id]) }}"
                               class="btn btn-sm btn-dark">
                                View
                            </a>

                            <a href="{{ route('agency.promote', $job->slug) }}"
                               class="btn btn-sm btn-outline-warning">
                                Promote
                            </a>

                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-5">
                        No Jobs Found
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>

    </div>

</div>

</div>

<script>
const dates = @json($chartDates);
const counts = @json($chartCounts);
const countryLabels = @json($countryNames);
const countryData = @json($countryApplications);
const genderLabels = @json($genderLabels);
const genderData = @json($genderCounts);

// LINE CHART
new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dates,
        datasets:[{
            data: counts,
            borderColor:'#6366F1',
            backgroundColor:'rgba(99,102,241,0.15)',
            fill:true,
            tension:0.4
        }]
    },
    options:{ plugins:{legend:{display:false}} }
});

// PIE
new Chart(document.getElementById('applicationsByCountryChart'), {
    type:'pie',
    data:{labels:countryLabels,datasets:[{data:countryData}]}
});

// DOUGHNUT
new Chart(document.getElementById('genderChart'), {
    type:'doughnut',
    data:{labels:genderLabels,datasets:[{data:genderData}]}
});

// SPARKLINES
@foreach($metrics as $index => $metric)
new Chart(document.getElementById('sparkline{{ $index }}'), {
    type:'line',
    data:{
        labels:dates,
        datasets:[{
            data:counts,
            borderColor:'#fff',
            fill:false,
            tension:0.4,
            pointRadius:0
        }]
    },
    options:{
        plugins:{legend:{display:false}},
        scales:{x:{display:false},y:{display:false}}
    }
});
@endforeach
</script>

@endsection