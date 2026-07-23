@extends('components.website.agency.layout.app')

@section('title', __('Applications Dashboard'))

@section('frontend_links')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
/* Global */
body { font-family: "Helvetica Neue", Helvetica, Arial, sans-serif; background: #f4f6f8; }

/* Metrics Cards */
.dashboard-metrics { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 25px; }
.metric-card { flex: 1; min-width: 140px; background: #fff; border-radius: 10px; padding: 15px 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform .2s; }
.metric-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
.metric-card .title { font-size: 14px; font-weight: 600; color: #555; margin-bottom: 5px; }
.metric-card .count { font-size: 28px; font-weight: 700; color: #222; }

/* Colors */
.applicants { border-left: 5px solid #0073b1; }
.shortlisted { border-left: 5px solid #33cc33; }
.interview { border-left: 5px solid #ff9900; }
.rejected { border-left: 5px solid #ff3333; }

/* Filters */
.filters { background: #fff; border-radius: 10px; padding: 15px 20px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
.filters label { font-weight: 600; }
.filters .form-control { margin-bottom: 10px; }

/* Candidate Cards */
.candidate-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 20px; }
.candidate-card { background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); transition: transform .2s; cursor: pointer; position: relative; }
.candidate-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
.candidate-card .card-body { padding: 15px; }
.profile-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; }
.profile-info h5 { margin: 0; font-weight: 600; }
.profile-info small { color: #666; }

/* Status Badge */
.status-badge { position: absolute; top: 12px; right: 12px; font-size: 0.7rem; padding: 5px 8px; border-radius: 6px; color: #fff; text-transform: uppercase; font-weight: 600; }
.status-badge.selected { background: #ff9900; }
.status-badge.shortlisted { background: #33cc33; }
.status-badge.rejected { background: #ff3333; }
.status-badge.pending { background: #999; }

/* Download CV Button */
.download-btn { font-size: 0.8rem; padding: 5px 10px; border-radius: 6px; margin-top: 5px; display: inline-block; }

</style>
@endsection

@section('main')
<div class="container mt-4">

    <!-- Top Metrics -->
    <div class="dashboard-metrics">
        <div class="metric-card applicants">
            <div class="title">Applicants</div>
            <div class="count">{{$rejected}}</div>
        </div>
        <div class="metric-card shortlisted">
            <div class="title">Shortlisted</div>
            <div class="count">{{$shortlisted}}</div>
        </div>
        <div class="metric-card interview">
            <div class="title">Selected</div>
            <div class="count">{{$selected}}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters">
        <form action="{{ route('agency.job.application') }}" method="get" class="row">
            @csrf
            <div class="col-md-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="{{ request('name') }}">
            </div>
            <div class="col-md-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">-- Select Gender --</option>
                    <option value="male" {{ request('gender')=='male'?'selected':'' }}>Male</option>
                    <option value="female" {{ request('gender')=='female'?'selected':'' }}>Female</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Country</label>
                <select name="country" class="form-control">
                    <option value="">-- Select Country --</option>
                    @foreach($countries as $country)
                        <option value="{{$country->name}}" {{ request('country')==$country->name?'selected':'' }}>{{$country->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>Education</label>
                <select name="education" class="form-control">
                    <option value="">-- Select Education --</option>
                    @foreach($educations as $education)
                        <option value="{{$education->name}}" {{ request('education')==$education->name?'selected':'' }}>{{$education->name}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 mt-3">
                <button class="btn btn-primary">Search</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm('{{ $job->id }}')">Reset</button>
            </div>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="filters d-flex align-items-center gap-3 flex-wrap">
        <strong><span id="bulkSelectedCount">0</span> selected</strong>
        <select id="bulkStatusSelect" class="form-control" style="max-width:220px;">
            <option value="">-- Set status to --</option>
            <option value="shortlisted">Shortlisted</option>
            <option value="selected">Selected</option>
            <option value="rejected">Rejected</option>
            <option value="pending">Pending</option>
        </select>
        <button type="button" class="btn btn-primary" onclick="applyBulkStatus()">Apply to Selected</button>
    </div>

    <form id="bulkStatusForm" method="POST" action="{{ route('agency.applications.bulk.status') }}">
        @csrf
        <input type="hidden" name="status" id="bulkStatusInput">
    </form>

    <!-- Candidate Grid -->
    <div class="candidate-grid">
        @foreach($application_groups as $group)
            @foreach($group->applications as $app)
                @if($app->candidate)
                    <div class="candidate-card" onclick="window.location='{{ route('agency.application.detail',['candidate_id'=>$app->candidate->id,'job_id'=>$app->job_id]) }}'">
                        <input type="checkbox" class="bulk-select-checkbox" value="{{ $app->id }}"
                               onclick="event.stopPropagation(); updateBulkSelectedCount();"
                               style="position:absolute; top:12px; left:12px; width:18px; height:18px; z-index:2;">
                        <div class="status-badge {{ $app->status }}">{{ ucfirst($app->status) }}</div>
                        <div class="card-body d-flex align-items-center">
                            <img src="{{ $app->candidate->user->image_url ?? 'default.jpg' }}" class="profile-img me-3" alt="Candidate">
                            <div class="profile-info">
                                <h5>{{ $app->candidate->user->name ?? '-' }}</h5>
                                <small>{{ $app->candidate->profession->name ?? '-' }}</small>
                                <ul class="list-unstyled mb-0 mt-1">
                                    @if($app->candidate->experience)
                                        <li><strong>Exp:</strong> {{$app->candidate->experience->name}}</li>
                                    @endif
                                    @if($app->candidate->education)
                                        <li><strong>Edu:</strong> {{$app->candidate->education->name}}</li>
                                    @endif
                                </ul>
                                @if($app->candidate_resume_id)
                                    <a href="{{ route('website.candidate.download.cv', $app->candidate_resume_id) }}" class="btn btn-primary btn-sm download-btn">Download CV</a>
                                @elseif($app->candidate_id && isset($job))
                                    <a href="{{ route('agency.download.applicant.resume', [$app->candidate_id, $job->id]) }}" class="btn btn-primary btn-sm download-btn">Download CV</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endforeach
    </div>
</div>

<script>
function resetForm(jobId){
    window.location.href = "{{ route('agency.job.application') }}?job=" + jobId;
}

function updateBulkSelectedCount(){
    const count = document.querySelectorAll('.bulk-select-checkbox:checked').length;
    document.getElementById('bulkSelectedCount').innerText = count;
}

function applyBulkStatus(){
    const ids = Array.from(document.querySelectorAll('.bulk-select-checkbox:checked')).map(el => el.value);
    const status = document.getElementById('bulkStatusSelect').value;

    if (ids.length === 0) {
        alert('Select at least one candidate first.');
        return;
    }
    if (!status) {
        alert('Choose a status to apply.');
        return;
    }

    const form = document.getElementById('bulkStatusForm');
    document.getElementById('bulkStatusInput').value = status;

    ids.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'application_ids[]';
        input.value = id;
        form.appendChild(input);
    });

    form.submit();
}
</script>
@endsection