@extends(request()->is('agency/*') ? 'components.website.agency.layout.app' : 'components.website.company.layout.app')
@section('title', 'Visa Case #'.$case->id)

@section('css')
<link rel="stylesheet" href="{{ asset('css/company-visa-case.css') }}?v={{ @filemtime(public_path('css/company-visa-case.css')) ?: '1' }}">
@endsection

@section('main')
@php
    use App\Support\VisaLiability;
    use App\Support\VisaCasePayload;

    $vpRoutePrefix = $vpRoutePrefix ?? 'company.visa-processing';
    $actorRole = match (true) {
        str_starts_with($vpRoutePrefix, 'agency.') => 'agency',
        str_starts_with($vpRoutePrefix, 'candidate.') => 'seeker',
        default => 'employer',
    };
    $statusKey = $case->status;
    $statusLabel = match ($statusKey) {
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        default => str_replace('_', ' ', (string) $statusKey),
    };
    $pillClass = match ($statusKey) {
        'completed' => 'cw-visa-pill--success',
        'cancelled' => 'cw-visa-pill--danger',
        'in_progress' => 'cw-visa-pill--progress',
        default => 'cw-visa-pill--muted',
    };
    $progress = $case->progressPercent();
    $barClass = match ($statusKey) {
        'completed' => 'is-success',
        'cancelled' => 'is-danger',
        default => '',
    };
    $candidateName = $case->candidate?->user?->name ?? ('Candidate #'.$case->candidate_id);

    $visaPayload = VisaCasePayload::forCase($case, $vpRoutePrefix, $actorRole, true);
@endphp

<div class="dashboard-wrapper">
    <div class="container-fluid px-3">
        <div class="cw-visa">
            <a class="cw-visa-back" href="{{ route($vpRoutePrefix.'.index') }}">&larr; All visa cases</a>

            <div class="cw-visa-hero">
                <div class="cw-visa-hero-top">
                    <div>
                        <h1>{{ $candidateName }} — {{ $case->country_name }}</h1>
                        @if($case->candidate?->public_code)
                            <div class="cw-visa-code">{{ $case->candidate->public_code }}</div>
                        @endif
                        <div class="cw-visa-meta">
                            <span>{{ $case->job?->title ?? 'Job not set' }}</span>
                            <span class="cw-visa-meta-dot" aria-hidden="true"></span>
                            <span class="cw-visa-pill {{ $pillClass }}">{{ $statusLabel }}</span>
                            <span class="cw-visa-meta-dot" aria-hidden="true"></span>
                            <span>Case #{{ $case->id }}</span>
                        </div>
                    </div>
                    @if($case->status === 'cancelled')
                        <form method="POST" action="{{ route($vpRoutePrefix.'.restart', $case) }}">
                            @csrf
                            <button class="btn btn-warning"
                                onclick="return confirm('Start again? All uploaded files from the cancelled attempt will be permanently deleted.')">
                                Start Again
                            </button>
                        </form>
                    @endif
                </div>

                <div class="cw-visa-progress-wrap" id="visa-case-progress">
                    <div class="cw-visa-progress-label">
                        <span>Overall progress</span>
                        <span data-visa-progress-text>{{ $progress }}%</span>
                    </div>
                    <div class="cw-visa-progress" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="cw-visa-progress-bar {{ $barClass }}" data-visa-progress-bar style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            </div>

            @if($case->status === 'cancelled')
                <div class="cw-visa-alert">Cancelled: {{ $case->cancel_reason }}</div>
            @endif

            @if($case->status === 'completed' && $actorRole !== 'seeker')
                @if($case->isDeployed())
                    <div class="cw-visa-alert" style="background:#e6f4ea;border-color:#34a853;color:#1e7e34;">
                        Deployed on {{ optional($case->flight_date)->format('d M Y') ?? optional($case->deployed_at)->format('d M Y') }}
                        @if($case->flight_airline) via {{ $case->flight_airline }} @endif
                        @if($case->flight_ticket_number) (Ticket #{{ $case->flight_ticket_number }}) @endif
                    </div>
                @elseif(Route::has($vpRoutePrefix.'.mark-deployed'))
                    <form method="POST" action="{{ route($vpRoutePrefix.'.mark-deployed', $case) }}" class="cw-visa-alert" style="background:#fff8e1;border-color:#f9a825;">
                        @csrf
                        <strong class="d-block mb-2">Mark worker as deployed</strong>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <input type="text" name="flight_airline" class="form-control form-control-sm" placeholder="Airline">
                            </div>
                            <div class="col-md-3">
                                <input type="text" name="flight_ticket_number" class="form-control form-control-sm" placeholder="Ticket No.">
                            </div>
                            <div class="col-md-3">
                                <input type="date" name="flight_date" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-sm btn-success w-100">Mark as Deployed</button>
                            </div>
                        </div>
                    </form>
                @endif
            @endif

            <div class="cw-visa-layout">
                <aside class="cw-visa-nav" aria-label="Step outline">
                    <h2>Steps</h2>
                    @foreach($case->steps as $navStep)
                        <a class="cw-visa-nav-item {{ $navStep->status === 'active' ? 'is-active' : '' }} {{ $navStep->status === 'completed' ? 'is-done' : '' }}"
                           href="#visa-step-{{ $navStep->id }}"
                           data-visa-step-id="{{ $navStep->id }}">
                            <span class="cw-visa-nav-index">{{ $navStep->sort_order + 1 }}</span>
                            <span class="cw-visa-nav-text">
                                {{ $navStep->name }}
                                <span class="cw-visa-nav-sub" data-visa-nav-sub>{{ VisaLiability::label((string) $navStep->assignee) }} · {{ $navStep->status }}</span>
                            </span>
                        </a>
                    @endforeach
                </aside>

                <div class="cw-visa-main">
                    <div id="visa-case-app"></div>
                    <script type="application/json" id="visa-case-payload">{!! json_encode($visaPayload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) !!}</script>

                    <noscript>
                    @foreach($case->steps as $step)
                        <div class="cw-visa-step {{ $step->status === 'active' ? 'is-active' : '' }} {{ $step->status === 'completed' ? 'is-done' : '' }}" id="visa-step-{{ $step->id }}">
                            <div class="cw-visa-step-head">
                                <h3 class="cw-visa-step-title">
                                    <span class="cw-visa-step-num">Step {{ $step->sort_order + 1 }}</span>
                                    {{ $step->name }}
                                </h3>
                                <div class="cw-visa-chips">
                                    <span class="cw-visa-chip cw-visa-chip--{{ $step->assignee }}">{{ VisaLiability::label((string) $step->assignee) }}</span>
                                    <span class="cw-visa-chip cw-visa-chip--{{ $step->status }}">{{ $step->status }}</span>
                                </div>
                            </div>
                            <div class="cw-visa-step-body">
                                @if($step->description)<p class="cw-visa-step-desc">{{ $step->description }}</p>@endif
                                @if($step->rejection_reason)
                                    <div class="cw-visa-warn">Sent back: {{ $step->rejection_reason }}</div>
                                @endif
                                @if($case->status === 'in_progress' && $step->status === 'active' && VisaLiability::actorCanAct($actorRole, (string) $step->assignee))
                                    <form method="POST" action="{{ route($vpRoutePrefix.'.submit', $case) }}" enctype="multipart/form-data" class="cw-visa-form">
                                        @csrf
                                        @include('frontend.pages.company.visa-processing.partials.requirement-fields', ['requirements' => $step->requirements])
                                        <div class="cw-visa-actions">
                                            <button class="btn btn-primary">Save &amp; complete step</button>
                                            @if($actorRole !== 'seeker')
                                                <button formaction="{{ route($vpRoutePrefix.'.verify', $case) }}" class="btn btn-outline-success" formnovalidate>Complete step</button>
                                            @endif
                                        </div>
                                        <p class="cw-visa-help-note mb-0 mt-2">
                                            <strong>Save &amp; complete step</strong> uploads files and moves forward.
                                            <strong>Complete step</strong> is only when requirements are already saved above.
                                        </p>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    </noscript>

                    {{-- Classic timeline (hidden when Vue mounts) --}}
                    @foreach($case->steps as $step)
                        @php
                            $canAct = $case->status === 'in_progress'
                                && $step->status === 'active'
                                && VisaLiability::actorCanAct($actorRole, (string) $step->assignee);
                        @endphp
                        <div class="cw-visa-step visa-classic-step {{ $step->status === 'active' ? 'is-active' : '' }} {{ $step->status === 'completed' ? 'is-done' : '' }}"
                             id="visa-step-{{ $step->id }}">
                            <div class="cw-visa-step-head">
                                <h3 class="cw-visa-step-title">
                                    <span class="cw-visa-step-num">Step {{ $step->sort_order + 1 }}</span>
                                    {{ $step->name }}
                                </h3>
                                <div class="cw-visa-chips">
                                    <span class="cw-visa-chip cw-visa-chip--{{ $step->assignee }}">{{ VisaLiability::label((string) $step->assignee) }}</span>
                                    <span class="cw-visa-chip cw-visa-chip--{{ $step->status }}">{{ str_replace('_', ' ', $step->status) }}</span>
                                </div>
                            </div>
                            <div class="cw-visa-step-body">
                                @if($step->description)<p class="cw-visa-step-desc">{{ $step->description }}</p>@endif
                                @if($step->rejection_reason)
                                    <div class="cw-visa-warn">Sent back reason on this step: {{ $step->rejection_reason }}</div>
                                @endif

                                <ul class="cw-visa-reqs">
                                    @forelse($step->requirements as $req)
                                        <li>
                                            <span class="cw-visa-req-status {{ ($req->type === 'file' ? $req->file : $req->answer?->value) ? 'is-done' : 'is-missing' }}">
                                                {{ ($req->type === 'file' ? $req->file : filled($req->answer?->value)) ? 'Done' : 'Missing' }}
                                            </span>
                                            <strong>{{ $req->label }}</strong>
                                            @if($req->type === 'file')
                                                @if($req->file)
                                                    <span>{{ $req->file->original_name }} ({{ number_format($req->file->size/1024, 1) }} KB)</span>
                                                    <a href="{{ route($vpRoutePrefix.'.file', [$case, $req->file->id]) }}">Download</a>
                                                @else
                                                    <span class="empty">no file yet</span>
                                                @endif
                                            @else
                                                <span>{{ $req->answer?->value ?? '—' }}</span>
                                            @endif
                                        </li>
                                    @empty
                                        <li><span class="empty">No requirements on this step</span></li>
                                    @endforelse
                                </ul>

                                @if($canAct)
                                    <form method="POST" action="{{ route($vpRoutePrefix.'.submit', $case) }}" enctype="multipart/form-data" class="cw-visa-form">
                                        @csrf
                                        @include('frontend.pages.company.visa-processing.partials.requirement-fields', ['requirements' => $step->requirements])
                                        <div class="cw-visa-actions">
                                            <button class="btn btn-primary">Save &amp; complete step</button>
                                            @if($actorRole !== 'seeker')
                                                <button formaction="{{ route($vpRoutePrefix.'.verify', $case) }}" class="btn btn-outline-success" formnovalidate>Complete step</button>
                                            @endif
                                        </div>
                                        <p class="cw-visa-help-note mb-0 mt-2">
                                            <strong>Save &amp; complete step</strong> uploads files and moves forward.
                                            <strong>Complete step</strong> is only when requirements are already saved above.
                                        </p>
                                    </form>
                                    @if(in_array($actorRole, ['employer', 'agency'], true) && $step->assignee === $actorRole)
                                        <form method="POST" action="{{ route($vpRoutePrefix.'.send-back', $case) }}" class="cw-visa-sendback">
                                            @csrf
                                            <label>Reject &amp; send back (reason required)</label>
                                            <textarea name="reason" class="form-control mb-2" rows="2" required></textarea>
                                            <button class="btn btn-outline-danger btn-sm">Send back to candidate</button>
                                        </form>
                                    @endif
                                @elseif($step->status === 'active' && $case->status === 'in_progress')
                                    <p class="cw-visa-waiting">Waiting on {{ VisaLiability::label((string) $step->assignee) }} to complete this step.</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@vite(['resources/js/visa-case-app.js'])
@endsection

@section('script')
<script>
(function () {
    document.querySelectorAll('.cw-visa-file-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var targetId = input.getAttribute('data-filename-target');
            var target = targetId ? document.getElementById(targetId) : null;
            if (target && input.files && input.files[0]) {
                target.textContent = 'Selected: ' + input.files[0].name;
            }
        });
    });
})();
</script>
@endsection
