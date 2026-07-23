@php
    $candidate = $app->candidate;
    $user = $candidate?->user;
    $status = $app->status ?: 'pending';
    $photo = $candidate?->photo ?: ($user->image_url ?? asset('backend/image/default.png'));
    $age = $candidate->age ?? null;
    if (! $age && ! empty($candidate?->birth_date)) {
        try {
            $age = \Carbon\Carbon::parse($candidate->birth_date)->age;
        } catch (\Throwable $e) {
            $age = null;
        }
    }
    $city = $candidate->district ?? $candidate->region ?? $candidate->country ?? null;
    $demo = collect([
        $candidate->gender ? ucfirst($candidate->gender) : null,
        $age ? $age : null,
        $city,
    ])->filter()->implode(', ');
    $expHistory = $candidate?->experiences?->first();
    $eduHistory = $candidate?->educations?->first();
    $expLine = $expHistory
        ? trim(($expHistory->designation ?? '').($expHistory->company ? ' at '.$expHistory->company : ''))
        : ($candidate?->experience?->name ?? '—');
    $eduLine = $eduHistory
        ? trim(collect([$eduHistory->degree ?? null, $eduHistory->level ?? null, $eduHistory->year ?? null])->filter()->implode(', '))
        : ($candidate?->education?->name ?? '—');
    $years = $candidate?->experience?->years ?? null;
    $showJob = $showJob ?? false;
    $detailUrl = ($candidate && $app->job_id)
        ? route('company.application.detail', [$candidate->id, $app->job_id])
        : '#';
@endphp

<article class="cw-apps-card">
    <div class="cw-apps-card-status status-{{ $status }}">{{ strtoupper($status) }}</div>

    <div class="cw-apps-card-actions">
        <a href="{{ $detailUrl }}" class="btn btn-outline-primary btn-sm">Preview CV</a>

        @if($app->candidate_resume_id)
            <a href="{{ route('website.candidate.download.cv', $app->candidate_resume_id) }}" class="btn btn-outline-secondary btn-sm">Download uploaded CV</a>
        @endif

        @if($candidate && $app->job_id)
            <a href="{{ route('company.applicant.cv', [$candidate->id, $app->job_id]) }}" class="btn btn-outline-secondary btn-sm">
                {{ $app->candidate_resume_id ? 'Generate profile CV' : 'View / Download CV' }}
            </a>
        @endif

        @if($status !== 'shortlisted' && $status !== 'interview' && $status !== 'selected')
            <form method="POST" action="{{ route('company.update.application.status') }}" class="d-inline">
                @csrf
                <input type="hidden" name="id" value="{{ $app->id }}">
                <input type="hidden" name="status" value="shortlisted">
                <button type="submit" class="btn btn-success btn-sm">Shortlist</button>
            </form>
        @endif

        @if($status !== 'interview' && $status !== 'selected' && $status !== 'rejected')
            <form method="POST" action="{{ route('company.update.application.status') }}" class="d-inline">
                @csrf
                <input type="hidden" name="id" value="{{ $app->id }}">
                <input type="hidden" name="status" value="interview">
                <button type="submit" class="btn btn-primary btn-sm">Call for Interview</button>
            </form>
        @elseif($status === 'interview')
            <a href="{{ route('company.interviews') }}" class="btn btn-outline-primary btn-sm">Manage Interview</a>
        @endif

        @if($status === 'selected')
            @php
                $vpCase = $app->relationLoaded('vpCase') ? $app->vpCase : $app->vpCase()->first();
                $vpRoutePrefix = $vpRoutePrefix ?? 'company.visa-processing';
                $visaCountries = $visaCountries ?? \App\Models\SearchCountry::query()->orderBy('name')->get(['id','name','short_name']);
            @endphp
            @if($vpCase)
                <div class="w-100 mb-1">
                    <div class="progress mb-1" style="height:6px;">
                        <div class="progress-bar bg-{{ $vpCase->status === 'completed' ? 'success' : ($vpCase->status === 'cancelled' ? 'danger' : 'primary') }}"
                             style="width:{{ $vpCase->progressPercent() }}%"></div>
                    </div>
                    @if($vpCase->status === 'in_progress')
                        <span class="badge bg-warning text-dark">Visa In Progress</span>
                        <a href="{{ route($vpRoutePrefix.'.show', $vpCase) }}" class="btn btn-sm btn-primary">{{ $vpCase->progressPercent() }}%</a>
                    @elseif($vpCase->status === 'completed')
                        <span class="badge bg-success">Visa Completed</span>
                        <a href="{{ route($vpRoutePrefix.'.show', $vpCase) }}" class="btn btn-sm btn-outline-success">Open</a>
                    @elseif($vpCase->status === 'cancelled')
                        <span class="badge bg-danger">Visa Cancelled</span>
                        <form method="POST" action="{{ route($vpRoutePrefix.'.restart', $vpCase) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-warning">Start Visa Again</button>
                        </form>
                    @endif
                </div>
            @else
                {{-- Bootstrap 4 company layout: data-toggle (not data-bs-toggle) --}}
                <button type="button" class="btn btn-sm btn-outline-primary"
                    data-toggle="modal"
                    data-target="#startVisaModal{{ $app->id }}">
                    Start Visa Processing
                </button>
                @php $showStartVisaModal = true; @endphp
            @endif
        @endif

        @if($status !== 'rejected')
            <form method="POST" action="{{ route('company.update.application.status') }}" class="d-inline">
                @csrf
                <input type="hidden" name="id" value="{{ $app->id }}">
                <input type="hidden" name="status" value="rejected">
                <button type="submit" class="btn btn-outline-danger btn-sm">Reject</button>
            </form>
        @endif

        <a href="{{ $detailUrl }}" class="btn btn-link btn-sm">More Actions</a>
    </div>

    <div class="cw-apps-card-body">
        <div>
            <img src="{{ $photo }}" alt="" class="cw-apps-photo">
            <div class="cw-apps-photo-meta">
                <div>CV #{{ $app->id }}</div>
                <div>Apply Date: {{ optional($app->created_at)->format('M d, Y') }}</div>
            </div>
        </div>

        <div>
            <h3 class="cw-apps-name">
                {{ $user->name ?? 'Applicant' }}
                @if($candidate?->public_code)
                    <code class="ms-1" style="font-size:.75rem;font-weight:600;color:#0f172a;">{{ $candidate->public_code }}</code>
                @endif
                @if($demo)
                    <span style="font-weight:500;color:#64748b;font-size:.9rem;">({{ $demo }})</span>
                @endif
            </h3>

            @if($showJob && $app->job)
                <div class="cw-apps-job-line">Applied to: {{ $app->job->title }}</div>
            @endif

            <dl class="cw-apps-dl">
                <dt>Experience</dt>
                <dd>{{ $expLine ?: '—' }}</dd>
                <dt>Education</dt>
                <dd>{{ $eduLine ?: '—' }}</dd>
                <dt>Profession</dt>
                <dd>{{ $candidate?->profession?->name ?? '—' }}</dd>
                <dt>Location</dt>
                <dd>{{ collect([$candidate->district ?? null, $candidate->country ?? null])->filter()->implode(', ') ?: '—' }}</dd>
            </dl>

            @if($candidate?->skills?->count())
                <div class="cw-apps-skills">
                    @foreach($candidate->skills->take(8) as $skill)
                        <span class="cw-apps-skill">{{ $skill->name }}</span>
                    @endforeach
                </div>
            @endif

            <div class="cw-apps-footer">
                <div>
                    <strong>Years of Experience</strong>
                    <span>{{ $years !== null ? $years.' Years' : '—' }}</span>
                </div>
                <div>
                    <strong>Apply Status</strong>
                    <span>{{ ucfirst($status) }}</span>
                </div>
                <div>
                    <strong>Job</strong>
                    <span>{{ $app->job->title ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>
</article>

{{-- Modal outside card: .cw-apps-card has overflow:hidden; BS4 uses data-dismiss --}}
@if(!empty($showStartVisaModal))
<div class="modal fade" id="startVisaModal{{ $app->id }}" tabindex="-1" role="dialog" aria-labelledby="startVisaModalLabel{{ $app->id }}" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form method="POST" action="{{ route($vpRoutePrefix.'.start') }}" class="modal-content">
            @csrf
            <input type="hidden" name="applied_job_id" value="{{ $app->id }}">
            <div class="modal-header">
                <h5 class="modal-title" id="startVisaModalLabel{{ $app->id }}">Start Visa Processing</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <label class="form-label">Destination country</label>
                @php
                    $defaultCountry = $app->job->country ?? $app->job->exact_location ?? '';
                    $matched = ($visaCountries ?? collect())->first(fn ($c) => strcasecmp($c->name, (string) $defaultCountry) === 0);
                @endphp
                <select name="search_country_id" class="form-control" required>
                    <option value="">Select country</option>
                    @foreach(($visaCountries ?? []) as $country)
                        <option value="{{ $country->id }}" @selected($matched && (int)$matched->id === (int)$country->id)>
                            {{ $country->name }} ({{ $country->short_name }})
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Only countries with an active admin visa flow can start.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Start</button>
            </div>
        </form>
    </div>
</div>
@endif
