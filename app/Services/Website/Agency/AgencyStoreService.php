<?php

namespace App\Services\Website\Agency;

use App\Http\Traits\JobAble;
use App\Models\Admin;
use App\Models\CandidateJobAlert;
use App\Models\AgencyAttributeTranslation;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobCategoryTranslation;
use App\Models\JobRole;
use App\Models\JobRoleTranslation;
use App\Notifications\Admin\NewJobAvailableNotification;
use App\Notifications\Website\Candidate\RelatedJobNotification;
use App\Notifications\Website\Agency\JobCreatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class AgencyStoreService
{
    use JobAble;

    /**
     * Create a job from AI-parsed advertisement data (Sophia / batch).
     *
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $shared
     */
    public function createFromParsedJob(array $job, array $shared = []): Job
    {
        return DB::transaction(function () use ($job, $shared) {
            storePlanInformation();
            $userPlan = session('user_plan');

            if (! $userPlan || (int) ($userPlan->job_limit ?? 0) < 1) {
                throw new \RuntimeException(__('you_have_reached_your_plan_limit_please_upgrade_your_plan'));
            }

            $title = (string) ($job['job_title'] ?? '');
            if ($title === '') {
                throw new \InvalidArgumentException('Job title is required.');
            }

            $categoryInput = $job['category_id'] ?? $job['industry'] ?? $shared['category_id'] ?? $shared['industry'] ?? 'General';
            $jobCategory = JobCategoryTranslation::where('job_category_id', $categoryInput)
                ->orWhere('name', $categoryInput)
                ->first();

            if (! $jobCategory) {
                $newCategory = JobCategory::create(['name' => (string) $categoryInput]);
                foreach (loadLanguage() as $language) {
                    $newCategory->translateOrNew($language->code)->name = (string) $categoryInput;
                }
                $newCategory->save();
                $jobCategoryId = $newCategory->id;
            } else {
                $jobCategoryId = $jobCategory->job_category_id;
            }

            $roleName = (string) ($job['job_role'] ?? $title);
            $jobRole = JobRoleTranslation::where('job_role_id', $roleName)->orWhere('name', $roleName)->first();
            if (! $jobRole) {
                $newRole = JobRole::create(['name' => $roleName]);
                foreach (loadLanguage() as $language) {
                    $newRole->translateOrNew($language->code)->name = $roleName;
                }
                $newRole->save();
                $jobRoleId = $newRole->id;
            } else {
                $jobRoleId = $jobRole->job_role_id;
            }

            $deadline = Carbon::parse(now()->addDays(setting('job_deadline_expiration_limit')))->format('Y-m-d');
            $rawDeadline = $job['deadline'] ?? $shared['deadline'] ?? null;
            if ($rawDeadline) {
                try {
                    $deadline = Carbon::parse($rawDeadline)->format('Y-m-d');
                } catch (\Throwable $e) {
                }
            }

            $salaryMode = in_array($job['salary_mode'] ?? '', ['range', 'custom'], true)
                ? $job['salary_mode']
                : 'custom';

            $description = (string) ($job['description'] ?? '');
            if (mb_strlen(strip_tags($description)) < 30) {
                $description = '<p>'.e($title).'</p>'.$description;
            }

            $applyOn = 'app';
            if (! empty($job['apply_email']) || ! empty($shared['apply_email'])) {
                $applyOn = 'email';
            } elseif (! empty($job['apply_url']) || ! empty($shared['apply_url'])) {
                $applyOn = 'custom_url';
            }

            $country = $job['country'] ?? $shared['country'] ?? null;
            $location = $job['location'] ?? $shared['location'] ?? $country;
            if ($location || $country) {
                session()->put('location', [
                    'country' => $country ?? $location,
                    'region' => $job['city'] ?? $shared['city'] ?? '',
                    'district' => '',
                    'exact_location' => (string) $location,
                ]);
            }

            $ip = request()->ip();
            if (app()->environment('local')) {
                $ip = '8.8.8.8';
            }

            $ipCountry = null;
            try {
                $locationData = Cache::remember("ip_location_{$ip}", now()->addMinutes(30), function () use ($ip) {
                    return Http::timeout(3)->get("http://ip-api.com/json/{$ip}")->json();
                });
                $ipCountry = $locationData['country'] ?? null;
            } catch (\Throwable $e) {
            }

            $educationId = \App\Models\Education::query()->value('id');
            $experienceId = \App\Models\Experience::query()->value('id');
            $salaryTypeId = \App\Models\SalaryType::query()->value('id');
            $jobTypeId = \App\Models\JobType::query()->value('id');

            $jobCreated = Job::create([
                'title' => $title,
                'agency_id' => currentAgency()->id,
                'category_id' => $jobCategoryId,
                'role_id' => $jobRoleId,
                'education_id' => $job['education_id'] ?? $educationId,
                'experience_id' => $job['experience_id'] ?? $experienceId,
                'salary_mode' => $salaryMode,
                'custom_salary' => $salaryMode === 'custom'
                    ? ($job['custom_salary'] ?? ($job['min_salary'] && $job['max_salary']
                        ? $job['min_salary'].' - '.$job['max_salary'].' '.($job['currency'] ?? 'USD')
                        : 'Competitive'))
                    : null,
                'min_salary' => $salaryMode === 'range' ? ($job['min_salary'] ?? null) : null,
                'max_salary' => $salaryMode === 'range' ? ($job['max_salary'] ?? null) : null,
                'salary_type_id' => $job['salary_type_id'] ?? $salaryTypeId,
                'deadline' => $deadline,
                'job_type_id' => $job['job_type_id'] ?? $jobTypeId,
                'vacancies' => max(1, (int) ($job['vacancies'] ?? 1)),
                'apply_on' => $applyOn,
                'apply_email' => $job['apply_email'] ?? $shared['apply_email'] ?? null,
                'apply_url' => $job['apply_url'] ?? $shared['apply_url'] ?? null,
                'description' => $description,
                'title_ar' => $job['job_title_ar'] ?? null,
                'description_ar' => $job['description_ar'] ?? null,
                'featured' => 0,
                'highlight' => 0,
                'is_remote' => ! empty($job['is_remote']) ? 1 : 0,
                'status' => initialJobStatus(),
                'job_roles' => 'public',
                'currency' => $job['currency'] ?? 'USD',
                'min_age' => $job['min_age'] ?? null,
                'max_age' => $job['max_age'] ?? null,
                'gender' => $job['gender'] ?? null,
                'city_limit' => 0,
                'education_limit' => 0,
                'experience_limit' => 0,
                'age_limit' => 0,
                'gender_limit' => 0,
                'ip_address' => $ip,
                'ip_country' => $ipCountry,
            ]);

            updateMap($jobCreated);
            finalizeJobForListing($jobCreated);

            $agencyPlan = currentAgency()->userPlan()->first();
            if ($agencyPlan) {
                $agencyPlan->job_limit = $agencyPlan->job_limit - 1;
                $agencyPlan->save();
            }
            storePlanInformation();

            Notification::send(authUser(), new JobCreatedNotification($jobCreated));

            return $jobCreated;
        });
    }

    /**
     * Store job
     *
     * @return Job $jobCreated
     */
    public function execute($request): Job
    {
        // Check if user has reached the job limit
        storePlanInformation();
        $userPlan = session('user_plan');

        if ((int) ($userPlan->job_limit ?? 0) < 1) {
            throw new \RuntimeException(__('you_have_reached_your_plan_limit_please_upgrade_your_plan'));
        }

        $min = $request->min_salary;
        $max = $request->max_salary;

        $request->validate([
            'min_salary' => 'nullable|numeric|between:0,' . $max,
            'max_salary' => 'nullable|numeric|min:' . $min,
        ]);

        if ($request->apply_on === 'custom_url') {
            $request->validate([
                'apply_url' => 'required|url',
            ]);
        }
        if ($request->apply_on === 'email') {
            $request->validate([
                'apply_email' => 'required|email',
            ]);
        }

        // Highlight & featured
        $highlight = $request->badge == 'highlight' ? 1 : 0;
        $featured = $request->badge == 'featured' ? 1 : 0;

        // Job Category
        $job_category_request = $request->category_id;

        $job_category = JobCategoryTranslation::where('job_category_id', $job_category_request)->orWhere('name', $job_category_request)->first();
        if (! $job_category) {
            $new_job_category = JobCategory::create(['name' => $job_category_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_job_category->translateOrNew($language->code)->name = $job_category_request;
            }
            $new_job_category->save();

            $job_category_id = $new_job_category->id;
        } else {
            $job_category_id = $job_category->job_category_id;
        }

        // Job Role
        $job_role_request = $request->role_id;

        $job_category = JobRoleTranslation::where('job_role_id', $job_role_request)->orWhere('name', $job_role_request)->first();

        if (! $job_category) {
            $new_job_role = JobRole::create(['name' => $job_role_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_job_role->translateOrNew($language->code)->name = $job_role_request;
            }
            $new_job_role->save();

            $job_role_id = $new_job_role->id;
        } else {
            $job_role_id = $job_category->job_role_id;
        }

        $deadline = Carbon::parse(now()->addDays(setting('job_deadline_expiration_limit')))->format('Y-m-d');
        if ($request->custom_title !== null) {
            $title = $request->custom_title;
        } else {
            $title = $request->title;
        }

        $ip = $request->ip();

        if (app()->environment('local')) {
            $ip = '8.8.8.8';
        }

        $country = null;
        try {
            $location = Cache::remember("ip_location_{$ip}", now()->addMinutes(30), function () use ($ip) {
                return Http::timeout(3)->get("http://ip-api.com/json/{$ip}")->json();
            });
            $country = $location['country'] ?? null;
        } catch (\Throwable $e) {
            // Non-blocking when geo lookup fails.
        }

        $jobCreated = Job::create([
            'title' => $title,
            'agency_id' => currentAgency()->id,
            'category_id' => $job_category_id,
            'role_id' => $job_role_id,
            'education_id' => $request->education,
            'experience_id' => $request->experience,
            'salary_mode' => $request->salary_mode,
            'custom_salary' => $request->custom_salary,
            'min_salary' => $request->min_salary,
            'max_salary' => $request->max_salary,
            'salary_type_id' => $request->salary_type,
            'deadline' => $deadline,
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
            'status' => initialJobStatus(),
            'job_roles' => 'public',


            'currency'=>$request->currency,
            'min_age' => $request->min_age,
            'max_age' => $request->max_age,
            'gender' => $request->gender,
            'city_limit' => $request->city_limit ?? 0,
            'education_limit' => $request->education_limit ?? 0,
            'experience_limit' => $request->experience_limit ?? 0,
            'age_limit' => $request->age_limit ?? 0,
            'gender_limit' => $request->gender_limit ?? 0,
            'ip_address' => $ip,
            'ip_country' => $country,
        ]);

        // Location
        updateMap($jobCreated);
        finalizeJobForListing($jobCreated);

        // Question
        if (isset($request->agencyQuestions) && $request->has('agencyQuestions')) {
            $jobCreated->questions()->attach($request->get('agencyQuestions'));
        }

        // Benefits
        $benefits = $request->benefits ?? null;
        if ($benefits) {
            $this->jobBenefitsInsert($request->benefits, $jobCreated);
        }

        // Tags
        $tags = $request->tags ?? null;
        if ($tags) {
            $this->jobTagsInsert($request->tags, $jobCreated);
        }

        // skills
        $skills = $request->skills ?? null;
        if ($skills) {
            $this->jobSkillsInsert($request->skills, $jobCreated);
        }

        // custom addition by moeed
        if ($request->has('dynamic_inputs')) {
            foreach ($request->dynamic_inputs as $input) {
                // Validate that the necessary fields are present
                if (isset($input['id']) && isset($input['value'])) {
                    AgencyAttributeTranslation::create([
                        'agency_id' => currentAgency()->id, // Assuming this function returns the current Agency
                        'job_id' => $jobCreated->id,
                        'agency_attribute_id' => $input['id'], // This is the attribute ID
                        'attribute_value' => $input['value'],  // This is the input value
                    ]);
                }
            }
        }

        if ($jobCreated) {
            $user_plan = currentAgency()->userPlan()->first();

            $user_plan->job_limit = $user_plan->job_limit - 1;
            if ($featured) {
                $user_plan->featured_job_limit = $user_plan->featured_job_limit - 1;
            }
            if ($highlight) {
                $user_plan->highlight_job_limit = $user_plan->highlight_job_limit - 1;
            }
            $user_plan->save();

            storePlanInformation();

            Notification::send(authUser(), new JobCreatedNotification($jobCreated));

            if ($jobCreated->status == 'active') {
                $candidates = CandidateJobAlert::where('job_role_id', $jobCreated->role_id)->get();

                foreach ($candidates as $candidate) {
                    if ($candidate->candidate->received_job_alert) {
                        $candidate->candidate->user->notify(new RelatedJobNotification($jobCreated));
                    }
                }
            }

            if (checkMailConfig()) {
                // make notification to admins for approved
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    Notification::send($admin, new NewJobAvailableNotification($admin, $jobCreated));
                }
            }
        }

        return $jobCreated;
    }
}
