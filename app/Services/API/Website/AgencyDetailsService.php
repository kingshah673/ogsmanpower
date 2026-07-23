<?php

namespace App\Services\API\Website;

use App\Models\Agency;
use App\Models\Job;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use F9Web\ApiResponseHelpers;
use Modules\Location\Entities\Country;

class AgencyDetailsService
{
    use ApiResponseHelpers;

    public function execute($username)
    {
        $user = User::select(['id', 'name', 'username'])->with(['contactInfo', 'socialInfo'])->where('username', $username)->first();

        if (! $user) {
            return $this->respondNotFound(__('user_not_found'));
        }

        $agencyDetails = Agency::with(
            'organization',
            'industry',
            'team_size:id,name',
        )->where('user_id', $user->id)->withCount([
            'jobs as activejobs' => function ($q) {
                $q->where('status', true);
                $q->where('deadline', '>=', Carbon::now()->toDateString());
                $selected_country = session()->get('selected_country');
                if ($selected_country && $selected_country != null && $selected_country != 'all') {
                    $country = selected_country()->name;
                    $q->where('country', 'LIKE', "%$country%");
                } else {

                    $setting = Setting::first();
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
                'bookmarkCandidateAgency as candidatemarked' => function ($q) {
                    $q->where('user_id', auth('sanctum')->id());
                },
            ])
            ->withCasts(['candidatemarked' => 'boolean'])
            ->first();

        // open_jobs Jobs With Single && Multiple Country Base
        $open_jobs_query = Job::withoutEdited()->with('agency');

        $setting = Setting::first();
        if ($setting->app_country_type == 'single_base') {
            if ($setting->app_country) {

                $country = Country::where('id', $setting->app_country)->first();
                if ($country) {
                    $open_jobs_query->where('country', 'LIKE', "%$country->name%");
                }
            }
        } else {
            $selected_country = request()->header('selected_country');

            if ($selected_country && $selected_country != null) {
                $open_jobs_query->where('country', 'LIKE', "%$selected_country%");
            }
        }
        $open_jobs = $open_jobs_query->agencyJobs($agencyDetails->id)->with('job_type')->latest()->paginate(8);

        return [
            'agencyDetails' => $agencyDetails,
            'user' => $user,
            'open_jobs' => $open_jobs,
        ];
    }
}
