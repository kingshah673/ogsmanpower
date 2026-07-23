@props(['industries', 'organizationTypes', 'teamsizes'])

@php
    $map = setting('default_map');
    $selectedIndustries = array_filter((array) request('industry_type'));
    $selectedOrganizations = array_filter((array) request('organization_type'));
    $selectedTeamSizes = array_filter((array) request('team_size'));
    $hasCompanyFilters = request('keyword')
        || count($selectedIndustries)
        || count($selectedOrganizations)
        || count($selectedTeamSizes);
@endphp

<div class="cw-jobs-portal">
    <div class="container">
        <div class="cw-jobs-portal__header">
            <h1 class="cw-jobs-portal__title">{{ __('find_employers') }}</h1>
            <ul class="cw-jobs-portal__breadcrumb">
                <li><a href="{{ route('website.home') }}">{{ __('home') }}</a></li>
                <li>/</li>
                <li>{{ __('companies') }}</li>
            </ul>
        </div>

        <div class="cw-jobs-portal__search-console">
            <input type="hidden" name="lat" id="lat" class="leaf_lat" value="{{ request('lat') }}">
            <input type="hidden" name="long" id="long" class="leaf_lon" value="{{ request('long') }}">

            <div class="cw-jobs-portal__search-grid leaflet-map-results">
                <div class="cw-jobs-portal__field fromGroup has-icon position-relative">
                    <span class="cw-jobs-portal__field-icon">
                        <x-svg.search-icon />
                    </span>
                    <input id="search" name="keyword" type="text" placeholder="{{ __('company_title_keyword') }}"
                        value="{{ request('keyword') }}" autocomplete="off" class="cw-jobs-portal__input">
                    <span id="autocomplete_job_results" class="autocomplete-job-dropdown"></span>
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
                        data-bs-target="#companyFiltersModal">
                        <x-svg.filters-icon />
                        <span>{{ __('filter') }}</span>
                    </button>
                    <button type="submit" class="cw-jobs-portal__submit-btn">
                        <x-svg.search-icon />
                        <span>{{ __('search_employers') }}</span>
                    </button>
                </div>
            </div>

            @if ($industries->count())
                <div class="cw-jobs-portal__tags">
                    <p class="cw-jobs-portal__tags-label">{{ __('industry_type') }}:</p>
                    <ul class="cw-jobs-portal__tag-list">
                        @foreach ($industries->take(10) as $industry)
                            <li>
                                <button type="button" onclick="industryFilter('{{ $industry->name }}')"
                                    class="cw-jobs-portal__tag {{ in_array($industry->name, $selectedIndustries, true) ? 'is-active' : '' }}">
                                    {{ $industry->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        @if ($hasCompanyFilters)
            <div class="cw-jobs-portal__active-filters">
                <span class="cw-jobs-portal__tags-label">{{ __('active_filter') }}:</span>
                @if (request('keyword'))
                    <span class="cw-jobs-portal__filter-chip">
                        {{ __('keyword') }}: {{ request('keyword') }}
                        <button type="button" onclick="FilterClose('keyword')" aria-label="Clear">×</button>
                    </span>
                @endif
                @if (count($selectedIndustries))
                    <span class="cw-jobs-portal__filter-chip">
                        {{ __('industry_type') }}: {{ implode(', ', $selectedIndustries) }}
                        <button type="button" onclick="companyFilterClearField('industry_type')" aria-label="Clear">×</button>
                    </span>
                @endif
                @if (count($selectedOrganizations))
                    <span class="cw-jobs-portal__filter-chip">
                        {{ __('organization_type') }}: {{ implode(', ', $selectedOrganizations) }}
                        <button type="button" onclick="companyFilterClearField('organization_type')" aria-label="Clear">×</button>
                    </span>
                @endif
                @if (count($selectedTeamSizes))
                    <span class="cw-jobs-portal__filter-chip">
                        {{ __('team_size') }}: {{ implode(', ', $selectedTeamSizes) }}
                        <button type="button" onclick="companyFilterClearField('team_size')" aria-label="Clear">×</button>
                    </span>
                @endif
            </div>
        @endif

        <div class="job-filter-overlay" id="companyFilterOverlay"></div>
        <x-website.company.company-filters-modal :industries="$industries" :organization-types="$organizationTypes"
            :teamsizes="$teamsizes" />
    </div>
</div>

@push('frontend_links')
    <link rel="stylesheet" href="{{ asset('css/filter-drawer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jobs-portal.css') }}">
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="{{ asset('backend') }}/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <x-map.leaflet.autocomplete_links />
@endpush

@push('frontend_scripts')
    <x-map.leaflet.autocomplete_scripts />

    <script>
        function FilterClose(name) {
            $('[name="' + name + '"]').val('');
            $('#company_search_form').submit();
        }

        function industryFilter(name) {
            var $el = $('#company_filter_industry');
            if ($el.length) {
                $el.val([name]).trigger('change');
            }
            $('#company_search_form').submit();
        }

        var companyFilterModal = document.getElementById('companyFiltersModal');
        var companyFilterOverlay = document.getElementById('companyFilterOverlay');

        if (companyFilterModal && companyFilterOverlay) {
            companyFilterModal.addEventListener('show.bs.modal', function() {
                companyFilterOverlay.classList.add('active');
                document.body.classList.add('body-no-scrolling');
            });
            companyFilterModal.addEventListener('hidden.bs.modal', function() {
                companyFilterOverlay.classList.remove('active');
                document.body.classList.remove('body-no-scrolling');
            });
            companyFilterOverlay.addEventListener('click', function() {
                bootstrap.Modal.getOrCreateInstance(companyFilterModal).hide();
            });
        }

        var path = "{{ route('website.job.autocomplete') }}";
        $('#search').keyup(function() {
            var keyword = $(this).val();
            if (keyword != '') {
                $.ajax({
                    url: path,
                    type: 'GET',
                    dataType: 'json',
                    data: { search: keyword },
                    success: function(data) {
                        $('#autocomplete_job_results').fadeIn();
                        $('#autocomplete_job_results').html(data);
                    }
                });
            } else {
                $('#autocomplete_job_results').fadeOut();
            }
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
                    const total = place.address_components.length;
                    let amount = total > 1 ? total - 2 : '';
                    const result = place.address_components.slice(amount);
                    let country = '';
                    for (let index = 0; index < result.length; index++) {
                        if (result[index].types[0] == 'country') {
                            country = result[index].long_name;
                        }
                    }
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
