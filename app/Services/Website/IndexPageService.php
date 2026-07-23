<?php

namespace App\Services\Website;

use App\Http\Traits\HasCountryBasedJobs;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\IndustryTypeTranslation;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobRole;
use Modules\Location\Entities\Country;
use Modules\Testimonial\Entities\Testimonial;

class IndexPageService
{
    use HasCountryBasedJobs;

    /**
     * Get index page data
     */
    public function execute(): array
    {
        $data = [];

        /*
        |--------------------------------------------------------------------------
        | COUNTERS
        |--------------------------------------------------------------------------
        */

        $data['newjobs'] = $this->filterCountryBasedJobs(
            Job::withoutEdited()->newJobs()
        )->count();

        $data['companies']  = Company::count();
        $data['candidates'] = Candidate::count();
        $data['testimonials'] = Testimonial::whereCode(currentLangCode())->get();

        /*
        |--------------------------------------------------------------------------
        | TOP COMPANIES
        |--------------------------------------------------------------------------
        */

        $data['top_companies'] = Company::with('user', 'user.contactInfo', 'industry.translations')
            ->withCount([
                'jobs as jobs_count' => function ($q) {
                    $q->where('status', 'active');
                    $q = $this->filterCountryBasedJobs($q);
                },
            ])
            ->latest('jobs_count')
            ->take(9)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | FEATURED JOBS (WITH SMART FALLBACK)
        |--------------------------------------------------------------------------
        */

        $featured_jobs_query = Job::query()
            ->withoutEdited()
            ->with('company.user', 'job_type:id', 'category')
            ->withCount([
                'bookmarkJobs',
                'appliedJobs',
                'bookmarkJobs as bookmarked' => function ($q) {
                    $q->where(
                        'candidate_id',
                        auth('user')->check() && currentCandidate()
                            ? currentCandidate()->id
                            : ''
                    );
                },
                'appliedJobs as applied' => function ($q) {
                    $q->where(
                        'candidate_id',
                        auth('user')->check() && currentCandidate()
                            ? currentCandidate()->id
                            : ''
                    );
                },
            ]);

        $featured_jobs = applyCandidateAgeFilter($this->filterCountryBasedJobs($featured_jobs_query))
            ->where('featured', 1)
            ->publicListing()
            ->deadlineActive()
            ->active()
            ->latest()
            ->take(4)
            ->get();

        // If no featured jobs exist → fallback to latest active jobs
        if ($featured_jobs->isEmpty()) {
            $featured_jobs = applyCandidateAgeFilter($this->filterCountryBasedJobs($featured_jobs_query))
                ->publicListing()
                ->deadlineActive()
                ->active()
                ->latest()
                ->take(4)
                ->get();
        }

        $data['featured_jobs'] = $featured_jobs;

        /*
        |--------------------------------------------------------------------------
        | POPULAR CATEGORIES
        |--------------------------------------------------------------------------
        */

        $setting = loadSetting();
        $is_single_base_country_type = $setting->app_country_type === 'single_base';

        $popular_categories_list = JobCategory::query()
            ->withCount([
                'jobs' => function ($query) use ($setting, $is_single_base_country_type) {

                    $country = null;

                    if ($is_single_base_country_type && $setting->app_country) {
                        $country = Country::find($setting->app_country);
                    }

                    if ($selected_country = session()->get('selected_country')) {
                        $country = Country::find($selected_country);
                    }

                    return $query->openPosition()
                        ->when($country, function ($query) use ($country) {
                            $query->where('country', 'LIKE', "%{$country->name}%");
                        });
                }
            ])
            ->orderByDesc('jobs_count')
            ->take(8)
            ->get();

        $data['popular_categories'] = $popular_categories_list->values();

        /*
        |--------------------------------------------------------------------------
        | POPULAR ROLES
        |--------------------------------------------------------------------------
        */

        $popular_roles_list = JobRole::withCount('jobs')
            ->take(8)
            ->get()
            ->map(function ($role) use ($setting, $is_single_base_country_type) {

                if ($is_single_base_country_type && $setting->app_country) {

                    $country = Country::find($setting->app_country);

                    if ($country) {
                        $role->open_position_count = $role->jobs()
                            ->where('country', 'LIKE', "%{$country->name}%")
                            ->openPosition()
                            ->count();
                    }

                } else {

                    $selected_country = session()->get('selected_country');

                    if ($selected_country) {
                        $country = selected_country()->name;
                        $role->open_position_count = $role->jobs()
                            ->where('country', 'LIKE', "%{$country}%")
                            ->openPosition()
                            ->count();
                    } else {
                        $role->open_position_count = $role->jobs()
                            ->openPosition()
                            ->count();
                    }
                }

                return $role;
            });

        $data['popular_roles'] = $popular_roles_list
            ->sortByDesc('open_position_count')
            ->values();

        /*
        |--------------------------------------------------------------------------
        | EXTRA SECTIONS
        |--------------------------------------------------------------------------
        */

        $data['top_categories'] = JobCategory::withCount('jobs')
            ->latest('jobs_count')
            ->take(4)
            ->get();

        $data['candidate_countries'] = Country::where('candidates_by_country', 1)->get();
        $data['job_countries'] = Country::where('jobs_by_country', 1)->get();
        $data['job_industries'] = IndustryTypeTranslation::where('jobs_by_industry', 1)->get();
        $data['candidates_industries'] = IndustryTypeTranslation::where('candidates_by_industry', 1)->get();

        /*
        |--------------------------------------------------------------------------
        | RESUMES (IF CANDIDATE LOGGED IN)
        |--------------------------------------------------------------------------
        */

        if (auth('user')->check() && authUser()->role === 'candidate') {
            $data['resumes'] = currentCandidate()->resumes;
        } else {
            $data['resumes'] = [];
        }

        return $data;
    }
}