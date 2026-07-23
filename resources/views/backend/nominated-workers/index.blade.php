@extends('backend.layouts.app')
@section('title', 'Nominated Workers')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">All Nominated Workers</h3></div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Company</th>
                        <th>Passport</th>
                        <th>Status</th>
                        <th>Docs</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($workers as $worker)
                        <tr>
                            <td>{{ $worker->full_name }}</td>
                            <td>{{ $worker->company?->user?->name ?? $worker->company_id }}</td>
                            <td>{{ $worker->passport_number ?: '—' }}</td>
                            <td>{{ $worker->status }}</td>
                            <td>{{ $worker->documents_count }}</td>
                            <td><a href="{{ route('admin.nominated-workers.show', $worker) }}" class="btn btn-sm btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center p-4">None yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $workers->links() }}</div>
    </div>
</div>
@endsection
