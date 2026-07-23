<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobCreateRequest;
use App\Http\Traits\HasAgencyApplication;
use App\Http\Traits\JobAble;
use App\Models\AppliedJob;
use App\Models\Attachment;
use App\Models\Benefit;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Models\CandidateLanguage;
use App\Models\CandidateStatus;
use App\Models\cms;
use App\Models\AgencyBookmarkCategory;
use App\Models\AgencyQuestion;
use App\Models\Earning;
use App\Models\Education;
use App\Models\Experience;
use App\Models\IndustryType;
use App\Models\AgencyAttribute;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\JobRole;
use App\Models\JobType;
use App\Models\ManualPayment;
use App\Models\OrganizationType;
use App\Models\PaymentSetting;
use App\Models\SalaryType;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Role;
use App\Models\JobTitle;
use App\Models\Agency;
use App\Models\AgencyAttributeTranslation;
use App\Models\ContactInfo;
use App\Models\HireRequest;
use App\Models\Profession;
use App\Models\TeamSize;
use App\Models\User;
use App\Models\UserPlan;
use App\Notifications\Website\Agency\CandidateBookmarkNotification;
use App\Services\Midtrans\CreateSnapTokenService;
use App\Services\Website\Agency\AgencyAccountProgressService;
use App\Services\Website\Agency\AgencyPromoteJobService;
use App\Services\Website\Agency\AgencySettingUpdateService;
use App\Services\Website\Agency\AgencyStoreService;
use App\Services\Website\Agency\AgencyUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Modules\Currency\Entities\Currency;
use Modules\Location\Entities\Country;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;
use Mpdf\Mpdf;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\ForwardCandidateMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\ApplicationGroup;






class AgencyController extends Controller
{
    use HasAgencyApplication, JobAble;

    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'settingUpdateInformation',
        ]);
    }

    /**
     * agency Dashboard
     *
     * @return Response
     */

    public function downloadApplicantResume($candidate_id, $job_id)
    {
        try {
            // dd('asdfg');
            $candidate = Candidate::with(['user', 'socialInfo', 'attributes' => function ($query) {
                $query->whereNotNull('attribute_value') // Select attributes with non-null values
                    ->where('is_active', 1); // Select only active attributes
            }])
                ->where('id', $candidate_id)
                ->first();
            $appliedJob = AppliedJob::where('candidate_id', $candidate_id)->where('job_id', $job_id)->first();
            // dd($appliedJob->resume_format);
            $contactInfo = ContactInfo::where('user_id', $candidate->user_id)->first();
            $contact = $contactInfo ? $contactInfo : '';

            $socials = $candidate->user->socialInfo;
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $attachments = Attachment::where('candidate_id', $candidate->id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts:id,candidate_id,job_role_id');
            $translate = new GoogleTranslate($candidate->language_code);
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'expected_country', 'jobRoleAlerts:id,candidate_id,job_role_id');
            $viewMap = [
                'general_format' => 'frontend.pages.candidate.general-resume',
                'driver_format' => 'frontend.pages.candidate.driver-resume',
                'guard_format' => 'frontend.pages.candidate.security-guard-resume',
                'beautician_format' => 'frontend.pages.candidate.beautician-resume',
                'web_developer_format' => 'frontend.pages.candidate.web-developer-resume',
                'bike_rider_format' => 'frontend.pages.candidate.bike-rider-resume',
                'bilangual_format' => 'frontend.pages.candidate.bilangual-resume',
            ];

            $format = ($appliedJob->resume_format ?? null) ?: ($candidate->resume_format ?? 'general_format');
            $view = $viewMap[$format] ?? $viewMap['general_format'];
            // $qrCode = base64_encode(QrCode::format('png')->size(80)->generate('https://example.com/candidate/'.$candidate->id));
            $qrCode = QrCode::size(70)->generate('https://dev.ogstravel.com/candidate/' . $candidate->id);

            $data = [
                'candidate' => $candidate,
                'contact' => $contact,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                'attachments' => $attachments,
                'qrCode' => $qrCode,
                'translate' => $translate
            ];

            if ($format == 'bilangual_format') {
                $htmlContent = view($view, $data)->render();
                $mpdf = new Mpdf();
                mpdf_write_html_chunked($mpdf, $htmlContent);
                return $mpdf->Output('candidate_cv_' . $candidate->id . '.pdf', 'D');
            } else {

                // Generate PDF for download
                $pdf = PDF::loadView($view, $data);
                return $pdf->download('candidate_cv_' . $candidate->id . '.pdf');
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }
    public function applicationDetail($candidate_id, $job_id)
    {

        try {


            $candidate = Candidate::with('skills', 'languages:id,name', 'profession')->findOrFail($candidate_id);
            $user = User::with('socialInfo', 'contactInfo')->findOrFail($candidate->user_id);
            $appliedJobs = $candidate->appliedJobs()->with('agency.user', 'category', 'role')->get();
            $bookmarkJobs = $candidate->bookmarkJobs()->with('agency.user', 'category', 'role')->get();
            $candiateJob = AppliedJob::where('candidate_id', $candidate_id)->where('job_id', $job_id)->first();
            return view('frontend.pages.agency.application-detail', compact('candidate', 'user', 'appliedJobs', 'bookmarkJobs', 'candiateJob'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function availableJobs()
{
    $agency = auth()->user()->agency;

    if (!$agency) {

        abort(404);

    }

    /*
    |--------------------------------------------------------------------------
    | USER PLAN
    |--------------------------------------------------------------------------
    */

    $userPlan = $agency->userPlan;

    /*
    |--------------------------------------------------------------------------
    | PLAN LIMITS
    |--------------------------------------------------------------------------
    */

    $subAgencyLimit = $userPlan->plan->subagency_limit ?? 0;

    $agentLimit = $userPlan->plan->agent_limit ?? 0;

    $candidateLimit = $userPlan->plan->candidate_limit ?? 0;

    /*
    |--------------------------------------------------------------------------
    | ASSIGNED JOBS
    |--------------------------------------------------------------------------
    */

    $jobs = $agency->assignedJobs()

        ->with([
            'company.user',
            'job_type',
            'agencies.user',
            'subAgencies.user',
            'agents'
        ])

        ->latest()

        ->paginate(12);

    /*
    |--------------------------------------------------------------------------
    | CANDIDATES
    |--------------------------------------------------------------------------
    */

    $candidates = \App\Models\Candidate::where(
        'agency_id',
        $agency->id
    )->get();

    /*
    |--------------------------------------------------------------------------
    | SUB AGENCIES
    |--------------------------------------------------------------------------
    */

    $subAgencies = \App\Models\Agency::with('user')

        ->where('parent_agency_id', $agency->id)
        ->latest()

        ->take($subAgencyLimit)

        ->get();

    /*
    |--------------------------------------------------------------------------
    | AGENTS
    |--------------------------------------------------------------------------
    */

    $agents = \App\Models\User::where('role', 'agent')

        ->whereHas('agency', function ($query) use ($agency) {

        $query->where('country', $agency->country);

    })
        ->latest()

        ->take($agentLimit)

        ->get();

    /*
    |--------------------------------------------------------------------------
    | RETURN VIEW
    |--------------------------------------------------------------------------
    */

    return view(
        'frontend.pages.agency.available-jobs',
        compact(
            'jobs',
            'candidates',
            'subAgencies',
            'agents',
            'subAgencyLimit',
            'agentLimit',
            'candidateLimit'
        )
    );
}

/**
 * Agency accepts or declines a job assignment made by an employer
 * (job_agencies pivot). Notifies the employer either way.
 */
public function respondToJobAssignment(Request $request, $jobId)
{
    $validated = $request->validate([
        'action' => 'required|in:accept,decline',
        'reason' => 'nullable|string|max:1000',
    ]);

    $agency = currentAgency();
    $job = Job::with('company.user')->findOrFail($jobId);

    $pivotExists = $job->agencies()->where('agency_id', $agency->id)->exists();
    abort_if(! $pivotExists, 404);

    $status = $validated['action'] === 'accept' ? 'accepted' : 'declined';

    $job->agencies()->updateExistingPivot($agency->id, [
        'status' => $status,
        'decline_reason' => $status === 'declined' ? ($validated['reason'] ?? null) : null,
        'responded_at' => now(),
    ]);

    if ($job->company?->user) {
        Notification::send(
            $job->company->user,
            new \App\Notifications\Website\Company\AgencyJobResponseNotification($job, $agency, $status, $validated['reason'] ?? null)
        );
    }

    flashSuccess($status === 'accepted' ? 'Job accepted.' : 'Job declined.');

    return back();
}

public function assignSubAgency(Request $request)
{
    $request->validate([
        'job_id' => 'required',
        'sub_agency_id' => 'required',
    ]);

    $agency = auth()->user()->agency;

    $userPlan = $agency->userPlan;

    $subAgencyLimit = $userPlan->plan->subagency_limit ?? 1;

    $job = \App\Models\Job::findOrFail($request->job_id);

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE
    |--------------------------------------------------------------------------
    */

    $alreadyAssigned = $job->subAgencies()
        ->where('sub_agency_id', $request->sub_agency_id)
        ->exists();

    if($alreadyAssigned){

        flashError('Sub agency already assigned');

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK LIMIT
    |--------------------------------------------------------------------------
    */

    $currentCount = $job->subAgencies()->count();

    if($currentCount >= $subAgencyLimit){

        flashError(
            "Your plan allows only {$subAgencyLimit} sub agencies"
        );

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN SUB AGENCY
    |--------------------------------------------------------------------------
    */

    $job->subAgencies()->attach(

        $request->sub_agency_id,

        [

            'hide_company_name' =>
                $request->hide_company_name ? 1 : 0,

            'hide_salary' =>
                $request->hide_salary ? 1 : 0,

            'hide_city' =>
                $request->hide_city ? 1 : 0,

            'hide_country' =>
                $request->hide_country ? 1 : 0,

            'hide_company_logo' =>
                $request->hide_company_logo ? 1 : 0,

            'hide_job_description' =>
                $request->hide_job_description ? 1 : 0,

        ]

    );

    flashSuccess('Job assigned to sub agency successfully');

    return back();
}


public function assignAgent(Request $request)
{
    $request->validate([
        'job_id' => 'required',
        'agent_id' => 'required',
    ]);

    $agency = auth()->user()->agency;

    $userPlan = $agency->userPlan;

    $agentLimit = $userPlan->plan->agent_limit ?? 1;

    $job = \App\Models\Job::findOrFail($request->job_id);

    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE
    |--------------------------------------------------------------------------
    */

    $alreadyAssigned = $job->agents()
        ->where('agent_id', $request->agent_id)
        ->exists();

    if($alreadyAssigned){

        flashError('Agent already assigned');

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK LIMIT
    |--------------------------------------------------------------------------
    */

    $currentCount = $job->agents()->count();

    if($currentCount >= $agentLimit){

        flashError(
            "Your plan allows only {$agentLimit} agents"
        );

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGN AGENT
    |--------------------------------------------------------------------------
    */

    $job->agents()->attach(

        $request->agent_id,

        [

            'hide_company_name' =>
                $request->hide_company_name ? 1 : 0,

            'hide_salary' =>
                $request->hide_salary ? 1 : 0,

            'hide_city' =>
                $request->hide_city ? 1 : 0,

            'hide_country' =>
                $request->hide_country ? 1 : 0,

            'hide_company_logo' =>
                $request->hide_company_logo ? 1 : 0,

            'hide_job_description' =>
                $request->hide_job_description ? 1 : 0,

        ]

    );

    flashSuccess('Job assigned to agent successfully');

    return back();
}


public function candidatePipeline()
{
    $agency = auth()->user()->agency;

    /*
    |--------------------------------------------------------------------------
    | SUB AGENCY
    |--------------------------------------------------------------------------
    */

    if($agency->parent_agency_id){

        $candidates = \DB::table('job_candidate_pipeline')

    ->leftJoin(
        'candidate_contracts',
        'candidate_contracts.pipeline_id',
        '=',
        'job_candidate_pipeline.id'
    )

    ->select(

        'job_candidate_pipeline.*',

        'candidate_contracts.id as contract_id',

        'candidate_contracts.contract_title',

        'candidate_contracts.status as contract_status'

    )

    ->latest()

    ->get();

    }

    /*
    |--------------------------------------------------------------------------
    | MAIN AGENCY
    |--------------------------------------------------------------------------
    */

    else{

        $candidates = \DB::table('job_candidate_pipeline')

            ->where('status', 'approved_by_subagency')

            ->latest()

            ->get();

    }

    return view(
        'frontend.pages.agency.pipeline',
        compact('candidates')
    );
}
    public function acceptContract($id)
{
    /*
    |--------------------------------------------------------------------------
    | GET CONTRACT
    |--------------------------------------------------------------------------
    */

    $contract = \DB::table('candidate_contracts')

        ->where('id', $id)

        ->first();

    /*
    |--------------------------------------------------------------------------
    | NOT FOUND
    |--------------------------------------------------------------------------
    */

    if(!$contract){

        return back()->with(
            'error',
            'Contract not found'
        );

    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE CONTRACT
    |--------------------------------------------------------------------------
    */

    \DB::table('candidate_contracts')

        ->where('id', $id)

        ->update([

            'status' => 'accepted',

            'candidate_signed_at' => now(),

            'updated_at' => now()

        ]);

    /*
    |--------------------------------------------------------------------------
    | UPDATE PIPELINE
    |--------------------------------------------------------------------------
    */

    \DB::table('job_candidate_pipeline')

        ->where('id', $contract->pipeline_id)

        ->update([

            'hiring_status' => 'contract_accepted',

            'status' => 'selected',

            'updated_at' => now()

        ]);

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    return back()->with(
        'success',
        'Contract accepted successfully'
    );
}

public function rejectContract($id)
{
    /*
    |--------------------------------------------------------------------------
    | GET CONTRACT
    |--------------------------------------------------------------------------
    */

    $contract = \DB::table('candidate_contracts')

        ->where('id', $id)

        ->first();

    /*
    |--------------------------------------------------------------------------
    | NOT FOUND
    |--------------------------------------------------------------------------
    */

    if(!$contract){

        return back()->with(
            'error',
            'Contract not found'
        );

    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE CONTRACT
    |--------------------------------------------------------------------------
    */

    \DB::table('candidate_contracts')

        ->where('id', $id)

        ->update([

            'status' => 'rejected',

            'updated_at' => now()

        ]);

    /*
    |--------------------------------------------------------------------------
    | UPDATE PIPELINE
    |--------------------------------------------------------------------------
    */

    \DB::table('job_candidate_pipeline')

        ->where('id', $contract->pipeline_id)

        ->update([

            'hiring_status' => 'not_started',

            'status' => 'rejected',

            'updated_at' => now()

        ]);

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    return back()->with(
        'success',
        'Contract rejected successfully'
    );
}

public function updateCandidateStatus(Request $request, $id)
{
    $request->validate([
        'status' => 'required'
    ]);

    \DB::table('job_candidate_pipeline')

        ->where('id', $id)

        ->update([

            'status' => $request->status,

            'updated_at' => now()

        ]);

    flashSuccess('Candidate status updated successfully');

    return back();
}


public function approveCandidate($id)
{
    $agency = auth()->user()->agency;

    /*
    |--------------------------------------------------------------------------
    | SUB AGENCY APPROVAL
    |--------------------------------------------------------------------------
    */

    if($agency->parent_agency_id){

        \DB::table('job_candidate_pipeline')

            ->where('id', $id)

            ->update([

                'status' => 'approved_by_subagency',

                'sub_agency_id' => $agency->id,

                'updated_at' => now()

            ]);

        flashSuccess('Candidate sent to main agency');

    }

    /*
    |--------------------------------------------------------------------------
    | MAIN AGENCY APPROVAL
    |--------------------------------------------------------------------------
    */

    else{

        \DB::table('job_candidate_pipeline')

            ->where('id', $id)

            ->update([

                'status' => 'approved_by_agency',

                'agency_id' => $agency->id,

                'updated_at' => now()

            ]);

        flashSuccess('Candidate sent to company');

    }

    return back();
}

public function jobCandidates($jobId)
{
    $job = Job::findOrFail($jobId);

    $candidates = auth()->user()
        ->agency
        ->candidates()
        ->with('user', 'profession')
        ->latest()
        ->get();

    $appliedCandidateIds = AppliedJob::where('job_id', $jobId)
        ->whereIn('candidate_id', $candidates->pluck('id'))
        ->pluck('candidate_id')
        ->toArray();

    return view('frontend.pages.agency.job-candidates', compact('candidates', 'jobId', 'job', 'appliedCandidateIds'));
}

public function submitCandidates(Request $request)
{
    $request->validate([
        'job_id' => 'required',
        'candidates' => 'required|array'
    ]);

    $agencyId = auth()->user()->agency->id;

    foreach ($request->candidates as $candidateId) {

        $exists = AppliedJob::where('job_id', $request->job_id)
            ->where('candidate_id', $candidateId)
            ->exists();

        if (!$exists) {

            $job = Job::find($request->job_id);

            AppliedJob::create([
                'job_id' => $request->job_id,
                'candidate_id' => $candidateId,
                'agency_id' => $agencyId,
                'company_id' => $job->company_id,
                'status' => 'pending'
            ]);
        }
    }

    return back()->with('success', 'Candidates submitted successfully');
}
    public function updateApplicationStatus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:applied_jobs,id',
            'status' => 'required|in:selected,rejected,shortlisted,pending,interview',
            'interview_date' => 'nullable|date',
            'interview_location' => 'nullable|string|max:255',
        ]);

        $application = AppliedJob::with('job:id,agency_id')->findOrFail($validated['id']);
        $agencyId = currentAgency()->id;
        $owns = (int) $application->agency_id === (int) $agencyId
            || (int) optional($application->job)->agency_id === (int) $agencyId;
        abort_if(! $owns, 403);

        $extra = [];
        if ($validated['status'] === 'interview') {
            $extra['interview_date'] = $validated['interview_date'] ?? null;
            $extra['interview_location'] = $validated['interview_location'] ?? null;
            $extra['interview_outcome'] = 'scheduled';
        }

        app(\App\Services\Jobs\ApplicationStatusService::class)
            ->updateStatus($application, $validated['status'], $extra);

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    /**
     * Interview pipeline — applicants with status=interview, scoped to this agency.
     */
    public function interviews(Request $request)
    {
        $agencyId = (int) currentAgency()->id;

        $query = AppliedJob::query()
            ->with([
                'job:id,title,slug,agency_id',
                'candidate' => function ($q) {
                    $q->with(['user:id,name,username,image', 'profession']);
                },
            ])
            ->where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)
                    ->orWhereHas('job', fn ($jq) => $jq->where('agency_id', $agencyId));
            })
            ->where('status', 'interview')
            ->whereNotNull('candidate_id')
            ->latest('id');

        if ($request->filled('outcome') && $request->outcome !== 'all') {
            $query->where('interview_outcome', $request->outcome);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->whereHas('candidate.user', fn ($q) => $q->where('name', 'like', '%'.$term.'%'));
        }

        $interviews = $query->paginate(12)->withQueryString();

        $base = AppliedJob::query()
            ->where(function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)
                    ->orWhereHas('job', fn ($jq) => $jq->where('agency_id', $agencyId));
            })
            ->where('status', 'interview')
            ->whereNotNull('candidate_id');

        $counts = [
            'all' => (clone $base)->count(),
            'scheduled' => (clone $base)->where('interview_outcome', 'scheduled')->count(),
            'rescheduled' => (clone $base)->where('interview_outcome', 'rescheduled')->count(),
            'completed' => (clone $base)->where('interview_outcome', 'completed')->count(),
        ];

        return view('frontend.pages.agency.interviews', compact('interviews', 'counts'));
    }

    public function updateInterview(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:applied_jobs,id',
            'action' => 'required|in:invite,accept,reject,reschedule,complete,completed',
            'interview_date' => 'nullable|date',
            'interview_location' => 'nullable|string|max:255',
        ]);

        $application = AppliedJob::with('job:id,agency_id')->findOrFail($validated['id']);
        $agencyId = (int) currentAgency()->id;
        $owns = (int) $application->agency_id === $agencyId
            || (int) optional($application->job)->agency_id === $agencyId;
        abort_if(! $owns, 403);

        app(\App\Services\Jobs\ApplicationStatusService::class)->updateInterview(
            $application,
            $validated['action'],
            [
                'interview_date' => $validated['interview_date'] ?? null,
                'interview_location' => $validated['interview_location'] ?? null,
            ]
        );

        flashSuccess('Interview updated ('.$validated['action'].'). Candidate has been notified.');

        return back();
    }

    public function candidate_status()
    {
        // dd('hj');
        $agency_id = auth()->user()->agency->id;
        $candidates = AppliedJob::with(['candidate', 'applicationGroup', 'job'])
            ->where('agency_id', $agency_id)
            ->whereIn('status', ['shortlisted', 'selected'])
            ->whereNotNull('candidate_id')
            // ->whereHas('applicationGroup', function ($query) {
            //     $query->whereIn('name', ['Shortlisted', 'Selected']);
            // })
            ->paginate(10);
        // dd($candidates);
        return view('frontend.pages.agency.candidate_status', compact('candidates'));
    }
    public function approvedCandidateStatus(Request $request)
    {
        $candidate = AppliedJob::with('applicationGroup', 'job', 'candidate')->where('candidate_id', $request->candidate_id)->where('job_id', $request->job_id)->first();
        $assignCandidates = CandidateStatus::where('candidate_id', $request->candidate_id)->where('job_id', $request->job_id)->where('is_approved', 1)->paginate('10');
        return view('frontend.pages.agency.approved-candidate-status', compact('candidate', 'assignCandidates'));
    }
    public function dynamic_input($id)
    {
        $agency = Agency::with('attributes')->where('id', $id)->first();
        return view('backend.agency.dynamic-inputs', compact('agency'));
    }
    public function dashboard()
    {
        try {
            $data['userplan'] = UserPlan::with('plan')
                ->agencyData()
                ->firstOrFail();

            $agency = currentAgency();
            $data = array_merge($data, app(\App\Services\Agency\AgencyDashboardService::class)->build($agency));

            return view('frontend.pages.agency.dashboard', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function mobile_dashboard()
    {
        try {
            $data['userplan'] = UserPlan::with('plan')
                ->agencyData()
                ->firstOrFail();
            $data['openJobCount'] = auth()
                ->user()
                ->agency->jobs()
                ->active()
                ->count();
            $data['pendingJobCount'] = auth()
                ->user()
                ->agency->jobs()
                ->pending()
                ->count();

            // Recent 4 Jobs
            $data['recentJobs'] = auth()
                ->user()
                ->agency->jobs()
                ->latest()
                ->take(4)
                ->with('agency.user', 'job_type')
                ->withCount('appliedJobs')
                ->get();
            $data['savedCandidates'] = auth()
                ->user()
                ->agency->bookmarkCandidates()
                ->count();

            $data['applicants'] = AppliedJob::where('agency_id', auth()->user()->agency->id)->count();

            $dailyApplications = AppliedJob::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('agency_id', auth()->user()->agency->id)
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->get();
            // dd($dailyApplications);

            $data['chartDates'] = $dailyApplications->pluck('date')->toArray(); // Dates for the chart
            $data['chartCounts'] = $dailyApplications->pluck('count')->toArray(); // Application counts for the chart
            return view('frontend.pages.agency.mobile-dashboard', array_merge($data));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency my jobs
     *
     * @return Response
     */
    public function myjobs(Request $request)
    {
        try {
            $query = currentAgency()
                ->jobs()
                ->withCount([
                    'appliedJobs',
                    'appliedJobs as selected_jobs_count' => fn ($q) => $q->where('status', 'selected'),
                ])
                ->withoutEdited();

            // status search
            if ($request->has('status') && $request->status != null) {
                $query->where('status', $request->status);
            }

            // status search
            if ($request->has('apply_on') && $request->apply_on != null) {
                $query->where('apply_on', $request->apply_on);
            }

            $myJobs = $query
                ->with('job_type:id')
                ->latest()
                ->paginate(12)
                ->withQueryString();
                
                // ✅ ADD THIS (same as admin)
foreach ($myJobs as $job) {
    $job->assigned_roles = $job->job_roles
        ? array_map('trim', explode(',', strtolower($job->job_roles)))
        : [];

    // keep your expiry logic
    if ($job->days_remaining < 1) {
        $job->update([
            'status' => 'expired',
            'deadline' => null,
        ]);
    }
}

         // ✅ ALSO ADD THIS (you missed earlier)
$roles = Role::where('id', '!=', 1)->get();

return view('frontend.pages.agency.myjobs', compact('myJobs', 'roles'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
public function assignRoles(Request $request, $jobId)
{
    $job = Job::findOrFail($jobId);

    // SAVE ROLES
    $job->job_roles = implode(',', $request->roles ?? []);

    // AGENTS
    $job->assigned_agents = !empty($request->agents)
        ? json_encode(in_array('all', $request->agents) ? ['all'] : $request->agents)
        : null;

    // AGENCY
    $job->assigned_agency = !empty($request->agencies)
        ? json_encode(in_array('all', $request->agencies) ? ['all'] : $request->agencies)
        : null;

    $job->save();

    return back()->with('success', 'Saved successfully');
}
  public function bulkAssign(Request $request)
{
    $jobIds = explode(',', $request->job_ids);

    foreach($jobIds as $jobId){

        $job = Job::find($jobId);
        if(!$job) continue;

        // FIXED ROLE COLUMN
        $job->job_roles = implode(',', $request->roles ?? []);

        // AGENTS
        $job->assigned_agents = !empty($request->agents)
            ? json_encode(in_array('all', $request->agents) ? ['all'] : $request->agents)
            : null;

        // AGENCY
        $job->assigned_agency = !empty($request->agencies)
            ? json_encode(in_array('all', $request->agencies) ? ['all'] : $request->agencies)
            : null;

        $job->save();
    }

    return back()->with('success','Jobs assigned successfully');
}
    /**
     * agency Edited Pending job list
     *
     * @Return response
     */
    public function pendingEditedJobs()
    {
        try {
            if (setting('edited_job_auto_approved')) {
                abort(404);
            }

            $query = currentAgency()
                ->jobs()
                ->withCount('appliedJobs')
                ->edited();

            $myJobs = $query
                ->with('job_type:id')
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

            return view('frontend.pages.agency.edited-jobs', compact('myJobs'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function applyCandidate(Request $request)
{
    $job = Job::findOrFail($request->job_id);

    $exists = AppliedJob::where([
        'job_id' => $request->job_id,
        'candidate_id' => $request->candidate_id
    ])->first();

    if ($exists) {
        return back()->with('error', 'Candidate already applied');
    }

    $group = ApplicationGroup::firstOrCreate([
        'name' => 'Job '.$request->job_id,
        'company_id' => $job->company_id
    ]);

    AppliedJob::create([
        'job_id' => $request->job_id,
        'candidate_id' => $request->candidate_id,
        'company_id' => $job->company_id,
        'agency_id' => $job->agency_id, // Main Agency Job Owner
        'agent_id' => auth()->id(),     // Agent who sent candidate
        'application_group_id' => $group->id,
        'status' => 'pending'
    ]);

    return back()->with('success', 'Candidate sent to Main Agency successfully');
}
public function applications()
{
    $applications = AppliedJob::with(['job', 'candidate.user', 'agent'])
        ->where('agency_id', currentAgency()->id)
        ->latest()
        ->paginate(20);

    return view('frontend.pages.agency.applications-all', compact('applications'));
}

    /**
     * agency all notifications
     *
     * @return Response
     */
    public function allNotification()
    {
        try {
            $notifications = auth()
                ->user()
                ->notifications()
                ->paginate(20);

            return view('frontend.pages.agency.all-notifications', compact('notifications'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency payperjob
     *
     * @return Response
     */
    public function payPerJob()
    {
        try {
            if (! setting('per_job_active')) {
                abort(404);
            }

            $data['jobCategories'] = JobCategory::all()->sortBy('name');
            $data['roles'] = JobRole::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['educations'] = Education::all();
            $data['job_types'] = JobType::all();
            $data['salary_types'] = SalaryType::all();
            $data['tags'] = Tag::all()->sortBy('name');
            $data['setting'] = loadSetting();
            $all_benefits = Benefit::all()->sortBy('name');
            $data['questions'] = currentAgency()
                ->questions()
                ->where('reuse', true)
                ->get();
            $non_agency_benefits = $all_benefits->whereNull('agency_id');
            $agency_benefits = $all_benefits->where('agency_id', currentAgency()->id);
            $data['benefits'] = $non_agency_benefits->merge($agency_benefits);

            return view('frontend.pages.agency.pay-per-job', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency payperjob store
     *
     * @return Response
     */
    public function storePayPerJob(JobCreateRequest $request)
    {
        try {

            $location = session()->get('location');
            if (! $location) {
                $request->validate([
                    'location' => 'required',
                ]);
            }

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

            session(['job_total_amount' => $request->total_price_perjob]);
            session(['job_request' => $request->all()]);

            return redirect()->route('agency.payperjob.payment');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency payperjob payment
     *
     * @return Response
     */
    public function payPerJobPayment()
    {
        try {
            abort_if(auth('user')->check() && authUser()->role == 'candidate', 404);

            // session data storing
            $job_total_amount = session('job_total_amount') ?? 100;
            session(['job_payment_type' => 'per_job']);

            if ($job_total_amount < 1) {
                session(['payperjob_code' => uniqid()]);

                return to_route('purchase.zero.pricing.job', session('payperjob_code'));
            }

            session(['stripe_amount' => currencyConversion($job_total_amount) * 100]);
            session(['razor_amount' => currencyConversion($job_total_amount, 'INR', 1) * 100]);
            session(['ssl_amount' => currencyConversion($job_total_amount, 'BDT', 1)]);

            $payment_setting = PaymentSetting::first();
            $manual_payments = ManualPayment::whereStatus(1)->get();

            // midtrans snap token
            if (config('templatecookie.midtrans_active') && config('templatecookie.midtrans_merchat_id') && config('templatecookie.midtrans_client_key') && config('templatecookie.midtrans_server_key')) {
                $usd = $job_total_amount;
                $checkCurrency = Currency::where('code', 'IDR')->first();
                if ($usd && $checkCurrency) {
                    $fromRate = Currency::whereCode(config('templatecookie.currency'))->first()->rate;
                    $toRate = $checkCurrency->rate;
                    $rate = $fromRate / $toRate;
                    $amount = round($usd / $rate, 2);
                }

                $order['order_no'] = uniqid();
                $order['total_price'] = $amount;

                $midtrans = new CreateSnapTokenService($order);
                $snapToken = $midtrans->getSnapToken();

                session([
                    'midtrans_details' => [
                        'order_no' => $order['order_no'],
                        'total_price' => $order['total_price'],
                        'snap_token' => $snapToken,
                    ],
                ]);

                session([
                    'order_payment' => [
                        'payment_provider' => 'midtrans',
                        'amount' => $amount,
                        'currency_symbol' => 'Rp',
                        'usd_amount' => $usd,
                    ],
                ]);
            }

            // Flutterwave Amount
            if (config('templatecookie.flw_public_key') && config('templatecookie.flw_secret') && config('templatecookie.flw_secret_hash') && config('templatecookie.flw_active')) {
                $flutterwave_amount = currencyConversion($job_total_amount, 'NGN', 1);
            }

            return view('frontend.pages.agency.payperjob_pricing', [
                'payment_setting' => $payment_setting,
                'mid_token' => $snapToken ?? null,
                'manual_payments' => $manual_payments,
                'job_total_amount' => $job_total_amount,
                'job_total_amount' => $job_total_amount,
                'flutterwave_amount' => $flutterwave_amount ?? null,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function agencyInvitations()
{
    $invitations = DB::table('company_invitations')
        ->latest()
        ->paginate(20);

    return view(
        'frontend.pages.agency.invitations',
        compact('invitations')
    );
}
public function sendCompanyInvitation(Request $request)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'invitation_type' => 'required',

            'company_name' => 'required',

            'company_email' => 'required|email',

        ]);

        /*
        |--------------------------------------------------------------------------
        | CHECK USER EXIST
        |--------------------------------------------------------------------------
        */

        $existingUser = \App\Models\User::where(
            'email',
            $request->company_email
        )->first();

        /*
        |--------------------------------------------------------------------------
        | COMPANY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $request->invitation_type == 'company'
            && $existingUser
        ) {

            $company = \App\Models\Company::where(
                'user_id',
                $existingUser->id
            )->first();

            if ($company) {

                return back()->with(

                    'error',

                    'Company already exists in system.'

                );

            }

        }

        /*
        |--------------------------------------------------------------------------
        | AGENCY CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $request->invitation_type == 'agency'
            && $existingUser
        ) {

            $agency = \App\Models\Agency::where(
                'user_id',
                $existingUser->id
            )->first();

            if ($agency) {

                return back()->with(

                    'error',

                    'Agency already exists in system.'

                );

            }

        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING INVITATION
        |--------------------------------------------------------------------------
        */

        $existingInvitation = DB::table('company_invitations')

            ->where('company_email', $request->company_email)

            ->where('invitation_type', $request->invitation_type)

            ->first();

        if ($existingInvitation) {

            return back()->with(

                'error',

                'Invitation already sent.'

            );

        }

        /*
        |--------------------------------------------------------------------------
        | TOKEN
        |--------------------------------------------------------------------------
        */

        $token = \Str::random(64);

        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        DB::table('company_invitations')->insert([

            'invitation_type' => $request->invitation_type,

            'company_name' => $request->company_name,

            'company_email' => $request->company_email,

            'whatsapp' => $request->whatsapp,

            'agency_id' => auth()->user()->agency->id,

            'message' => $request->message,

            'token' => $token,

            'created_at' => now(),

            'updated_at' => now(),

        ]);

        /*
        |--------------------------------------------------------------------------
        | LINK
        |--------------------------------------------------------------------------
        */

        $inviteLink =
            route('company.invitation.page', $token);

        /*
        |--------------------------------------------------------------------------
        | EMAIL
        |--------------------------------------------------------------------------
        */

        \Mail::raw(

            "You are invited to join Career Workforce.\n\n".
            $inviteLink,

            function ($mail) use ($request) {

                $mail->to($request->company_email)
                     ->subject('Invitation');

            }

        );

        /*
        |--------------------------------------------------------------------------
        | SUCCESS
        |--------------------------------------------------------------------------
        */

        return back()->with(

            'success',

            ucfirst($request->invitation_type).
            ' invitation sent successfully.'

        );

    } catch (\Exception $e) {

        return back()->with(

            'error',

            $e->getMessage()

        );

    }
}
public function companyInvitationPage($token)
{
    $invitation = DB::table('company_invitations')
        ->where('token', $token)
        ->first();

    if (!$invitation) {

        abort(404);

    }

    return view(
        'frontend.pages.company.invitation',
        compact('invitation')
    );
}

    /**
     * agency create job page
     *
     * @return Response
     */
    /**
     * AJAX lookup for agency job form Select2 fields.
     */
    public function jobFormLookup(string $type, \Illuminate\Http\Request $request)
    {
        return app(\App\Services\AttributeLookupService::class)->search($type, $request);
    }

    public function createJob()
    {
        try {
            // Check if user has reached the job limit
            storePlanInformation();
            $userPlan = session('user_plan');

            if ((int) $userPlan->job_limit < 1) {
                session()->flash('error', __('you_have_reached_your_plan_limit_please_upgrade_your_plan'));

                return redirect()->route('agency.plan');
            }

            $data['jobCategories'] = IndustryType::all()->sortBy('name');
            $data['roles'] = JobRole::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['educations'] = Education::all();
            $data['job_types'] = JobType::all();
            $data['salary_types'] = SalaryType::all();
            $data['tags'] = Tag::all()->sortBy('name');
            $data['setting'] = loadSetting();
            $all_benefits = Benefit::all()->sortBy('name');
            $data['questions'] = Auth::user()
                ->agency->questions()
                ->where('reuse', true)
                ->get();
            $non_agency_benefits = $all_benefits->whereNull('agency_id');
            $agency_benefits = $all_benefits->where('agency_id', currentAgency()->id);
            $data['benefits'] = $non_agency_benefits->merge($agency_benefits);
            $data['skills'] = Skill::all()->sortBy('name');
            $data['dynamicInputs'] = AgencyAttribute::all();
            $data['jobtitles']    = Profession::all();


            return view('frontend.pages.agency.postjob', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency store job
     *
     * @return Response
     */
    public function storeJob(JobCreateRequest $request)
    {
        try {
            $jobCreated = (new AgencyStoreService)->execute($request);

            flashSuccess(__('job_created_successfully'));

            return redirect()->route('agency.job.promote.show', $jobCreated->slug);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * job edit
     *
     * @return Response
     */
    public function editJob(Job $job)
    {
        try {
            $data['jobCategories'] = IndustryType::all()->sortBy('name');
            $data['roles'] = JobRole::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['educations'] = Education::all();
            $data['job_types'] = JobType::all();
            $data['salary_types'] = SalaryType::all();
            $data['tags'] = Tag::all()->sortBy('name');
            $data['start_day'] = $job->created_at->diffInDays();
            $data['end_day'] = $data['start_day'] + setting('job_deadline_expiration_limit');
            $data['skills'] = Skill::all()->sortBy('name');
            $job->load('tags', 'benefits');
            $data['job'] = $job;

            $all_benefits = Benefit::all()->sortBy('name');
            $non_agency_benefits = $all_benefits->whereNull('agency_id');
            $agency_benefits = $all_benefits->where('agency_id', currentAgency()->id);
            $data['benefits'] = $non_agency_benefits->merge($agency_benefits);
            $data['questions'] = Auth::user()
                ->agency->questions()
                ->where('reuse', true)
                ->get();
            $data['dynamicInputs'] = AgencyAttribute::all();
            $data['inputsData'] = AgencyAttributeTranslation::where('agency_id', currentAgency()->id)->where('job_id', $job->id)->get();
            $data['jobtitles']    = Profession::all();



            return view('frontend.pages.agency.editjob', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * job update
     *
     * @return Response
     */
    public function updateJob(JobCreateRequest $request, Job $job)
    {
        try {
            (new AgencyUpdateService)->execute($request, $job);

            return redirect()->route('agency.myjob');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Show promote job page
     *
     * @return Response
     */
    public function showPromoteJob(Job $job)
    {
        try {
            return view('frontend.pages.agency.job-created-success', [
                'jobCreated' => $job,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency promote job page
     *
     * @return Response
     */
    public function jobPromote(Job $job)
    {
        try {
            if (! auth('user')->check() || authUser()->role != 'agency') {
                return abort(403);
            }

            return view('frontend.pages.agency.promote-job', [
                'jobCreated' => $job,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency promote job
     *
     * @return Response
     */
    public function promoteJob(Request $request, Job $jobCreated)
    {
        try {
            (new AgencyPromoteJobService)->execute($request, $jobCreated);

            flashSuccess(__('job_promote_successfully'));

            return redirect()->route('website.job.details', $jobCreated->slug);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency bookmark candidate page
     *
     * @return Response
     */
    public function bookmarks(Request $request)
    {
        try {
            $query = currentAgency()->bookmarkCandidates();

            if ($request->category != 'all' && $request->has('category') && $request->category != null) {
                $query->wherePivot('category_id', $request->category);
            }

            $bookmarks = $query
                ->with('profession')
                ->paginate(12)
                ->withQueryString();
            $categories = AgencyBookmarkCategory::where('agency_id', auth()->user()->agency->id)->get();

            return view('frontend.pages.agency.bookmark', compact('bookmarks', 'categories'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency bookmark candidate
     *
     * @return Response
     */
    public function agencyBookmarkCandidate(Request $request, Candidate $candidate)
    {
        try {
            $agency = currentAgency();

            if ($request->cat) {
                $user_plan = $agency->userPlan;

                if (isset($user_plan) && $user_plan->candidate_cv_view_limit <= 0) {
                    return response()->json([
                        'message' => __('you_have_reached_your_limit_for_viewing_candidate_cv_please_upgrade_your_plan'),
                        'success' => false,
                        'redirect_url' => route('website.plan'),
                    ]);
                }

                isset($user_plan) ? $user_plan->decrement('candidate_cv_view_limit') : '';
            }

            $check = $agency->bookmarkCandidates()->toggle($candidate->id);

            if ($check['attached'] == [$candidate->id]) {
                DB::table('bookmark_agency')
                    ->where('agency_id', currentAgency()->id)
                    ->where('candidate_id', $candidate->id)
                    ->update(['category_id' => $request->cat]);

                // make notification to candidate
                $user = Auth::user('user');
                if ($candidate->user->shortlisted_alert) {
                    Notification::send($candidate->user, new CandidateBookmarkNotification($user, $candidate));
                }
                // notify to agency
                Notification::send(auth()->user(), new CandidateBookmarkNotification($user, $candidate));

                flashSuccess(__('candidate_added_to_bookmark_list'));
            } else {
                flashSuccess(__('candidate_removed_from_bookmark_list'));
            }

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency setting page
     *
     * @param  Request  $request
     * @param  Candidate  $candidate
     * @return Response
     */
    public function setting()
    {
        try {
            $user = authUser();

            if (! $user) {
                return redirect()->route('login');
            }

            $user->load(['agency', 'contactInfo', 'socialInfo']);
            $data['user'] = $user;
            $data['socials'] = $data['user']->socialInfo;
            $data['contact'] = $data['user']->contactInfo;
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();

            return view('frontend.pages.agency.setting', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency setting update
     *
     * @return Response
     */
    public function settingUpdateInformation(Request $request)
    {
        try {
            (new AgencySettingUpdateService)->update($request);

            flashSuccess(__('profile_updated'));

            return back();
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency Plan
     *
     * @return \Illuminate\Http\Response
     */
    public function plan()
    {
        try {
            $current_language = currentLanguage();
            $current_language_code = $current_language ? $current_language->code : config('templatecookie.default_language');
            $userplan = UserPlan::with([
                'plan' => function ($q) use ($current_language_code) {
                    $q->with([
                        'descriptions' => function ($q) use ($current_language_code) {
                            $q->where('locale', $current_language_code);
                        },
                    ]);
                },
            ])
                ->agencyData()
                ->firstOrFail();
            $transactions = Earning::with('plan:id,label', 'manualPayment:id,name')
                ->agencyData()
                ->latest()
                ->paginate(6);

            return view('frontend.pages.agency.plan', compact('userplan', 'transactions', 'current_language', 'current_language_code'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Download Transaction Invoice
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadTransactionInvoice(Earning $transaction)
    {
        try {
            $transaction = $transaction->load('plan', 'agency.user.contactInfo');
            $pdf = PDF::loadView('frontend.pages.invoice.download-invoice', compact('transaction'))->setOptions(['defaultFont' => 'sans-serif']);

            return $pdf->download('invoice_' . $transaction->order_id . '.pdf');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * View Transaction Invoice
     *
     * @return \Illuminate\Http\Response
     */
    public function viewTransactionInvoice(Earning $transaction)
    {
        try {
            if (currentAgency()->id != $transaction->agency_id) {
                abort(404);
            }

            $transaction = $transaction->load('plan', 'agency.user.contactInfo');

            return view('frontend.pages.invoice.preview-invoice', compact('transaction'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    
    

    /**
     * Account Progress
     *
     * @return \Illuminate\Http\Response
     */
    public function accountProgress()
    {
        try {
            $agency = currentAgency();

            if ($agency && $agency->profile_completion) {
                return redirect()->route('agency.dashboard');
            }

            if (request()->hasAny('profile', 'social', 'contact', 'complete')) {
                $fragment = match (true) {
                    request()->has('profile') => 'section-profile',
                    request()->has('social') => 'section-social',
                    request()->has('contact') => 'section-contact',
                    default => null,
                };

                $redirect = redirect()->route('agency.account-progress');
                if ($fragment) {
                    $redirect->withFragment($fragment);
                }

                return $redirect;
            }

            $user = authUser();

            if (! $user) {
                return redirect()->route('login');
            }

            $user->load(['agency', 'contactInfo', 'socialInfo']);
            $data['user'] = $user;
            $data['countries'] = Country::all();
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();
            $title = cms::first()->account_setup_title;
            $subtitle = cms::first()->account_setup_subtitle;
            $data['title'] = $title;
            $data['subtitle'] = $subtitle;
            $data['socials'] = $data['user']->socialInfo;

            return view('frontend.pages.agency.account-progress', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Profile Complete Progress
     *
     * @return \Illuminate\Http\Response
     */
    public function profileCompleteProgress(Request $request)
    {
        try {
            return (new AgencyAccountProgressService)->execute($request);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function repostJob(Request $request)
{
    $request->validate([
        'job_id' => 'required|exists:jobs,id',
    ]);

    $originalJob = Job::findOrFail($request->job_id);

    $agency = Auth::user()->agency;

    if (!$agency) {
        return back()->with('error', 'Agency profile not found.');
    }

    // Prevent duplicate repost
    $alreadyReposted = Job::where('repost_job_id', $originalJob->id)
        ->where('agency_id', $agency->id)
        ->exists();

    if ($alreadyReposted) {
        return back()->with('error', 'You already reposted this job.');
    }

    $job = $originalJob->replicate();

    // New reposted job values
    $job->company_id     = null;
    $job->agency_id      = $agency->id;
    $job->repost_job_id  = $originalJob->id;
    $job->status         = 'active';
    $job->created_at     = now();
    $job->updated_at     = now();
    $job->slug           = Str::slug($originalJob->title . '-' . Str::random(6));

    $job->save();

    return back()->with('success', 'Job reposted successfully.');
}
/**  candidate for agency */
public function candidates()
{
    $candidates = Candidate::where('agency_id', currentAgency()->id)->latest()->get();

    return view('frontend.pages.agency.candidates.index', compact('candidates'));
}

// CREATE PAGE
public function createCandidate()
{
    return view('frontend.pages.agency.candidates.create');
}

// STORE
public function storeCandidate(Request $request)
{
    // ✅ VALIDATION
    $request->validate([
        'first_name' => 'required',
        'cnic_number' => 'nullable',
        'passport_number' => 'nullable',
    ]);

    // ✅ STEP 1: CHECK DUPLICATE CANDIDATE
    $existingCandidate = Candidate::where(function ($q) use ($request) {

    if ($request->cnic_number) {

        $q->where('cnic_number', $request->cnic_number);

    }

    if ($request->passport_number) {

        $q->orWhere(
            'passport_number',
            $request->passport_number
        );

    }

})->first();

/*
|--------------------------------------------------------------------------
| DUPLICATE CHECK
|--------------------------------------------------------------------------
*/

if ($existingCandidate) {

    $ownerType = ucfirst(
        $existingCandidate->owner_type ?? 'public'
    );

    $ownerName = 'Unknown';

    /*
    |--------------------------------------------------------------------------
    | AGENT OWNER
    |--------------------------------------------------------------------------
    */

    if ($existingCandidate->owner_type == 'agent') {

        $owner = User::find(
            $existingCandidate->owner_id
        );

        $ownerName = $owner->name ?? 'Unknown Agent';
    }

    /*
    |--------------------------------------------------------------------------
    | AGENCY OWNER
    |--------------------------------------------------------------------------
    */

    elseif ($existingCandidate->owner_type == 'agency') {

        $owner = \App\Models\Agency::find(
            $existingCandidate->owner_id
        );

        $ownerName = optional($owner->user)->name
            ?? 'Unknown Agency';
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY OWNER
    |--------------------------------------------------------------------------
    */

    elseif ($existingCandidate->owner_type == 'company') {

        $owner = \App\Models\Company::find(
            $existingCandidate->owner_id
        );

        $ownerName = optional($owner->user)->name
            ?? 'Unknown Company';
    }

    /*
    |--------------------------------------------------------------------------
    | PUBLIC OWNER
    |--------------------------------------------------------------------------
    */

    elseif ($existingCandidate->owner_type == 'public') {

        $ownerName = 'Public Registration';

    }

    return redirect()->back()->with(

        'error',

        "Candidate already registered under {$ownerType} ({$ownerName})"

    );
}

    DB::beginTransaction();

    try {

        // ✅ STEP 2: CREATE USER (this auto creates candidate)
        $password = Str::random(8);

        $user = User::create([
            'name' => $request->first_name . ' ' . $request->last_name,
            'username' => strtolower(Str::slug($request->first_name)) . rand(100,999),
            'email' => $request->email,
            'whatsapp' => $request->whatsapp_number ?? null,
            'password' => Hash::make($password),
            'role' => 'candidate',
            'status' => 1,
            'auth_type' => 'email',
        ]);

        // ✅ STEP 3: GET AUTO CREATED CANDIDATE
        $candidate = $user->candidate;

        if (!$candidate) {
            throw new \Exception("Candidate not created automatically");
        }

        // ✅ STEP 4: PREPARE DATA
        $data = [
            'agency_id' => currentAgency()->id,
            'owner_type' => 'agency',
            'owner_id' => currentAgency()->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'marital_status' => $request->marital_status,
            'whatsapp_number' => $request->whatsapp_number,
            'passport_number' => $request->passport_number,
            'passport_issue_date' => $request->passport_issue_date,
            'passport_expiry_date' => $request->passport_expiry_date,
            'place_of_issue' => $request->place_of_issue,
            'cnic_number' => $request->cnic_number,
            'expected_salary' => $request->expected_salary,
            'expected_location' => $request->expected_location,
            'status' => $request->status,
            'address' => $request->address,
            'district' => $request->district,
            'country' => $request->country,
            'bio' => $request->bio,
        ];

        // ✅ STEP 5: FILE UPLOADS
        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('candidates', 'public');
        }

        if ($request->hasFile('cv')) {
            $data['cv'] = $request->file('cv')->store('candidates', 'public');
        }

        if ($request->hasFile('passport_file')) {
            $data['passport_file'] = $request->file('passport_file')->store('candidates', 'public');
        }

        if ($request->hasFile('driving_license')) {
            $data['driving_license'] = $request->file('driving_license')->store('candidates', 'public');
        }

        if ($request->hasFile('cnic_document')) {
            $data['cnic_document'] = $request->file('cnic_document')->store('candidates', 'public');
        }

        if ($request->hasFile('other_document')) {
            $data['other_document'] = $request->file('other_document')->store('candidates', 'public');
        }

        // ✅ STEP 6: UPDATE EXISTING CANDIDATE (NOT CREATE)
        $candidate->update($data);

        DB::commit();

        return redirect()->route('agency.candidates.index')
            ->with('success', 'Candidate Added Successfully');

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()->back()->with('error', $e->getMessage());
    }
}

// EDIT
private function agencyOwnedCandidateOrFail($id): Candidate
{
    return Candidate::where('agency_id', currentAgency()->id)->findOrFail($id);
}

public function editCandidate($id)
{
    $candidate = $this->agencyOwnedCandidateOrFail($id);

    return view('frontend.pages.agency.candidates.edit', compact('candidate'));
}

// UPDATE
public function updateCandidate(Request $request, $id)
{
    $candidate = $this->agencyOwnedCandidateOrFail($id);

    $data = $request->except(['_token', '_method', 'id', 'agency_id', 'agent_id', 'owner_id', 'owner_type', 'user_id']);

    // ✅ handle photo
    if($request->hasFile('photo')){
        $data['photo'] = $request->file('photo')->store('candidates','public');
    }

    // ✅ handle CV
    if($request->hasFile('cv')){
        $data['cv'] = $request->file('cv')->store('candidates','public');
    }
    if($request->hasFile('passport_file')){
    $data['passport_file'] = $request->file('passport_file')->store('candidates','public');
}

if($request->hasFile('driving_license')){
    $data['driving_license'] = $request->file('driving_license')->store('candidates','public');
}
if($request->hasFile('cnic_document')){
    $data['cnic_document'] = $request->file('cnic_document')->store('candidates','public');
}

if($request->hasFile('other_document')){
    $data['other_document'] = $request->file('other_document')->store('candidates','public');
}

    $candidate->update($data);

    return redirect()->route('agency.candidates.index')->with('success','Updated successfully');
}

// DELETE
public function deleteCandidate($id)
{
    $candidate = $this->agencyOwnedCandidateOrFail($id);

    /*
    |--------------------------------------------------------------------------
    | PUBLIC CANDIDATE
    |--------------------------------------------------------------------------
    */

    if($candidate->owner_type == 'public'){

        return back()->with(
            'error',
            'Only admin can delete public candidates'
        );

    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    $candidate->delete();

    return back()->with(
        'success',
        'Candidate deleted successfully'
    );
}
// DOCUMENT CHECKLIST
public function candidateDocuments($id)
{
    $candidate = $this->agencyOwnedCandidateOrFail($id);
    $document = CandidateDocument::where('candidate_id', $candidate->id)->first();
    $checklist = CandidateDocument::CHECKLIST_KEYS;

    return view('frontend.pages.agency.candidates.documents', compact('candidate', 'document', 'checklist'));
}

public function updateCandidateDocumentStatus(Request $request, $id)
{
    $candidate = $this->agencyOwnedCandidateOrFail($id);
    $document = CandidateDocument::where('candidate_id', $candidate->id)->first();

    if (! $document) {
        flashError('This candidate has not uploaded any documents yet.');

        return back();
    }

    $validated = $request->validate([
        'doc_key' => ['required', 'string', 'in:'.implode(',', array_keys(CandidateDocument::CHECKLIST_KEYS))],
        'status' => ['required', 'in:pending,approved,rejected'],
        'note' => ['nullable', 'string', 'max:1000'],
        'expiry_date' => ['nullable', 'date'],
    ]);

    $statuses = $document->review_status ?? [];
    $notes = $document->review_notes ?? [];
    $statuses[$validated['doc_key']] = $validated['status'];
    $notes[$validated['doc_key']] = $validated['note'] ?? null;

    $updates = [
        'review_status' => $statuses,
        'review_notes' => $notes,
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
    ];

    if (filled($validated['expiry_date'] ?? null)) {
        if ($validated['doc_key'] === 'medical') {
            $updates['medical_expiry_date'] = $validated['expiry_date'];
        } elseif ($validated['doc_key'] === 'police_character_certificate') {
            $updates['police_certificate_expiry_date'] = $validated['expiry_date'];
        }
    }

    $document->update($updates);

    flashSuccess(CandidateDocument::CHECKLIST_KEYS[$validated['doc_key']].' marked as '.$validated['status'].'.');

    return back();
}
/** end cadidate for agency */

    /**
     * Make Job Expire
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function makeJobExpire(Job $job)
    {
        try {
            $job->update(['status' => 'expired']);

            flashSuccess(__('job_status_now_expire'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Make Job Active
     *
     * @return \Illuminate\Http\Response
     */
    public function makeJobActive(Job $job)
    {
        try {

            if ($job->deadline < now()) {

                flashWarning('Deadline expired');
            } else {

                $job->update(['status' => 'active']);

                flashSuccess('Job Status Now Active');
            }

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Bookmark Categories
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCategories(Request $request)
    {
        try {
            $query = AgencyBookmarkCategory::where('agency_id', auth()->user()->agency->id);
            $categories = $query->paginate(12);
            $dataCount = AgencyBookmarkCategory::where('agency_id', auth()->user()->agency->id)->count();

            if ($request->ajax) {
                return response()->json($query->get());
            }

            return view('frontend.pages.agency.bookmark-category', compact('categories', 'dataCount'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Bookmark Category Store
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCategoriesStore(Request $request)
    {
        try {
            $request->validate(['name' => 'required| min:2']);

            AgencyBookmarkCategory::create([
                'agency_id' => auth()->user()->agency->id,
                'name' => $request->name,
            ]);

            flashSuccess(__('category_created_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Bookmark Category Edit
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCategoriesEdit(AgencyBookmarkCategory $category)
    {
        try {
            $categories = AgencyBookmarkCategory::where('agency_id', auth()->user()->agency->id)->paginate(12);
            $dataCount = AgencyBookmarkCategory::where('agency_id', auth()->user()->agency->id)->count();

            return view('frontend.pages.agency.bookmark-category', compact('categories', 'dataCount', 'category'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Bookmark Category Update
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCategoriesUpdate(Request $request, AgencyBookmarkCategory $category)
    {
        try {
            $category->update(['name' => $request->name]);

            flashSuccess(__('category_updated_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Bookmark Category Delete
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCategoriesDestroy(AgencyBookmarkCategory $category)
    {
        try {
            $category->delete();

            flashSuccess(__('category_deleted_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Job Clone
     *
     * @return \Illuminate\Http\Response
     */
    public function jobClone(Job $job)
    {
        try {
            $user = authUser();
            $user_plan = $user->agency->userPlan;

            if (! $user_plan->job_limit) {
                session()->flash('error', __('you_have_reached_your_plan_limit_please_upgrade_your_plan'));

                return redirect()->route('agency.plan');
            }

            $newJob = $job->replicate();
            $newJob->created_at = now();

            if ($job->featured && $user_plan->featured_job_limit) {
                $newJob->featured = 1;
                $user_plan->featured_job_limit = $user_plan->featured_job_limit - 1;
            } else {
                $newJob->featured = 0;
            }

            if ($job->highlight && $user_plan->highlight_job_limit) {
                $newJob->highlight = 1;
                $user_plan->highlight_job_limit = $user_plan->highlight_job_limit - 1;
            } else {
                $newJob->highlight = 0;
            }

            $newJob->save();
            $user_plan->job_limit = $user_plan->job_limit - 1;
            $user_plan->save();

            storePlanInformation();

            flashSuccess(__('job_cloned_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * agency Username Update
     *
     * @return \Illuminate\Http\Response
     */
    public function usernameUpdate(Request $request)
    {
        try {
            $request->session()->put('type', 'account');

            if ($request->type == 'agency_username') {
                $request->validate([
                    'username' => 'required|unique:users,username,' . auth()->user()->id,
                ]);

                authUser()->update([
                    'username' => $request->username,
                ]);

                flashSuccess(__('username_updated_successfully'));

                return back();
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function manageQuestion()
    {
        try {
            $questions = currentAgency()
                ->questions()
                ->latest()
                ->paginate(8);
            $dataCount = currentAgency()
                ->questions()
                ->count();

            return view('frontend.pages.agency.manage-questions', [
                'questions' => $questions,
                'dataCount' => $dataCount,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function storeQuestion(Request $request)
    {
        try {
            if ($request->get('isEditing') == 'true' && $request->get('editingId')) {
                $toEdit = AgencyQuestion::query()->findOrFail($request->get('editingId'));

                $toEdit->update([
                    'title' => $request->get('newQuestion'),
                    'required' => $request->has('isRequired'),
                ]);

                flashSuccess(__('question_updated_success'));

                return back();
            }

            if ($request->wantsJson()) {
                $request->validate(['newQuestion' => 'required']);
                $question = currentAgency()
                    ->questions()
                    ->create([
                        'reuse' => $request->get('newQuestionSave'),
                        'title' => $request->get('newQuestion'),
                        'required' => $request->get('isRequired'),
                    ]);

                return response()->json($question->only('id', 'reuse', 'title', 'required'), 201);
            }
            $request->validate(['newQuestion' => 'required']);
            currentAgency()
                ->questions()
                ->create([
                    'reuse' => $request->has('newQuestionSave'),
                    'title' => $request->get('newQuestion'),
                    'required' => $request->has('isRequired'),
                    'reuse' => 1,
                ]);

            flashSuccess(__('question_created_success'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function deleteQuestion(AgencyQuestion $question)
    {
        $question->delete();
        flashSuccess(__('question_deleted_success'));

        return back();
    }

    public function featureToggle(Request $request)
    {
        try {
            if ($request->has('enableQuestion')) {
                currentAgency()->update([
                    'question_feature_enable' => true,
                ]);
                flashSuccess(__('question_feature_enable'));
            } else {
                currentAgency()->update([
                    'question_feature_enable' => false,
                ]);
                flashSuccess(__('question_feature_disabled'));
            }

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
   public function forwardCandidateEmail(Request $request)
{
    try {

        $request->validate([
            'candidate_id'=>'required',
            'job_id'=>'required',
            'email'=>'required|email'
        ]);

        $candidate = Candidate::with('user')->findOrFail($request->candidate_id);
        $job = Job::findOrFail($request->job_id);

        $docs = $request->docs ?? [];

        Mail::to($request->email)
            ->send(new ForwardCandidateMail($candidate,$request->job_id,$docs));

        // Structured audit trail (not just an email fire-and-forget): record/
        // update the AppliedJob row so the forward shows up in the pipeline
        // and the employer gets an in-app notification if they have an account.
        $application = AppliedJob::firstOrNew([
            'job_id' => $job->id,
            'candidate_id' => $candidate->id,
        ]);

        if (! $application->exists) {
            $group = ApplicationGroup::firstOrCreate([
                'name' => 'Job '.$job->id,
                'company_id' => $job->company_id,
            ]);
            $application->application_group_id = $group->id;
        }

        $application->company_id = $job->company_id;
        $application->agency_id = currentAgency()->id;
        if (in_array($application->status, [null, '', 'pending'], true)) {
            $application->status = 'forwarded';
        }
        $application->save();

        if ($job->company?->user) {
            Notification::send(
                $job->company->user,
                new \App\Notifications\Website\Company\CandidateForwardedNotification($application)
            );
        }

        return back()->with('success','Candidate sent successfully');

    } catch (\Exception $e) {

        return back()->with('error',$e->getMessage());

    }
}
    public function hire_request(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
        ]);

        $employer = auth()->user(); // Assumes the employer is logged in
        $candidate = Candidate::findOrFail($request->candidate_id);

        $message = "Employer {$employer->name} wants to hire Candidate {$candidate->name}.";

        // Save the request to the database
        HireRequest::create([
            'candidate_id' => $candidate->id,
            'agency_id' => $employer->agency->id,
            'message' => $message,
        ]);

        // Send notification to admin (optional)
        // Notification logic goes here

        return back()->with('success', 'Your request has been sent to the admin.');
    }

    // ─── Phase 10: Agent Sub-Account Invite Flow ───────────────────────────

    public function myAgents()
    {
        $agencyUserId = auth()->id();
        $agents   = \App\Models\User::where('agency_id', $agencyUserId)->get();
        $invites  = \App\Models\AgentInvite::where('agency_user_id', $agencyUserId)
            ->orderByDesc('created_at')->get();

        return view('frontend.pages.agency.my-agents', compact('agents', 'invites'));
    }

    public function sendAgentInvite(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'agent_name'  => 'required|string|max:100',
            'agent_email' => 'required|email|max:150',
        ]);

        $existing = \App\Models\AgentInvite::where('agency_user_id', auth()->id())
            ->where('agent_email', $request->agent_email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return back()->with('error', 'A pending invite for this email already exists.');
        }

        $token  = \Str::random(64);
        $invite = \App\Models\AgentInvite::create([
            'agency_user_id' => auth()->id(),
            'agent_name'     => $request->agent_name,
            'agent_email'    => $request->agent_email,
            'token'          => $token,
            'expires_at'     => now()->addDays(7),
        ]);

        $agencyName = auth()->user()->name;
        $link       = route('agency.agent.invite.accept', $token);

        \Mail::raw(
            "Hi {$request->agent_name},\n\n"
            . "{$agencyName} has invited you to join their recruitment team on Career Workforce.\n\n"
            . "Click the link below to accept the invitation and create your agent account:\n\n"
            . $link . "\n\n"
            . "This link expires in 7 days.\n\n"
            . "If you did not expect this invitation, you can ignore this email.",
            function ($mail) use ($request, $agencyName) {
                $mail->to($request->agent_email)
                     ->subject("You're invited to join {$agencyName} on Career Workforce");
            }
        );

        return back()->with('success', 'Invitation sent to ' . $request->agent_email . '.');
    }

    public function toggleAgentStatus(int $id)
    {
        $agent = \App\Models\User::query()
            ->where('role', 'agent')
            ->where('agency_id', auth()->id())
            ->findOrFail($id);

        $agent->status = (int) $agent->status === 1 ? 0 : 1;
        $agent->save();

        $label = $agent->status ? 'activated' : 'suspended';

        return back()->with('success', "Agent {$agent->name} has been {$label}.");
    }

    public function commissions(Request $request)
    {
        $agencyId = currentAgency()->id;

        $commissions = \App\Models\Commission::with(['candidate.user', 'job', 'appliedJob.job.company.user'])
            ->where('agency_id', $agencyId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $totals = [
            'pending' => \App\Models\Commission::where('agency_id', $agencyId)->where('status', \App\Models\Commission::STATUS_PENDING)->sum('amount'),
            'approved' => \App\Models\Commission::where('agency_id', $agencyId)->where('status', \App\Models\Commission::STATUS_APPROVED)->sum('amount'),
            'paid' => \App\Models\Commission::where('agency_id', $agencyId)->where('status', \App\Models\Commission::STATUS_PAID)->sum('amount'),
        ];

        return view('frontend.pages.agency.commissions', compact('commissions', 'totals'));
    }

    public function exportCommissions()
    {
        $agencyId = currentAgency()->id;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Export\CommissionExport($agencyId),
            'agency-commissions-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function downloadCommissionReceipt(int $id)
    {
        $commission = \App\Models\Commission::with(['candidate.user', 'job', 'appliedJob.job.company.user'])
            ->where('agency_id', currentAgency()->id)
            ->findOrFail($id);

        $pdf = PDF::loadView('frontend.pages.agency.commission-receipt', compact('commission'))
            ->setOptions(['defaultFont' => 'sans-serif']);

        return $pdf->download('commission-receipt-'.$commission->id.'.pdf');
    }

    public function agentInviteAcceptPage(string $token)
    {
        $invite = \App\Models\AgentInvite::where('token', $token)->first();

        if (!$invite || $invite->isExpired()) {
            return view('frontend.pages.agency.invite-expired');
        }

        if ($invite->accepted_at) {
            return view('frontend.pages.agency.invite-already-accepted');
        }

        $agency = \App\Models\User::find($invite->agency_user_id);

        return view('frontend.pages.agency.invite-accept', compact('invite', 'agency', 'token'));
    }
}
