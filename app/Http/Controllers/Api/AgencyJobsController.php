<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use App\Models\CandidateResume;
use App\Models\Job;
use Carbon\Carbon;
use F9Web\ApiResponseHelpers;
use Illuminate\Http\Request;

class AgencyJobsController extends Controller
{
    use ApiResponseHelpers;

    // get all jobs for athenticate Agency
    public function getJobs()
    {
        $jobs = Job::where('agency_id', auth('sanctum')->user()->agency->id)
            ->select(['id', 'title', 'agency_id', 'country', 'max_salary', 'min_salary', 'job_type_id', 'slug', 'deadline', 'status'])
        // ->whereDate('deadline', '>', Carbon::now()->toDateString())
            ->with('agency:id', 'job_type')->withCount('appliedJobs')
        // ->where('status', request('status'))
            ->when($status = request('status'), function ($query) use ($status) {
                $query->where('status', $status);
            }, function ($query) {
                $query->where('status', 'active');
            })
            ->latest()->paginate(5)->withQueryString();

        return $this->respondWithSuccess([
            'data' => $jobs,
        ]);
    }

    // Retrived all job applications
    public function applications($id)
    {
        $application_groups = auth('sanctum')->user()
            ->agency
            ->applicationGroups()
            ->with(['applications' => function ($query) use ($id) {
                $query->where('job_id', $id)->with(['apiCandidate' => function ($query) {
                    return $query->select('id', 'user_id', 'profession_id', 'experience_id', 'education_id')

                        ->with('profession', 'education:id,name', 'experience:id,name', 'user:id,name,username,image');
                }]);
            }])
            ->get();

        return $this->respondWithSuccess([
            'data' => $application_groups,
        ]);
    }

    // Job application group update
    // Param $id = job application id
    public function applicationGroupUpdate($id, Request $request)
    {
        AppliedJob::find($id)->update([
            'application_group_id' => $request->group,
        ]);

        return $this->respondWithSuccess([
            'data' => [
                'message' => 'Application updated successful!',
            ],
        ]);

    }

    // download candidate resume
    // Param $id = resume id
    public function downloadCv($id)
    {
        // CandidateResume $resume
        $resume = CandidateResume::findOrFail($id);
        // $filename = time() . '.pdf';

        // $headers = ['Content-Type: application/pdf',  'filename' => $filename,];
        // $fileName = rand() . '-resume' . '.pdf';

        // return response()->download($resume->file, $fileName, $headers);
        return asset($resume->file);

    }
}
