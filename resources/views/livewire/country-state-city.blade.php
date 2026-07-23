<div class="country-state-city-root">
<div class="{{ $row ? 'row':'select-wrapper mx-0 w-100 d-flex flex-column' }}">
    <div class="{{ $row ? 'col-lg-4':'px-0 w-100' }}">
        <select name="country" wire:model="selectedCountryId" class="select21 location city max-w-100">
            <option value="">Select Country</option>
            @foreach ($countries as $country)
                <option value="{{ $country['name'] }}" required>{{ $country['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="{{ $row ? 'col-lg-4':'px-0 w-100' }}">
        <select name="state" wire:model="selectedStateId" class="select21 location zone max-w-100">
            <option value="">Select State </option>
            @foreach ($states as $state)
                <option value="{{ $state['name'] }}">{{ $state['name'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="{{ $row ? 'col-lg-4':'px-0 w-100' }}">
        <select name="district" wire:model="selectedCityId" class="select21 location area max-w-100">
            <option value="">Select City </option>
            @foreach ($cities as $city)
                <option value="{{ $city['name'] }}">{{ $city['name'] }}</option>
            @endforeach
        </select>


    </div>
</div>

<style>
    .country-state-city-root .select-wrapper {
        gap: 16px;
    }
    .country-state-city-root .location+.bigdrop,
    .country-state-city-root .location+.select2-container {
        width: 100% !important;
    }

    @media (max-width: 1199px) {
        .country-state-city-root .location+.select2-container {
            margin: 4px 0px;
        }
    }
    .country-state-city-root .select2-container .select2-selection--single,
    .country-state-city-root .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px !important;
    }
    .country-state-city-root .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 48px !important;
    }
    .country-state-city-root .card .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 36px !important;
    }
</style>
</div>

@once('country-state-city-js')
@push('js')
    <script>
    (function ($) {
        if (!$) {
            return;
        }

        function findCscComponent(el) {
            var root = el.closest('[wire\\:id]');
            if (!root || !window.Livewire) {
                return null;
            }

            return window.Livewire.find(root.getAttribute('wire:id'));
        }

        function cscValue(event, $el) {
            if (event.type === 'select2:select' && event.params && event.params.data) {
                return event.params.data.id;
            }

            return $el.val();
        }

        function initCountryStateCitySelect2() {
            $('.country-state-city-root select.select21').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2();
            });
        }

        function bindCountryStateCityEvents() {
            $(document)
                .off('change.csc select2:select.csc', '.country-state-city-root .location.city')
                .on('change.csc select2:select.csc', '.country-state-city-root .location.city', function (e) {
                    var component = findCscComponent(this);
                    if (!component) {
                        return;
                    }
                    var value = cscValue(e, $(this));
                    component.set('selectedCountryId', value);
                    component.call('getStateByCountryId', value);
                });

            $(document)
                .off('change.csc select2:select.csc', '.country-state-city-root .location.zone')
                .on('change.csc select2:select.csc', '.country-state-city-root .location.zone', function (e) {
                    var component = findCscComponent(this);
                    if (!component) {
                        return;
                    }
                    var value = cscValue(e, $(this));
                    component.set('selectedStateId', value);
                    component.call('getCityByStateId', value);
                });

            $(document)
                .off('change.csc select2:select.csc', '.country-state-city-root .location.area')
                .on('change.csc select2:select.csc', '.country-state-city-root .location.area', function (e) {
                    var component = findCscComponent(this);
                    if (!component) {
                        return;
                    }
                    var value = cscValue(e, $(this));
                    component.set('selectedCityId', value);
                });
        }

        function bootCountryStateCity() {
            initCountryStateCitySelect2();
            bindCountryStateCityEvents();
        }

        document.addEventListener('livewire:load', bootCountryStateCity);
        window.addEventListener('render-select2', bootCountryStateCity);
        $(bootCountryStateCity);
    })(window.jQuery);
    </script>
@endpush
@endonce
