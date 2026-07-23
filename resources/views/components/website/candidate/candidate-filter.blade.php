@props(['professions', 'experiences', 'educations', 'skills', 'popularTags' => null])

@php
    $map = setting('default_map');
    $hasCandidateFilters = request('keyword')
        || request('country')
        || (request('sortby') && request('sortby') != 'latest')
        || request('profession')
        || (request('experience') && request('experience') != 'all')
        || (request('skills') && request('skills') != 'all')
        || (request('gender') && request('gender') != 'all')
        || (request('education') && request('education') != 'all');
@endphp

<form id="candidate_search_form" action="{{ route('website.candidate') }}" method="GET">
    <div class="cw-jobs-portal">
        <div class="container">
            <div class="cw-jobs-portal__header">
                <h1 class="cw-jobs-portal__title">{{ __('find_candidates') }}</h1>
                <ul class="cw-jobs-portal__breadcrumb">
                    <li><a href="{{ route('website.home') }}">{{ __('home') }}</a></li>
                    <li>/</li>
                    <li>{{ __('candidates') }}</li>
                </ul>
            </div>

            <div class="cw-jobs-portal__search-console">
                <input type="hidden" name="lat" id="lat" class="leaf_lat" value="{{ request('lat') }}">
                <input type="hidden" name="long" id="long" class="leaf_lon" value="{{ request('long') }}">

                <div class="cw-jobs-portal__search-grid leaflet-map-results">
                    <div class="cw-jobs-portal__field cw-jobs-portal__field--select fromGroup has-icon position-relative">
                        <span class="cw-jobs-portal__field-icon">
                            <x-svg.layer-icon stroke="#9ca3af" width="20" height="20" />
                        </span>
                        <select class="rt-selectactive candidate-profession cw-jobs-portal__select" name="profession">
                            <option value="">{{ __('select_profession') }}</option>
                            @foreach ($professions as $profession)
                                <option {{ $profession->id == request('profession') ? 'selected' : '' }}
                                    value="{{ $profession->id }}">
                                    {{ $profession->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cw-jobs-portal__search-divider"></div>

                    @if ($map == 'google-map')
                        <div class="cw-jobs-portal__field cw-jobs-portal__field--location fromGroup has-icon position-relative">
                            <span class="cw-jobs-portal__field-icon">
                                <x-svg.location-icon width="20" height="20" stroke="#9ca3af" />
                            </span>
                            <input type="text" id="searchInput" placeholder="{{ __('enter_location') }}"
                                name="location" value="{{ request('location') }}" class="cw-jobs-portal__input" />
                            <div id="google-map" class="d-none"></div>
                        </div>
                    @else
                        <div class="cw-jobs-portal__field cw-jobs-portal__field--location fromGroup has-icon position-relative">
                            <span class="cw-jobs-portal__field-icon">
                                <x-svg.location-icon width="20" height="20" stroke="#9ca3af" />
                            </span>
                            <input type="text" id="leaflet_search" placeholder="{{ __('enter_location') }}"
                                name="location" value="{{ request('location') }}" class="cw-jobs-portal__input"
                                autocomplete="off" />
                        </div>
                    @endif

                    <div class="cw-jobs-portal__actions">
                        <button type="button" class="cw-jobs-portal__filter-btn" data-bs-toggle="modal"
                            data-bs-target="#candidateFiltersModal">
                            <x-svg.filters-icon />
                            <span>{{ __('filter') }}</span>
                        </button>
                        <button type="submit" class="cw-jobs-portal__submit-btn">
                            <x-svg.search-icon />
                            <span>{{ __('search_candidates') }}</span>
                        </button>
                    </div>
                </div>

                <div class="cw-jobs-portal__tags">
                    <p class="cw-jobs-portal__tags-label">{{ __('Popular Profession') }}:</p>
                    <ul class="cw-jobs-portal__tag-list">
                        @foreach ($professions->take(10) as $profession)
                            <li>
                                <button type="button" onclick="professionFilter('{{ $profession->id }}')"
                                    class="cw-jobs-portal__tag {{ request('profession') == $profession->id ? 'is-active' : '' }}">
                                    {{ $profession->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @if ($hasCandidateFilters)
                <div class="cw-jobs-portal__active-filters">
                    <span class="cw-jobs-portal__tags-label">{{ __('active_filter') }}:</span>
                    @if (request('keyword'))
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('keyword') }}: {{ request('keyword') }}
                            <button type="button" onclick="FilterClose('keyword')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('country'))
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('country') }}: {{ request('country') }}
                            <button type="button" onclick="FilterClose('country')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('sortby') && request('sortby') != 'latest')
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('sortby') }}: {{ request('sortby') }}
                            <button type="button" onclick="FilterClose('sortby')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('profession'))
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('profession') }}:
                            {{ $professions->where('id', request('profession'))->first()->name ?? '-' }}
                            <button type="button" onclick="FilterClose('profession')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('experience') && request('experience') != 'all')
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('experience') }}: {{ request('experience') }}
                            <button type="button" onclick="FilterClose('experience')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request()->has('skills') && request('skills') != 'all')
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('skills') }}: {{ implode(', ', (array) request('skills')) }}
                            <button type="button" onclick="candidateFilterClearField('skills')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('gender') && request('gender') != 'all')
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('gender') }}: {{ request('gender') }}
                            <button type="button" onclick="FilterClose('gender')" aria-label="Clear">×</button>
                        </span>
                    @endif
                    @if (request('education') && request('education') != 'all')
                        <span class="cw-jobs-portal__filter-chip">
                            {{ __('education') }}: {{ request('education') }}
                            <button type="button" onclick="FilterClose('education')" aria-label="Clear">×</button>
                        </span>
                    @endif
                </div>
            @endif

            <div class="job-filter-overlay" id="candidateFilterOverlay"></div>
            <x-website.modal.candidate-filters-modal :experiences="$experiences" :educations="$educations" :skills="$skills" />
        </div>
    </div>
</form>

@push('frontend_links')
    @include('map::links')
    <x-map.leaflet.autocomplete_links />
    <link rel="stylesheet" href="{{ asset('css/filter-drawer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jobs-portal.css') }}">
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
@endpush

@push('frontend_scripts')
    <x-map.leaflet.autocomplete_scripts />

    <script>
        function professionFilter(professionId) {
            $('select[name=profession]').val(professionId).trigger('change');
            $('#candidate_search_form').submit();
        }

        function FilterClose(name) {
            if (name === 'profession') {
                $('select[name=profession]').val('').trigger('change');
            } else {
                $('[name="' + name + '"]').val('');
            }
            $('#candidate_search_form').submit();
        }

        function candidateFilterClearField(name) {
            if (name === 'skills' && $('#candidate_filter_skills').length) {
                $('#candidate_filter_skills').val(null).trigger('change');
            }
            $('#candidate_search_form').submit();
        }

        var candidateFilterModal = document.getElementById('candidateFiltersModal');
        var candidateFilterOverlay = document.getElementById('candidateFilterOverlay');

        if (candidateFilterModal && candidateFilterOverlay) {
            candidateFilterModal.addEventListener('show.bs.modal', function() {
                candidateFilterOverlay.classList.add('active');
                document.body.classList.add('body-no-scrolling');
            });
            candidateFilterModal.addEventListener('hidden.bs.modal', function() {
                candidateFilterOverlay.classList.remove('active');
                document.body.classList.remove('body-no-scrolling');
            });
            candidateFilterOverlay.addEventListener('click', function() {
                bootstrap.Modal.getOrCreateInstance(candidateFilterModal).hide();
            });
        }

        var clearFiltersBtn = document.getElementById('candidateClearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                if ($('#candidate_filter_skills').length) {
                    $('#candidate_filter_skills').val(null).trigger('change');
                }
                ['#candidate_filter_experience', '#candidate_filter_education', '#candidate_filter_gender'].forEach(function(selector) {
                    if ($(selector).length) {
                        $(selector).val('').trigger('change');
                    }
                });
            });
        }

        function initCandidateFilterSelect2() {
            var $modal = $('#candidateFiltersModal');
            if (!$modal.length || typeof $.fn.select2 === 'undefined') {
                return;
            }

            var common = {
                theme: 'bootstrap4',
                width: '100%',
                dropdownParent: $modal,
            };

            if ($('#candidate_filter_skills').length && !$('#candidate_filter_skills').hasClass('select2-hidden-accessible')) {
                $('#candidate_filter_skills').select2($.extend({}, common, {
                    placeholder: '{{ __('select') }} {{ __('skills') }}',
                    allowClear: true,
                    closeOnSelect: false,
                }));
            }

            ['#candidate_filter_experience', '#candidate_filter_education', '#candidate_filter_gender'].forEach(function(selector) {
                var $el = $(selector);
                if ($el.length && !$el.hasClass('select2-hidden-accessible')) {
                    $el.select2($.extend({}, common, {
                        placeholder: '{{ __('all') }}',
                        allowClear: true,
                    }));
                }
            });
        }

        if (candidateFilterModal) {
            candidateFilterModal.addEventListener('shown.bs.modal', function() {
                initCandidateFilterSelect2();
            });
        }

        $(document).ready(function() {
            initCandidateFilterSelect2();
        });
    </script>

    @if ($map == 'google-map')
        <script>
            function initMap() {
                var oldlat = {{ Session::has('location') ? Session::get('location')['lat'] : $setting->default_lat }};
                var oldlng = {{ Session::has('location') ? Session::get('location')['lng'] : $setting->default_long }};
                const map = new google.maps.Map(document.getElementById('google-map'), {
                    zoom: 7,
                    center: { lat: oldlat, lng: oldlng },
                });
                var input = document.getElementById('searchInput');
                map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

                let country_code = '{{ current_country_code() }}';
                var autocomplete = country_code
                    ? new google.maps.places.Autocomplete(input, { componentRestrictions: { country: country_code } })
                    : new google.maps.places.Autocomplete(input);

                autocomplete.bindTo('bounds', map);
                var infowindow = new google.maps.InfoWindow();
                var marker = new google.maps.Marker({
                    map: map,
                    anchorPoint: new google.maps.Point(0, -29)
                });
                autocomplete.addListener('place_changed', function() {
                    infowindow.close();
                    marker.setVisible(false);
                    var place = autocomplete.getPlace();
                    $('#lat').val(place.geometry.location.lat());
                    $('#long').val(place.geometry.location.lng());
                    if (place.geometry.viewport) {
                        map.fitBounds(place.geometry.viewport);
                    } else {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                    }
                });
            }
            window.initMap = initMap;
        </script>
        @php
            $scr = 'https://maps.googleapis.com/maps/api/js?key=' . $setting->google_map_key . '&callback=initMap&libraries=places,geometry';
        @endphp
        <script src="{{ $scr }}" async defer></script>
    @endif
@endpush
