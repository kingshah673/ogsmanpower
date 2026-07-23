<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobFormRequest;
use App\Http\Traits\JobAble;
use App\Imports\JobsImport;
use App\Models\AppliedJob;
use App\Models\Benefit;
use App\Models\CandidateJobAlert;
use App\Models\Company;
use App\Models\Agency;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\CompanyAttribute;
use App\Models\AgencyAttribute;
use App\Models\CompanyAttributeTranslation;
use App\Models\AgencyAttributeTranslation;
use App\Models\HireRequest;
use App\Models\JobRole;
use App\Models\JobTitle;
use App\Models\Profession;
use App\Models\JobType;
use App\Models\SalaryType;
use App\Models\Skill;
use App\Models\Tag;
use App\Notifications\JobApprovalNotification;
use App\Notifications\Website\Candidate\RelatedJobNotification;
use App\Services\Admin\Job\JobCreateService;
use App\Services\Admin\Job\JobListService;
use App\Services\Admin\Job\JobUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Location\Entities\Country;
use App\Mail\HireCandidateMail;
use App\Models\Candidate;
use App\Models\IndustryType;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class JobController extends Controller
{
    use JobAble;

    public function __construct()
    {
        $this->middleware('access_limitation')->only(['destroy', 'clone']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function myJob(Request $request)
    {
        // $myJobs = Job::where('admin_id', auth()->user()->id)
        //     ->with('job_type:id')
        //     ->latest()
        //     ->paginate(12)
        //     ->withQueryString();
        // return view('backend.Job.my-job', [
        //     'myJobs' => $myJobs,

        // ]);
        try {
            $query = Job::where('admin_id', auth()->user()->id)
                ->withCount('appliedJobs');

            // Status search
            if ($request->has('status') && $request->status != null) {
                $query->where('status', $request->status);
            }

            // Apply-on search
            if ($request->has('apply_on') && $request->apply_on != null) {
                $query->where('apply_on', $request->apply_on);
            }

            $myJobs = $query
                ->with('job_type:id')
                ->latest()
                ->paginate(12)
                ->withQueryString();

            foreach ($myJobs as $job) {
                if ($job->days_remaining < 1) {
                    $job->update([
                        'status' => 'expired',
                        'deadline' => null,
                    ]);
                }
            }

            return view('backend.Job.my-job', [
                'myJobs' => $myJobs,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }

    }
    public function saveJob(Request $request)
    {
        // Validate the request
        $request->validate([
            'job_id' => 'required|exists:jobs,id',
            'cv' => 'nullable|mimes:pdf,doc,docx|max:2048',
            'candidate_id' => 'nullable|exists:candidates,id',
            'cover_letter' => 'nullable|string',
            'resume_format' => 'nullable|string',
        ]);

        // Find the job
        $job = Job::findOrFail($request->job_id);

        // Prepare the common data for the insert
        $data = [
            'job_id' => $job->id,
            'admin_id' => auth()->user()->id,
            'company_id' => $job->company_id ?? null,
            'cover_letter' => $request->cover_letter,
            'application_group_id' => $job->company->applicationGroups->where('is_deleteable', false)->first()->id ?? 1,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if ($request->filled('resume_format') && Schema::hasColumn('applied_jobs', 'resume_format')) {
            $data['resume_format'] = $request->resume_format;
        }

        // Check if a CV is uploaded
        if ($request->hasFile('cv')) {
            $path = $request->file('cv')->store('public/cvs');
            if (Schema::hasColumn('applied_jobs', 'cv_path')) {
                $data['cv_path'] = $path;
            }
        }

        // Check if a candidate is selected
        if ($request->candidate_id) {
            $data['candidate_id'] = $request->candidate_id;

            if ($request->hasFile('cv')) {
                $filePath = uploadFileToPublic($request->file('cv'), 'file/candidates/');
                $resume = \App\Models\CandidateResume::create([
                    'candidate_id' => $request->candidate_id,
                    'name' => pathinfo($request->file('cv')->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Admin Apply CV',
                    'file' => $filePath,
                ]);
                $data['candidate_resume_id'] = $resume->id;
            }
        }

        // Insert the record into the database
        DB::table('applied_jobs')->insert($data);

        return redirect()->route('job.applyJob')->with('success', 'Job applied successfully.');
    }

    public function assignRoles(Request $request, $jobId)
{
    $request->validate([
        'roles'   => 'nullable|array',
        'agents'  => 'nullable|array',
        'agencies'=> 'nullable|array',
    ]);

    $job = Job::findOrFail($jobId);

    // ✅ SAVE ROLES
    $job->job_roles = implode(',', $request->roles ?? []);

    // ✅ SAVE AGENTS
    if ($request->has('agents')) {
        $job->assigned_agents = in_array('all', $request->agents)
            ? json_encode(['all'])
            : json_encode($request->agents);
    } else {
        $job->assigned_agents = null;
    }

    // ✅ SAVE AGENCY (FIXED COLUMN NAME)
    if ($request->has('agencies')) {
        $job->assigned_agency = in_array('all', $request->agencies)
            ? json_encode(['all'])
            : json_encode($request->agencies);
    } else {
        $job->assigned_agency = null;
    }

    $job->save();

    return redirect()->route('job.index')->with('success', 'Job assigned successfully.');
}
    
    public function bulkAssign(Request $request)
{
    $jobIds = explode(',', $request->job_ids);

    foreach($jobIds as $jobId){

        $job = \App\Models\Job::find($jobId);
        if(!$job) continue;

        // ✅ SAVE ROLES
        $job->assigned_roles = json_encode($request->roles ?? []);

        // =========================
        // ✅ SAVE AGENTS
        // =========================
        if(in_array('all', $request->agents ?? [])){
            $job->assigned_agents = json_encode(['all']);
        } else {
            $job->assigned_agents = json_encode($request->agents ?? []);
        }

        // =========================
        // ✅ SAVE AGENCIES (NEW)
        // =========================
        if(in_array('all', $request->agencies ?? [])){
            $job->assigned_agency = json_encode(['all']);
        } else {
            $job->assigned_agency = json_encode($request->agencies ?? []);
        }

        $job->save();
    }

    return back()->with('success','Jobs assigned successfully');
}


    public function index(Request $request)
    {
        try {
            abort_if(!userCan('job.view'), 403);

            $jobs = (new JobListService)->execute($request);
            $job_categories = JobCategory::all()->sortBy('name');
            $experiences = Experience::all();
            $job_types = JobType::all();
            $companies = Company::with('user:id,name')->get(['id', 'user_id']);
            $edited_jobs = Job::edited()->count();
            $roles = Role::where('id', '!=', 1)->get();

            // Attach assigned roles to each job
            foreach ($jobs as $job) {
                $job->assigned_roles = explode(',', $job->job_roles); // Convert job_roles to an array
            }
            // dd( $jobs[0]->assigned_roles);

            return view('backend.Job.index', [
                'jobs' => $jobs,
                'job_categories' => $job_categories,
                'experiences' => $experiences,
                'job_types' => $job_types,
                'companies' => $companies,
                'edited_jobs' => $edited_jobs,
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }
    public function applyJob(Request $request)
    {
        try {
            abort_if(!userCan('job.view'), 403);
            $jobs = $query = Job::query()->with('role', 'category', 'salary_type', 'company', 'allAppliedJobs');
            if (auth()->user()->hasRole('superadmin')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->latest()->paginate(15);
                $jobs->appends($request->all());
            } elseif (auth()->user()->hasRole('third party workforce supply')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->whereRaw("FIND_IN_SET('third party workforce supply', job_roles)")->latest()->paginate(15);
                $jobs->appends($request->all());
            }

            if (auth()->user()->hasRole('recruitment agency')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->whereRaw("FIND_IN_SET('recruitment agency', job_roles)")->latest()->paginate(15);
                $jobs->appends($request->all());
            }

            if (auth()->user()->hasRole('hr consultancy')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->whereRaw("FIND_IN_SET('hr consultancy', job_roles)")->latest()->paginate(15);
                $jobs->appends($request->all());
            }
            if (auth()->user()->hasRole('third party contracting small establishment')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->whereRaw("FIND_IN_SET('third party contracting small establishment', job_roles)")->latest()->paginate(15);
                $jobs->appends($request->all());
            }
            if (auth()->user()->hasRole('domestic worker Istaqdam offices')) {
                $jobs = $query->withoutEdited()->with(['experience', 'job_type', 'category'])->whereRaw("FIND_IN_SET('domestic worker Istaqdam offices', job_roles)")->latest()->paginate(15);
                $jobs->appends($request->all());
            }
            $job_categories = JobCategory::all()->sortBy('name');
            $experiences = Experience::all();
            $job_types = JobType::all();
            $companies = Company::with('user:id,name')->get(['id', 'user_id']);
            $edited_jobs = Job::edited()->count();
            $roles = Role::where('id', '!=', 1)->get();
            $candidates = Candidate::where('admin_id', auth()->user()->id)->get();


            // Attach assigned roles to each job
            foreach ($jobs as $job) {
                $job->assigned_roles = explode(',', $job->job_roles); // Convert job_roles to an array
            }
            // dd( $jobs[0]->assigned_roles);

            return view('backend.Job.apply-job', [
                'jobs' => $jobs,
                'job_categories' => $job_categories,
                'experiences' => $experiences,
                'job_types' => $job_types,
                'companies' => $companies,
                'edited_jobs' => $edited_jobs,
                'roles' => $roles,
                'candidates' => $candidates,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        try {
            abort_if(! userCan('job.create'), 403);

            $data['countries'] = Country::all();
            $data['companies'] = Company::all();
            $data['job_category'] = JobCategory::all()->sortBy('name');
            $data['job_roles'] = JobRole::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['job_types'] = JobType::all();
            $data['salary_types'] = SalaryType::all();
            $data['educations'] = Education::all();
            $data['benefits'] = Benefit::whereNull('company_id')->get()->sortBy('name');
            $data['tags'] = Tag::all()->sortBy('name');
            $data['skills'] = Skill::all()->sortBy('name');
            $data['dynamicInputs'] = CompanyAttribute::all();
            $data['jobtitles']    = Profession::all()->sortBy('name');
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            return view('backend.Job.create', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function jobStatusChange(Job $job, Request $request)
    {
        try {
            abort_if(! userCan('job.update'), 403);

            $job->update([
                'status' => $request->status,
            ]);

            if ($request->status == 'active') {
                if ($job->company) {
                    Notification::send($job->company->user, new JobApprovalNotification($job));
                }

                $candidates = CandidateJobAlert::where('job_role_id', $job->role_id)->get();

                foreach ($candidates as $candidate) {
                    if ($candidate->candidate->received_job_alert) {
                        $candidate->candidate->user->notify(new RelatedJobNotification($job));
                    }
                }
            }

            flashSuccess(__('job_status_changed'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(JobFormRequest $request)
{
    try {

        abort_if(! userCan('job.create'), 403);

        /*
        |--------------------------------------------------------------------------
        | FEATURE LIMIT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !auth()->user()->canUseFeature(
                'active_job_posting_limit'
            )
        ) {

            flashError(
                'Your job posting limit has been exceeded'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE JOB
        |--------------------------------------------------------------------------
        */

        (new JobCreateService)->execute($request);

        /*
        |--------------------------------------------------------------------------
        | INCREASE FEATURE USAGE
        |--------------------------------------------------------------------------
        */

        auth()->user()->increaseFeatureUsage(
            'active_job_posting_limit'
        );

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        flashSuccess(
            __('job_created_successfully')
        );

        return redirect()
            ->route('job.index');

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Job $job)
    {
        try {
            abort_if(! userCan('job.view'), 403);

            return view('backend.Job.show', compact('job'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Job $job)
    {
        try {
            abort_if(! userCan('job.update'), 403);

            $data['companies'] = Company::all();
            $data['job_category'] = JobCategory::all()->sortBy('name');
            $data['job_roles'] = JobRole::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['job_types'] = JobType::all();
            $data['salary_types'] = SalaryType::all();
            $data['educations'] = Education::all();
            $data['benefits'] = Benefit::whereNull('company_id')->get()->sortBy('name');
            $data['tags'] = Tag::all()->sortBy('name');
            $job->load('tags', 'benefits', 'company');
            $data['job'] = $job;
            $data['lat'] = $job->lat ? floatval($job->lat) : floatval(setting('default_lat'));
            $data['long'] = $job->long ? floatval($job->long) : floatval(setting('default_long'));
            $data['skills'] = Skill::all()->sortBy('name');
            $data['dynamicInputs'] = CompanyAttribute::all();
            $data['inputsData'] = CompanyAttributeTranslation::where('job_id', $job->id)->get();
            $data['jobtitles']    = Profession::all()->sortBy('name');
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            return view('backend.Job.edit', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(JobFormRequest $request, Job $job)
    {
        try {
            abort_if(! userCan('job.update'), 403);

            (new JobUpdateService)->execute($request, $job);

            flashSuccess(__('job_updated_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    
    /*
|--------------------------------------------------------------------------
| FEATURED JOB
|--------------------------------------------------------------------------
*/

public function makeFeatured(Job $job)
{
    try {

        abort_if(! userCan('job.update'), 403);

        /*
        |--------------------------------------------------------------------------
        | FEATURE LIMIT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !auth()->user()->canUseFeature(
                'featured_job_limit'
            )
        ) {

            flashError(
                'Your featured job limit has been exceeded'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | ALREADY FEATURED
        |--------------------------------------------------------------------------
        */

        if ($job->featured) {

            flashError(
                'Job is already featured'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE JOB
        |--------------------------------------------------------------------------
        */

        $job->update([

            'featured' => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | INCREASE USAGE
        |--------------------------------------------------------------------------
        */

        auth()->user()->increaseFeatureUsage(
            'featured_job_limit'
        );

        flashSuccess(
            'Job featured successfully'
        );

        return back();

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}

/*
|--------------------------------------------------------------------------
| HIGHLIGHT JOB
|--------------------------------------------------------------------------
*/

public function makeHighlight(Job $job)
{
    try {

        abort_if(! userCan('job.update'), 403);

        /*
        |--------------------------------------------------------------------------
        | FEATURE LIMIT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !auth()->user()->canUseFeature(
                'highlight_job_limit'
            )
        ) {

            flashError(
                'Your highlight job limit has been exceeded'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | ALREADY HIGHLIGHTED
        |--------------------------------------------------------------------------
        */

        if ($job->highlight) {

            flashError(
                'Job is already highlighted'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE JOB
        |--------------------------------------------------------------------------
        */

        $job->update([

            'highlight' => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | INCREASE USAGE
        |--------------------------------------------------------------------------
        */

        auth()->user()->increaseFeatureUsage(
            'highlight_job_limit'
        );

        flashSuccess(
            'Job highlighted successfully'
        );

        return back();

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Job $job)
    {
        try {
            abort_if(! userCan('job.delete'), 403);

            if ($job->delete()) {
                flashSuccess(__('job_deleted_successfully'));

                return back();
            } else {
                flashError(__('something_went_wrong'));

                return back();
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function deleteSelected(Request $request)
    {
        $ids = $request->ids;
        Job::whereIn('id', $ids)->delete();
        flashSuccess(__('job_deleted_successfully'));

        return back();

        // Return response if needed
    }

    public function clone(Job $job)
    {
        try {
            $newJob = $job->replicate();
            $newJob->created_at = now();
            $newJob->slug = Str::slug($job->title) . '-' . time() . '-' . uniqid();
            $newJob->save();

            flashSuccess(__('job_cloned_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Edited Approval job list
     */
    public function editedJobList(Request $request)
    {
        try {
            abort_if(! userCan('job.view'), 403);

            $query = Job::latest()->edited();

            // keyword
            if ($request->title && $request->title != null) {
                $query->where('title', 'LIKE', "%$request->title%");
            }

            // status
            if ($request->status && $request->status != null) {
                if ($request->status != 'all') {
                    $query->where('status', $request->status);
                }
            }

            // job_category
            if ($request->job_category && $request->job_category != null) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->job_category);
                });
            }

            // experience
            if ($request->experience && $request->experience != null) {
                $query->whereHas('experience', function ($q) use ($request) {
                    $q->where('slug', $request->experience);
                });
            }

            // job_type
            if ($request->job_type && $request->job_type != null) {
                $query->whereHas('job_type', function ($q) use ($request) {
                    $q->where('slug', $request->job_type);
                });
            }

            // filter_by
            if ($request->filter_by && $request->filter_by != null) {
                $query->where('status', $request->filter_by);
            }

            $jobs = $query->with(['experience', 'job_type'])->paginate(15);
            $jobs->appends($request->all());

            $job_categories = JobCategory::all()->sortBy('name');
            $experiences = Experience::all();
            $job_types = JobType::all();

            return view('backend.Job.edited_jobs', compact('jobs', 'job_categories', 'experiences', 'job_types'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Show Edited job
     */
    public function editedShow(Job $job)
    {
        try {
            $parent_job = Job::FindOrFail($job->parent_job_id);

            return view('backend.Job.show_edited', compact('parent_job', 'job'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Show Edited job
     */
    public function editedApproved(Job $job)
    {
        try {
            $main_job = Job::FindOrFail($job->parent_job_id);

            $main_job->update([
                'title' => $job->title,
                'category_id' => $job->category_id,
                'role_id' => $job->role_id,
                'education_id' => $job->education_id,
                'experience_id' => $job->experience_id,
                'salary_mode' => $job->salary_mode,
                'custom_salary' => $job->custom_salary,
                'min_salary' => $job->min_salary,
                'max_salary' => $job->max_salary,
                'salary_type_id' => $job->salary_type_id,
                'deadline' => Carbon::parse($job->deadline)->format('Y-m-d'),
                'job_type_id' => $job->job_type_id,
                'vacancies' => $job->vacancies,
                'apply_on' => $job->apply_on,
                'apply_email' => $job->apply_email,
                'apply_url' => $job->apply_url,
                'description' => $job->description,
                'is_remote' => $job->is_remote,

                // map deatils
                'address' => $job->address,
                'neighborhood' => $job->neighborhood,
                'locality' => $job->locality,
                'place' => $job->place,
                'district' => $job->district,
                'postcode' => $job->postcode,
                'region' => $job->region,
                'country' => $job->country,
                'long' => $job->long,
                'lat' => $job->lat,
            ]);

            $job->delete();

            flashSuccess(__('job_changes_applied_successfully'));

            return redirect()->route('admin.job.edited.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|mimes:csv,xlsx,xls',
            'company' => 'required|exists:companies,id',
        ]);

        try {
            Excel::import(new JobsImport($request->company), $request->import_file);

            flashSuccess('Jobs imported successfully');

            return back();
        } catch (\Throwable $th) {
            flashError($th->getMessage());

            return back();
        }
    }

    public function appliedJobs()
    {
        $applied_jobs = AppliedJob::whereNotNull('candidate_id')->paginate(10);
        $companies = Company::with('user:id,name')->get(['id', 'user_id']);

        return view('backend.Job.applied_index', [
            'applied_jobs' => $applied_jobs,
            'companies' => $companies,
        ]);
    }

    public function appliedJobsShow(AppliedJob $applied_job)
    {
        return view('backend.Job.applied_job_show', [
            'applied_job' => $applied_job,
        ]);
    }
    public function  hiring_requests()
    {
        $hireRequests = HireRequest::with(['candidate', 'employer'])->latest()->get();
        return view('backend.Job.hiring_request', [
            'hireRequests' => $hireRequests,
        ]);
    }
    public function sendHireMail($id)
    {
        $request = HireRequest::findOrFail($id);

        $candidate = $request->candidate;
        $message = "Someone is interested in hiring you. Please contact OGS Manpower for further details.";

        // Send email
        Mail::to($candidate->user->email)->send(new HireCandidateMail($candidate, $message));

        return back()->with('success', 'Mail sent successfully to the candidate.');
    }
}
