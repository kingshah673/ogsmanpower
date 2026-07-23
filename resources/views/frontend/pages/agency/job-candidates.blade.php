@extends('components.website.agency.layout.app')

@section('title', __('Nominate Candidates'))

@section('main')
<div class="container-fluid mt-4">

    <div class="mb-3">
        <a href="{{ route('agency.myjob') }}" class="text-muted small"><i class="ph-arrow-left"></i> Back to My Jobs</a>
        <h4 class="mb-0 mt-1">Nominate Candidates for "{{ $job->title ?? 'Job #'.$jobId }}"</h4>
        <p class="text-muted small mb-0">Pick candidates from your talent pool to submit against this job. Already-submitted candidates are pre-checked.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('agency.submit.candidates') }}">
        @csrf
        <input type="hidden" name="job_id" value="{{ $jobId }}">

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Candidate</th>
                            <th>Role</th>
                            <th>Nationality</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($candidates as $candidate)
                            @php $already = in_array($candidate->id, $appliedCandidateIds ?? []); @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="candidates[]" value="{{ $candidate->id }}"
                                           class="form-check-input" @checked($already) @disabled($already)>
                                </td>
                                <td>{{ optional($candidate->user)->name ?? trim(($candidate->first_name ?? '').' '.($candidate->last_name ?? '')) ?: '—' }}</td>
                                <td>{{ $candidate->title ?? optional($candidate->profession)->name ?? '—' }}</td>
                                <td>{{ $candidate->nationality ?? '—' }}</td>
                                <td>
                                    @if($already)
                                        <span class="badge bg-success">Already submitted</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Not submitted</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No candidates in your talent pool yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        @if($candidates->isNotEmpty())
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Submit Selected Candidates</button>
            </div>
        @endif
    </form>

</div>
@endsection
