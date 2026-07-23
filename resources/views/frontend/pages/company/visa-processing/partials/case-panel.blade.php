@php
    use App\Support\VisaLiability;

    $vpRoutePrefix = $vpRoutePrefix ?? 'company.visa-processing';
    $actorRole = $actorRole ?? 'employer';
    $caseIdPrefix = $caseIdPrefix ?? ('case-'.$case->id);

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
@endphp

<div class="cw-visa {{ $wrapperClass ?? '' }}">
    @if(!empty($showBackLink))
        <a class="cw-visa-back" href="{{ route($vpRoutePrefix.'.index') }}">&larr; All visa cases</a>
    @endif

    <div class="cw-visa-hero">
        <div class="cw-visa-hero-top">
            <div>
                <h1>{{ $heroTitle ?? ($case->country_name.' — '.($case->job?->title ?? 'Visa case')) }}</h1>
                @if(!empty($heroSubtitle))
                    <p class="cw-visa-location mb-2">{{ $heroSubtitle }}</p>
                @elseif($case->candidate?->public_code)
                    <div class="cw-visa-code">{{ $case->candidate->public_code }}</div>
                @endif
                <div class="cw-visa-meta">
                    @if(empty($hideJobMeta))
                        <span>{{ $case->job?->title ?? 'Job not set' }}</span>
                        <span class="cw-visa-meta-dot" aria-hidden="true"></span>
                    @endif
                    <span class="cw-visa-pill {{ $pillClass }}">{{ $statusLabel }}</span>
                    <span class="cw-visa-meta-dot" aria-hidden="true"></span>
                    <span>Case #{{ $case->id }}</span>
                </div>
            </div>
            @if($case->status === 'cancelled' && !empty($showRestart) && $actorRole === 'employer')
                <form method="POST" action="{{ route($vpRoutePrefix.'.restart', $case) }}">
                    @csrf
                    <button class="btn btn-warning"
                        onclick="return confirm('Start again? All uploaded files from the cancelled attempt will be permanently deleted.')">
                        Start Again
                    </button>
                </form>
            @endif
        </div>

        <div class="cw-visa-progress-wrap">
            <div class="cw-visa-progress-label">
                <span>Overall progress</span>
                <span>{{ $progress }}%</span>
            </div>
            <div class="cw-visa-progress" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                <div class="cw-visa-progress-bar {{ $barClass }}" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    </div>

    @if($case->status === 'cancelled')
        <div class="cw-visa-alert">
            {{ $cancelledMessage ?? ('Cancelled: '.$case->cancel_reason) }}
        </div>
    @endif

    <div class="cw-visa-layout">
        <aside class="cw-visa-nav" aria-label="Step outline">
            <h2>Steps</h2>
            @foreach($case->steps as $navStep)
                <a class="cw-visa-nav-item {{ $navStep->status === 'active' ? 'is-active' : '' }} {{ $navStep->status === 'completed' ? 'is-done' : '' }}"
                   href="#{{ $caseIdPrefix }}-step-{{ $navStep->id }}">
                    <span class="cw-visa-nav-index">{{ $navStep->sort_order + 1 }}</span>
                    <span class="cw-visa-nav-text">
                        {{ $navStep->name }}
                        <span class="cw-visa-nav-sub">{{ VisaLiability::label((string) $navStep->assignee) }} · {{ $navStep->status }}</span>
                    </span>
                </a>
            @endforeach
        </aside>

        <div class="cw-visa-main">
            @if(!empty($vueAppId))
                <div id="{{ $vueAppId }}"></div>
                <script type="application/json" id="{{ $payloadId }}">{!! json_encode($visaPayload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT) !!}</script>
            @endif

            @foreach($case->steps as $step)
                @php
                    $canAct = $case->status === 'in_progress'
                        && $step->status === 'active'
                        && VisaLiability::actorCanAct($actorRole, (string) $step->assignee);
                    $stepDomId = $caseIdPrefix.'-step-'.$step->id;
                @endphp
                <div class="cw-visa-step {{ !empty($vueAppId) ? 'visa-classic-step' : '' }} {{ $step->status === 'active' ? 'is-active' : '' }} {{ $step->status === 'completed' ? 'is-done' : '' }}"
                     id="{{ $stepDomId }}">
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
                            <div class="cw-visa-warn">{{ $rejectionPrefix ?? 'Sent back' }}: {{ $step->rejection_reason }}</div>
                        @endif

                        @if($step->requirements->isNotEmpty())
                            <div class="cw-visa-req-panel">
                                <h4 class="cw-visa-req-panel-title">Requirements checklist</h4>
                                <ul class="cw-visa-reqs">
                                    @foreach($step->requirements as $req)
                                        @php
                                            $reqDone = $req->type === 'file' ? (bool) $req->file : filled($req->answer?->value);
                                            $reviewStatus = \App\Support\VisaSubmissionReview::status($req, (string) $step->assignee);
                                        @endphp
                                        <li class="cw-visa-req-row">
                                            <div class="cw-visa-req-row-main">
                                                <span class="cw-visa-req-status {{ $reqDone ? 'is-done' : 'is-missing' }}">
                                                    {{ $reqDone ? 'Done' : 'Missing' }}
                                                </span>
                                                <strong>{{ $req->label }}</strong>
                                                <span class="cw-visa-field-type">{{ ucfirst($req->type) }}</span>
                                                @if($req->type === 'file')
                                                    @if($req->file)
                                                        <span>{{ $req->file->original_name }} ({{ number_format($req->file->size/1024, 1) }} KB)</span>
                                                        @if(!empty($canDownloadFiles))
                                                            <a href="{{ route($vpRoutePrefix.'.file.view', [$case, $req->file->id]) }}" target="_blank" rel="noopener">View</a>
                                                            <a href="{{ route($vpRoutePrefix.'.file', [$case, $req->file->id]) }}">Download</a>
                                                        @else
                                                            <span class="text-muted">Uploaded</span>
                                                        @endif
                                                    @else
                                                        <span class="empty">No file uploaded</span>
                                                    @endif
                                                @else
                                                    <span>{{ $req->answer?->value ?? '—' }}</span>
                                                @endif
                                            </div>
                                            @include('frontend.pages.company.visa-processing.partials.requirement-review', [
                                                'req' => $req,
                                                'step' => $step,
                                                'case' => $case,
                                                'vpRoutePrefix' => $vpRoutePrefix,
                                                'actorRole' => $actorRole,
                                            ])
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($canAct)
                            <form method="POST" action="{{ route($vpRoutePrefix.'.submit', $case) }}" enctype="multipart/form-data" class="cw-visa-form">
                                @csrf
                                <div class="cw-visa-help">
                                    <strong>How to complete this step</strong>
                                    <ul>
                                        <li><strong>Save &amp; complete step</strong> — uploads your documents and moves the visa process forward.</li>
                                    </ul>
                                </div>
                                @include('frontend.pages.company.visa-processing.partials.requirement-fields', ['requirements' => $step->requirements])
                                <div class="cw-visa-actions">
                                    <button class="btn btn-primary">Save &amp; complete step</button>
                                    @if($actorRole !== 'seeker' && Route::has($vpRoutePrefix.'.verify'))
                                        <button formaction="{{ route($vpRoutePrefix.'.verify', $case) }}" class="btn btn-outline-success" formnovalidate>Complete step</button>
                                    @endif
                                </div>
                            </form>
                            @if(in_array($actorRole, ['employer', 'agency'], true) && $step->assignee === $actorRole && Route::has($vpRoutePrefix.'.send-back'))
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
