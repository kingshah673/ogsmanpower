@extends('backend.layouts.app')
@section('title', 'Edit Visa Flow')
@section('content')
@php
    $assignees = $assignees ?? \App\Support\VisaLiability::assignees();
@endphp
<style>
    .badge-purple { background-color: #6f42c1; color: #fff; }
    .liability-chip { font-size: 11px; padding: 3px 8px; border-radius: 3px; }
</style>
<div class="container-fluid">
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap">
        <a href="{{ route('admin.visa-flows.index') }}">&larr; All flows</a>
        <div class="d-flex" style="gap:8px;">
            <span class="badge badge-{{ ($flow->publish_status ?? 'published') === 'published' ? 'success' : 'secondary' }}">
                {{ strtoupper($flow->publish_status ?? 'published') }} · v{{ $flow->version ?? 1 }}
            </span>
            @if(($flow->publish_status ?? 'published') === 'published')
                <form method="POST" action="{{ route('admin.visa-flows.draft', $flow) }}">@csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit">Mark draft</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.visa-flows.publish', $flow) }}"
                onsubmit="return confirm('Publish this flow? Version will bump. Existing nominated batches keep their frozen version.')">
                @csrf
                <button class="btn btn-sm btn-success" type="submit">Publish flow</button>
            </form>
        </div>
    </div>

    <div class="alert alert-info py-2">
        Manage steps dynamically below. Liability colors:
        <span class="badge badge-primary liability-chip">Employer</span>
        <span class="badge badge-success liability-chip">Agency</span>
        <span class="badge badge-warning liability-chip">Seeker</span>
        <span class="badge badge-purple liability-chip">Shared / Government</span>
        — Published changes do not alter in-progress batches.
    </div>

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">{{ $flow->country_name }}</h3></div>
        <form method="POST" action="{{ route('admin.visa-flows.update', $flow) }}">
            @csrf @method('PUT')
            <div class="card-body row">
                <div class="form-group col-md-5">
                    <label>Country *</label>
                    @php
                        $selectedCountryId = old('search_country_id', $flow->search_country_id);
                        if (! $selectedCountryId && $flow->country_name) {
                            $nameMatch = $countries->first(
                                fn ($c) => strcasecmp((string) $c->name, (string) $flow->country_name) === 0
                            );
                            $selectedCountryId = $nameMatch?->id;
                        }
                    @endphp
                    <select name="search_country_id" class="form-control" required>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}"
                                @selected((string) $selectedCountryId === (string) $country->id)>
                                {{ $country->name }}@if($country->short_name) ({{ $country->short_name }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('search_country_id')<small class="text-danger d-block">{{ $message }}</small>@enderror
                </div>
                <div class="form-group col-md-4">
                    <label>Visa type</label>
                    <input type="text" name="visa_type" class="form-control" value="{{ old('visa_type', $flow->visa_type) }}">
                </div>
                <div class="form-group col-md-3">
                    <label class="d-block">Status</label>
                    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $flow->is_active))> Active</label>
                </div>
            </div>
            <div class="card-footer"><button class="btn btn-primary btn-sm">Save flow</button></div>
        </form>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Add step</strong></div>
        <form method="POST" action="{{ route('admin.visa-flows.steps.store', $flow) }}" class="card-body row">
            @csrf
            <div class="form-group col-md-3">
                <input name="name" class="form-control" placeholder="Step name" required>
            </div>
            <div class="form-group col-md-2">
                <select name="assignee" class="form-control" required>
                    @foreach($assignees as $a)
                        <option value="{{ $a }}">{{ \App\Support\VisaLiability::label($a) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-2">
                <input type="number" name="estimated_duration_days" class="form-control" placeholder="Days (SLA)" min="1" max="365">
            </div>
            <div class="form-group col-md-3">
                <input name="description" class="form-control" placeholder="Short description">
            </div>
            <div class="form-group col-md-2">
                <button class="btn btn-success btn-block">Add</button>
            </div>
        </form>
    </div>

    @foreach($flow->steps as $step)
        <div class="card mb-3 {{ $step->is_active ? '' : 'border-secondary' }}">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <strong>#{{ $step->sort_order + 1 }} {{ $step->name }}</strong>
                    <span class="badge {{ \App\Support\VisaLiability::badgeClass($step->assignee) }}">
                        {{ \App\Support\VisaLiability::label($step->assignee) }}
                    </span>
                    @if($step->estimated_duration_days)
                        <small class="text-muted">{{ $step->estimated_duration_days }}d</small>
                    @endif
                    @unless($step->is_active)<span class="badge badge-secondary">inactive</span>@endunless
                </div>
                <div class="d-flex flex-wrap" style="gap:4px;">
                    <form method="POST" action="{{ route('admin.visa-steps.move', $step) }}">
                        @csrf
                        <input type="hidden" name="direction" value="up">
                        <button class="btn btn-xs btn-outline-dark" title="Move up">↑</button>
                    </form>
                    <form method="POST" action="{{ route('admin.visa-steps.move', $step) }}">
                        @csrf
                        <input type="hidden" name="direction" value="down">
                        <button class="btn btn-xs btn-outline-dark" title="Move down">↓</button>
                    </form>
                    @if($step->is_active)
                        <form method="POST" action="{{ route('admin.visa-steps.deactivate', $step) }}" onsubmit="return confirm('Deactivate this step?')">
                            @csrf
                            <button class="btn btn-xs btn-outline-secondary">Deactivate</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('admin.visa-steps.destroy', $step) }}"
                        onsubmit="return confirm('Delete this step permanently?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-xs btn-outline-danger">Delete</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.visa-steps.update', $step) }}" class="mb-3 row">
                    @csrf @method('PUT')
                    <div class="form-group col-md-3"><input name="name" class="form-control" value="{{ $step->name }}"></div>
                    <div class="form-group col-md-2">
                        <select name="assignee" class="form-control">
                            @foreach($assignees as $a)
                                <option value="{{ $a }}" @selected($step->assignee === $a)>{{ \App\Support\VisaLiability::label($a) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <input type="number" name="estimated_duration_days" class="form-control"
                            value="{{ $step->estimated_duration_days }}" placeholder="Days" min="1" max="365">
                    </div>
                    <div class="form-group col-md-3"><input name="description" class="form-control" value="{{ $step->description }}"></div>
                    <div class="form-group col-md-2"><button class="btn btn-sm btn-primary">Save</button></div>
                </form>

                <h6>Requirements</h6>
                @foreach($step->requirements as $req)
                    <div class="border rounded p-2 mb-2 {{ $req->is_active ? '' : 'text-muted bg-light' }}">
                        <form id="req-form-{{ $req->id }}" method="POST" action="{{ route('admin.visa-requirements.update', $req) }}">
                            @csrf @method('PUT')
                            <div class="row align-items-end">
                                <div class="form-group col-md-3 mb-2 mb-md-0">
                                    <label class="small mb-1">Label</label>
                                    <input name="label" class="form-control form-control-sm" value="{{ $req->label }}" required>
                                </div>
                                <div class="form-group col-md-2 mb-2 mb-md-0">
                                    <label class="small mb-1">Type</label>
                                    <select name="type" class="form-control form-control-sm" required>
                                        @foreach(['file' => 'File', 'text' => 'Text', 'date' => 'Date', 'checkbox' => 'Checkbox'] as $tVal => $tLabel)
                                            <option value="{{ $tVal }}" @selected($req->type === $tVal)>{{ $tLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3 mb-2 mb-md-0">
                                    <label class="small mb-1">Parent</label>
                                    <select name="parent_id" class="form-control form-control-sm">
                                        <option value="">Top-level</option>
                                        @foreach($step->requirements->where('is_active', true)->where('id', '!=', $req->id) as $parentReq)
                                            <option value="{{ $parentReq->id }}" @selected((int) $req->parent_id === (int) $parentReq->id)>
                                                Sub of: {{ $parentReq->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2 mb-2 mb-md-0">
                                    <label class="d-block small mb-1">Options</label>
                                    <label class="mb-0">
                                        <input type="checkbox" name="is_required" value="1" @checked($req->is_required)> Required
                                    </label>
                                    @unless($req->is_active)<span class="badge badge-secondary ml-1">inactive</span>@endunless
                                </div>
                                <div class="form-group col-md-2 mb-0 d-flex flex-wrap" style="gap:4px;">
                                    <button class="btn btn-sm btn-primary" type="submit">Save</button>
                                </div>
                            </div>
                        </form>
                        <div class="d-flex flex-wrap mt-1" style="gap:4px;">
                            @if($req->is_active)
                                <form method="POST" action="{{ route('admin.visa-requirements.deactivate', $req) }}" class="d-inline"
                                    onsubmit="return confirm('Deactivate this requirement?')">
                                    @csrf
                                    <button class="btn btn-xs btn-outline-secondary">Deactivate</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.visa-requirements.destroy', $req) }}" class="d-inline"
                                onsubmit="return confirm('Delete this requirement permanently?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-xs btn-outline-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                @endforeach
                @if($step->requirements->isEmpty())
                    <p class="text-muted small mb-3">No requirements yet.</p>
                @endif

                <form method="POST" action="{{ route('admin.visa-steps.requirements.store', $step) }}" class="row">
                    @csrf
                    <div class="form-group col-md-3"><input name="label" class="form-control" placeholder="Requirement label" required></div>
                    <div class="form-group col-md-2">
                        <select name="type" class="form-control" required>
                            <option value="file">File</option>
                            <option value="text">Text</option>
                            <option value="date">Date</option>
                            <option value="checkbox">Checkbox</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <select name="parent_id" class="form-control">
                            <option value="">Top-level (no parent)</option>
                            @foreach($step->requirements->where('is_active', true) as $parentReq)
                                <option value="{{ $parentReq->id }}">Sub of: {{ $parentReq->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label class="mt-2"><input type="checkbox" name="is_required" value="1" checked> Required</label>
                    </div>
                    <div class="form-group col-md-2"><button class="btn btn-success btn-sm">Add requirement</button></div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
