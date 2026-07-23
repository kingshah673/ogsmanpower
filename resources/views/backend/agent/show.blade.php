@extends('backend.layouts.app')
@section('title')
    {{ $agent->name }} — Agent / Facilitator
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">{{ $agent->name }}</h4>
                <div>
                    <a href="{{ route('agent.edit', $agent->id) }}" class="btn btn-sm btn-primary">Edit</a>
                    <a href="{{ route('agent.candidates', $agent->id) }}" class="btn btn-sm btn-success">Workers</a>
                    <a href="{{ route('agent.index') }}" class="btn btn-sm btn-secondary">Back</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Email:</strong> {{ $agent->email }}</p>
                        <p><strong>Username:</strong> {{ $agent->username }}</p>
                        <p><strong>Status:</strong> {{ (int) $agent->status === 1 ? 'Active' : 'Inactive' }}</p>
                        <p><strong>Email verified:</strong> {{ $agent->email_verified_at ? 'Yes' : 'No' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Parent Agency:</strong> {{ $agent->parentAgencyUser?->name ?? '—' }}</p>
                        <p><strong>Workers registered:</strong> {{ $agent->candidates_count ?? 0 }}</p>
                        <p><strong>Created:</strong> {{ $agent->created_at?->format('j M Y') }}</p>
                    </div>
                </div>

                <h5 class="mt-4">Recent workers</h5>
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Registered</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($workers as $worker)
                            <tr>
                                <td>{{ trim(($worker->first_name ?? '').' '.($worker->last_name ?? '')) ?: '—' }}</td>
                                <td>{{ $worker->status ?? '—' }}</td>
                                <td>{{ $worker->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted">No workers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
