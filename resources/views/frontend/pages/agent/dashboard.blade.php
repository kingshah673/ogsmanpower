@extends('components.website.agent.new-sidebar')
@php
$totalWorkers = $totalWorkers ?? 0;
$activeJobs = $activeJobs ?? 0;
$selected = $selected ?? 0;
$pending = $pending ?? 0;
$earnings = $earnings ?? 0;

$pipeline = $pipeline ?? [
    'submitted' => 0,
    'shortlisted' => 0,
    'interview' => 0,
    'selected' => 0,
    'deployed' => 0,
];

$workers = $workers ?? collect();
$notifications = $notifications ?? collect();
@endphp
@section('main')

<style>
    body {
        background: #f5f7fb;
    }

    .dashboard-header h4 {
        font-weight: 700;
    }

    .card-modern {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: 0.3s;
    }

    .card-modern:hover {
        transform: translateY(-3px);
    }

    .stat-card {
        padding: 20px;
        text-align: center;
        border-radius: 16px;
        color: white;
    }

    .bg-gradient-blue {
        background: linear-gradient(135deg, #4facfe, #00f2fe);
    }

    .bg-gradient-purple {
        background: linear-gradient(135deg, #667eea, #764ba2);
    }

    .bg-gradient-green {
        background: linear-gradient(135deg, #43e97b, #38f9d7);
    }

    .bg-gradient-orange {
        background: linear-gradient(135deg, #fa709a, #fee140);
    }

    .bg-gradient-dark {
        background: linear-gradient(135deg, #2b5876, #4e4376);
    }

    .pipeline-step {
        flex: 1;
        text-align: center;
        position: relative;
    }

    .pipeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        top: 20px;
        right: -50%;
        width: 100%;
        height: 4px;
        background: #e0e6ed;
        z-index: 0;
    }

    .pipeline-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4facfe;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: auto;
        font-weight: bold;
        z-index: 2;
        position: relative;
    }

    .table-modern thead {
        background: #f1f3f9;
    }

    .table-modern tbody tr:hover {
        background: #f9fbff;
    }

    .notification-box {
        border-left: 4px solid #4facfe;
        background: #f9fbff;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 10px;
    }
</style>

<div class="container mt-4">

    {{-- HEADER --}}
    <div class="dashboard-header mb-4">
        <h4>Hello, {{ auth()->user()->name }} 👋</h4>
        <p class="text-muted">Your premium recruitment dashboard overview</p>
    </div>

    {{-- STATS --}}
    <div class="row mb-4">

        <div class="col-md-2">
            <div class="card-modern stat-card bg-gradient-blue">
                <h6>Total Candidates</h6>
                <h3>{{ $totalWorkers }}</h3>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-modern stat-card bg-gradient-purple">
                <h6>Active Jobs</h6>
                <h3>{{ $activeJobs }}</h3>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-modern stat-card bg-gradient-green">
                <h6>Selected</h6>
                <h3>{{ $selected }}</h3>
            </div>
        </div>

        <div class="col-md-2">
            <div class="card-modern stat-card bg-gradient-orange">
                <h6>Pending</h6>
                <h3>{{ $pending }}</h3>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-modern stat-card bg-gradient-dark">
                <h6>Total Earnings</h6>
                <h3>{{ number_format($earnings,2) }}</h3>
            </div>
        </div>

    </div>

    {{-- PIPELINE --}}
    <div class="card-modern p-4 mb-4">
        <h5 class="mb-4">Recruitment Pipeline</h5>

        <div class="d-flex justify-content-between align-items-center">

            @foreach($pipeline as $key => $value)
            <div class="pipeline-step">
                <div class="pipeline-circle">{{ $value }}</div>
                <small class="mt-2 d-block text-muted">{{ ucfirst($key) }}</small>
            </div>
            @endforeach

        </div>
    </div>

    <div class="row">

        {{-- WORKERS --}}
        <div class="col-md-8">
            <div class="card-modern p-4 mb-4">
                <div class="d-flex justify-content-between mb-3">
                    <h5>Workers Status</h5>
                    <input type="text" class="form-control w-25" placeholder="Search...">
                </div>

                <div class="table-responsive">
                    <table class="table table-modern align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Passport</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($workers as $w)
                            <tr>
                                <td><strong>{{ $w->name }}</strong></td>
                                <td>{{ $w->passport_no }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ ucfirst($w->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    No records found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- NOTIFICATIONS --}}
        <div class="col-md-4">
            <div class="card-modern p-4">
                <h5 class="mb-3">Notifications</h5>

                @forelse($notifications as $n)
                    <div class="notification-box">
                        {{ $n->title }}
                    </div>
                @empty
                    <p class="text-muted">No new notifications</p>
                @endforelse

            </div>
        </div>

    </div>

</div>

@endsection