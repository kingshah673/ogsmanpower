<?php

namespace App\Services\API\Website\Agency\PostingJob;

use App\Models\Benefit;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobCategory;
use App\Models\JobRole;
use App\Models\JobType;
use App\Models\SalaryType;
use App\Models\Tag;
use F9Web\ApiResponseHelpers;

class FetchEditJobDataService
{
    use ApiResponseHelpers;

    public function execute($job)
    {
        $job->load('tags', 'benefits');
        $data['job'] = $job;
        $data['jobCategories'] = JobCategory::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['roles'] = JobRole::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['benefits'] = Benefit::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['tags'] = Tag::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['experiences'] = Experience::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['educations'] = Education::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['job_types'] = JobType::all();
        $data['salary_types'] = SalaryType::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['start_day'] = $job->created_at->diffInDays();
        $data['end_day'] = $data['start_day'] + setting('job_deadline_expiration_limit');

        return $this->respondWithSuccess([
            'data' => $data,
        ]);
    }
}
