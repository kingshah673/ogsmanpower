@extends(request()->is('agency/*') ? 'components.website.agency.layout.app' : 'components.website.company.layout.app')
@section('title', $worker->full_name)
@section('main')
@php
    $nwRoutePrefix = $nwRoutePrefix ?? 'company.nominated-workers';
    $visaCase = $visaCase ?? $worker->activeVisaCase;
    $actorSide = $actorSide ?? (request()->is('agency/*') ? 'agency' : 'employer');
@endphp
<style>
    .liability-employer { background:#0d6efd; color:#fff; }
    .liability-agency { background:#198754; color:#fff; }
    .liability-seeker { background:#ffc107; color:#212529; }
    .liability-shared, .liability-government { background:#6f42c1; color:#fff; }
    .chip { font-size:11px; padding:2px 8px; border-radius:3px; }
    .vp-step { border-left:3px solid #dee2e6; padding-left:12px; margin-bottom:16px; }
    .vp-step.active { border-left-color:#0d6efd; }
    .vp-step.completed { border-left-color:#198754; }
</style>
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        @if($worker->batch_id)
            <a href="{{ route($nwRoutePrefix.'.batches.show', $worker->batch_id) }}">&larr; Batch</a>
        @else
            <a href="{{ route($nwRoutePrefix.'.index') }}">&larr; Nominated Workers</a>
        @endif
        <h3 class="mt-2">{{ $worker->full_name }}</h3>
        <p class="text-muted">{{ $worker->passport_number }} · {{ $worker->nationality }} · {{ $worker->destination_country }} · {{ str_replace('_',' ', $worker->status) }}</p>

        @if($visaCase)
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <span>Visa processing — {{ $visaCase->country_name }}</span>
                    <span class="badge bg-secondary">{{ $visaCase->progressPercent() }}%</span>
                </div>
                <div class="card-body">
                    @foreach($visaCase->steps as $step)
                        <div class="vp-step {{ $step->status }}">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <strong>#{{ $step->sort_order + 1 }} {{ $step->name }}</strong>
                                <span>
                                    <span class="chip liability-{{ $step->assignee }}">{{ \App\Support\VisaLiability::label($step->assignee) }}</span>
                                    <span class="badge bg-light text-dark">{{ $step->status }}</span>
                                </span>
                            </div>
                            @if($step->description)<p class="small text-muted mb-1">{{ $step->description }}</p>@endif

                            @if($step->status === 'active' && \App\Support\VisaLiability::actorCanAct($actorSide, $step->assignee))
                                <form method="POST" action="{{ route($nwRoutePrefix.'.visa-step', $worker) }}" enctype="multipart/form-data" class="mt-2 border rounded p-3 bg-light">
                                    @csrf
                                    @foreach($step->requirements as $req)
                                        <div class="mb-2">
                                            <label class="form-label small mb-0">{{ $req->label }}@if($req->is_required) * @endif</label>
                                            @if($req->type === 'file')
                                                <input type="file" name="files[{{ $req->id }}]" class="form-control form-control-sm" @if($req->is_required && ! $req->file) required @endif>
                                                @if($req->file)<div class="small text-success">Uploaded: {{ $req->file->original_name }}</div>@endif
                                            @elseif($req->type === 'checkbox')
                                                <div><label><input type="checkbox" name="answers[{{ $req->id }}]" value="1"> Confirm</label></div>
                                            @elseif($req->type === 'date')
                                                <input type="date" name="answers[{{ $req->id }}]" class="form-control form-control-sm" @if($req->is_required) required @endif>
                                            @else
                                                <input type="text" name="answers[{{ $req->id }}]" class="form-control form-control-sm" @if($req->is_required) required @endif>
                                            @endif
                                        </div>
                                    @endforeach
                                    <button class="btn btn-sm btn-primary">Complete step</button>
                                </form>
                            @elseif($step->status === 'active')
                                <p class="small text-muted mb-0 mt-1">Waiting on {{ \App\Support\VisaLiability::label($step->assignee) }}.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($worker->batch && $worker->batch->status === 'active')
            <div class="alert alert-info">Visa case will appear once processing has started for this worker.</div>
        @endif

        <form method="POST" action="{{ route($nwRoutePrefix.'.documents') }}" enctype="multipart/form-data" class="card card-body mb-4" style="max-width:480px;">
            @csrf
            @if($worker->batch_id)
                <input type="hidden" name="batch_id" value="{{ $worker->batch_id }}">
            @endif
            <input type="hidden" name="nominated_worker_id" value="{{ $worker->id }}">
            <label class="form-label">Attach documents</label>
            <input type="file" name="documents[]" class="form-control mb-2" multiple required>
            <button class="btn btn-primary btn-sm">Upload</button>
        </form>

        <div class="list-group">
            @forelse($worker->documents as $doc)
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>{{ $doc->original_name }}</strong>
                        <div class="small text-muted">
                            Match:
                            @if($doc->match_status === 'matched')
                                <span class="badge bg-success">matched</span>
                            @elseif($doc->match_status === 'suggested')
                                <span class="badge bg-warning text-dark">suggested</span>
                            @else
                                <span class="badge bg-secondary">unmatched</span>
                            @endif
                            @if($doc->match_confidence) ({{ $doc->match_confidence }}%) @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route($nwRoutePrefix.'.rematch', $doc) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">Re-run AI match</button>
                    </form>
                </div>
            @empty
                <div class="list-group-item text-muted">No documents yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
