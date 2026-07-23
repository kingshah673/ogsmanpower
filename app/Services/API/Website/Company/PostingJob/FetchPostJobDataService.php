<?php

namespace App\Services\API\Website\Company\PostingJob;

use App\Models\Benefit;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobCategory;
use App\Models\JobRole;
use App\Models\JobType;
use App\Models\SalaryType;
use App\Models\Tag;
use F9Web\ApiResponseHelpers;

class FetchPostJobDataService
{
    use ApiResponseHelpers;

    public function execute()
    {
        $data['jobCategories'] = JobCategory::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['roles'] = JobRole::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['benefits'] = Benefit::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['tags'] = Tag::all()
            ->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['experiences'] = Experience::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['educations'] = Education::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);
        $data['job_types'] = JobType::all();
        $data['salary_types'] = SalaryType::all()->map(fn ($data) => ['id' => $data->id, 'name' => $data->name]);

        return $this->respondWithSuccess([
            'data' => $data,
        ]);
    }
}
