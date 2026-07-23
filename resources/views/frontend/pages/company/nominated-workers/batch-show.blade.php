@extends(request()->is('agency/*') ? 'components.website.agency.layout.app' : 'components.website.company.layout.app')
@section('title', $batch->name)
@section('main')
@php
    $nwRoutePrefix = $nwRoutePrefix ?? 'company.nominated-workers';
    $agencyMode = $agencyMode ?? false;
    $canEditWorkers = ! $agencyMode && $batch->isEditableByEmployer();
@endphp
<style>
    .liability-employer { background:#0d6efd; color:#fff; }
    .liability-agency { background:#198754; color:#fff; }
    .liability-seeker { background:#ffc107; color:#212529; }
    .liability-shared, .liability-government { background:#6f42c1; color:#fff; }
    .chip { font-size:11px; padding:2px 8px; border-radius:3px; display:inline-block; }
</style>
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        <a href="{{ route($nwRoutePrefix.'.index') }}">&larr; Back</a>
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-2 mb-3">
            <div>
                <h3 class="mb-1">{{ $batch->name }}</h3>
                <p class="text-muted mb-0">
                    {{ $batch->country_name }} ·
                    <span class="badge bg-secondary">{{ str_replace('_',' ', $batch->status) }}</span>
                    @if($batch->frozen_flow_version) · frozen v{{ $batch->frozen_flow_version }} @endif
                </p>
                @if($batch->admin_comment)
                    <div class="alert alert-warning mt-2 py-2">Admin: {{ $batch->admin_comment }}</div>
                @endif
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if(! $agencyMode && $batch->isEditableByEmployer())
                    <form method="POST" action="{{ route('company.nominated-workers.batches.submit', $batch) }}">
                        @csrf
                        <button class="btn btn-primary btn-sm" onclick="return confirm('Submit batch for admin approval?')">Submit for approval</button>
                    </form>
                @endif
                @if($agencyMode && $batch->status === 'awaiting_agency')
                    <form method="POST" action="{{ route('agency.nominated-workers.batches.respond', $batch) }}" class="d-inline">
                        @csrf
                        <input type="hidden" name="decision" value="accepted">
                        <button class="btn btn-success btn-sm">Accept batch</button>
                    </form>
                    <form method="POST" action="{{ route('agency.nominated-workers.batches.respond', $batch) }}" class="d-inline-flex gap-1">
                        @csrf
                        <input type="hidden" name="decision" value="declined">
                        <input name="reason" class="form-control form-control-sm" placeholder="Decline reason" required>
                        <button class="btn btn-outline-danger btn-sm">Decline</button>
                    </form>
                @endif
            </div>
        </div>

        @if($batch->flow)
            <div class="card mb-4">
                <div class="card-header">Visa liability template — {{ $batch->flow->country_name }}</div>
                <div class="card-body">
                    <ol class="mb-0 ps-3">
                        @foreach($batch->flow->activeSteps as $step)
                            <li class="mb-2">
                                <strong>{{ $step->name }}</strong>
                                <span class="chip liability-{{ $step->assignee }}">{{ \App\Support\VisaLiability::label($step->assignee) }}</span>
                                @if($step->description)<div class="small text-muted">{{ $step->description }}</div>@endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        @else
            <div class="alert alert-warning">No published visa flow attached yet for this country.</div>
        @endif

        @if($canEditWorkers)
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header">Add worker</div>
                        <form method="POST" action="{{ route($nwRoutePrefix.'.store') }}" class="card-body">
                            @csrf
                            <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                            <div class="mb-2"><input name="full_name" class="form-control" placeholder="Full name *" required></div>
                            <div class="mb-2"><input name="passport_number" class="form-control" placeholder="Passport number"></div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <select name="nationality" class="form-control">
                                        <option value="">Nationality</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->name }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 mb-2"><input type="date" name="date_of_birth" class="form-control"></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <x-forms.intl-phone-input name="phone" optional placeholder="Phone" class="form-control" />
                                </div>
                                <div class="col-md-6 mb-2"><input name="email" type="email" class="form-control" placeholder="Email"></div>
                            </div>
                            <input type="hidden" name="destination_country" value="{{ $batch->country_name }}">
                            <div class="mb-2"><input name="job_title" class="form-control" placeholder="Job title"></div>
                            <button class="btn btn-primary btn-sm">Save worker</button>
                        </form>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header">Batch CSV import</div>
                        <form method="POST" action="{{ route($nwRoutePrefix.'.import') }}" enctype="multipart/form-data" class="card-body">
                            @csrf
                            <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                            <p class="small text-muted">CSV headers: full_name, passport_number, nationality, date_of_birth, phone, email, job_title</p>
                            <input type="file" name="batch_file" accept=".csv,text/csv" class="form-control mb-2" required>
                            <button class="btn btn-outline-primary btn-sm">Import</button>
                        </form>
                    </div>
                    <div class="card">
                        <div class="card-header">Upload documents (AI match)</div>
                        <form method="POST" action="{{ route($nwRoutePrefix.'.documents') }}" enctype="multipart/form-data" class="card-body">
                            @csrf
                            <input type="hidden" name="batch_id" value="{{ $batch->id }}">
                            <input type="file" name="documents[]" class="form-control mb-2" multiple required>
                            <button class="btn btn-outline-success btn-sm">Upload &amp; match</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Passport</th>
                        <th>Status</th>
                        <th>Visa stage</th>
                        <th>Docs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batch->workers as $worker)
                        @php $active = optional($worker->activeVisaCase)->activeStep(); @endphp
                        <tr>
                            <td>{{ $worker->full_name }}</td>
                            <td>{{ $worker->passport_number ?: '—' }}</td>
                            <td>{{ str_replace('_',' ', $worker->status) }}</td>
                            <td>
                                @if($active)
                                    {{ $active->name }}
                                    <span class="chip liability-{{ $active->assignee }}">{{ \App\Support\VisaLiability::label($active->assignee) }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $worker->documents_count }}</td>
                            <td><a href="{{ route($nwRoutePrefix.'.show', $worker) }}" class="btn btn-sm btn-primary">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No workers in this batch yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
