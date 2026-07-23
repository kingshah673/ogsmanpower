@extends(request()->is('agency/*') ? 'components.website.agency.layout.app' : 'components.website.company.layout.app')
@section('title', 'Visa Processing')
@section('main')
@php $vpRoutePrefix = $vpRoutePrefix ?? 'company.visa-processing'; @endphp
<div class="dashboard-wrapper">
    <div class="container-fluid py-4">
        <h3 class="mb-3">Visa Processing</h3>
        <p class="text-muted">Track each Selected candidate’s country paperwork. Exactly one step is active at a time.</p>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Code</th>
                        <th>Job</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cases as $case)
                        @php
                            $statusClass = match($case->status) {
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'in_progress' => 'warning',
                                default => 'secondary',
                            };
                            $statusLabel = match($case->status) {
                                'in_progress' => 'In Progress',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                default => str_replace('_', ' ', $case->status),
                            };
                        @endphp
                        <tr>
                            <td>{{ $case->candidate?->user?->name ?? ('#'.$case->candidate_id) }}</td>
                            <td><code>{{ $case->candidate?->public_code ?: '—' }}</code></td>
                            <td>{{ $case->job?->title ?? '—' }}</td>
                            <td>{{ $case->country_name }}</td>
                            <td><span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>
                                <div class="progress" style="height:8px;min-width:100px;">
                                    <div class="progress-bar bg-{{ $statusClass }}" style="width:{{ $case->progressPercent() }}%"></div>
                                </div>
                                <small>{{ $case->progressPercent() }}%</small>
                            </td>
                            <td><a href="{{ route($vpRoutePrefix.'.show', $case) }}" class="btn btn-sm btn-primary">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No visa cases yet. Start from a Selected applicant.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $cases->links() }}</div>
    </div>
</div>
@endsection
