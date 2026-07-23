@extends('components.website.agency.layout.app')

@section('title', __('All Applications'))

@section('main')
<div class="container-fluid mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">All Applications</h4>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Candidate</th>
                        <th>Job</th>
                        <th>Status</th>
                        <th>Applied On</th>
                        <th style="width: 220px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($applications as $app)
                        <tr>
                            <td>{{ $app->candidate->user->name ?? ($app->candidate?->first_name.' '.$app->candidate?->last_name) ?? '—' }}</td>
                            <td>{{ $app->job->title ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ match($app->status) {
                                    'selected' => 'success',
                                    'shortlisted' => 'primary',
                                    'interview' => 'warning',
                                    'rejected' => 'danger',
                                    'forwarded' => 'info',
                                    default => 'secondary',
                                } }} text-white">{{ ucfirst($app->status) }}</span>
                            </td>
                            <td>{{ optional($app->created_at)->format('d M Y') }}</td>
                            <td>
                                @if($app->job_id)
                                    <a href="{{ route('agency.job.application', ['job' => $app->job_id]) }}" class="btn btn-sm btn-outline-primary">Open Board</a>
                                    @if(Route::has('agency.ai.candidate-matcher'))
                                        <a href="{{ route('agency.ai.candidate-matcher', $app->job_id) }}" class="btn btn-sm btn-outline-info" title="AI-suggested candidates for this job">
                                            <i class="ph-magic-wand"></i>
                                        </a>
                                    @endif
                                @endif
                                @if($app->status === 'selected' && Route::has('contracts.create'))
                                    <a href="{{ route('contracts.create', ['candidate_id' => $app->candidate?->user_id, 'company_id' => $app->job?->company?->user_id]) }}" class="btn btn-sm btn-outline-success">Create Contract</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No applications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $applications->links() }}
    </div>

</div>
@endsection
