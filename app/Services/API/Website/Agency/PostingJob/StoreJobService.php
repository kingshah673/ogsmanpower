<?php

namespace App\Services\API\Website\Agency\PostingJob;

use App\Http\Traits\AgencyJobTrait;
use App\Http\Traits\Jobable;
use App\Models\Admin;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobCategoryTranslation;
use App\Models\JobRole;
use App\Models\JobRoleTranslation;
use App\Notifications\Admin\NewJobAvailableNotification;
use App\Notifications\Website\Agency\JobCreatedNotification;
use Carbon\Carbon;
use F9Web\ApiResponseHelpers;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Language\Entities\Language;

class StoreJobService
{
    use ApiResponseHelpers, AgencyJobTrait, Jobable;

    public function execute($request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category_id' => 'required',
            'role_id' => 'required',
            'experience' => 'required',
            'education' => 'required',
            'job_type' => 'required',
            'vacancies' => 'required',
            'salary_mode' => 'required',
            'custom_salary' => 'required_if:salary_mode,==,custom',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'salary_type' => 'required',
            'deadline' => 'required|date',
            'description'    => 'required',
            'title_ar'       => 'nullable|string|max:255',
            'description_ar' => 'nullable|string',
            'featured' => 'nullable|numeric',
            'is_remote' => 'nullable',
            'apply_on' => 'required',
            //   'location' => request()->method() == 'PUT' ? '' : Rule::requiredIf(!session('location'))
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['errors' => $validator->messages()], 422
            );
        }

        $min = $request->min_salary;
        $max = $request->max_salary;

        $request->validate([
            'min_salary' => 'nullable|numeric|between:0,'.$max,
            'max_salary' => 'nullable|numeric|min:'.$min,
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

            $languages = Language::all();
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

            $languages = Language::all();
            foreach ($languages as $language) {
                $new_job_role->translateOrNew($language->code)->name = $job_role_request;
            }
            $new_job_role->save();

            $job_role_id = $new_job_role->id;
        } else {
            $job_role_id = $job_category->job_role_id;
        }

        // Experience
        $education_request = $request->education;
        $education = Education::where('id', $education_request)->orWhere('name', $education_request)->first();
        if (! $education) {
            $education = Education::where('name', $education_request)->first();

            if (! $education) {
                $education = Education::create(['name' => $education_request]);
            }
        }

        // Education
        $experience_request = $request->experience;
        $experience = Experience::where('id', $experience_request)->orWhere('name', $experience_request)->first();
        if (! $experience) {
            $experience = Experience::where('name', $experience_request)->first();

            if (! $experience) {
                $experience = Experience::create(['name' => $experience_request]);
            }
        }

        $deadline = Carbon::parse(now()
            ->addDays(setting('job_deadline_expiration_limit')))
            ->format('Y-m-d');

        $jobCreated = Job::create([
            'title' => $request->title,
            'agency_id' => auth('sanctum')->user()->agency->id,
            'category_id' => $job_category_id,
            'role_id' => $job_role_id,
            'education_id' => $education->id,
            'experience_id' => $experience->id,
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
        ]);

        // Location
        // updateMap($jobCreated);

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

        if ($jobCreated) {
            $user_plan = auth('sanctum')->user()->agency->userPlan()->first();

            $user_plan->job_limit = $user_plan->job_limit - 1;
            if ($featured) {
                $user_plan->featured_job_limit = $user_plan->featured_job_limit - 1;
            }
            if ($highlight) {
                $user_plan->highlight_job_limit = $user_plan->highlight_job_limit - 1;
            }
            $user_plan->save();

            storePlanInformation();

            Notification::send(auth('sanctum')->user(), new JobCreatedNotification($jobCreated));

            if (checkMailConfig()) {
                // make notification to admins for approved
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    Notification::send($admin, new NewJobAvailableNotification($admin, $jobCreated));
                }
            }
        }

        return $this->respondWithSuccess([
            'data' => [
                'job' => $jobCreated,
                'message' => __('job_created_successfully'),
            ],
        ]);
    }
}
