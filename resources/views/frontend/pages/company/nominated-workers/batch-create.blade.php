@extends('components.website.company.layout.app')
@section('title', 'Create Nominated Batch')
@section('main')
@php $defaultDestinationCountry = $defaultDestinationCountry ?? null; @endphp
<div class="dashboard-wrapper">
    <div class="container-fluid py-4" style="max-width:720px;">
        <a href="{{ route('company.nominated-workers.index') }}">&larr; Batches</a>
        <h3 class="mt-2 mb-3">Create Nominated Worker Batch</h3>
        <form method="POST" action="{{ route('company.nominated-workers.batches.store') }}" class="card card-body">
            @csrf
            <div class="mb-3">
                <label class="form-label">Batch name *</label>
                <input name="name" class="form-control" required value="{{ old('name') }}" placeholder="e.g. Dubai Site A — 50 workers">
            </div>
            <div class="mb-3">
                <label class="form-label">Destination country *</label>
                <select name="search_country_id" class="form-control" required id="batchCountry">
                    <option value="">Select country</option>
                    @foreach($countries as $country)
                        <option value="{{ $country->id }}"
                            @selected((string) old('search_country_id') === (string) $country->id || ($defaultDestinationCountry && $defaultDestinationCountry === $country->name))>
                            {{ $country->name }}@if($country->short_name) ({{ $country->short_name }})@endif
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Loads the published visa liability template for this country.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Linked job (optional)</label>
                <select name="job_id" class="form-control">
                    <option value="">—</option>
                    @foreach($jobs as $job)
                        <option value="{{ $job->id }}" @selected((string) old('job_id') === (string) $job->id)>{{ $job->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Agency assignment *</label>
                <select name="assignment_mode" class="form-control" id="assignmentMode" required>
                    <option value="open_all" @selected(old('assignment_mode', 'open_all') === 'open_all')>Open to all approved agencies</option>
                    <option value="direct" @selected(old('assignment_mode') === 'direct')>Direct assign to one agency</option>
                </select>
            </div>
            <div class="mb-3" id="agencyPick" style="display:none;">
                <label class="form-label">Agency *</label>
                <select name="agency_id" class="form-control">
                    <option value="">Select agency</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" @selected((string) old('agency_id') === (string) $agency->id)>
                            {{ $agency->name ?? $agency->user?->name ?? ('Agency #'.$agency->id) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-primary">Create batch</button>
        </form>
    </div>
</div>
<script>
    (function () {
        const mode = document.getElementById('assignmentMode');
        const pick = document.getElementById('agencyPick');
        function sync() { pick.style.display = mode.value === 'direct' ? '' : 'none'; }
        mode.addEventListener('change', sync); sync();
    })();
</script>
@endsection
