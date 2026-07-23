@extends('components.website.company.layout.app')

@section('title', __('Dashboard'))

@section('css')
<style>
.employer-kpi {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
}
.employer-kpi h6 { margin: 0 0 4px; font-size: 1.35rem; font-weight: 700; color: #0f172a; }
.employer-kpi small { color: #64748b; font-size: 0.8rem; }
.employer-kpi.kpi-primary { border-left: 3px solid #2563eb; }
.employer-kpi.kpi-warning { border-left: 3px solid #f59e0b; }
.employer-kpi.kpi-success { border-left: 3px solid #10b981; }
.employer-kpi.kpi-info { border-left: 3px solid #06b6d4; }
.inner-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
}
.panel-title { font-size: 0.9rem; font-weight: 600; color: #334155; margin-bottom: 12px; }
.mini-stat {
    display: flex; justify-content: space-between; align-items: center;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px;
}
.mini-stat span { color: #64748b; font-size: 0.8rem; }
.status-pill {
    display: inline-block; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;
}
.status-pill.status-active { background: #d1fae5; color: #065f46; }
.status-pill.status-pending { background: #fef3c7; color: #92400e; }
.status-pill.status-expired { background: #fee2e2; color: #b91c1c; }
.seeker-table thead th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
</style>
@endsection

@section('main')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
    $company = auth()->user()->company;
    $companyCompletion = $company->profile_completion ? 100 : (
        collect([
            filled($company->logo),
            filled($company->name ?? auth()->user()->name),
            filled($company->bio),
            filled($company->industry_type_id),
            filled($company->organization_type_id),
            optional(auth()->user()->contactInfo)->phone || optional(auth()->user()->contactInfo)->email,
        ])->filter()->count() * (100 / 6)
    );
@endphp

<div class="dashboard-wrapper seeker-module-page">
<div class="container">
<div class="dashboard-right">

<x-website.company.employer-page-header
    :title="'Welcome back, ' . auth()->user()->name"
    subtitle="Here's your hiring activity and job overview."
>
    <x-slot:actions>
        <span class="profile-badge">{{ number_format($companyCompletion, 0) }}% complete</span>
        <a href="{{ route('company.setting') }}" class="pv-topbar-btn"><i class="fas fa-cog"></i> Settings</a>
        <a href="{{ route('company.job.create') }}" class="pv-topbar-btn"><i class="fas fa-plus"></i> Post Job</a>
    </x-slot:actions>
</x-website.company.employer-page-header>

<div class="glass-card">
<div class="glass-card-body">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div class="d-flex gap-3 align-items-center">
            <img src="{{ $company->logo_url }}"
                 width="52" class="rounded-circle" style="height:52px;object-fit:cover">
            <div>
                <div class="title">{{ auth()->user()->name }}</div>
                <div class="sub">{{ optional($company->industry)->name ?? __('Employer account') }}</div>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('company.myjob') }}" class="btn-ghost">My Jobs</a>
            <a href="{{ route('company.pipeline') }}" class="btn-ui">Pipeline</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $metrics = [
                ['label' => __('Open Jobs'), 'value' => $openJobCount, 'class' => 'kpi-primary'],
                ['label' => __('Pending Jobs'), 'value' => $pendingJobCount, 'class' => 'kpi-warning'],
                ['label' => __('Saved Candidates'), 'value' => $savedCandidates, 'class' => 'kpi-success'],
                ['label' => __('Applicants'), 'value' => $applicants, 'class' => 'kpi-info'],
            ];
        @endphp
        @foreach($metrics as $metric)
        <div class="col-lg-3 col-md-6">
            <div class="employer-kpi {{ $metric['class'] }}">
                <h6>{{ $metric['value'] }}</h6>
                <small>{{ $metric['label'] }}</small>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="inner-panel">
                <h6 class="panel-title">{{ __('Daily Applications') }}</h6>
                <canvas id="dailyChart" height="120"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="inner-panel h-100">
                <h6 class="panel-title">{{ __('Quick Overview') }}</h6>
                <div class="row g-2">
                    <div class="col-12">
                        <div class="mini-stat"><strong>{{ $openJobCount }}</strong><span>Active Jobs</span></div>
                    </div>
                    <div class="col-12">
                        <div class="mini-stat"><strong>{{ $applicants }}</strong><span>Total Applicants</span></div>
                    </div>
                    <div class="col-12">
                        <div class="mini-stat"><strong>{{ $savedCandidates }}</strong><span>Saved Profiles</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="inner-panel text-center">
                <h6 class="panel-title">{{ __('Applications by Country') }}</h6>
                <canvas id="applicationsByCountryChart"></canvas>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="inner-panel text-center">
                <h6 class="panel-title">{{ __('Gender Distribution') }}</h6>
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <div class="inner-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h6 class="panel-title mb-0">{{ __('Recent Jobs') }}</h6>
            <a href="{{ route('company.myjob') }}" class="btn-ghost btn-sm">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table seeker-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Job') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Applications') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentJobs as $job)
                    <tr>
                        <td>{{ Str::limit($job->title, 40) }}</td>
                        <td>
                            <span class="status-pill status-{{ $job->status }}">{{ ucfirst($job->status) }}</span>
                        </td>
                        <td>{{ $job->applied_jobs_count }}</td>
                        <td class="d-flex gap-2">
                            <a href="{{ route('company.job.application', ['job' => $job->id]) }}" class="btn-ui btn-sm">View</a>
                            <a href="{{ route('company.promote', $job->slug) }}" class="btn-ghost btn-sm">Promote</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">No jobs found yet. <a href="{{ route('company.job.create') }}">Post your first job</a>.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

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

new Chart(document.getElementById('dailyChart'), {
    type: 'line',
    data: {
        labels: dates,
        datasets: [{
            data: counts,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.12)',
            fill: true,
            tension: 0.35
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('applicationsByCountryChart'), {
    type: 'pie',
    data: { labels: countryLabels, datasets: [{ data: countryData }] }
});

new Chart(document.getElementById('genderChart'), {
    type: 'doughnut',
    data: { labels: genderLabels, datasets: [{ data: genderData }] }
});
</script>

@endsection
