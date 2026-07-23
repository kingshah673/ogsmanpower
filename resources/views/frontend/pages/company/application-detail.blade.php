@extends('components.website.company.layout.app')

@section('title', __('Application Details'))

@section('css')
<link rel="stylesheet" href="{{ asset('css/company-applicants.css') }}?v={{ @filemtime(public_path('css/company-applicants.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('css/company-application-detail.css') }}?v={{ @filemtime(public_path('css/company-application-detail.css')) ?: '1' }}">
<x-map.leaflet.map_links />
@endsection

@section('main')
@php
    $status = $candiateJob?->status ?: 'pending';
    $locationParts = collect([
        $candidate->exact_location,
        $candidate->district,
        $candidate->region,
        $candidate->country,
    ])->map(fn ($v) => is_string($v) ? trim($v) : null)->filter();
    $location = $locationParts->isNotEmpty()
        ? $locationParts->unique()->implode(', ')
        : (filled($candidate->full_address) ? trim($candidate->full_address) : '—');
    $mapLat = $candidate->lat ? (float) $candidate->lat : null;
    $mapLng = $candidate->long ? (float) $candidate->long : null;
    $mapQuery = ($location !== '—') ? $location : null;
    $showMap = $mapLat && $mapLng || $mapQuery;
    $age = null;
    if (! empty($candidate->birth_date)) {
        try {
            $age = \Carbon\Carbon::parse($candidate->birth_date)->age;
        } catch (\Throwable $e) {
            $age = null;
        }
    }
@endphp

<div class="container-fluid px-3">
    <div class="cw-ad">
        <a href="{{ route('company.applicants') }}" class="cw-ad-back">&larr; Back to applicants</a>

        <div class="cw-ad-hero">
            <div class="cw-ad-hero-status status-{{ $status }}">{{ str_replace('_', ' ', $status) }}</div>
            <div class="cw-ad-hero-body">
                <img src="{{ $candidate->photo }}" class="cw-ad-photo" alt="{{ $candidate->user->name }}">

                <div>
                    <h1 class="cw-ad-name">{{ $candidate->user->name }}</h1>
                    @if($candidate->public_code)
                        <div class="cw-ad-code">{{ $candidate->public_code }}</div>
                    @endif
                    <p class="cw-ad-role">{{ $candidate->profession?->name ?? __('Profession not set') }}</p>
                    <p class="cw-ad-location">{{ $location }}</p>
                    <div class="cw-ad-meta">
                        <span><strong>Applied for:</strong> {{ $candiateJob?->job?->title ?? '—' }}</span>
                        @if($candiateJob?->created_at)
                            <span><strong>Applied:</strong> {{ $candiateJob->created_at->format('M d, Y') }}</span>
                        @endif
                        @if($age)
                            <span><strong>Age:</strong> {{ $age }}</span>
                        @endif
                    </div>
                </div>

                <div class="cw-ad-pipeline">
                    @if($status !== 'shortlisted' && $status !== 'interview' && $status !== 'selected')
                        <form method="POST" action="{{ route('company.pipeline.shortlist') }}">
                            @csrf
                            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                            <input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">
                            <button type="submit" class="btn btn-success btn-block">Shortlist</button>
                        </form>
                    @endif

                    @if($status !== 'interview' && $status !== 'selected' && $status !== 'rejected')
                        <form method="POST" action="{{ route('company.pipeline.interview') }}">
                            @csrf
                            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                            <input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">
                            <button type="submit" class="btn btn-primary btn-block">Call for Interview</button>
                        </form>
                    @elseif($status === 'interview')
                        <a href="{{ route('company.interviews') }}" class="btn btn-outline-primary btn-block">Manage Interview</a>
                    @endif

                    @if($status !== 'rejected')
                        <form method="POST" action="{{ route('company.pipeline.reject') }}" onsubmit="return confirm('Reject this applicant?')">
                            @csrf
                            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
                            <input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">
                            <button type="submit" class="btn btn-outline-danger btn-block">Reject</button>
                        </form>
                    @endif

                    <button type="button" class="btn btn-outline-secondary btn-block" data-toggle="modal" data-target="#contractModal">
                        Create Contract
                    </button>
                </div>
            </div>

            <div class="cw-ad-toolbar">
                @if($candiateJob?->candidate_resume_id)
                    <a href="{{ route('website.candidate.download.cv', $candiateJob->candidate_resume_id) }}" class="btn btn-primary btn-sm">
                        Download uploaded CV
                    </a>
                @endif
                <a href="{{ route('company.applicant.cv', ['candidate_id' => $candidate->id, 'job_id' => $candiateJob->job_id]) }}" class="btn btn-outline-primary btn-sm">
                    {{ $candiateJob?->candidate_resume_id ? 'Generate profile CV' : 'View / Download CV' }}
                </a>
                @if($user->contactInfo?->phone)
                    <a href="tel:{{ $user->contactInfo->phone }}" class="btn btn-outline-secondary btn-sm">Call</a>
                @endif
                @if($user->contactInfo?->email)
                    <a href="mailto:{{ $user->contactInfo->email }}" class="btn btn-outline-secondary btn-sm">Email</a>
                @endif
                <button type="button" class="btn btn-outline-secondary btn-sm" data-toggle="modal" data-target="#forwardCandidateModal">
                    Forward to client
                </button>
            </div>
        </div>

        <div class="cw-ad-layout">
            <aside>
                <div class="cw-ad-panel">
                    <h2>Personal details</h2>
                    <ul class="cw-ad-dl">
                        <li><span>Experience</span><span>{{ $candidate->experience?->name ?? '—' }}</span></li>
                        <li><span>Education</span><span>{{ $candidate->education?->name ?? '—' }}</span></li>
                        <li><span>Gender</span><span>{{ $candidate->gender ? ucfirst($candidate->gender) : '—' }}</span></li>
                        <li><span>Marital status</span><span>{{ $candidate->marital_status ? __($candidate->marital_status) : '—' }}</span></li>
                        <li><span>Birth date</span><span>{{ $candidate->birth_date ? date('d M Y', strtotime($candidate->birth_date)) : '—' }}</span></li>
                    </ul>
                </div>

                <div class="cw-ad-panel">
                    <h2>Contact</h2>
                    <ul class="cw-ad-dl">
                        @if($user->contactInfo?->phone)
                            <li><span>Phone</span><span>{{ $user->contactInfo->phone }}</span></li>
                        @endif
                        @if($user->contactInfo?->email)
                            <li><span>Email</span><span>{{ $user->contactInfo->email }}</span></li>
                        @endif
                        @if($candidate->website)
                            <li><span>Website</span><span><a href="{{ $candidate->website }}" target="_blank" rel="noopener">Visit</a></span></li>
                        @endif
                    </ul>

                    @if($user->socialInfo && $user->socialInfo->count() > 0)
                        <div class="cw-ad-social">
                            @foreach($user->socialInfo as $contact)
                                <a href="{{ $contact->url }}" target="_blank" rel="noopener" title="{{ ucfirst($contact->social_media) }}">
                                    @switch($contact->social_media)
                                        @case('facebook') <x-svg.facebook-icon /> @break
                                        @case('twitter') <x-svg.twitter-icon /> @break
                                        @case('instagram') <x-svg.instagram-icon /> @break
                                        @case('youtube') <x-svg.youtube-icon /> @break
                                        @case('linkedin') <x-svg.linkedin-icon /> @break
                                        @case('pinterest') <x-svg.pinterest-icon /> @break
                                        @case('reddit') <x-svg.reddit-icon /> @break
                                        @case('github') <x-svg.github-icon /> @break
                                        @default <x-svg.link-icon />
                                    @endswitch
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </aside>

            <div>
                @if($candidate->bio)
                    <div class="cw-ad-panel">
                        <h2>Professional summary</h2>
                        <p class="cw-ad-summary">{!! nl2br(e($candidate->bio)) !!}</p>
                    </div>
                @endif

                @if($candidate->experiences->isNotEmpty())
                    <div class="cw-ad-panel">
                        <h2>Work experience</h2>
                        @foreach($candidate->experiences as $exp)
                            <div class="cw-ad-timeline-item">
                                <strong>{{ $exp->designation ?? 'Role' }}{{ $exp->company ? ' · '.$exp->company : '' }}</strong>
                                <span>{{ collect([$exp->start ?? null, $exp->end ?? 'Present'])->filter()->implode(' – ') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($candidate->educations->isNotEmpty())
                    <div class="cw-ad-panel">
                        <h2>Education</h2>
                        @foreach($candidate->educations as $edu)
                            <div class="cw-ad-timeline-item">
                                <strong>{{ $edu->degree ?? $edu->level ?? 'Qualification' }}</strong>
                                <span>{{ collect([$edu->level ?? null, $edu->year ?? null])->filter()->implode(' · ') }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="cw-ad-panel">
                    <h2>Skills &amp; languages</h2>
                    @if($candidate->skills->isNotEmpty())
                        <h3>Skills</h3>
                        <div class="cw-ad-tags mb-3">
                            @foreach($candidate->skills as $skill)
                                <span class="cw-ad-tag">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="cw-ad-empty mb-3">No skills listed.</p>
                    @endif

                    @if($candidate->languages->isNotEmpty())
                        <h3>Languages</h3>
                        <div class="cw-ad-tags">
                            @foreach($candidate->languages as $language)
                                <span class="cw-ad-tag cw-ad-tag--muted">{{ $language->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="cw-ad-empty">No languages listed.</p>
                    @endif
                </div>

                <div class="cw-ad-panel">
                    <h2>Location</h2>
                    <p class="cw-ad-summary mb-0">{{ $location }}</p>
                    @if($showMap)
                        <div id="leaflet-map" class="cw-ad-map" data-lat="{{ $mapLat }}" data-lng="{{ $mapLng }}" data-query="{{ $mapQuery }}">
                            <div class="cw-ad-map-loading text-muted small p-3">Loading map…</div>
                        </div>
                        @if(! $mapLat || ! $mapLng)
                            <p class="cw-ad-map-note">Approximate location from address (candidate has not pinned exact coordinates).</p>
                        @endif
                    @else
                        <p class="cw-ad-empty mt-2 mb-0">No location on file for this candidate.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Forward to client --}}
<div class="modal fade" id="forwardCandidateModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form action="{{ route('company.forward.candidate.email') }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
            <input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">
            <div class="modal-header">
                <h5 class="modal-title">Forward candidate to client</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Client email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <label class="form-label font-weight-bold">Documents to include</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="docs[]" value="cv" id="docCv" checked>
                    <label class="form-check-label" for="docCv">Candidate CV (PDF)</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="docs[]" value="photo" id="docPhoto">
                    <label class="form-check-label" for="docPhoto">Profile picture</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="docs[]" value="passport" id="docPassport">
                    <label class="form-check-label" for="docPassport">Passport</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="docs[]" value="video" id="docVideo">
                    <label class="form-check-label" for="docVideo">Video introduction</label>
                </div>
                <div class="form-group mt-3 mb-0">
                    <label class="form-label">Message (optional)</label>
                    <textarea name="message" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send</button>
            </div>
        </form>
    </div>
</div>

{{-- Contract --}}
<div class="modal fade" id="contractModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <form method="POST" action="{{ route('company.contract.store') }}" class="modal-content">
            @csrf
            <input type="hidden" name="candidate_id" value="{{ $candidate->id }}">
            <input type="hidden" name="job_id" value="{{ $candiateJob->job_id }}">
            <div class="modal-header">
                <h5 class="modal-title">Create candidate contract</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label class="form-label">Contract title</label>
                        <input type="text" name="contract_title" class="form-control" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Salary</label>
                        <input type="text" name="salary" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Duty hours</label>
                        <input type="text" name="duty_hours" class="form-control">
                    </div>
                    <div class="col-md-6 form-group">
                        <label class="form-label">Contract duration</label>
                        <input type="text" name="contract_duration" class="form-control">
                    </div>
                    <div class="col-md-12 form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="col-md-12 form-group mb-0">
                        <label class="form-label">Contract details</label>
                        <textarea name="contract_details" rows="5" class="form-control"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Send contract</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
@if($showMap)
    <x-map.leaflet.map_scripts />
    <script>
    (function () {
        var el = document.getElementById('leaflet-map');
        if (!el) return;

        var lat = parseFloat(el.dataset.lat);
        var lng = parseFloat(el.dataset.lng);
        var query = el.dataset.query || '';
        var hasCoords = !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0;

        function setMapMessage(msg) {
            el.innerHTML = '<p class="cw-ad-empty p-3 mb-0">' + msg + '</p>';
        }

        function renderMap(latitude, longitude) {
            if (typeof L === 'undefined') {
                setMapMessage('Map library could not be loaded.');
                return;
            }
            el.innerHTML = '';
            var map = L.map(el, { scrollWheelZoom: false });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);
            var target = L.latLng(latitude, longitude);
            map.setView(target, 13);
            L.marker(target).addTo(map);
            setTimeout(function () { map.invalidateSize(); }, 250);
        }

        if (hasCoords) {
            renderMap(lat, lng);
            return;
        }

        if (!query) {
            setMapMessage('No address available to plot.');
            return;
        }

        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(query), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (response) { return response.json(); })
            .then(function (results) {
                if (results && results[0]) {
                    renderMap(parseFloat(results[0].lat), parseFloat(results[0].lon));
                } else {
                    setMapMessage('Could not find this address on the map.');
                }
            })
            .catch(function () {
                setMapMessage('Map could not be loaded. Check your connection and try again.');
            });
    })();
    </script>
@endif
<script>
(function () {
    $('#forwardCandidateModal').on('shown.bs.modal', function () {
        $(this).find('input[name="email"]').trigger('focus');
    });
})();
</script>
@endsection
