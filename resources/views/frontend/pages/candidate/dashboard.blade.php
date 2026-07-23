@extends('components.website.candidate.layout.app')

@section('title','Dashboard')

@section('css')
<style>
.employer-kpi {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 14px 16px;
    transition: box-shadow .15s ease, border-color .15s ease;
}
.employer-kpi:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}
.employer-kpi h6 { margin: 0 0 4px; font-size: 1.35rem; font-weight: 700; color: #0f172a; }
.employer-kpi small { color: #64748b; font-size: 0.8rem; }
.employer-kpi.kpi-primary { border-left: 3px solid #2563eb; }
.employer-kpi.kpi-warning { border-left: 3px solid #f59e0b; }
.employer-kpi.kpi-success { border-left: 3px solid #10b981; }
.employer-kpi.kpi-info { border-left: 3px solid #06b6d4; }
</style>
@endsection

@section('main')

@php
$percentage = $completionPercentage ?? ($candidate->profile_complete ?? 0);
$currentBio    = strip_tags($candidate->bio    ?? '');
$currentTitle  = $candidate->title  ?? '';
$currentStatus = $candidate->status ?? 'available';
$statusLabels  = [
    'available'     => 'Available',
    'not_available' => 'Not Available',
    'available_in'  => 'Available Soon',
];
$statusLabel = $statusLabels[$currentStatus] ?? 'Available';
@endphp

<div class="dashboard-wrapper seeker-settings-page">
<div class="container">
<div class="dashboard-right">

<x-website.candidate.seeker-page-header
    title="Welcome back, {{ auth()->user()->name }}"
    subtitle="Here's your profile and activity overview."
>
    <x-slot:actions>
        @if(!empty($candidate->public_code))
            <span class="profile-badge" title="Candidate code">{{ $candidate->public_code }}</span>
        @endif
        <span class="profile-badge">{{ number_format($percentage, 0) }}% complete</span>
        <a href="{{ route('candidate.setting') }}" class="pv-topbar-btn"><i class="fas fa-cog"></i> Settings</a>
        <a href="{{ route('candidate.profile.view') }}" class="pv-topbar-btn"><i class="fas fa-eye"></i> View Profile</a>
    </x-slot:actions>
</x-website.candidate.seeker-page-header>

<div class="glass-card">
<div class="glass-card-body">

    <x-website.candidate.profile-completion-hints />

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div class="d-flex gap-3 align-items-center">
            <img src="{{ $candidate->photo ?? asset('images/default-user.png') }}"
                 width="52" class="rounded-circle" style="height:52px;object-fit:cover">
            <div>
                <div class="title">{{ auth()->user()->name }}</div>
                <div id="view-meta">
                    @if($currentTitle)
                        <div class="sub">{{ $currentTitle }}</div>
                    @endif
                    <span class="status-badge status-{{ $currentStatus }} mt-1 d-inline-block" id="view-status-badge">
                        {{ $statusLabel }}
                    </span>
                </div>
                <div id="edit-meta" style="display:none">
                    <input type="text" id="edit-title" class="edit-field mt-1"
                           value="{{ $currentTitle }}" placeholder="Your job title"
                           style="max-width:260px">
                    <select id="edit-status" class="edit-field mt-1" style="max-width:200px">
                        <option value="available"     {{ $currentStatus==='available'     ? 'selected':'' }}>Available</option>
                        <option value="not_available" {{ $currentStatus==='not_available' ? 'selected':'' }}>Not Available</option>
                        <option value="available_in"  {{ $currentStatus==='available_in'  ? 'selected':'' }}>Available Soon</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button id="toggleEditBtn" class="btn-ghost" onclick="enterEditMode()">Edit</button>
            <a href="{{ route('candidate.setting') }}" class="btn-ui">Full Profile</a>
        </div>
    </div>

    <div id="view-bio" class="sub mb-2">
        @if($currentBio)
            {{ Str::limit($currentBio, 220) }}
        @else
            <em>Add a professional summary in Settings…</em>
        @endif
    </div>
    <div id="edit-bio" style="display:none" class="mb-2">
        <textarea id="edit-bio-text" class="edit-field" rows="4"
                  placeholder="Write a short professional summary…">{{ $currentBio }}</textarea>
    </div>
    <div id="edit-actions" style="display:none" class="d-flex gap-2 align-items-center">
        <button class="btn-ui" onclick="saveDashboard()">Save Changes</button>
        <button class="btn-ghost" onclick="cancelEdit()">Cancel</button>
        <span id="dashSaveMsg" style="display:none;color:#16a34a;font-size:0.8125rem;">Saved!</span>
        <span id="dashErrMsg" style="display:none;color:#dc2626;font-size:0.8125rem;">Save failed.</span>
    </div>

</div>
</div>

<div class="row g-3 mb-3">
    @php
        $seekerMetrics = [
            ['label' => 'Applied Jobs', 'value' => $statusCounts['all'] ?? ($appliedJobs ?? 0), 'class' => 'kpi-primary', 'url' => route('candidate.appliedjob')],
            ['label' => 'New Jobs', 'value' => $newJobs ?? 0, 'class' => 'kpi-info', 'url' => route('website.job')],
            ['label' => 'Pending', 'value' => $statusCounts['pending'] ?? 0, 'class' => 'kpi-warning', 'url' => route('candidate.appliedjob', ['status' => 'pending'])],
            ['label' => 'Shortlisted', 'value' => $statusCounts['shortlisted'] ?? 0, 'class' => 'kpi-success', 'url' => route('candidate.appliedjob', ['status' => 'shortlisted'])],
            ['label' => 'Interview', 'value' => $statusCounts['interview'] ?? 0, 'class' => 'kpi-primary', 'url' => route('candidate.appliedjob', ['status' => 'interview'])],
            ['label' => 'Selected', 'value' => $statusCounts['selected'] ?? 0, 'class' => 'kpi-success', 'url' => route('candidate.appliedjob', ['status' => 'selected'])],
            ['label' => 'Favorites', 'value' => $favoriteJobs ?? 0, 'class' => 'kpi-info', 'url' => route('candidate.bookmark')],
            ['label' => 'Unread Alerts', 'value' => $unreadNotifications ?? 0, 'class' => 'kpi-warning', 'url' => route('candidate.allNotification')],
        ];
    @endphp
    @foreach($seekerMetrics as $metric)
    <div class="col-xl-3 col-md-4 col-sm-6">
        <a href="{{ $metric['url'] }}" class="text-decoration-none d-block h-100">
            <div class="employer-kpi {{ $metric['class'] }} h-100">
                <h6>{{ $metric['value'] }}</h6>
                <small>{{ $metric['label'] }}</small>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="glass-card">
<div class="glass-card-body">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h3 class="mb-0">Recent Job Activity</h3>
        <a href="{{ route('candidate.appliedjob') }}" class="btn-ghost">All applications</a>
    </div>
    @forelse($jobs as $job)
    <div class="job-row d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <div class="fw-semibold">{{ $job->title ?? '' }}</div>
            <div class="sub">{{ $job->country ?? '' }}</div>
        </div>
        <div>
            <a href="{{ route('website.job.details', $job->slug ?? '#') }}" class="btn-ui">View</a>
        </div>
    </div>
    @empty
    <div class="text-center py-4">
        <a href="{{ route('website.job') }}" class="btn-ui">Browse Jobs</a>
    </div>
    @endforelse
</div>
</div>

</div>
</div>
</div>

<script>
const SAVE_URL    = "{{ route('candidate.dashboardUpdate') }}";
const CSRF        = "{{ csrf_token() }}";
const statusLabels = {
    available:     'Available',
    not_available: 'Not Available',
    available_in:  'Available Soon',
};

function enterEditMode() {
    document.getElementById('view-bio').style.display      = 'none';
    document.getElementById('view-meta').style.display     = 'none';
    document.getElementById('edit-bio').style.display      = 'block';
    document.getElementById('edit-meta').style.display     = 'block';
    document.getElementById('edit-actions').style.display  = 'flex';
    document.getElementById('toggleEditBtn').style.display = 'none';
    document.getElementById('dashSaveMsg').style.display   = 'none';
    document.getElementById('dashErrMsg').style.display    = 'none';
}

function cancelEdit() {
    document.getElementById('view-bio').style.display      = 'block';
    document.getElementById('view-meta').style.display     = 'block';
    document.getElementById('edit-bio').style.display      = 'none';
    document.getElementById('edit-meta').style.display     = 'none';
    document.getElementById('edit-actions').style.display  = 'none';
    document.getElementById('toggleEditBtn').style.display = 'inline-block';
}

function saveDashboard() {
    const bio    = document.getElementById('edit-bio-text').value.trim();
    const title  = document.getElementById('edit-title').value.trim();
    const status = document.getElementById('edit-status').value;
    const saveBtn = document.querySelector('#edit-actions .btn-ui');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving…';

    fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ bio, title, status }),
    })
    .then(r => r.json())
    .then(function(res) {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
        if (res.success) {
            document.getElementById('view-bio').textContent =
                res.bio ? res.bio.substring(0, 220) : 'Add a professional summary…';
            const titleEl = document.getElementById('view-meta').querySelector('.sub');
            if (titleEl) titleEl.textContent = res.title || '';
            const badge = document.getElementById('view-status-badge');
            badge.textContent = statusLabels[res.status] || 'Available';
            badge.className   = 'status-badge status-' + res.status + ' mt-1 d-inline-block';
            document.getElementById('dashSaveMsg').style.display = 'inline';
            setTimeout(() => {
                document.getElementById('dashSaveMsg').style.display = 'none';
                cancelEdit();
            }, 1500);
        } else {
            document.getElementById('dashErrMsg').style.display = 'inline';
        }
    })
    .catch(function() {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
        document.getElementById('dashErrMsg').style.display = 'inline';
    });
}
</script>

@endsection
