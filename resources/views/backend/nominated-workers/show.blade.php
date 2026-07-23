@extends('backend.layouts.app')
@section('title', $worker->full_name)
@section('content')
<div class="container-fluid">
    <a href="{{ route('admin.nominated-workers.index') }}">&larr; Back</a>
    <div class="card mt-2">
        <div class="card-header">
            <h3 class="card-title mb-0">{{ $worker->full_name }}</h3>
            <small>{{ $worker->passport_number }} · company #{{ $worker->company_id }} · {{ $worker->status }}</small>
        </div>
        <div class="card-body">
            @foreach($worker->documents as $doc)
                <div class="border rounded p-2 mb-2 d-flex justify-content-between">
                    <div>
                        <strong>{{ $doc->original_name }}</strong><br>
                        <small>Match: {{ $doc->match_status }} @if($doc->match_confidence)({{ $doc->match_confidence }}%)@endif</small>
                    </div>
                    <form method="POST" action="{{ route('admin.nominated-workers.rematch', $doc) }}">
                        @csrf
                        <button class="btn btn-sm btn-secondary">Rematch</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
