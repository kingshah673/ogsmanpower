<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Traits\CandidateAble;
use App\Http\Traits\CandidateSkillAble;
use App\Http\Traits\HasCandidateResume;
use App\Models\AppliedJob;
use App\Models\Attachment;
use App\Models\BilangualResumeSubscription;
use App\Models\Candidate;
use App\Models\CandidateLanguage;
use App\Models\CandidateResume;
use App\Models\Company;
use App\Models\agency;
use App\Models\ContactInfo;
use App\Models\Education;
use App\Models\Experience;
use Modules\Location\Entities\Country;
use App\Models\JobRole;
use App\Models\JobTitle;
use App\Models\CandidateAttribute;
use App\Models\CandidateDocument;
use App\Models\CandidatePlan;
use App\Models\CandidateSubscription;
use App\Models\City;
use App\Models\IndustryType;
use App\Models\JobRequirement;
use App\Models\LanguageData;
use App\Models\ManualPayment;
use App\Models\Profession;
use App\Models\SearchCountry;
use App\Models\Skill;
use App\Models\State;
use Modules\Language\Entities\Language;
use App\Services\CandidateResumeViewService;
use App\Services\Website\Candidate\CandidateSettingUpdateService;
use App\Services\Website\Candidate\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Mpdf\Mpdf;
use PDF;

class CandidateController extends Controller

{
    use CandidateAble, CandidateSkillAble, HasCandidateResume;

    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'settingUpdate',
        ]);
    }

    /**
     * Candidate dashboard
     *
     * @return \Illuminate\Http\Response
     */

    public function additionlSetting()
    {
        try {
            $candidate = auth()->user()->candidate;

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
                $candidate = auth()->user()->fresh()->candidate;
            }
            $nameParts = explode(' ', auth()->user()->name, 2); // Splits the name into two parts
            $firstName = $nameParts[0] ?? ''; // First name
            $lastName = $nameParts[1] ?? ''; // Last name (if available)


            // for contact
            $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
            $contact = [];
            if ($contactInfo) {
                $contact = $contactInfo;
            } else {
                $contact = '';
            }

            // for social link
            $socials = auth()->user()->socialInfo;
            $candidate_id = auth()->user()->candidate->id;
            // for candidate resume/cv
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $industries = IndustryType::all();
            $educations = Education::all();
            $attachments = Attachment::where('candidate_id', $candidate_id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $bilangualLanguaes = Language::all();
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts:id,candidate_id,job_role_id');
            $countries = Country::all();
            $jobtitles = JobTitle::all();
            $dynamicInputs = CandidateAttribute::where('candidate_id', $candidate->id)->where('is_active', '1')->get();

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
                // dd($state);
            }
            return view('frontend.pages.candidate.additionl-setting', [
                'candidate' => $candidate->load('skills', 'languages'),
                'contact' => $contact,
                'industries' => $industries,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                "attachments" => $attachments,
                "bilangualLanguaes" => $bilangualLanguaes,
                "countries" => $countries,
                "jobtitles" => $jobtitles,
                "dynamicInputs" => $dynamicInputs,
                "lastName" => $lastName,
                "firstName" => $firstName,

            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    public function plan()
    {
        $plan = candidateFeaturedPlan();
        $candidate_id = auth()->user()->candidate->id;
        $plan_Subscription = \Illuminate\Support\Facades\Schema::hasTable('candidate_subscriptions')
            ? CandidateSubscription::with('candidate')->where('candidate_id', $candidate_id)->first()
            : null;

        return view('frontend.pages.candidate.plan', compact('plan', 'plan_Subscription'));
    }

    public function candidateDocument()
    {
        $candidate_id = auth()->user()->candidate->id;
        // for candidate resume/cv

        $attachments = CandidateDocument::where('candidate_id', $candidate_id)->first();
        return view('frontend.pages.candidate.document', ['attachments' => $attachments]);
    }

    public function viewResume(Request $request, CandidateResumeViewService $resumeViewService)
    {
        try {
            $candidate = auth()->user()->candidate;

            $languageCode = normalizeBilingualLanguageCode(
                $request->input('language_code'),
                $request->input('language_code_custom')
            );
            $request->merge(['language_code' => $languageCode]);

            if ($request->format == 'bilangual_format') {
                $bilangualPlan = BilangualResumeSubscription::where('candidate_id', $candidate->id)
                    ->where('language_code', $request->language_code)
                    ->first();

                if ($bilangualPlan && $bilangualPlan->status === 'pending') {
                    return redirect()
                        ->route('candidate.view.cv')
                        ->with('warning', 'Your bilingual CV request is pending approval.');
                }

                if ($bilangualPlan && $bilangualPlan->status === 'rejected') {
                    return redirect()
                        ->route('candidate.view.cv')
                        ->with('error', 'Your bilingual CV request was rejected. Please contact support.');
                }

                $aiAvailable = ! empty(config('services.openai.key'));

                if (! $bilangualPlan && ! $aiAvailable) {
                    $plan = candidateFeaturedPlan();
                    $mid_token = null;
                    $manual_payments = ManualPayment::whereStatus(1)->get();
                    $language_code = $request->language_code;
                    $language = $request->format;

                    return view('frontend.pages.candidate.bilangual-plan', compact(
                        'manual_payments', 'mid_token', 'plan', 'language_code', 'language'
                    ));
                }
            }

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
                $candidate = auth()->user()->candidate;
            }

            $candidate->update([
                'resume_format' => $request->format,
                'language_code' => $request->language_code,
            ]);

            $resumeViewService->loadCandidateForResume($candidate);
            $view = $resumeViewService->resolveView($request->format);
            $data = $resumeViewService->buildViewData($candidate, $request);

            if ($request->format == 'bilangual_format') {
                if ($request->action_type == 'download') {
                    @ini_set('memory_limit', '512M');
                    $htmlContent = view($view, $data)->render();

                    return mpdf_download_bilingual_cv(
                        $htmlContent,
                        'candidate_cv_'.$candidate->id.'.pdf',
                        $request->language_code
                    );
                }

                return view($view, $data);
            }

            if ($request->action_type == 'download') {
                return download_resume_pdf($view, $data, 'candidate_cv_'.$candidate->id.'.pdf');
            }

            return view($view, $data);
        } catch (\Throwable $e) {
            \Log::error('viewResume failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);

            if ($request->format === 'bilangual_format') {
                $message = config('app.debug')
                    ? $e->getMessage()
                    : 'Could not generate bilingual CV. Please try again or contact support.';

                return response(
                    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>CV Error</title></head>'
                    .'<body style="font-family:sans-serif;padding:2rem"><h1>CV generation failed</h1>'
                    .'<p>'.e($message).'</p></body></html>',
                    500
                )->header('Content-Type', 'text/html; charset=UTF-8');
            }

            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
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
    | CONTRACT NOT FOUND
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

        'Contract accepted successfully. Hiring process started.'

    );
}
    public function dashboard()
    {
        try {
            $data = (new DashboardService)->execute();

            return view('frontend.pages.candidate.dashboard', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate notification page
     *
     * @return \Illuminate\Http\Response
     */
    public function allNotification()
    {
        try {
            $notifications = auth()
                ->user()
                ->notifications()
                ->paginate(12);

            return view('frontend.pages.candidate.all-notification', compact('notifications'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate job alert page
     *
     * @return \Illuminate\Http\Response
     */
    public function jobAlerts()
    {
        try {
            $notifications = auth()
                ->user()
                ->notifications()
                ->where('type', 'App\Notifications\Website\Candidate\RelatedJobNotification')
                ->paginate(12);

            return view('frontend.pages.candidate.job-alerts', compact('notifications'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate applied job page
     *
     * @return \Illuminate\Http\Renderable
     */
    public function appliedjobs(Request $request)
    {
        try {
            $candidate = Candidate::where('user_id', auth()->id())->first();
            if (empty($candidate)) {
                $candidate = new Candidate;
                $candidate->user_id = auth()->id();
                $candidate->save();
            }

            $allowedStatuses = ['pending', 'shortlisted', 'interview', 'selected', 'rejected'];
            $statusFilter = $request->query('status', 'all');
            if ($statusFilter !== 'all' && ! in_array($statusFilter, $allowedStatuses, true)) {
                $statusFilter = 'all';
            }

            $base = AppliedJob::query()->where('candidate_id', $candidate->id);

            $statusCounts = [
                'all' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'shortlisted' => (clone $base)->where('status', 'shortlisted')->count(),
                'interview' => (clone $base)->where('status', 'interview')->count(),
                'selected' => (clone $base)->where('status', 'selected')->count(),
                'rejected' => (clone $base)->where('status', 'rejected')->count(),
            ];

            $appliedJobs = AppliedJob::query()
                ->with([
                    'job.company.user',
                    'job.job_type',
                    'resume',
                    'applicationGroup:id,name',
                ])
                ->where('candidate_id', $candidate->id)
                ->when($statusFilter !== 'all', fn ($q) => $q->where('status', $statusFilter))
                ->latest('id')
                ->paginate(8)
                ->withQueryString();

            return view('frontend.pages.candidate.applied-jobs', compact('appliedJobs', 'statusCounts'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate bookmark page
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarks(Request $request)
    {
        try {
            $candidate = Candidate::where('user_id', auth()->id())->first();
            if (empty($candidate)) {
                $candidate = new Candidate;
                $candidate->user_id = auth()->id();
                $candidate->save();
            }

            $jobs = $candidate
                ->bookmarkJobs()
                ->withCount([
                    'appliedJobs as applied' => function ($q) use ($candidate) {
                        $q->where('candidate_id', $candidate->id);
                    },
                ])
                ->paginate(12);

            if (auth('user')->check() && authUser()->role == 'candidate') {
                $resumes = currentCandidate()->resumes;
            } else {
                $resumes = [];
            }

            return view('frontend.pages.candidate.bookmark', compact('jobs', 'resumes'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate bookmark company toggle
     *
     * @return \Illuminate\Http\Response
     */
    public function bookmarkCompany(Company $company)
    {
        try {
            $company->bookmarkCandidateCompany()->toggle(currentCandidate());

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function bookmarkAgency(Agency $agency)
    {
        try {
            $agency->bookmarkCandidateAgency()->toggle(currentCandidate());

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }


    public function getStates(Request $request)
    {
        $states = State::where('country_id', $request->country_id)->get(['id', 'name']);
        return response()->json(['states' => $states]);
    }

    public function getCities(Request $request)
    {
        $cities = City::where('state_id', $request->state_id)->get(['id', 'name']);
        return response()->json(['cities' => $cities]);
    }

    public function getStatesByName(Request $request)
    {
        $countryName = trim((string) $request->country_name);
        if ($countryName === '') {
            return response()->json(['states' => []]);
        }

        $country = SearchCountry::query()
            ->where(function ($q) use ($countryName) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($countryName)])
                    ->orWhere('name', 'like', '%'.$countryName.'%');
            })
            ->first();

        if (! $country) {
            return response()->json(['states' => []]);
        }

        $states = State::where('country_id', $country->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['states' => $states]);
    }

    public function getCitiesByName(Request $request)
    {
        $stateName = trim((string) $request->state_name);
        $countryName = trim((string) $request->country_name);

        if ($stateName === '') {
            return response()->json(['cities' => []]);
        }

        $stateQuery = State::query()
            ->where(function ($q) use ($stateName) {
                $q->whereRaw('LOWER(name) = ?', [mb_strtolower($stateName)])
                    ->orWhere('name', 'like', '%'.$stateName.'%');
            });

        if ($countryName !== '') {
            $country = SearchCountry::query()
                ->where(function ($q) use ($countryName) {
                    $q->whereRaw('LOWER(name) = ?', [mb_strtolower($countryName)])
                        ->orWhere('name', 'like', '%'.$countryName.'%');
                })
                ->first();

            if ($country) {
                $stateQuery->where('country_id', $country->id);
            }
        }

        $state = $stateQuery->orderBy('name')->first();

        if (! $state) {
            return response()->json(['cities' => []]);
        }

        $cities = City::where('state_id', $state->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['cities' => $cities]);
    }

    /**
     * Candidate settings page
     *
     * @return \Illuminate\Http\Response
     */
    public function setting()
    {
        try {
            $candidate = auth()->user()->candidate;

            if (empty($candidate)) {
                Candidate::create(['user_id' => auth()->id()]);
            }
            $nameParts = explode(' ', auth()->user()->name, 2); // Splits the name into two parts
            $firstName = $nameParts[0] ?? ''; // First name
            $lastName = $nameParts[1] ?? ''; // Last name (if available)


            // for contact
            $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
            $contact = [];
            if ($contactInfo) {
                $contact = $contactInfo;
            } else {
                $contact = '';
            }

            // for social link
            $socials = auth()->user()->socialInfo;
            $candidate_id = auth()->user()->candidate->id;
            // for candidate resume/cv
            $resumes = $candidate->resumes;
            $job_roles = JobRole::all()->sortBy('name');
            $experiences = Experience::all();
            $educations = Education::all();
            $industries = IndustryType::all();
            $attachments = Attachment::where('candidate_id', $candidate_id)->first();
            $professions = Profession::all()->sortBy('name');
            $skills = Skill::all()->sortBy('name');
            $languages = CandidateLanguage::all(['id', 'name']);
            $bilangualLanguaes = Language::all();
            $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts.jobRole', 'experience', 'education', 'profession');
            $jobtitles = JobTitle::all();
            $searchCountries = SearchCountry::all();
            $dynamicFieldsBySection = \App\Services\DynamicFieldService::seekerFieldsGroupedBySection($candidate);
            $jobRequirement = JobRequirement::where('candidate_id', $candidate->id)
                ->with(['searchcountry', 'state', 'city'])
                ->first();
            $phoneCountryIso = default_phone_country_iso();

            return view('frontend.pages.candidate.setting', [
                'candidate' => $candidate->load('skills', 'languages'),
                'contact' => $contact,
                'industries' => $industries,
                'socials' => $socials,
                'job_roles' => $job_roles,
                'experiences' => $experiences,
                'educations' => $educations,
                'professions' => $professions,
                'resumes' => $resumes,
                'skills' => $skills,
                'candidate_languages' => $languages,
                "attachments" => $attachments,
                "bilangualLanguaes" => $bilangualLanguaes,
                "jobtitles" => $jobtitles,
                "dynamicFieldsBySection" => $dynamicFieldsBySection,
                "lastName" => $lastName,
                "firstName" => $firstName,
                "searchCountries" => $searchCountries,
                'jobRequirement' => $jobRequirement,
                'phoneCountryIso' => $phoneCountryIso,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }
    public function profileView()
    {
        $candidate = auth()->user()->candidate;
        $candidate->load('skills', 'languages', 'experiences', 'educations', 'jobRoleAlerts', 'profession', 'experience', 'education');

        $nameParts = explode(' ', auth()->user()->name, 2);
        $attachments = Attachment::where('candidate_id', $candidate->id)->first();
        $socials = auth()->user()->socialInfo;
        $contact = ContactInfo::where('user_id', auth()->id())->first();
        $jobRequirement = JobRequirement::where('candidate_id', $candidate->id)->first();

        return view('frontend.pages.candidate.profile-view', [
            'candidate'    => $candidate,
            'firstName'    => $nameParts[0] ?? '',
            'lastName'     => $nameParts[1] ?? '',
            'attachments'  => $attachments,
            'socials'      => $socials,
            'contact'      => $contact,
            'jobRequirement' => $jobRequirement,
        ]);
    }

    public function candidateCV()
    {
        try {
            $candidate = auth()->user()->candidate;
            $subscription = BilangualResumeSubscription::where('candidate_id',$candidate->id)->get();
            return view('frontend.pages.candidate.cv', [
                'candidate' => $candidate->load('skills', 'languages'),
                'subscription'=> $subscription,
                'resumeLanguages' => bilingualResumeLanguages(),
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /**
     * Candidate setting update
     *
     * @return \Illuminate\Http\Response
     */
    public function settingUpdate(Request $request)
    {
        try {
            return (new CandidateSettingUpdateService)->update($request);
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    throw $e;
                }

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            flashError('An error occurred: ' . $e->getMessage());

            return back();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANT PROFILE PHOTO SAVE (AJAX)
    |--------------------------------------------------------------------------
    | Saves the profile photo immediately on upload (no full-form submit) and
    | returns the public URL so every avatar on the page can be swapped in
    | real time. The DB value is what every other page reads on next load.
    */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $candidate = auth()->user()->candidate;
        if (! $candidate) {
            return response()->json(['success' => false, 'message' => 'No profile found.'], 404);
        }

        try {
            deleteImage($candidate->getRawOriginal('photo'));

            $path  = 'uploads/images/candidates';
            $image = uploadImage($request->image, $path);

            $candidate->update(['photo' => $image]);

            $candidate->refresh();

            return response()->json([
                'success' => true,
                'url'     => asset($image),
                'message' => 'Profile photo updated.',
                'completionPercentage' => $candidate->calculateProfileCompletion(),
                'profileCompletionMissing' => array_map(static fn ($section) => [
                    'key' => $section['key'],
                    'label' => $section['label'],
                    'hint' => $section['hint'],
                    'anchor' => $section['anchor'],
                ], $candidate->profileCompletionMissing()),
            ]);
        } catch (\Exception $e) {
            \Log::error('Profile photo upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not save the photo. Please try a different image.',
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INSTANT ATTACHMENT SAVE / DELETE (AJAX)
    |--------------------------------------------------------------------------
    | Passport & License images behave exactly like the profile photo: the file
    | is saved the moment it's chosen and the preview swaps in real time, with no
    | full-form submit. Both fields live on the candidate's Attachment record.
    */
    public function updateAttachmentImage(Request $request)
    {
        $request->validate([
            'field' => 'required|in:passport_image,license_image',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,bmp,tiff|max:5120',
        ]);

        $candidate = auth()->user()->candidate;
        if (! $candidate) {
            return response()->json(['success' => false, 'message' => 'No profile found.'], 404);
        }

        try {
            $field      = $request->field;
            $attachment = Attachment::firstOrNew(['candidate_id' => $candidate->id]);

            // Remove the previous file for this field so we don't orphan it
            if ($attachment->{$field}) {
                deleteImage(public_path('storage/candidates/' . $attachment->{$field}));
            }

            // Write straight into public/storage/candidates (the served dir), just
            // like the profile photo. The storage:link symlink is unreliable across
            // environments, so we don't use the storage disk here.
            $relativePath = uploadImage($request->file('image'), 'storage/candidates');
            $fileName     = basename($relativePath);

            $attachment->candidate_id = $candidate->id;
            $attachment->{$field}     = $fileName;
            $attachment->save();

            return response()->json([
                'success' => true,
                'url'     => asset('storage/candidates/' . $fileName),
                'message' => ($field === 'passport_image' ? 'Passport' : 'License') . ' image updated.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Attachment upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Could not save the image. Please try a different file.',
            ], 500);
        }
    }

    public function deleteAttachmentImage(Request $request)
    {
        $request->validate([
            'field' => 'required|in:passport_image,license_image',
        ]);

        $candidate = auth()->user()->candidate;
        if (! $candidate) {
            return response()->json(['success' => false, 'message' => 'No profile found.'], 404);
        }

        $field      = $request->field;
        $attachment = Attachment::where('candidate_id', $candidate->id)->first();

        if ($attachment && $attachment->{$field}) {
            deleteImage(public_path('storage/candidates/' . $attachment->{$field}));
            $attachment->{$field} = null;
            $attachment->save();
        }

        return response()->json([
            'success' => true,
            'message' => ($field === 'passport_image' ? 'Passport' : 'License') . ' image removed.',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD QUICK EDIT
    |--------------------------------------------------------------------------
    */

    public function dashboardUpdate(Request $request)
    {
        $candidate = auth()->user()->candidate;

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'No profile found.'], 403);
        }

        $request->validate([
            'bio'    => 'nullable|string|max:2000',
            'title'  => 'nullable|string|max:255',
            'status' => 'nullable|in:available,not_available,available_in',
        ]);

        $candidate->update($request->only(['bio', 'title', 'status']));

        return response()->json([
            'success' => true,
            'bio'     => $candidate->bio,
            'title'   => $candidate->title,
            'status'  => $candidate->status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE DYNAMIC ATTRIBUTE VALUES (candidate fills in their form fields)
    |--------------------------------------------------------------------------
    */

    public function saveAttributeValues(Request $request)
    {
        $candidate = auth()->user()->candidate;

        if (!$candidate) {
            return response()->json(['success' => false, 'message' => 'No candidate profile found.'], 403);
        }

        $values = $request->input('values', []);

        foreach ($values as $attributeId => $value) {
            CandidateAttribute::where('id', $attributeId)
                ->where('candidate_id', $candidate->id)
                ->update(['attribute_value' => $value]);
        }

        return response()->json(['success' => true, 'message' => 'Saved successfully.']);
    }

    // custom code
    public function deleteAttachment($id)
    {
        $attachment = Attachment::findOrFail($id);

        // Delete the file from storage
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return redirect()->back()->with('success', 'Attachment deleted successfully.');
    }
    /**
     * Candidate username update
     *
     * @return \Illuminate\Http\Response
     */
    public function usernameUpdate(Request $request)
    {
        try {
            $request->session()->put('type', 'account');

            if ($request->type == 'candidate_username') {
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

    /**
     * AJAX lookup for candidate settings Select2 fields.
     */
    public function settingLookup(string $type, \Illuminate\Http\Request $request)
    {
        return app(\App\Services\AttributeLookupService::class)->search($type, $request);
    }
}
