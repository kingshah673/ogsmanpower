<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\SearchCountry;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationLookupController extends Controller
{
    public function statesByName(Request $request): JsonResponse
    {
        $countryName = trim((string) $request->country_name);
        if ($countryName === '') {
            return response()->json(['states' => []]);
        }

        $country = SearchCountry::query()
            ->where(function ($q) use ($countryName) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($countryName)])
                    ->orWhere('name', 'like', '%'.$countryName.'%');
            })
            ->first();

        if (! $country) {
            return response()->json(['states' => []]);
        }

        $states = State::where('country_id', $country->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['states' => $states]);
    }

    public function citiesByName(Request $request): JsonResponse
    {
        $stateName = trim((string) $request->state_name);
        $countryName = trim((string) $request->country_name);

        if ($stateName === '') {
            return response()->json(['cities' => []]);
        }

        $stateQuery = State::query()
            ->where(function ($q) use ($stateName) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($stateName)])
                    ->orWhere('name', 'like', '%'.$stateName.'%');
            });

        if ($countryName !== '') {
            $country = SearchCountry::query()
                ->where(function ($q) use ($countryName) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($countryName)])
                        ->orWhere('name', 'like', '%'.$countryName.'%');
                })
                ->first();

            if ($country) {
                $stateQuery->where('country_id', $country->id);
            }
        }

        $state = $stateQuery->orderBy('name')->first();

        if (! $state) {
            return response()->json(['cities' => []]);
        }

        $cities = City::where('state_id', $state->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['cities' => $cities]);
    }
}
