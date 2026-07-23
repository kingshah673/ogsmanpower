@extends('frontend.layouts.app')

@section('description')
    @php
        $data = metaData('company-details');
    @endphp
    {{ $data->description }}
@endsection

@section('og:image')
    {{ asset($data->image) }}
@endsection

@section('title')
    {{ __('company') }} {{ $user->name }}
@endsection

@section('css')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/employer-public-profile.css') }}?v={{ @filemtime(public_path('css/employer-public-profile.css')) ?: '1' }}">
    <x-map.leaflet.map_links />
    @include('map::links')
@endsection

@section('main')
    @php
        $company = $user->company;
        $lat = $company->lat;
        $long = $company->long;
        $map = $setting->default_map;
        $hasMapCoords = filled($lat) && filled($long) && is_numeric($lat) && is_numeric($long);
        $locationLabel = $company->exact_location ?: $company->full_address;
        $isOwner = auth('user')->check()
            && auth('user')->id() === $user->id
            && authUser()?->role === 'company';
        $nameParts = preg_split('/\s+/', trim($user->name));
        $initials = count($nameParts) >= 2
            ? strtoupper(mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[1], 0, 1))
            : strtoupper(mb_substr($user->name, 0, 2));
        $bannerStyle = $company->bannerFileExists()
            ? "background-image: url('".e($company->banner_url)."');"
            : '';
        $websiteDisplay = $company->website
            ? preg_replace('#^https?://#', '', rtrim($company->website, '/'))
            : null;
    @endphp

    @if ($user->userPlan && $user->userPlan->plan->profile_verify && ! $company->is_profile_verified)
        <div class="text-center mt-2 text-red">
            <small class="text-xs">
                Your account is not verified yet.
                <a href="{{ route('company.verify.documents.index') }}">See your documents</a>
            </small>
        </div>
    @endif

    <div class="cw-ep-page">
        <nav class="cw-ep-nav">
            <div class="cw-ep-container" style="padding-top:0;padding-bottom:0;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;">
                    <div style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;color:#6b7280;">
                        <a href="{{ route('website.home') }}" style="color:#6b7280;text-decoration:none;">{{ __('home') }}</a>
                        <span>/</span>
                        <span style="color:#111827;font-weight:500;">{{ $user->name }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        @if ($isOwner)
                            <a href="{{ route('company.setting') }}" class="cw-ep-btn">
                                <i class="fa-regular fa-pen-to-square"></i> {{ __('Edit Profile') }}
                            </a>
                        @endif
                        <button type="button" class="cw-ep-btn" id="cw-ep-share-btn">
                            <i class="fa-solid fa-share-nodes" style="color:#9ca3af;"></i> {{ __('share_this_profile') }}
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <main class="cw-ep-container">
            <div class="cw-ep-card" style="overflow:hidden;margin-bottom:1.5rem;">
                <div class="cw-ep-banner {{ $bannerStyle ? '' : 'cw-ep-banner--fallback' }}" style="{{ $bannerStyle }}"></div>
                <div style="padding:1rem 1.5rem 1.5rem;">
                        <div style="display:flex;flex-direction:column;gap:1rem;">
                            <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-start;">
                            <div class="cw-ep-logo-wrap">
                                @if ($company->logoFileExists())
                                    <img src="{{ $company->logo_url }}" alt="{{ $user->name }}">
                                @else
                                    <div class="cw-ep-logo-initials">{{ $initials }}</div>
                                @endif
                            </div>
                            <div style="flex:1;padding-top:.25rem;">
                                <h1 style="font-size:clamp(1.25rem,3vw,1.875rem);font-weight:700;color:#111827;margin:0;display:flex;align-items:center;flex-wrap:wrap;">
                                    {{ $user->name }}
                                    @if ($companyDetails->is_profile_verified)
                                        <span class="cw-ep-verified" title="{{ __('verified') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </span>
                                    @endif
                                </h1>
                                @if ($companyDetails->industry)
                                    <div style="margin-top:.35rem;">
                                        <span class="cw-ep-badge">
                                            <i class="fa-solid fa-building" style="color:#64748b;font-size:.7rem;"></i>
                                            {{ $companyDetails->industry->name }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div style="align-self:flex-start;">
                                <a href="#open_position" class="cw-ep-btn cw-ep-btn--dark">
                                    <i class="fa-solid fa-briefcase"></i> {{ __('open_position') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="cw-ep-grid">
                <div style="display:flex;flex-direction:column;gap:1.5rem;">
                    <section class="cw-ep-card" style="padding:1.5rem 2rem;">
                        <h2 class="cw-ep-section-title">
                            <i class="fa-solid fa-align-left" style="color:#9ca3af;font-size:.875rem;"></i>
                            {{ __('company_description') }}
                        </h2>
                        <div style="color:#4b5563;line-height:1.75;">
                            @if (filled(strip_tags($company->bio ?? '')))
                                {!! $company->bio !!}
                            @else
                                <p style="font-style:italic;color:#6b7280;">{{ __('no_data_found') }}</p>
                            @endif
                        </div>
                    </section>

                    <section class="cw-ep-card" style="padding:1.5rem 2rem;" id="open_position">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
                            <h2 class="cw-ep-section-title" style="margin:0;">
                                <i class="fa-solid fa-briefcase" style="color:#9ca3af;font-size:.875rem;"></i>
                                {{ __('open_positions') }}
                            </h2>
                            <span class="cw-ep-badge">{{ $open_jobs->total() }} {{ __('positions') }}</span>
                        </div>

                        @forelse ($open_jobs as $job)
                            <a href="{{ route('website.job.details', $job->slug) }}" class="cw-ep-job-card">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
                                    <div>
                                        <div style="font-weight:700;color:#111827;margin-bottom:.25rem;">{{ $job->title }}</div>
                                        <div style="font-size:.8rem;color:#6b7280;display:flex;flex-wrap:wrap;gap:.75rem;">
                                            @if ($job->job_type)
                                                <span><i class="fa-regular fa-clock"></i> {{ $job->job_type->name }}</span>
                                            @endif
                                            <span><i class="fa-solid fa-location-dot"></i> {{ $job->country }}</span>
                                            @if ($job->salary_mode == 'range')
                                                <span>{{ currencyAmountShort($job->min_salary) }} – {{ currencyAmountShort($job->max_salary) }} {{ currentCurrencyCode() }}</span>
                                            @elseif ($job->custom_salary)
                                                <span>{{ $job->custom_salary }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($job->featured)
                                        <span style="font-size:.7rem;font-weight:600;color:#dc2626;background:#fef2f2;padding:.15rem .5rem;border-radius:9999px;">{{ __('featured') }}</span>
                                    @endif
                                </div>
                            </a>
                        @empty
                            <div class="cw-ep-empty">
                                <div style="width:3rem;height:3rem;background:#fff;border:1px solid #f3f4f6;border-radius:9999px;display:flex;align-items:center;justify-content:center;margin:0 auto .75rem;">
                                    <i class="fa-solid fa-folder-open" style="color:#9ca3af;"></i>
                                </div>
                                <p style="font-weight:600;font-size:.875rem;color:#111827;margin:0;">{{ __('no_data_found') }}</p>
                                <p style="font-size:.75rem;color:#6b7280;margin-top:.25rem;">{{ __('There are currently no active job openings listed for this company.') }}</p>
                            </div>
                        @endforelse

                        @if ($open_jobs->total() > $open_jobs->count())
                            <div style="margin-top:1rem;" id="pagination-container">
                                {{ $open_jobs->links('vendor.pagination.frontend') }}
                            </div>
                        @endif
                    </section>
                </div>

                <aside style="display:flex;flex-direction:column;gap:1.5rem;">
                    @if ($company->establishment_date || $companyDetails->organization || $companyDetails->team_size || $company->website || $locationLabel)
                        <section class="cw-ep-card" style="padding:1.5rem;">
                            <h3 class="cw-ep-meta-label">{{ __('company_details') }}</h3>
                            <ul style="list-style:none;padding:0;margin:0;">
                                @if ($company->establishment_date)
                                    <li class="cw-ep-meta-row">
                                        <span style="color:#6b7280;"><i class="fa-regular fa-calendar-days" style="width:1rem;margin-right:.35rem;"></i>{{ __('founded_in') }}</span>
                                        <span style="font-weight:500;text-align:right;">{{ $company->establishment_date->format('j F, Y') }}</span>
                                    </li>
                                @endif
                                @if ($companyDetails->organization)
                                    <li class="cw-ep-meta-row">
                                        <span style="color:#6b7280;"><i class="fa-solid fa-building" style="width:1rem;margin-right:.35rem;"></i>{{ __('organization_type') }}</span>
                                        <span style="font-weight:500;text-align:right;">{{ $companyDetails->organization->name }}</span>
                                    </li>
                                @endif
                                @if ($companyDetails->team_size)
                                    <li class="cw-ep-meta-row">
                                        <span style="color:#6b7280;"><i class="fa-solid fa-user-group" style="width:1rem;margin-right:.35rem;"></i>{{ __('company_size') }}</span>
                                        <span style="font-weight:500;text-align:right;">{{ $companyDetails->team_size->name }}</span>
                                    </li>
                                @endif
                                @if ($company->website)
                                    <li class="cw-ep-meta-row">
                                        <span style="color:#6b7280;"><i class="fa-solid fa-globe" style="width:1rem;margin-right:.35rem;"></i>{{ __('website') }}</span>
                                        <a href="{{ $company->website }}" target="_blank" rel="noopener" style="font-weight:500;color:#2563eb;text-decoration:none;text-align:right;">
                                            {{ $websiteDisplay }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:.65rem;"></i>
                                        </a>
                                    </li>
                                @endif
                                @if ($locationLabel)
                                    <li class="cw-ep-meta-row" style="flex-direction:column;align-items:flex-start;">
                                        <span style="color:#6b7280;"><i class="fa-solid fa-location-dot" style="width:1rem;margin-right:.35rem;"></i>{{ __('location') }}</span>
                                        <span style="font-weight:500;font-size:.8rem;margin-top:.25rem;padding-left:1.35rem;">{{ $locationLabel }}</span>
                                    </li>
                                @endif
                            </ul>

                            @if ($hasMapCoords)
                                <div class="cw-ep-map">
                                    @if ($map == 'google-map')
                                        <div id="google-map"></div>
                                    @else
                                        <div id="leaflet-map"></div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endif

                    <section class="cw-ep-card" style="padding:1.5rem;">
                        <h3 class="cw-ep-meta-label">{{ __('contact_information') }}</h3>
                        <button type="button" class="cw-ep-btn cw-ep-btn--dark" style="width:100%;" id="cw-ep-contact-toggle">
                            <i class="fa-solid fa-eye"></i> {{ __('show_contact_information') }}
                        </button>
                        <div class="cw-ep-contact-panel" id="cw-ep-contact-panel" style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f3f4f6;">
                            @auth('user')
                                @if ($user->contactInfo?->email)
                                    <div style="display:flex;gap:.75rem;margin-bottom:.85rem;font-size:.875rem;">
                                        <span style="width:2rem;height:2rem;border-radius:.5rem;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;color:#6b7280;">
                                            <i class="fa-solid fa-envelope" style="font-size:.7rem;"></i>
                                        </span>
                                        <div>
                                            <p style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;margin:0;">{{ __('email_address') }}</p>
                                            <a href="mailto:{{ $user->contactInfo->email }}" style="font-weight:500;color:#111827;font-size:.8rem;">{{ $user->contactInfo->email }}</a>
                                        </div>
                                    </div>
                                @endif
                                @if ($user->contactInfo?->phone)
                                    <div style="display:flex;gap:.75rem;margin-bottom:.85rem;font-size:.875rem;">
                                        <span style="width:2rem;height:2rem;border-radius:.5rem;background:#f9fafb;border:1px solid #e5e7eb;display:flex;align-items:center;justify-content:center;color:#6b7280;">
                                            <i class="fa-solid fa-phone" style="font-size:.7rem;"></i>
                                        </span>
                                        <div>
                                            <p style="font-size:.65rem;color:#9ca3af;text-transform:uppercase;margin:0;">{{ __('phone') }}</p>
                                            <a href="tel:{{ $user->contactInfo->phone }}" style="font-weight:500;color:#111827;font-size:.8rem;">{{ $user->contactInfo->phone }}</a>
                                        </div>
                                    </div>
                                @endif
                                @if (! $user->contactInfo?->email && ! $user->contactInfo?->phone)
                                    <p style="font-size:.8rem;color:#6b7280;margin:0;">{{ __('no_data_found') }}</p>
                                @endif
                            @else
                                <p style="font-size:.8rem;color:#6b7280;margin:0;">
                                    <a href="{{ route('login') }}" style="color:#2563eb;">{{ __('login') }}</a>
                                    {{ __('to view contact details.') }}
                                </p>
                            @endauth
                            <button type="button" id="cw-ep-contact-hide" style="width:100%;margin-top:.5rem;padding:.35rem;background:none;border:none;font-size:.7rem;font-weight:600;color:#9ca3af;cursor:pointer;">
                                {{ __('Hide information') }}
                            </button>
                        </div>
                    </section>

                    @if ($user->socialInfo && $user->socialInfo->count() > 0)
                        <section class="cw-ep-card" style="padding:1.5rem;">
                            <h3 class="cw-ep-meta-label">{{ __('Follow us on') }}</h3>
                            <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                                @foreach ($user->socialInfo as $contact)
                                    <a href="{{ $contact->url }}" target="_blank" rel="noopener" class="cw-ep-social" title="{{ $contact->social_media }}">
                                        @switch($contact->social_media)
                                            @case('facebook') <i class="fa-brands fa-facebook-f"></i> @break
                                            @case('twitter') <x-svg.new-twitter-icon width="14" height="14" /> @break
                                            @case('instagram') <i class="fa-brands fa-instagram"></i> @break
                                            @case('youtube') <i class="fa-brands fa-youtube"></i> @break
                                            @case('linkedin') <i class="fa-brands fa-linkedin-in"></i> @break
                                            @case('pinterest') <i class="fa-brands fa-pinterest-p"></i> @break
                                            @case('reddit') <i class="fa-brands fa-reddit-alien"></i> @break
                                            @case('github') <i class="fa-brands fa-github"></i> @break
                                            @default <i class="fa-solid fa-link"></i>
                                        @endswitch
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="cw-ep-card" style="padding:1.5rem;">
                        <h3 class="cw-ep-meta-label">{{ __('share_this_profile') }}</h3>
                        <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                            <a href="{{ socialMediaShareLinks(url()->current(), 'facebook') }}" class="cw-ep-social" target="_blank" rel="noopener"><i class="fa-brands fa-facebook-f"></i></a>
                            <a href="{{ socialMediaShareLinks(url()->current(), 'twitter') }}" class="cw-ep-social" target="_blank" rel="noopener" aria-label="X (Twitter)"><x-svg.new-twitter-icon width="14" height="14" /></a>
                            <a href="{{ socialMediaShareLinks(url()->current(), 'linkedin') }}" class="cw-ep-social" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin-in"></i></a>
                        </div>
                    </section>
                </aside>
            </div>
        </main>
    </div>

    <div class="cw-ep-toast" id="cw-ep-toast">
        <i class="fa-solid fa-circle-check" style="color:#4ade80;"></i>
        <span id="cw-ep-toast-msg"></span>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            const shareBtn = document.getElementById('cw-ep-share-btn');
            const toast = document.getElementById('cw-ep-toast');
            const toastMsg = document.getElementById('cw-ep-toast-msg');
            const contactToggle = document.getElementById('cw-ep-contact-toggle');
            const contactPanel = document.getElementById('cw-ep-contact-panel');
            const contactHide = document.getElementById('cw-ep-contact-hide');

            function showToast(message) {
                if (!toast || !toastMsg) return;
                toastMsg.textContent = message;
                toast.classList.add('is-visible');
                setTimeout(function () { toast.classList.remove('is-visible'); }, 3000);
            }

            if (shareBtn) {
                shareBtn.addEventListener('click', function () {
                    const url = window.location.href;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(url).then(function () {
                            showToast('{{ __('Profile link copied to clipboard') }}');
                        });
                    } else {
                        const el = document.createElement('textarea');
                        el.value = url;
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        showToast('{{ __('Profile link copied to clipboard') }}');
                    }
                });
            }

            if (contactToggle && contactPanel) {
                contactToggle.addEventListener('click', function () {
                    contactPanel.classList.add('is-open');
                    contactToggle.style.display = 'none';
                });
            }
            if (contactHide && contactPanel && contactToggle) {
                contactHide.addEventListener('click', function () {
                    contactPanel.classList.remove('is-open');
                    contactToggle.style.display = 'inline-flex';
                });
            }

            const page = new URLSearchParams(window.location.search).get('page');
            if (page) {
                const target = document.getElementById('open_position') || document.getElementById('pagination-container');
                if (target) target.scrollIntoView({ behavior: 'smooth' });
            }
        })();
    </script>

    @if ($hasMapCoords && $map != 'google-map')
        <x-map.leaflet.map_scripts />
        <script>
            (function () {
                var oldlat = parseFloat({!! json_encode((float) ($lat ?: $setting->default_lat)) !!});
                var oldlng = parseFloat({!! json_encode((float) ($long ?: $setting->default_long)) !!});

                function initEmployerLeafletMap() {
                    if (typeof L === 'undefined') {
                        setTimeout(initEmployerLeafletMap, 50);
                        return;
                    }
                    var element = document.getElementById('leaflet-map');
                    if (!element) return;
                    element.style.height = '112px';
                    var leaflet_map = L.map(element, { zoomControl: false, attributionControl: false });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(leaflet_map);
                    var target = L.latLng(oldlat, oldlng);
                    leaflet_map.setView(target, 12);
                    L.marker(target).addTo(leaflet_map);
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initEmployerLeafletMap);
                } else {
                    initEmployerLeafletMap();
                }
            })();
        </script>
    @endif

    @if ($hasMapCoords && $map == 'google-map')
        <script>
            var oldlat = parseFloat({!! json_encode((float) ($lat ?: $setting->default_lat)) !!});
            var oldlng = parseFloat({!! json_encode((float) ($long ?: $setting->default_long)) !!});

            function initMap() {
                const map = new google.maps.Map(document.getElementById('google-map'), {
                    zoom: 12,
                    center: { lat: oldlat, lng: oldlng },
                    disableDefaultUI: true,
                });
                new google.maps.Marker({ position: { lat: oldlat, lng: oldlng }, map });
            }
            window.initMap = initMap;
        </script>
        @php
            $link1 = 'https://maps.googleapis.com/maps/api/js?key=';
            $link2 = $setting->google_map_key;
            $link3 = '&callback=initMap&libraries=places';
        @endphp
        <script src="{{ $link1 . $link2 . $link3 }}" async defer></script>
    @endif
@endsection
