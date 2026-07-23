<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Candidate;
use App\Models\AppliedJob;
use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Http;
use App\Services\AI\GPTCVParserService;
use App\Models\ApplicationGroup;
use App\Models\User;

use Illuminate\Support\Facades\Mail;
use App\Mail\ForwardCandidateMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AgentController extends Controller
{
    public function dashboard()
{
    $agentId = auth()->id();

    $totalWorkers = Candidate::where('agent_id', $agentId)->count();

    $selected = Candidate::where('agent_id', $agentId)
        ->where('status','selected')
        ->count();

    $pending = Candidate::where('agent_id', $agentId)
        ->whereIn('status',['submitted','shortlisted','interview'])
        ->count();

    // ✅ ADD PIPELINE HERE
    $pipeline = [
        'submitted' => Candidate::where('agent_id',$agentId)->where('status','submitted')->count(),
        'shortlisted' => Candidate::where('agent_id',$agentId)->where('status','shortlisted')->count(),
        'interview' => Candidate::where('agent_id',$agentId)->where('status','interview')->count(),
        'selected' => Candidate::where('agent_id',$agentId)->where('status','selected')->count(),
        'deployed' => Candidate::where('agent_id',$agentId)->where('status','deployed')->count(),
    ];

    return view('frontend.pages.agent.dashboard', compact(
        'totalWorkers',
        'selected',
        'pending',
        'pipeline' // ✅ IMPORTANT
    ));
}
    public function jobs()
{
    $agentId = (string) auth()->id(); // ✅ match your JSON ["839","843"]

    $jobs = \App\Models\Job::with([
            'company.user',   // eager load company + user
            'agency.user'     // eager load agency + user
        ])
        ->where(function($q) use ($agentId){

            // ✅ Jobs for ALL agents
            $q->whereJsonContains('assigned_agents', 'all')

              // ✅ Jobs assigned to this agent
              ->orWhereJsonContains('assigned_agents', $agentId);

        })
        ->where(function($q){
            // ✅ Only valid jobs (either company or agency)
            $q->whereNotNull('company_id')
              ->orWhereNotNull('agency_id');
        })
        ->latest()
        ->paginate(10);

    // ✅ Load candidates ONCE (avoid query in blade)
    $candidates = \App\Models\Candidate::where('agent_id', auth()->id())->get();

    return view('frontend.pages.agent.jobs', compact('jobs','candidates'));
}

public function applyCandidate(Request $request)
{
    $job = \App\Models\Job::findOrFail($request->job_id);

    $exists = \App\Models\AppliedJob::where([
        'job_id' => $request->job_id,
        'candidate_id' => $request->candidate_id
    ])->first();

    if ($exists) {
        return back()->with('error', 'Already applied');
    }

    // ✅ FIXED COMPANY ID
    $group = \App\Models\ApplicationGroup::firstOrCreate([
        'name' => 'Job '.$request->job_id,
        'company_id' => $job->company_id
    ]);

    \App\Models\AppliedJob::create([
        'job_id' => $request->job_id,
        'candidate_id' => $request->candidate_id,
        'company_id' => $job->company_id, // ✅ FIX
        'agent_id' => auth()->id(),
        'application_group_id' => $group->id,
        'status' => 'pending'
    ]);

    return back()->with('success', 'Candidate applied successfully');
}

public function setInterview(Request $request, $id)
{
    $app = \App\Models\AppliedJob::findOrFail($id);

    $app->interview_date = $request->interview_date;
    $app->interview_location = $request->interview_location;
    $app->status = 'interview';

    $app->save();

    return back()->with('success','Interview scheduled');
}

public function submitCandidate(Request $request)
{
    $request->validate([
        'job_id' => 'required',
        'candidate_id' => 'required',
    ]);

    $job = \App\Models\Job::findOrFail($request->job_id);

    \DB::table('job_candidate_pipeline')->insert([

        'job_id' => $job->id,

        'candidate_id' => $request->candidate_id,

        'agent_id' => auth()->id(),

        'agency_id' => $job->agency_id,

        'company_id' => $job->company_id,

        'status' => 'submitted_by_agent',

        'created_at' => now(),

        'updated_at' => now(),

    ]);

    flashSuccess('Candidate submitted successfully');

    return back();
}

public function updateVisa(Request $request, $id)
{
    $app = \App\Models\AppliedJob::findOrFail($id);

    $app->visa_status = $request->visa_status;
    $app->save();

    return back()->with('success','Visa updated');
}


public function applications()
{
    $applications = AppliedJob::with(['job','candidate'])
        ->where('agent_id', auth()->id()) // 🔥 FILTER
        ->latest()
        ->get();

    return view('frontend.pages.agent.applications', compact('applications'));
}

public function availableJobs()
{
    $jobs = auth()->user()

        ->assignedAgentJobs()

        ->with([
            'company.user',
            'job_type'
        ])

        ->latest()

        ->paginate(12);

    return view(
        'frontend.pages.agent.available-jobs',
        compact('jobs')
    );
}

public function pipeline()
{
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

        ->where(
            'job_candidate_pipeline.agent_id',
            auth()->id()
        )

        ->latest()

        ->get();

    return view(
        'frontend.pages.agent.pipeline',
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
public function updateStatus(Request $request, $id)
{
    $app = AppliedJob::findOrFail($id);

    $app->status = $request->status;
    $app->save();

    return back()->with('success','Status updated');
}

    public function viewJob($id)
    {
        $job = DB::table('agent_job')->where('id', $id)->first();

        // Remove NEW badge
        DB::table('agent_job')->where('id', $id)->update(['is_new' => 0]);

        return view('frontend.pages.agent.job-view', compact('job'));
    }

    public function notifications()
    {
        $agentId = auth()->id();

        $jobs = DB::table('agent_job')
            ->join('jobs', 'jobs.id', '=', 'agent_job.job_id')
            ->where('agent_job.agent_id', $agentId)
            ->where('agent_job.is_new', 1)
            ->select('jobs.title')
            ->get();

        return view('frontend.pages.agent.all-notification', compact('jobs'));
    }
    
    public function setting()
{
    $user = auth()->user();

    return view('frontend.pages.agent.setting', compact('user'));
}

    // LIST
public function candidates()
{
    $candidates = Candidate::where('agent_id', auth()->id())->latest()->get();

    return view('frontend.pages.agent.candidates.index', compact('candidates'));
}

// CREATE PAGE
public function createCandidate()
{
    $experiences     = \App\Models\Experience::all();
    $educations      = \App\Models\Education::all();
    $industries      = \App\Models\IndustryType::all();
    $professions     = \App\Models\Profession::all()->sortBy('name');
    $skills          = \App\Models\Skill::all()->sortBy('name');
    $languages       = \App\Models\CandidateLanguage::all(['id','name']);
    $searchCountries = \App\Models\SearchCountry::all();
    $job_roles       = \App\Models\JobRole::all()->sortBy('name');

    return view('frontend.pages.agent.candidates.create', compact(
        'experiences',
        'educations',
        'industries',
        'professions',
        'skills',
        'languages',
        'searchCountries',
        'job_roles'
    ));
}
public function getStates(Request $request)
{
    $states = \App\Models\State::where('country_id', $request->country_id)
                ->select('id','name')
                ->get();

    return response()->json([
        'states' => $states
    ]);
}

public function getCities(Request $request)
{
    $cities = \App\Models\City::where('state_id', $request->state_id)
                ->select('id','name')
                ->get();

    return response()->json([
        'cities' => $cities
    ]);
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
            'agent_id' => auth()->id(),
            'owner_type' => 'agent',
            'owner_id' => auth()->id(),
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

        return redirect()->route('agent.candidates.index')
            ->with('success', 'Candidate Added Successfully');

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()->back()->with('error', $e->getMessage());
    }
}

// EDIT
public function editCandidate($id)
{
    $candidate = Candidate::findOrFail($id);

    return view('frontend.pages.agent.candidates.edit', compact('candidate'));
}

// UPDATE
public function updateCandidate(Request $request, $id)
{
    $candidate = Candidate::findOrFail($id);

    $data = $request->all();

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

    return redirect()->route('agent.candidates.index')->with('success','Updated successfully');
}

// DELETE
public function deleteCandidate($id)
{
    $candidate = Candidate::findOrFail($id);

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
    | AGENT OWNER CHECK
    |--------------------------------------------------------------------------
    */

    if(

        $candidate->owner_type == 'agent'

        &&

        $candidate->owner_id != auth()->id()

    ){

        return back()->with(
            'error',
            'You do not have permission to delete this candidate'
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
function extractText($file)
{
    $parser = new Parser();
    $pdf = $parser->parseFile($file->getPathname());

    return $pdf->getText();
}
public function cvOCR(Request $request)
{
    $file = $request->file('cv');

    $text = $this->extractText($file);

    $data = app(GPTCVParserService::class)->parse($text);

    return response()->json($data ?? []);
}

public function settingUpdateInformation(Request $request)
{
    $user = auth()->user();

    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email,'.$user->id,
        'username' => 'required|unique:users,username,'.$user->id,
        'password' => 'nullable|min:8|same:confirm_password',
    ]);

    $user->name = $request->name;
    $user->email = $request->email;
    $user->username = $request->username;
    $user->whatsapp = $request->whatsapp;

    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    if ($request->hasFile('image')) {
        $user->image = uploadImage($request->file('image'), 'images/website');
    }

    $user->save();

    return back()->with('success', __('profile_updated'));
}

public function accountprogress()
{
    $user = auth()->user();
    $agentId = $user->id;

    $fields = [
        'name' => (bool) $user->name,
        'email' => (bool) $user->email,
        'whatsapp' => (bool) $user->whatsapp,
        'image' => (bool) $user->image,
        'agency_linked' => (bool) $user->agency_id,
    ];

    $completed = count(array_filter($fields));
    $pct = (int) round(($completed / count($fields)) * 100);

    $stats = [
        'workers' => Candidate::where('agent_id', $agentId)->count(),
        'selected' => Candidate::where('agent_id', $agentId)->where('status', 'selected')->count(),
        'pending' => Candidate::where('agent_id', $agentId)->whereIn('status', ['submitted', 'shortlisted', 'interview'])->count(),
    ];

    return view('frontend.pages.agent.account-progress', compact('user', 'fields', 'pct', 'stats'));
}
}