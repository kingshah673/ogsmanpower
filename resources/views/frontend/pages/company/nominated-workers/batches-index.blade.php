@extends('components.website.company.layout.app')
@section('title', 'Nominated Worker Batches')
@section('main')
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="mb-1">Nominated Worker Batches</h3>
                <p class="text-muted mb-0">Group pre-selected workers by destination country for visa outsourcing.</p>
            </div>
            <a href="{{ route('company.nominated-workers.batches.create') }}" class="btn btn-primary btn-sm">Create batch</a>
        </div>

        <div class="table-responsive bg-white rounded shadow-sm mb-4">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Country</th>
                        <th>Workers</th>
                        <th>Status</th>
                        <th>Flow</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->country_name }}</td>
                            <td>{{ $batch->workers_count }}</td>
                            <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $batch->status) }}</span></td>
                            <td>
                                @if($batch->flow)
                                    v{{ $batch->frozen_flow_version ?: $batch->flow->version }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><a href="{{ route('company.nominated-workers.batches.show', $batch) }}" class="btn btn-sm btn-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No batches yet. Create one to start.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mb-4">{{ $batches->links() }}</div>

        @if($orphanWorkers->isNotEmpty())
            <h5>Legacy workers (no batch)</h5>
            <p class="text-muted small">Workers added before batches. Create a batch and re-add or leave as archive.</p>
            <ul class="list-group">
                @foreach($orphanWorkers as $w)
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ $w->full_name }} · {{ $w->destination_country ?: '—' }}</span>
                        <a href="{{ route('company.nominated-workers.show', $w) }}">View</a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection
