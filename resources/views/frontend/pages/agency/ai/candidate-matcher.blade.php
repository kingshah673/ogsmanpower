@extends('components.website.agency.layout.app')

@section('title', __('Candidate Matcher'))

@section('main')
<div class="container-fluid mt-4">

    <div class="mb-3">
        <a href="{{ route('agency.job.candidates', $job->id) }}" class="text-muted small"><i class="ph-arrow-left"></i> Back to Job</a>
        <h4 class="mb-0 mt-1"><i class="ph-magic-wand text-primary"></i> Suggested Candidates for "{{ $job->title }}"</h4>
        <p class="text-muted small mb-0">Ranked from your own talent pool based on role, location, salary and age fit.</p>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Candidate</th>
                        <th>Match Score</th>
                        <th>Why</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matches as $i => $match)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ optional($match['candidate']->user)->name ?? trim(($match['candidate']->first_name ?? '').' '.($match['candidate']->last_name ?? '')) ?: '—' }}</td>
                            <td>
                                <div class="progress" style="height: 18px; width: 120px;">
                                    <div class="progress-bar bg-{{ $match['score'] >= 60 ? 'success' : ($match['score'] >= 30 ? 'warning' : 'secondary') }}"
                                         style="width: {{ min(100, $match['score']) }}%;">
                                        {{ $match['score'] }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                @foreach($match['reasons'] as $reason)
                                    <span class="badge bg-light text-dark border">{{ $reason }}</span>
                                @endforeach
                                @if(empty($match['reasons']))
                                    <span class="text-muted small">No strong signals</span>
                                @endif
                            </td>
                            <td>
                                @if(Route::has('agency.candidates.documents'))
                                    <a href="{{ route('agency.candidates.documents', $match['candidate']->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
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

</div>
@endsection
