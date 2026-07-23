<?php

namespace App\Http\Livewire;

use App\Models\City;
use App\Models\SearchCountry;
use App\Models\State;
use Livewire\Component;

class CountryStateCity extends Component
{
    public $countries = [];

    public $states = [];

    public $cities = [];

    public $row = false;

    public $selectedCountryId;

    public $selectedStateId;

    public $selectedCityId;

    protected $listeners = [
        'getStateByCountryId' => 'getStateByCountryId',
        'getCityByStateId' => 'getCityByStateId',
    ];

    public function hydrate()
    {
        $this->dispatchBrowserEvent('render-select2');
    }

    public function render()
    {
        $this->countries = SearchCountry::select('id', 'name')
            ->get()
            ->toArray();

        $selectedCountry = $this->resolveSelectedCountry();
        if ($selectedCountry) {
            $this->states = State::select('id', 'name', 'country_id')
                ->where('country_id', $selectedCountry->id)
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->states = [];
        }

        $selectedState = $this->resolveSelectedState($selectedCountry);

        if ($selectedState) {
            $this->cities = City::select('id', 'name', 'state_id')
                ->where('state_id', $selectedState->id)
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->cities = [];
        }

        return view('livewire.country-state-city');
    }

    public function getStateByCountryId($countryName = null)
    {
        if ($countryName) {
            $this->selectedCountryId = $countryName;
        }

        $selectedCountry = $this->resolveSelectedCountry();

        if ($selectedCountry) {
            $this->states = State::select('id', 'name', 'country_id')
                ->where('country_id', $selectedCountry->id)
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->states = [];
        }

        $this->selectedStateId = null;
        $this->selectedCityId = null;
        $this->cities = [];
    }

    public function getCityByStateId($stateName = null)
    {
        if ($stateName) {
            $this->selectedStateId = $stateName;
        }

        $selectedState = $this->resolveSelectedState($this->resolveSelectedCountry());

        if ($selectedState) {
            $this->cities = City::select('id', 'name', 'state_id', 'long', 'lat')
                ->where('state_id', $selectedState->id)
                ->orderBy('name')
                ->get()
                ->toArray();
        } else {
            $this->cities = [];
        }

        $this->selectedCityId = null;
    }

    private function resolveSelectedCountry(): ?SearchCountry
    {
        if (empty($this->selectedCountryId)) {
            return null;
        }

        return SearchCountry::where('name', $this->selectedCountryId)->first();
    }

    private function resolveSelectedState(?SearchCountry $selectedCountry): ?State
    {
        if (empty($this->selectedStateId)) {
            return null;
        }

        $query = State::where('name', $this->selectedStateId);

        if ($selectedCountry) {
            $query->where('country_id', $selectedCountry->id);
        }

        return $query->first();
    }

    public function mount()
    {
        $this->selectedCountryId = session('selectedCountryId', null);
        $this->selectedStateId = session('selectedStateId', null);
        $this->selectedCityId = session('selectedCityId', null);
        session([
            'selectedCountryLong' => null,
            'selectedCountryLat' => null,
            'selectedStateLong' => null,
            'selectedStateLat' => null,
            'selectedCityLong' => null,
            'selectedCityLat' => null,
        ]);
    }

    public function updatedselectedCountryId($value)
    {
        session(['selectedCountryId' => $value]);
        $this->selectedStateId = null;
        $this->selectedCityId = null;
        $this->cities = [];

        $selectedCountry = SearchCountry::where('name', $value)->first();
        if ($selectedCountry) {
            session(['selectedCountryLong' => $selectedCountry->long]);
            session(['selectedCountryLat' => $selectedCountry->lat]);
        }
    }

    public function updatedselectedStateId($value)
    {
        session(['selectedStateId' => $value]);
        $this->selectedCityId = null;

        $selectedState = $this->resolveSelectedState($this->resolveSelectedCountry());
        if ($selectedState) {
            session(['selectedStateLong' => $selectedState->long]);
            session(['selectedStateLat' => $selectedState->lat]);
        }
    }

    public function updatedselectedCityId($value)
    {
        session(['selectedCityId' => $value]);
        // Update session values for 'long' and 'lat' based on the selected city
        $selectedCity = City::where('name', $value)->first();
        if ($selectedCity) {
            session(['selectedCityLong' => $selectedCity->long]);
            session(['selectedCityLat' => $selectedCity->lat]);
        }
    }

    public function updated($field)
    {
        // Check if the updated field is one of the location-related fields
        if (in_array($field, ['selectedCountryId', 'selectedStateId', 'selectedCityId'])) {
            $this->updateLocationSession();
        }
    }

    private function updateLocationSession()
    {
        if (empty(config('templatecookie.map_show'))) {
            session()->put('location', [
                'country' => session('selectedCountryId'),
                'region' => session('selectedStateId'),
                'district' => session('selectedCityId'),
                'lng' => session('selectedCityLong') ?? session('selectedStateLong') ?? session('selectedCountryLong'),
                'lat' => session('selectedCityLat') ?? session('selectedStateLat') ?? session('selectedCountryLat'),
            ]);
        }
    }
}
