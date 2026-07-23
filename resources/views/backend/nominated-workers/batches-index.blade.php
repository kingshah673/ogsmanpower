@extends('backend.layouts.app')
@section('title', 'Nominated Batches')
@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h3 class="card-title mb-0">Nominated Worker Batches</h3>
            <a href="{{ route('admin.nominated-workers.index') }}" class="btn btn-sm btn-outline-secondary">All workers</a>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Company</th>
                        <th>Country</th>
                        <th>Workers</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->company?->name ?: '—' }}</td>
                            <td>{{ $batch->country_name }}</td>
                            <td>{{ $batch->workers_count }}</td>
                            <td>
                                <span class="badge badge-{{ $batch->status === 'pending_approval' ? 'warning' : 'secondary' }}">
                                    {{ str_replace('_',' ', $batch->status) }}
                                </span>
                            </td>
                            <td><a href="{{ route('admin.nominated-batches.show', $batch) }}" class="btn btn-sm btn-info">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center p-4">No batches yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($batches->hasPages())
            <div class="card-footer">{{ $batches->links() }}</div>
        @endif
    </div>
</div>
@endsection
