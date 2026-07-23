<?php

namespace App\Services\Website\Company;

use App\Models\Company;
use App\Models\IndustryType;
use App\Models\IndustryTypeTranslation;
use App\Models\OrganizationType;
use App\Models\OrganizationTypeTranslation;
use App\Models\TeamSize;
use Modules\Location\Entities\Country;

class CompanyListService
{
    /**
     * Get company list
     */
    public function execute($request): array
    {
        $query = Company::with('user', 'user.contactInfo', 'industry.translations')
            ->withCount([
                'jobs as activejobs' => function ($q) {
                    $q->where('status', 'active');

                    $selected_country = session()->get('selected_country');
                    if ($selected_country && $selected_country != null && $selected_country != 'all') {
                        $country = selected_country()->name;
                        $q->where('country', 'LIKE', "%$country%");
                    } else {
                        $setting = loadSetting();
                        if ($setting->app_country_type == 'single_base') {
                            if ($setting->app_country) {
                                $country = Country::where('id', $setting->app_country)->first();
                                if ($country) {
                                    $q->where('country', 'LIKE', "%$country->name%");
                                }
                            }
                        }
                    }
                },
            ])
            ->withCount([
                'bookmarkCandidateCompany as candidatemarked' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
            ])
            ->withCasts(['candidatemarked' => 'boolean'])
            ->active();

        // Keyword search
        if ($request->has('keyword') && $request->keyword != null) {
            session(['header_search_role' => 'company']);

            $keyword = $request->keyword;
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('name', 'LIKE', "%$keyword%");
            });
        }

        // location search
        if ($request->has('lat') && $request->has('long') && $request->lat != null && $request->long != null) {
            $location = $request->location ? $request->location : '';
            $query->where('country', 'LIKE', "%$location%");
        }

        // Industry Type
        if ($request->filled('industry_type')) {
            $names = array_filter((array) $request->industry_type);
            if ($names) {
                $ids = IndustryTypeTranslation::whereIn('name', $names)->pluck('industry_type_id');
                if ($ids->isNotEmpty()) {
                    $query->whereIn('industry_type_id', $ids);
                }
            }
        }

        // Organization Type
        if ($request->filled('organization_type')) {
            $names = array_filter((array) $request->organization_type);
            if ($names) {
                $ids = OrganizationTypeTranslation::whereIn('name', $names)->pluck('organization_type_id');
                if ($ids->isNotEmpty()) {
                    $query->whereIn('organization_type_id', $ids);
                }
            }
        }

        // Team Size
        if ($request->filled('team_size')) {
            $names = array_filter((array) $request->team_size);
            if ($names) {
                $ids = TeamSize::whereIn('name', $names)->pluck('id');
                if ($ids->isNotEmpty()) {
                    $query->whereIn('team_size_id', $ids);
                }
            }
        }

        $companies = $query->latest('activejobs')->paginate(12);

        // Only industries used by active companies (IndustryType::all is ~480 + translations)
        $industryIds = Company::query()
            ->active()
            ->whereNotNull('industry_type_id')
            ->distinct()
            ->pluck('industry_type_id');

        $industries = IndustryType::query()
            ->whereIn('id', $industryIds)
            ->get()
            ->sortBy('name');

        // Keep selected industries in the set for chips/labels
        $selectedNames = array_filter((array) $request->industry_type);
        if ($selectedNames) {
            $extra = IndustryTypeTranslation::whereIn('name', $selectedNames)->pluck('industry_type_id');
            $missing = $extra->diff($industryIds);
            if ($missing->isNotEmpty()) {
                $industries = $industries
                    ->merge(IndustryType::whereIn('id', $missing)->get())
                    ->sortBy('name');
            }
        }

        $team_sizes = TeamSize::all()->sortBy('name');
        $organization_types = OrganizationType::all();

        return [
            'companies' => $companies,
            'industries' => $industries,
            'organization_types' => $organization_types,
            'teamsizes' => $team_sizes,
        ];
    }
}
