@props([
    'selectedCountry' => null,
    'selectedState' => null,
    'selectedCity' => null,
    'prefix' => 'csc',
    'row' => false,
])

@php
    $countries = \App\Models\SearchCountry::query()->orderBy('name')->get(['id', 'name']);
    $countryFieldId = $prefix . '_country';
    $stateFieldId = $prefix . '_state';
    $cityFieldId = $prefix . '_city';

    if (request()->routeIs('company.*') && \Illuminate\Support\Facades\Route::has('company.location.statesByName')) {
        $statesUrl = route('company.location.statesByName');
        $citiesUrl = route('company.location.citiesByName');
    } elseif (request()->routeIs('agency.*') && \Illuminate\Support\Facades\Route::has('agency.location.statesByName')) {
        $statesUrl = route('agency.location.statesByName');
        $citiesUrl = route('agency.location.citiesByName');
    } else {
        $statesUrl = route('location.statesByName');
        $citiesUrl = route('location.citiesByName');
    }
@endphp

<div class="country-state-city-root {{ $row ? 'row g-3' : 'select-wrapper mx-0 w-100 d-flex flex-column' }}"
    data-csc-root="{{ $prefix }}"
    data-csc-states-url="{{ $statesUrl }}"
    data-csc-cities-url="{{ $citiesUrl }}"
    data-csc-country="{{ $selectedCountry }}"
    data-csc-state="{{ $selectedState }}"
    data-csc-city="{{ $selectedCity }}"
    data-csc-label-state="{{ __('Select State') }}"
    data-csc-label-city="{{ __('Select City') }}"
    data-csc-label-loading="{{ __('Loading...') }}">

    <div class="{{ $row ? 'col-lg-4' : 'px-0 w-100' }}">
        <select id="{{ $countryFieldId }}" name="country"
            class="select21 cw-location-select cw-static-select max-w-100" required>
            <option value="">{{ __('Select Country') }}</option>
            @foreach ($countries as $country)
                <option value="{{ $country->name }}" @selected($selectedCountry === $country->name)>
                    {{ $country->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="{{ $row ? 'col-lg-4' : 'px-0 w-100' }}">
        <select id="{{ $stateFieldId }}" name="state"
            class="select21 cw-location-select cw-static-select max-w-100"
            @disabled(! $selectedCountry)>
            <option value="">{{ __('Select State') }}</option>
            @if ($selectedState)
                <option value="{{ $selectedState }}" selected>{{ $selectedState }}</option>
            @endif
        </select>
    </div>

    <div class="{{ $row ? 'col-lg-4' : 'px-0 w-100' }}">
        <select id="{{ $cityFieldId }}" name="district"
            class="select21 cw-location-select cw-static-select max-w-100"
            @disabled(! $selectedState)>
            <option value="">{{ __('Select City') }}</option>
            @if ($selectedCity)
                <option value="{{ $selectedCity }}" selected>{{ $selectedCity }}</option>
            @endif
        </select>
    </div>
</div>

@once('cw-location-cascade-js')
@push('js')
    <script src="{{ asset('js/cw-location-cascade.js') }}?v={{ @filemtime(public_path('js/cw-location-cascade.js')) ?: '1' }}"></script>
@endpush
@endonce
