<?php

namespace App\Services\Admin\Job;

use App\Http\Traits\JobAble;
use App\Models\Job;
use App\Models\CompanyAttributeTranslation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class JobUpdateService
{
    use JobAble;

    /**
     * Update job
     *
     * @return Job $job
     */
    public function execute($request, $job): Job
    {
        if ($request->custom_title !== null) {
            $title = $request->custom_title;
        } else {
            $title = $request->title;
        }
        $highlight = $request->badge == 'highlight' ? 1 : 0;
        $featured = $request->badge == 'featured' ? 1 : 0;

        // Job title update
        // $job->title = $request->title;
        $job->title  = $title;

        $title_changed = $job->isDirty('title');
        if ($title_changed) {
            $job->update(['title' => $request->title]);
            $job->update(['title' => $title]);
        }
        $companyId = null;
        $companyName = null;

        if ($request->has('is_just_name')) {
            // he wants to update just name
            $companyName = $request->get('company_name');
        } else {
            $companyId = $request->get('company_id');
        }

        //job status update
        if ($request->deadline !== now()->format('Y-m-d') || $job->where('status', 'expired')->first()) {
            $status = 'active';
        }
        if ($request->deadline == now()->format('Y-m-d')) {
            $status = 'expired';
        }

        $ip = $request->ip();

        if (app()->environment('local')) {
            $ip = '8.8.8.8'; // Example public IP for testing
        }

        $location = Cache::remember("ip_location_{$ip}", now()->addMinutes(30), function () use ($ip) {
            return Http::get("http://ip-api.com/json/{$ip}")->json();
        });

        $country = $location['country'] ?? null;
        $city = $location['city'] ?? null;

        $vpnCheck = Cache::remember("vpn_check_{$ip}", now()->addMinutes(30), function () use ($ip) {
            return Http::get("https://ipqualityscore.com/api/json/ip/YOUR_API_KEY/{$ip}")->json();
        });

        $isVpn = $vpnCheck['vpn'] ?? false; // Detect VPN usage

        if ($isVpn) {
            return response()->json([
                'message' => 'VPN usage detected. Please disable VPN to continue.',
                'ip' => $ip,
                'location' => $location
            ], 403);
        }


        $job->update([
            'company_id' => $companyId,
            'company_name' => $companyName,
            'admin_id' => auth()->user()->id,
            'category_id' => $request->category_id,
            'role_id' => $request->role_id,
            'salary_mode' => $request->salary_mode,
            'custom_salary' => $request->custom_salary,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
            'salary_type_id' => $request->salary_type,
            'deadline' => Carbon::parse($request->deadline)->format('Y-m-d'),
            'education_id' => $request->education,
            'experience_id' => $request->experience,
            'job_type_id' => $request->job_type,
            'vacancies' => $request->vacancies,
            'apply_on' => $request->apply_on,
            'apply_email'    => $request->apply_email ?? null,
            'apply_url'      => $request->apply_url ?? null,
            'description'    => $request->description,
            'title_ar'       => $request->title_ar ?? null,
            'description_ar' => $request->description_ar ?? null,
            'featured' => $featured,
            'highlight' => $highlight,
            'is_remote' => $request->is_remote ?? 0,
            'status' => $status,

            // custom addition by moeed
            'min_age' => $request->min_age,
            'max_age' => $request->max_age,
            'gender' => $request->gender,
            'city_limit' => $request->city_limit ?? 0,
            'education_limit' => $request->education_limit ?? 0,
            'experience_limit' => $request->experience_limit ?? 0,
            'age_limit' => $request->age_limit ?? 0,
            'gender_limit' => $request->gender_limit ?? 0,
            'industry_type' => $request->industry_type,
            'ip_address' => $ip,
            'ip_country' => $country,

        ]);

        // Benefits
        $this->jobBenefitsSync($request->benefits, $job);

        // Tags
        $this->jobTagsSync($request->tags, $job);

        // skills
        $skills = $request->skills ?? null;
        if ($skills) {
            $this->jobSkillsSync($request->skills, $job);
        }

        // custom addition by moeed
        if ($request->has('dynamic_inputs')) {
            foreach ($request->dynamic_inputs as $input) {
                if (isset($input['id']) && isset($input['value'])) {
                    // Update existing or create a new translation
                    CompanyAttributeTranslation::updateOrCreate(
                        [
                            'company_id' => $companyId, // Match the current company
                            'job_id' => $job->id,
                            'company_attribute_id' => $input['id'], // Match the company attribute
                        ],
                        [
                            'attribute_value' => $input['value'], // Use the submitted value
                        ]
                    );
                }
            }
        }
        // location
        updateMap($job);

        return $job;
    }
}
