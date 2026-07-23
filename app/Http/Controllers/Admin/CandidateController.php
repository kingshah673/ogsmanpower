<?php

namespace App\Http\Controllers\Admin;

use App\Export\CandidateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateRequest;
use App\Models\Admin;
use App\Models\AppliedJob;
use App\Models\Candidate;
use App\Models\CandidateCvView;
use App\Models\CandidateLanguage;
use App\Models\ContactInfo;
use App\Models\Education;
use App\Models\Experience;
use App\Models\JobRole;
use App\Models\Profession;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillTranslation;
use App\Models\User;
use App\Models\Attachment;
use App\Models\BilangualResumeSubscription;
use App\Models\CandidateAttribute;
use App\Models\CandidateDocument;
use App\Models\CandidatePlan;
use App\Models\CandidateStatus;
use App\Models\City;
use App\Models\IndustryType;
use App\Models\JobRequirement;
use App\Models\LanguageData;
use App\Models\SearchCountry;
use App\Models\State;
use Modules\Language\Entities\Language;
use App\Notifications\CandidateCreateApprovalPendingNotification;
use App\Notifications\CandidateCreateNotification;
use App\Notifications\UpdateCompanyPassNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Location\Entities\Country;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use PDF;
use Mpdf\Mpdf;
use PDO;
use App\Services\CandidateResumeViewService;
use Twilio\Rest\Client;

class CandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     public function approveResumeSubscription($id)
    {
        $subscription = BilangualResumeSubscription::findOrFail($id);
        $subscription->status = 'approved'; // Set status to approved
        $subscription->save();

        return redirect()->back()->with('success', 'Subscription approved successfully.');
    }

    // Delete Subscription
    public function deleteResumeSubscription($id)
    {
        $subscription = BilangualResumeSubscription::findOrFail($id);
        $subscription->delete();

        return redirect()->back()->with('success', 'Subscription deleted successfully.');
    }
    public function resumeSubscription($id){
        $subscription = BilangualResumeSubscription::where('candidate_id',$id)->where('payment_method','manual')->get();

        return view('backend.candidate.resume-subscription', compact('subscription'));
    }
    public function editPlan()
    {
        $plan = candidateFeaturedPlan();

        return view('backend.candidate.edit-candidate-plan', compact('plan'));
    }
     public function storeOrUpdatePlan(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'planName' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
        ]);

        if (! \Illuminate\Support\Facades\Schema::hasTable('candidate_plans')) {
            return redirect()->back()->with('error', 'Candidate plans table is missing. Please run database migrations.');
        }

        // Fetch the first plan or create one if it doesn't exist
        $plan = CandidatePlan::firstOrCreate(
            [], // Condition (empty as we only allow one plan)
            [
                'name' => $validated['planName'],
                'price' => $validated['price'],
                'duration' => $validated['duration'],
            ]
        );

        // If the plan already existed, update it
        if (!$plan->wasRecentlyCreated) {
            $plan->update([
                'name' => $validated['planName'],
                'price' => $validated['price'],
                'duration' => $validated['duration'],
            ]);
            return redirect()->back()->with('success', 'Plan updated successfully!');
        }

        return redirect()->back()->with('success', 'Plan created successfully!');
    }

    public function addCandidateDetails(Request $request)
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'job_id' => 'nullable|exists:jobs,id',
            'name' => 'required|string|max:255',
            'attachments' => 'nullable|file|mimes:jpg,jpeg,png,pdf',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachments')) {
            $attachmentPath = $request->file('attachments')->store('attachments', 'public');
        }
        CandidateStatus::create([
            'candidate_id' => $request->candidate_id,
            'admin_id' =>  $request->admin_id,
            'job_id' => $request->job_id,
            'name' => $request->name,
            'attachments' => $attachmentPath,
        ]);

        return redirect()->back()->with('success', 'Details added successfully.');
    }

    public function candidate_status()
    {
        if (auth()->user()->hasRole('agent')) {
            $adminId = auth()->user()->id;
            $candidates = AppliedJob::with(['applicationGroup', 'job', 'candidate'])
                ->whereHas('candidate', function ($query) use ($adminId) {
                    $query->where('admin_id', $adminId);
                })
                // ->whereHas('applicationGroup', function ($query) {
                //     $query->whereIn('name', ['Shortlisted', 'Selected']);
                // })
                ->whereIn('status', ['Shortlisted', 'Selected'])
                ->paginate(10);

            return view('backend.candidate.assign-candidate', compact('candidates'));
        } else {
            $candidates = AppliedJob::with(['applicationGroup', 'job', 'candidate'])
                // ->whereHas('applicationGroup', function ($query) {
                //     $query->whereIn('name', ['Shortlisted', 'Selected']);
                // })
                ->whereIn('status', ['Shortlisted', 'Selected'])
                ->whereNotNull('candidate_id')
                ->paginate(10);
// dd($candidates);
            $agents = Admin::whereHas('roles', function ($query) {
                $query->where('name', 'agent');
            })
                ->get();
            return view('backend.candidate.candidate-status', compact('candidates', 'agents'));
        }
    }

    public function assignCandidateDetails(Request $request)
    {
        $candidate = AppliedJob::with('applicationGroup', 'job', 'candidate')->where('candidate_id', $request->candidate_id)->where('job_id', $request->job_id)->first();
        $assignCandidates = CandidateStatus::where('candidate_id', $request->candidate_id)->where('job_id', $request->job_id)->paginate('10');
        return view('backend.candidate.assign-candidate-details', compact('candidate', 'assignCandidates'));
    }

    public function assignAgent(Request $request, Candidate $candidate)
    {
        $request->validate([
            'agent_id' => 'nullable|exists:users,id',
        ]);

        $candidate->admin_id = $request->admin_id;
        $candidate->save();

        return back()->with('success', 'Agent assigned successfully.');
    }

    public function approveCandidate($id)
    {
        $candidate = CandidateStatus::findOrFail($id);
        $candidate->is_approved = 1; // Approve
        $candidate->save();

        return back()->with('success', 'Candidate approved successfully.');
    }

    public function disapproveCandidate($id)
    {
        $candidate = CandidateStatus::findOrFail($id);
        $candidate->is_approved = 0; // Disapprove
        $candidate->save();

        return back()->with('success', 'Candidate disapproved successfully.');
    }

    public function deleteCandidate($id)
    {
        $candidate = CandidateStatus::findOrFail($id);
        $candidate->delete();

        return back()->with('success', 'Candidate deleted successfully.');
    }

    public function seekerDynamicInputs()
    {
        abort_if(
            ! auth()->user()->hasRole('superadmin')
            && ! userCan('candidate.update')
            && ! userCan('candidate.create'),
            403
        );

        $sections = \App\Services\DynamicFieldService::seekerSections();
        $attributes = \App\Services\DynamicFieldService::seekerDefinitions();
        $groupedSections = \App\Services\DynamicFieldService::groupBySections($attributes, $sections, 'basic-info');

        return view('backend.candidate.seeker-dynamic-inputs', compact('attributes', 'sections', 'groupedSections'));
    }

    public function dynamic_inputs(Candidate $candidate)
    {
        return redirect()->route('admin.candidate.dynamic_inputs');
    }

    /**
     * @deprecated Use admin.candidate.dynamic_inputs — kept for old bookmarks/links.
     */
    public function dynamic_input($id)
    {
        return redirect()->route('admin.candidate.dynamic_inputs', $id);
    }
    public function viewResume(Request $request, CandidateResumeViewService $resumeViewService)
{
    try {

        /*
        |--------------------------------------------------------------------------
        | CANDIDATE
        |--------------------------------------------------------------------------
        */

        $candidate = Candidate::query()
            ->where('id', $request->candidate_id)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD LIMIT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            $request->action_type == 'download'
            &&
            !auth()->user()->canUseFeature(
                'multilingual_cv_downloads'
            )
        ) {

            flashError(
                'Your CV download limit has been exceeded'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE RESUME FORMAT
        |--------------------------------------------------------------------------
        */

        $candidate->update([

            'resume_format' => $request->format
        ]);

        $resumeViewService->loadCandidateForResume($candidate);
        $view = $resumeViewService->resolveView($request->format);
        $data = $resumeViewService->buildViewData($candidate, $request);

        /*
        |--------------------------------------------------------------------------
        | BILANGUAL PDF
        |--------------------------------------------------------------------------
        */

        if (
            $request->format == 'bilangual_format'
        ) {

            if (
                $request->action_type == 'download'
            ) {

                $htmlContent = view(
                    $view,
                    $data
                )->render();

                $mpdf = new Mpdf([
                    'mode' => 'utf-8',
                    'format' => 'A4',
                    'default_font' => 'dejavusans',
                ]);

                $mpdf->autoScriptToLang = true;
                $mpdf->autoLangToFont = true;
                mpdf_write_html_chunked($mpdf, $htmlContent);

                auth()->user()->increaseFeatureUsage(
                    'multilingual_cv_downloads'
                );

                return $mpdf->Output(
                    'candidate_cv_' .
                    $candidate->id .
                    '.pdf',
                    'D'
                );
            }

            return view($view, $data);
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL PDF
        |--------------------------------------------------------------------------
        */

        if (
            $request->action_type == 'download'
        ) {

            auth()->user()->increaseFeatureUsage(
                'multilingual_cv_downloads'
            );

            $pdf = download_resume_pdf(
                $view,
                $data,
                'candidate_cv_'.$candidate->id.'.pdf'
            );

            return $pdf;
        }

        return view($view, $data);

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}
    public function view_cv(Request $request, $id, CandidateResumeViewService $resumeViewService)
{
    try {

        $candidate = Candidate::query()
            ->where('id', $id)
            ->firstOrFail();

        if (
            $request->action_type == 'download'
            &&
            !auth()->user()->canUseFeature(
                'multilingual_cv_downloads'
            )
        ) {

            flashError(
                'Your CV download limit exceeded'
            );

            return back();
        }

        $resumeViewService->loadCandidateForResume($candidate);
        $view = $resumeViewService->resolveView($request->format ?: 'bilangual_format');
        $data = $resumeViewService->buildViewData($candidate, $request);

        if ($request->action_type == 'download') {
            $htmlContent = view($view, $data)->render();

            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'default_font' => 'dejavusans',
            ]);

            $mpdf->autoScriptToLang = true;
            $mpdf->autoLangToFont = true;
            mpdf_write_html_chunked($mpdf, $htmlContent);

            auth()->user()->increaseFeatureUsage(
                'multilingual_cv_downloads'
            );

            return $mpdf->Output(
                'candidate_cv_'.$candidate->id.'.pdf',
                'D'
            );
        }

        return view($view, $data);

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}
    public function whatsappCandidate()
    {
        $candidates = Candidate::with('user')->get();
        $titles = Candidate::whereNotNull('title')
            ->with('user')
            ->get();


        return view('backend.candidate.whatsapp-candidate', compact('candidates', 'titles'));
    }
  public function index(Request $request)
{
    try {

        abort_if(! userCan('candidate.view'), 403);

        /*
        |--------------------------------------------------------------------------
        | FEATURE LIMIT CHECK
        |--------------------------------------------------------------------------
        */

        if (
            !auth()->user()->canUseFeature(
                'candidate_search_limit'
            )
        ) {

            flashError(
                'Your candidate search limit has been exceeded'
            );

            return back();
        }

        /*
        |--------------------------------------------------------------------------
        | CANDIDATE QUERY
        |--------------------------------------------------------------------------
        */

        if (auth()->user()->hasRole('superadmin')) {

            $query = Candidate::withCount(
                'appliedJobs'
            )->with(
                'user',
                'jobRole'
            );

        } else {

            $adminId = auth()->user()->id;

            $query = Candidate::withCount(
                'appliedJobs'
            )->with(
                'user',
                'jobRole',
                'agent'
            )->where(
                'admin_id',
                $adminId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | VERIFIED STATUS FILTER
        |--------------------------------------------------------------------------
        */

        if (
            $request->has('ev_status')
            &&
            $request->ev_status != null
        ) {

            if ($request->ev_status == 'true') {

                $query->whereHas(
                    'user',
                    function ($q) {

                        $q->whereNotNull(
                            'email_verified_at'
                        );
                    }
                );

            } else {

                $query->whereHas(
                    'user',
                    function ($q) {

                        $q->whereNull(
                            'email_verified_at'
                        );
                    }
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | KEYWORD SEARCH
        |--------------------------------------------------------------------------
        */

        if (
            $request->keyword
            &&
            $request->keyword != null
        ) {

            $query->whereHas(
                'user',
                function ($q) use ($request) {

                    $q->where(
                        'name',
                        'LIKE',
                        "%{$request->keyword}%"
                    )

                    ->orWhere(
                        'email',
                        'LIKE',
                        "%{$request->keyword}%"
                    );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | SORTING
        |--------------------------------------------------------------------------
        */

        if (
            $request->sort_by == 'latest'
            ||
            $request->sort_by == null
        ) {

            $query->latest();

        } else {

            $query->oldest();
        }

        /*
        |--------------------------------------------------------------------------
        | PAGINATION
        |--------------------------------------------------------------------------
        */

        $candidates = $query
            ->paginate(10)
            ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | INCREASE FEATURE USAGE
        |--------------------------------------------------------------------------
        */

        auth()->user()->increaseFeatureUsage(
            'candidate_search_limit'
        );

        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'backend.candidate.index',
            compact('candidates')
        );

    } catch (\Exception $e) {

        flashError(
            'An error occurred: '
            . $e->getMessage()
        );

        return back();
    }
}
    public function sendMessages(Request $request)
    {
        $request->validate([
            'filter' => 'required',
            'message' => 'required|string',
            'candidate_ids' => 'required_if:filter,all|array',
            'title' => 'required_if:filter,title|string',
        ]);

        try {
            $twilioSid = env('TWILIO_SID');
            $twilioToken = env('TWILIO_AUTH_TOKEN');
            $twilioWhatsappNumber = env('TWILIO_WHATSAPP_NUMBER');
            $client = new Client($twilioSid, $twilioToken);

            $message = $request->message;

            if ($request->filter === 'all') {
                // Send messages to selected candidates
                $candidates = Candidate::whereIn('id', $request->candidate_ids)->with('user')->get();
            } else {
                // Send messages to candidates by title
                $candidates = Candidate::where('title', $request->title)->with('user')->get();
            }

            foreach ($candidates as $candidate) {
                $recipientNumber = $candidate->user->whatsapp; // Ensure the phone field is in international format

                // Corrected line to use 'whatsapp:' prefix
                $client->messages->create(
                    'whatsapp:' . $recipientNumber, // Fix the 'whatsapp' format
                    [
                        'from' => 'whatsapp:' . $twilioWhatsappNumber, // Ensure the Twilio WhatsApp number is used with the 'whatsapp:' prefix
                        'body' => $message,
                    ]
                );
            }

            return response()->json(['status' => 'Messages sent successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
            abort_if(! userCan('candidate.create'), 403);

            $data['countries'] = Country::all();
            $data['job_roles'] = JobRole::all()->sortBy('name');
            $data['professions'] = Profession::all()->sortBy('name');
            $data['experiences'] = Experience::all();
            $data['educations'] = Education::all();
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['skills'] = Skill::all()->sortBy('name');
            $data['candidate_languages'] = CandidateLanguage::all(['id', 'name']);

            return view('backend.candidate.create', $data);
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
    public function userCreate($request)
    {
        $request->validate([
            'username' => 'unique:users,username',
            'email' => 'unique:users,email',

        ]);

        try {
            $password = $request->password ?? Str::random(8);

            $data = User::create([
                'role' => 'candidate',
                'name' => $request->name,
                'username' => Str::slug('K' . $request->name . '122'),
                'email' => $request->email,
                'email_verified_at' => now(),
                'password' => bcrypt($password),
                'remember_token' => Str::random(10),
            ]);

            return [$password, $data];
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }



    public function candidateCreate($request, $data)
    {
        try {
            $dateTime = Carbon::parse($request->birth_date);
            $date = $request['birth_date'] = $dateTime->format('Y-m-d H:i:s');

            // create candidate
            $name = $request->name ?? fake()->name();
            $candidate = Candidate::where('user_id', $data[1]->id)->first();
            if (auth()->user()->hasRole('superadmin')) {
                $adminId = null;
            } else {
                $adminId = auth()->user()->id;
            }
            // $candidate->update([
            $candidate->update([
                'role_id' => $request->role_id,
                'admin_id' => $adminId,
                'profession_id' => $request->profession_id,
                'experience_id' => $request->experience,
                'education_id' => $request->education,
                'gender' => $request->gender,
                'website' => $request->website,
                'bio' => $request->bio,
                'marital_status' => $request->marital_status,
                'birth_date' => $date,
                'country_id' => $request->country_id,
                'expected_salary' => $request->expected_salary,
                'expected_location' => $request->expected_location,
                'industry_type' => $request->industry_type,




            ]);
            // Location
            updateMap($candidate);
            // cv upload
            if ($request->cv) {
                $pdfPath = '/file/candidates/';
                $pdf = pdfUpload($request->cv, $pdfPath);
                $candidate->update(['cv' => $pdf]);
            }

            // image upload
            if ($request->image) {
                // $path = 'images/candidates';
                $path = 'uploads/images/candidates';

                $image = uploadImage($request->image, $path, [164, 164]);
            } else {
                $setDimension = [164, 164];
                $path = 'uploads/images/candidates';

                $image = createAvatar($name, $path, $setDimension);
                // $image = createAvatar($data['name'], 'uploads/images/candidate');
            }

            $candidate->update(['photo' => $image]);

            // skills insert
            $skills = $request->skills;

            if ($skills) {
                $skillsArray = [];

                foreach ($skills as $skill) {
                    // $skill_exists = Skill::where('id', $skill)->orWhere('name', $skill)->first();
                    $skill_exists = Skill::where('id', $skill)->first();


                    if (! $skill_exists) {
                        $select_tag = Skill::create(['name' => $skill]);
                        array_push($skillsArray, $select_tag->id);
                    } else {
                        array_push($skillsArray, $skill);
                    }
                }

                $candidate->skills()->attach($skillsArray);
            }


            // languages insert
            $candidate->languages()->attach($request->languages);
            if ($request->has('dynamic_inputs')) {
                foreach ($request->dynamic_inputs as $attribute_name => $attribute_value) {
                    CandidateAttribute::create([
                        'candidate_id' => $candidate->id,
                        'attribute_name' => $attribute_name,
                        'attribute_value' => $attribute_value,
                    ]);
                }
            }

            return $candidate;
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(CandidateRequest $request)
    {
        // dd('qq');

        abort_if(! userCan('candidate.create'), 403);
        $location = session()->get('location');
        if (! $location) {
            $request->validate(['location' => 'required']);
        }

        // try {
        if ($request->image) {
            $request->validate(['image' => 'image|mimes:jpeg,png,jpg,gif']);
        }
        if ($request->cv) {
            $request->validate(['cv' => 'mimetypes:application/pdf']);
        }

        $data = $this->userCreate($request);
        $candidate = $this->candidateCreate($request, $data);
        $user = $data[1];
        $password = $data[0];



        // if mail is configured
        if (checkMailConfig()) {
            $candidate_account_auto_activation_enabled = Setting::where('candidate_account_auto_activation', 1)->count();

            // if candidate activation enabled, send account created mail
            // else, send will be activated mail.
            if ($candidate_account_auto_activation_enabled) {
                Notification::route('mail', $user->email)->notify(new CandidateCreateNotification($user, $password));
            } else {
                Notification::route('mail', $user->email)->notify(new CandidateCreateApprovalPendingNotification($user, $password));
            }
        }

        flashSuccess(__('candidate_created_successfully'));

        return redirect()->route('candidate.index');
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($candidate)
    {
        try {
            abort_if(! userCan('candidate.view'), 403);

            $candidate = Candidate::with([
                'user.socialInfo',
                'user.contactInfo',
                'skills',
                'languages',
                'profession',
                'experience',
                'education',
            ])->findOrFail($candidate);

            $user = $candidate->user;
            if (! $user) {
                flashError('This candidate has no linked user account (orphaned profile). user_id='.var_export($candidate->getAttributes()['user_id'] ?? null, true));

                return redirect()->route('candidate.index');
            }

            $appliedJobs = $candidate->appliedJobs()->with('company.user', 'category', 'role')->get();
            $bookmarkJobs = $candidate->bookmarkJobs()->with('company.user', 'category', 'role')->get();

            // Fetch Job Requirements
            $jobsRequirments = JobRequirement::where('candidate_id', $candidate->id)->first();

            // Initialize empty collections
            $Jobs = collect();
            $Industries = collect();
            $country = null;
            $state = null;
            $city = null;

            if ($jobsRequirments) {
                if ($jobsRequirments->jobs) {
                    $jobIds = cw_json_array($jobsRequirments->jobs);
                    $Jobs = Profession::whereIn('id', $jobIds)->get();
                }

                if ($jobsRequirments->industries) {
                    $industryIds = cw_json_array($jobsRequirments->industries);
                    $Industries = IndustryType::whereIn('id', $industryIds)->get();
                }

                $country = SearchCountry::where('id', $jobsRequirments->search_country_id)->first();
                $city = City::where('id', $jobsRequirments->city_id)->first();
                $state = State::where('id', $jobsRequirments->state_id)->first();
            }

            $candidateDocument = CandidateDocument::where('candidate_id', $candidate->id)->first();

            return view('backend.candidate.show', compact(
                'candidate', 'user', 'appliedJobs', 'bookmarkJobs',
                'Jobs', 'Industries', 'candidateDocument', 'country', 'state', 'city'
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            flashError('Candidate or linked user not found.');

            return redirect()->route('candidate.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return redirect()->route('candidate.index');
        }
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Candidate $candidate)
    {
        try {
            abort_if(! userCan('candidate.update'), 403);

            $candidate->loadMissing('user.contactInfo');
            $user = $candidate->user;
            if (! $user) {
                flashError('This candidate has no linked user account (orphaned profile).');

                return redirect()->route('candidate.index');
            }

            $contactInfo = ContactInfo::where('user_id', $user->id)->first();
            $job_roles = JobRole::all()->sortBy('name');
            $professions = Profession::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $skills = Skill::all()->sortBy('name');
            $candidate_languages = CandidateLanguage::all(['id', 'name']);
            $candidate->load('skills', 'languages:id,name');
            $lat = $candidate->lat ? floatval($candidate->lat) : floatval(setting('default_lat'));
            $long = $candidate->long ? floatval($candidate->long) : floatval(setting('default_long'));
            $attachments = Attachment::where('candidate_id', $candidate->id)->first();
            $dynamicInputs = CandidateAttribute::where('candidate_id', $candidate->id)->where('is_active', '1')->get();
            $bilangualLanguaes = Language::all();
            $countries = Country::all();
            $industry_types = IndustryType::all()->sortBy('name');


            return view('backend.candidate.edit', compact('contactInfo', 'candidate', 'user', 'job_roles', 'professions', 'experiences', 'educations', 'skills', 'candidate_languages', 'lat', 'long', 'attachments', 'dynamicInputs', 'bilangualLanguaes', 'countries', 'industry_types'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return redirect()->route('candidate.index');
        }
    }



    public function update(Request $request, Candidate $candidate)
    {
        try {
            abort_if(! userCan('candidate.update'), 403);

            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email,' . $candidate->user_id,
                'dynamic_inputs.*.is_required' => 'required|boolean', // Ensure this is present and a boolean
            ]);


            // user update
            $user = User::FindOrFail($candidate->user_id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            // candidate update
            $candidate->update([
                'role_id' => $request->role_id,
                'profession_id' => $request->profession,
                'experience_id' => $request->experience,
                'education_id' => $request->education,
                'gender' => $request->gender,
                'website' => $request->website,
                'bio' => $request->bio,
                'marital_status' => $request->marital_status,
                'birth_date' => date('Y-m-d', strtotime($request->birth_date)),
                'passport_number' => $request->passport_number,
                'passport_issue_date' => $request->passport_issue_date ? Carbon::parse($request->passport_issue_date)->format('Y-m-d') : null,
                'passport_expiry_date' => $request->passport_expiry_date ? Carbon::parse($request->passport_expiry_date)->format('Y-m-d') : null,
                'place_of_issue' => $request->place_of_issue,
                'cnic_number' => $request->cnic_number,
                'language_code' => $request->language_code,
                'country_id' => $request->country_id,
                'expected_salary' => $request->expected_salary,
                'industry_type' => $request->industry_type,
            ]);

            // password change
            if ($request->password) {
                $request->validate([
                    'password' => 'required',
                ]);
                $user->update([
                    'password' => bcrypt($request->password),
                ]);
            }

            // image upload
            if ($request->image) {
                $request->validate([
                    'image' => 'image|mimes:jpeg,png,jpg',
                ]);

                deleteImage($candidate->photo);

                $path = 'uploads/images/candidates';
                $image = uploadImage($request->image, $path, [164, 164]);

                $candidate->update([
                    'photo' => $image,
                ]);
            }
            // cv
            if ($request->cv) {
                $request->validate([
                    'cv' => 'mimetypes:application/pdf',
                ]);
                $pdfPath = '/file/candidates/';
                $pdf = pdfUpload($request->cv, $pdfPath);

                $candidate->update([
                    'cv' => $pdf,
                ]);
            }

            // Location
            updateMap($candidate);

            // skills
            $skills = $request->skills ?? [];
            // dd($skills);

            if ($skills) {
                $skillsArray = [];
                if ($skills != '' && $skills != Null) {
                    foreach ($skills as $skill) {
                        $skill_exists = SkillTranslation::where('skill_id', $skill)->orWhere('name', $skill)->first();

                        if (! $skill_exists) {
                            $select_tag = Skill::create(['name' => $skill]);

                            $languages = loadLanguage();
                            foreach ($languages as $language) {
                                $select_tag->translateOrNew($language->code)->name = $skill;
                            }
                            $select_tag->save();

                            array_push($skillsArray, $select_tag->id);
                        } else {
                            array_push($skillsArray, $skill_exists->id);
                        }
                    }
                }
                $candidate->skills()->sync($request->skills);
            }

            // languages
            $candidate->languages()->sync($request->languages);

            if ($request->password) {
                // make Notification
                $data[] = $user;
                $data[] = $request->password;
                $data[] = 'Candidate';

                checkMailConfig() ? Notification::route('mail', $user->email)->notify(new UpdateCompanyPassNotification($data)) : '';
            }

            // for attachments
            $attachment = Attachment::where('candidate_id', $candidate->id)->firstOrNew();

            // Handle passport image upload
            if ($request->hasFile('passport_image')) {
                // Delete old passport image if it exists
                if ($attachment->passport_image) {
                    Storage::delete('public/candidates/' . $attachment->passport_image);
                }


                // Store new passport image
                $passportImagePath = $request->file('passport_image')->store('public/candidates');
                $attachment->passport_image = basename($passportImagePath);
            }

            // Handle license image upload
            if ($request->hasFile('license_image')) {
                // Delete old license image if it exists
                if ($attachment->license_image) {
                    Storage::delete('public/candidates/' . $attachment->license_image);
                }

                // Store new license image
                $licenseImagePath = $request->file('license_image')->store('public/candidates');
                $attachment->license_image = basename($licenseImagePath);
            }

            // Set the candidate_id on the attachment if it's a new record
            $attachment->candidate_id = $candidate->id;

            // Save the attachment (insert or update)
            $attachment->save();
            // dd($request->input('dynamic_inputs'));
            if ($request->input('dynamic_inputs') != '' && $request->input('dynamic_inputs') != Null) {

                foreach ($request->input('dynamic_inputs') as $inputData) {

                    $dynamicInput = CandidateAttribute::find($inputData['id']);

                    if ($dynamicInput) {
                        $dynamicInput->attribute_value = $inputData['value']; // Update the value
                        $dynamicInput->save(); // Save the changes
                    }
                }
            }
            flashSuccess(__('candidate_updated_successfully'));



            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Candidate $candidate)
    {
        try {
            abort_if(! userCan('candidate.delete'), 403);

            $user = User::FindOrFail($candidate->user_id);
            CandidateCvView::query()
                ->where('candidate_id', $candidate->id)
                ->delete();
            $user->delete();

            if (file_exists($candidate->cv)) {
                unlink($candidate->cv);
            }

            if (file_exists($candidate->photo)) {
                if ($candidate->photo != 'backend/image/default.png') {
                    unlink($candidate->photo);
                }
            }
            $candidate->delete();

            flashSuccess(__('candidate_deleted_successfully'));

            return redirect()->route('candidate.index');
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Change candidate status
     *
     * @return \Illuminate\Http\Response
     */
    public function statusChange(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|integer',
                'status' => 'required|in:0,1',
            ]);

            $user = User::findOrFail((int) $request->id);
            $user->status = (int) $request->status;
            $user->save();

            if ((int) $request->status === 1) {
                return responseSuccess(__('candidate_activated_successfully'));
            }

            return responseSuccess(__('candidate_deactivated_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first() ?: 'Invalid status change request.',
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
    public function is_candidate_featured(Request $request)
    {
        try {
            $user = Candidate::findOrFail($request->id);
            $user->is_candidate_featured = $request->is_candidate_featured;
            $user->save();

            if ($request->is_candidate_featured == 1) {
                return responseSuccess(__('Candidate Featured Successfully'));
            } else {
                return responseSuccess(__('Candidate Non-featured Successfully'));
            }
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Change candidate verification status
     *
     * @return \Illuminate\Http\Response
     */
    public function verificationChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            if ($request->status) {
                $user->update(['email_verified_at' => now()]);
                $message = __('email_verified_successfully');
            } else {
                $user->update(['email_verified_at' => null]);
                $message = __('email_unverified_successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function candidateExport($type)
    {
        $name = time() . '_candidates.' . $type;
        try {
            return Excel::download(new CandidateExport, $name);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
}
