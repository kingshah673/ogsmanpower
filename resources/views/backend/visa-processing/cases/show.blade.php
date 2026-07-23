@extends('backend.layouts.app')
@section('title', 'Visa Case #'.$case->id)
@section('content')
<div class="container-fluid">
    <a href="{{ route('admin.visa-cases.index') }}">&larr; All cases</a>
    <div class="card mt-2">
        <div class="card-header d-flex justify-content-between">
            <div>
                <h3 class="card-title mb-0">Case #{{ $case->id }} — {{ $case->country_name }}</h3>
                <small>{{ $case->candidate?->user?->name }} · {{ $case->status }} · {{ $case->progressPercent() }}%</small>
            </div>
        </div>
        <div class="card-body">
            @if($case->status === 'cancelled')
                <div class="alert alert-danger">Cancelled: {{ $case->cancel_reason }}</div>
            @elseif($case->status === 'in_progress')
                <form method="POST" action="{{ route('admin.visa-cases.cancel', $case) }}" class="mb-4">
                    @csrf
                    <label>Cancel case (reason required)</label>
                    <textarea name="cancel_reason" class="form-control mb-2" required rows="2"></textarea>
                    <button class="btn btn-danger btn-sm" onclick="return confirm('Cancel this case?')">Cancel case</button>
                </form>
            @endif

            @foreach($case->steps as $step)
                <div class="border rounded p-3 mb-2">
                    <strong>#{{ $step->sort_order + 1 }} {{ $step->name }}</strong>
                    <span class="badge badge-light">{{ $step->assignee }}</span>
                    <span class="badge badge-{{ $step->status === 'active' ? 'warning' : ($step->status === 'completed' ? 'success' : 'secondary') }}">{{ $step->status }}</span>
                    @if($step->rejection_reason)
                        <div class="text-danger small mt-1">Send-back: {{ $step->rejection_reason }}</div>
                    @endif
                    <ul class="mt-2 mb-0">
                        @foreach($step->requirements as $req)
                            <li>
                                {{ $req->label }} ({{ $req->type }})
                                @if($req->type === 'file' && $req->file)
                                    — {{ $req->file->original_name }}
                                    <a href="{{ route('admin.visa-cases.file', [$case, $req->file->id]) }}">Download</a>
                                @elseif($req->answer)
                                    — {{ $req->answer->value }}
                                @else
                                    — <em>empty</em>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @if($case->relationLoaded('events') || $case->events)
                <h5 class="mt-4">Event timeline</h5>
                <ul class="list-group mb-3">
                    @forelse($case->events as $event)
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $event->type }}</strong>
                                <small class="text-muted">{{ optional($event->created_at)->format('Y-m-d H:i') }}</small>
                            </div>
                            <div>{{ $event->message }}</div>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No events logged yet.</li>
                    @endforelse
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
