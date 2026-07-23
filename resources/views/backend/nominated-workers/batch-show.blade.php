@extends('backend.layouts.app')
@section('title', 'Review Batch')
@section('content')
<style>.badge-purple{background:#6f42c1;color:#fff}</style>
<div class="container-fluid">
    <a href="{{ route('admin.nominated-batches.index') }}">&larr; Batches</a>
    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $batch->name }}</h3>
            <span class="badge badge-secondary">{{ str_replace('_',' ', $batch->status) }}</span>
        </div>
        <div class="card-body">
            <p><strong>Company:</strong> {{ $batch->company?->name }}</p>
            <p><strong>Country:</strong> {{ $batch->country_name }}</p>
            <p><strong>Assignment:</strong> {{ $batch->assignment_mode }}
                @if($batch->agency) → {{ $batch->agency->user?->name ?? ('#'.$batch->agency_id) }} @endif
            </p>
            <p><strong>Workers:</strong> {{ $batch->workers->count() }}</p>

            @if($batch->flow)
                <h5 class="mt-3">Liability template</h5>
                <ol>
                    @foreach($batch->flow->activeSteps as $step)
                        <li>
                            {{ $step->name }}
                            <span class="badge {{ \App\Support\VisaLiability::badgeClass($step->assignee) }}">
                                {{ \App\Support\VisaLiability::label($step->assignee) }}
                            </span>
                        </li>
                    @endforeach
                </ol>
            @endif

            @if($batch->status === 'pending_approval')
                <div class="d-flex flex-wrap" style="gap:8px;">
                    <form method="POST" action="{{ route('admin.nominated-batches.approve', $batch) }}">
                        @csrf
                        <button class="btn btn-success" onclick="return confirm('Approve and freeze visa flow version?')">Approve batch</button>
                    </form>
                    <form method="POST" action="{{ route('admin.nominated-batches.return', $batch) }}" class="form-inline d-flex" style="gap:6px;">
                        @csrf
                        <input name="admin_comment" class="form-control" placeholder="Return comment" required>
                        <button class="btn btn-warning">Return</button>
                    </form>
                </div>
            @endif

            <h5 class="mt-4">Workers</h5>
            <ul class="list-group">
                @foreach($batch->workers as $w)
                    <li class="list-group-item">
                        <a href="{{ route('admin.nominated-workers.show', $w) }}">{{ $w->full_name }}</a>
                        — {{ $w->passport_number ?: 'no passport' }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
