@extends('components.website.agency.layout.app')
@section('title', 'Nominated Batches')
@section('main')
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        <h3 class="mb-1">Nominated Worker Batches</h3>
        <p class="text-muted">Accept outsourcing batches and complete your liability steps.</p>

        <h5 class="mt-4">My liability only</h5>
        <div class="table-responsive bg-white rounded shadow-sm mb-4">
            <table class="table mb-0">
                <thead><tr><th>Worker</th><th>Active step</th><th></th></tr></thead>
                <tbody>
                    @forelse($liabilityCases as $case)
                        <tr>
                            <td>{{ $case->nominatedWorker?->full_name ?: '—' }}</td>
                            <td>{{ optional($case->steps->first())->name }}</td>
                            <td>
                                @if($case->nominated_worker_id)
                                    <a class="btn btn-sm btn-success" href="{{ route('agency.nominated-workers.show', $case->nominated_worker_id) }}">Open</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">No agency-liable steps right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h5>Batch invites</h5>
        <div class="table-responsive bg-white rounded shadow-sm mb-4">
            <table class="table mb-0">
                <thead><tr><th>Batch</th><th>Employer</th><th>Country</th><th>Workers</th><th></th></tr></thead>
                <tbody>
                    @forelse($invites as $batch)
                        <tr>
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->company?->name ?: '—' }}</td>
                            <td>{{ $batch->country_name }}</td>
                            <td>{{ $batch->workers_count }}</td>
                            <td><a href="{{ route('agency.nominated-workers.batches.show', $batch) }}" class="btn btn-sm btn-primary">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No open invites.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h5>Active batches</h5>
        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table mb-0">
                <thead><tr><th>Batch</th><th>Country</th><th>Workers</th><th></th></tr></thead>
                <tbody>
                    @forelse($activeBatches as $batch)
                        <tr>
                            <td>{{ $batch->name }}</td>
                            <td>{{ $batch->country_name }}</td>
                            <td>{{ $batch->workers_count }}</td>
                            <td><a href="{{ route('agency.nominated-workers.batches.show', $batch) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">No active batches.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $activeBatches->links() }}</div>
    </div>
</div>
@endsection
