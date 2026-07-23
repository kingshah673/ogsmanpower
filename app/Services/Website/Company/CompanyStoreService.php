<?php

namespace App\Services\Website\Company;

use App\Http\Traits\JobAble;
use App\Models\Admin;
use App\Models\CandidateJobAlert;
use App\Models\CompanyAttributeTranslation;
use App\Models\Education;
use App\Models\EducationTranslation;
use App\Models\Experience;
use App\Models\ExperienceTranslation;
use App\Models\IndustryType;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobCategoryTranslation;
use App\Models\JobRole;
use App\Models\JobRoleTranslation;
use App\Models\JobType;
use App\Models\SalaryType;
use App\Notifications\Admin\NewJobAvailableNotification;
use App\Notifications\Website\Candidate\RelatedJobNotification;
use App\Notifications\Website\Company\JobCreatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class CompanyStoreService
{
    use JobAble;

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

        if (! $userPlan || (int) ($userPlan->job_limit ?? 0) < 1) {
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

        // Job Category (form posts IndustryType id — jobs.category_id references job_categories)
        $job_category_id = $this->resolveJobCategoryId($request->category_id);

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
            // Non-blocking: job posting must not fail when geo lookup is unavailable.
        }

        $jobCreated = Job::create([
            'title' => $title,
            'company_id' => currentCompany()->id,
            'category_id' => $job_category_id,
            'role_id' => $job_role_id,
            'education_id' => $request->education ?: $this->defaultEducationId(),
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
            'apply_email' => $request->apply_email ?? null,
            'apply_url' => $request->apply_url ?? null,
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
        if (isset($request->companyQuestions) && $request->has('companyQuestions')) {
            $jobCreated->questions()->attach($request->get('companyQuestions'));
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
                    CompanyAttributeTranslation::create([
                        'company_id' => currentCompany()->id, // Assuming this function returns the current company
                        'job_id' => $jobCreated->id,
                        'company_attribute_id' => $input['id'], // This is the attribute ID
                        'attribute_value' => $input['value'],  // This is the input value
                    ]);
                }
            }
        }

        if ($jobCreated) {
            $user_plan = currentCompany()->userPlan()->first();

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

    /**
     * Create a job from AI-parsed advertisement data (batch or single).
     *
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $shared
     */
    public function createFromParsedJob(array $job, array $shared = []): Job
    {
        return DB::transaction(function () use ($job, $shared) {
            return $this->createFromParsedJobWithinTransaction($job, $shared);
        });
    }

    /**
     * @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $shared
     */
    private function createFromParsedJobWithinTransaction(array $job, array $shared = []): Job
    {
        storePlanInformation();
        $userPlan = session('user_plan');

        if (! $userPlan || (int) ($userPlan->job_limit ?? 0) < 1) {
            throw new \RuntimeException(__('you_have_reached_your_plan_limit_please_upgrade_your_plan'));
        }

        $title = (string) ($job['job_title'] ?? '');
        if ($title === '') {
            throw new \InvalidArgumentException(__('Job title is required.'));
        }

        $categoryInput = $job['category_id'] ?? $job['industry'] ?? $shared['category_id'] ?? $shared['industry'] ?? 'General';
        $job_category_id = $this->resolveJobCategoryId($categoryInput);

        $roleName = (string) ($job['job_role'] ?? $title);
        $job_role_id = $this->resolveOrCreateJobRoleId($roleName);

        $deadline = $this->resolveParsedDeadline($job, $shared);

        $this->seedSessionLocationForParsedJob($job, $shared);

        $salaryMode = in_array($job['salary_mode'] ?? '', ['range', 'custom'], true)
            ? $job['salary_mode']
            : 'custom';

        $description = (string) ($job['description'] ?? '');
        if (mb_strlen(strip_tags($description)) < 30) {
            $description = '<p>' . e($title) . '</p>' . $description;
        }

        $applyOn = 'app';
        if (! empty($job['apply_email']) || ! empty($shared['apply_email'])) {
            $applyOn = 'email';
        } elseif (! empty($job['apply_url']) || ! empty($shared['apply_url'])) {
            $applyOn = 'custom_url';
        }

        $ip = request()->ip();
        if (app()->environment('local')) {
            $ip = '8.8.8.8';
        }

        $ipCountry = null;
        try {
            $location = Cache::remember("ip_location_{$ip}", now()->addMinutes(30), function () use ($ip) {
                return Http::timeout(3)->get("http://ip-api.com/json/{$ip}")->json();
            });
            $ipCountry = $location['country'] ?? null;
        } catch (\Throwable $e) {
        }

        $jobCreated = Job::create([
            'title' => $title,
            'company_id' => currentCompany()->id,
            'category_id' => $job_category_id,
            'role_id' => $job_role_id,
            'education_id' => $job['education_id'] ?? $this->defaultEducationId(),
            'experience_id' => $job['experience_id'] ?? $this->defaultExperienceId(),
            'salary_mode' => $salaryMode,
            'custom_salary' => $salaryMode === 'custom'
                ? ($job['custom_salary'] ?? ($job['min_salary'] && $job['max_salary']
                    ? $job['min_salary'] . ' - ' . $job['max_salary'] . ' ' . ($job['currency'] ?? 'USD')
                    : 'Competitive'))
                : null,
            'min_salary' => $salaryMode === 'range' ? ($job['min_salary'] ?? null) : null,
            'max_salary' => $salaryMode === 'range' ? ($job['max_salary'] ?? null) : null,
            'salary_type_id' => $job['salary_type_id'] ?? $this->defaultSalaryTypeId(),
            'deadline' => $deadline,
            'job_type_id' => $job['job_type_id'] ?? $this->defaultJobTypeId(),
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
            'education_limit' => empty($job['education_id']) ? 0 : 0,
            'experience_limit' => 0,
            'age_limit' => 0,
            'gender_limit' => 0,
            'ip_address' => $ip,
            'ip_country' => $ipCountry,
        ]);

        updateMap($jobCreated);
        finalizeJobForListing($jobCreated);

        if (! empty($job['benefits'])) {
            $this->jobBenefitsInsert($job['benefits'], $jobCreated);
        }
        if (! empty($job['tags'])) {
            $this->jobTagsInsert($job['tags'], $jobCreated);
        }
        if (! empty($job['skills'])) {
            $this->jobSkillsInsert($job['skills'], $jobCreated);
        }

        $user_plan = currentCompany()->userPlan()->first();
        $user_plan->job_limit = $user_plan->job_limit - 1;
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
            $admins = Admin::all();
            foreach ($admins as $admin) {
                Notification::send($admin, new NewJobAvailableNotification($admin, $jobCreated));
            }
        }

        return $jobCreated;
    }

    private function resolveOrCreateJobRoleId(string $roleName): int
    {
        $existing = JobRoleTranslation::query()
            ->where('job_role_id', $roleName)
            ->orWhere('name', $roleName)
            ->first();

        if ($existing) {
            return (int) $existing->job_role_id;
        }

        $newJobRole = JobRole::create(['name' => $roleName]);
        $languages = loadLanguage();
        foreach ($languages as $language) {
            $newJobRole->translateOrNew($language->code)->name = $roleName;
        }
        $newJobRole->save();

        return (int) $newJobRole->id;
    }

  /** @param  array<string, mixed>  $job
   * @param  array<string, mixed>  $shared
   */
    private function resolveParsedDeadline(array $job, array $shared): string
    {
        $raw = $job['deadline'] ?? $shared['deadline'] ?? null;
        if ($raw) {
            try {
                return Carbon::createFromFormat('d-m-Y', $raw)->format('Y-m-d');
            } catch (\Throwable $e) {
                try {
                    return Carbon::parse($raw)->format('Y-m-d');
                } catch (\Throwable $e) {
                }
            }
        }

        return Carbon::parse(now()->addDays(setting('job_deadline_expiration_limit')))->format('Y-m-d');
    }

    /** @param  array<string, mixed>  $job
     * @param  array<string, mixed>  $shared
     */
    private function seedSessionLocationForParsedJob(array $job, array $shared): void
    {
        $country = $job['country'] ?? $shared['country'] ?? null;
        $location = $job['location'] ?? $shared['location'] ?? $country;

        if (! $location && ! $country) {
            return;
        }

        session()->put('location', [
            'country' => $country ?? $location,
            'region' => $job['city'] ?? $shared['city'] ?? '',
            'district' => '',
            'exact_location' => (string) $location,
            'lng' => null,
            'lat' => null,
        ]);
    }

    private function defaultEducationId(): int
    {
        $any = EducationTranslation::query()
            ->where('name', 'like', '%Any%')
            ->value('education_id');

        if ($any) {
            return (int) $any;
        }

        return (int) Education::query()->orderBy('id')->value('id');
    }

    private function defaultExperienceId(): int
    {
        $fresher = ExperienceTranslation::query()
            ->where('name', 'like', '%Fresher%')
            ->value('experience_id');

        if ($fresher) {
            return (int) $fresher;
        }

        return (int) Experience::query()->orderBy('id')->value('id');
    }

    private function defaultJobTypeId(): int
    {
        return (int) JobType::query()->orderBy('id')->value('id');
    }

    private function defaultSalaryTypeId(): int
    {
        $monthly = SalaryType::query()
            ->whereHas('translations', fn ($q) => $q->where('name', 'like', '%Month%'))
            ->value('id');

        return (int) ($monthly ?: SalaryType::query()->orderBy('id')->value('id'));
    }

    /**
     * Map industry type (form category_id) to a valid job_categories.id.
     */
    private function resolveJobCategoryId(mixed $categoryRequest): int
    {
        if ($categoryRequest === null || $categoryRequest === '') {
            throw new \InvalidArgumentException(__('job_category_is_required'));
        }

        if (is_numeric($categoryRequest) && JobCategory::whereKey((int) $categoryRequest)->exists()) {
            return (int) $categoryRequest;
        }

        if (is_numeric($categoryRequest)) {
            $industry = IndustryType::find((int) $categoryRequest);
            if ($industry) {
                $name = trim((string) ($industry->name ?? ''));
                if ($name !== '') {
                    $translation = JobCategoryTranslation::where('name', $name)->first();
                    if ($translation) {
                        return (int) $translation->job_category_id;
                    }

                    $newJobCategory = JobCategory::create(['name' => $name]);
                    $languages = loadLanguage();
                    foreach ($languages as $language) {
                        $newJobCategory->translateOrNew($language->code)->name = $name;
                    }
                    $newJobCategory->save();

                    return (int) $newJobCategory->id;
                }
            }
        }

        $byName = JobCategoryTranslation::where('name', $categoryRequest)->first();
        if ($byName) {
            return (int) $byName->job_category_id;
        }

        $newJobCategory = JobCategory::create(['name' => (string) $categoryRequest]);
        $languages = loadLanguage();
        foreach ($languages as $language) {
            $newJobCategory->translateOrNew($language->code)->name = (string) $categoryRequest;
        }
        $newJobCategory->save();

        return (int) $newJobCategory->id;
    }
}
