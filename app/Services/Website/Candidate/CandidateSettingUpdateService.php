<?php

namespace App\Services\Website\Candidate;

use App\Mail\SendEmailUpdateVerification;
use App\Models\Candidate;
use App\Models\ContactInfo;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Profession;
use App\Models\ProfessionTranslation;
use App\Models\SearchCountry;
use App\Models\Setting;
use App\Models\Skill;
use App\Models\SkillTranslation;
use App\Models\User;
use App\Models\Attachment;
use App\Models\CandidateAttribute;
use App\Models\CandidateDocument;
use App\Models\JobRequirement;
use App\Services\DynamicFieldService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Language\Entities\Language;
use Illuminate\Support\Facades\Storage;

class CandidateSettingUpdateService
{
    /**
     * Candidate setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update($request)
    {
        $user = User::FindOrFail(auth()->id());
        $candidate = Candidate::where('user_id', $user->id)->first();
        $contactInfo = ContactInfo::where('user_id', auth()->id())->first();
        $request->session()->put('type', $request->type);

        if ($request->type == 'basic') {
            $this->candidateBasicInfoUpdate($request, $user, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'basic');
        }
        if ($request->type == 'jobRequirements') {
            $this->jobRequirments($request, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'jobRequirements');
        }
        if ($request->type == 'summary') {
            $this->candidateSummaryUpdate($request, $user, $candidate);

            return $this->respond($request, __('Summary Updated'), $candidate, 'summary');
        }

        if ($request->type == 'skill') {
            $this->candidateSkillUpdate($request, $candidate);

            return $this->respond($request, __('Skills Updated'), $candidate, 'skill');
        }

        if ($request->type == 'language') {
            $this->candidateLanguageUpdate($request, $candidate);

            return $this->respond($request, __('Languages Updated'), $candidate, 'language');
        }

        if ($request->type == 'profile') {
            $this->candidateProfileInfoUpdate($request, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'profile');
        }

        if ($request->type == 'social') {
            $this->socialUpdate($request);

            return $this->respond($request, __('profile_updated'), $candidate, 'social');
        }

        if ($request->type == 'contact') {
            $this->contactUpdate($request, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'contact');
        }

        if ($request->type == 'account') {
            $sent = $this->emailUpdate($request);
            $message = $sent ? __('Mail Verification Sent') : __('profile_updated');

            return $this->respond($request, $message, $candidate, 'account');
        }

        if ($request->type == 'attachments') {
            $this->attachmentUpdate($request);

            return $this->respond($request, __('Attachments updated successfully'), $candidate, 'attachments');
        }

        if ($request->type == 'alert') {
            $this->alertUpdate($request, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'alert');
        }

        if ($request->type == 'visibility') {
            $this->visibilityUpdate($request, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'visibility');
        }

        if ($request->type == 'password') {
            $this->passwordUpdate($request, $user, $candidate);

            return $this->respond($request, __('profile_updated'), $candidate, 'password');
        }

        if ($request->type == 'account-delete') {
            $this->accountDelete($user);

            return $this->respond($request, __('profile_updated'), $candidate, 'account-delete');
        }

        if ($request->type == 'documents') {
            $this->documentUpdate($request);

            return $this->respond($request, __('Document Updated Successfully'), $candidate, 'documents');
        }

        return $this->respond($request, __('profile_updated'), $candidate, $request->type);
    }

    /**
     * Candidate basic setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @param  \App\Models\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    /**
     * Normalise a user-supplied date (dd-mm-yyyy from the picker, or other
     * common formats) to MySQL's Y-m-d. Returns null for empty/unparseable
     * values so nullable date columns stay null instead of erroring.
     */
    private function toDbDate($value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d', 'Y-m-d H:i:s', 'm/d/Y'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $value);
                if ($d !== false) {
                    return $d->format('Y-m-d');
                }
            } catch (\Exception $e) {
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function candidateBasicInfoUpdate($request, $user, $candidate)
    {

        $request->validate([
            // 'name' => 'required',
            'birth_date' => 'nullable|date',
            'education' => 'required',
            'experience' => 'required',
            'gender' => 'required',
            'marital_status' => 'required',
            'profession' => 'required',
            'status' => 'required',
        ]);

        $nameParts = [$request->first_name, $request->last_name];

        $name = implode(' ', $nameParts); // Joins the names with a space

        $user->update(['name' => $name]);
        // Experience
        $experience_request = $request->experience;
        $experience = Experience::where('id', $experience_request)->first();

        if (! $experience) {
            $experience = Experience::create(['name' => $experience_request]);
        }

        // Education
        $education_request = $request->education;
        $education = Education::where('id', $education_request)->first();

        if (! $education) {
            $education = Education::create(['name' => $education_request]);
        }

        $date = null;
        if ($request->filled('birth_date')) {
            $dateTime = Carbon::parse($request->birth_date);
            $date = $dateTime->format('Y-m-d H:i:s');
        }

        if ($request->custom_title !== null && $request->custom_title !== '') {
            $title = $request->custom_title;
        } else {
            $title = $request->title;
        }
        if ($request->status == 'available_in') {
            $request->validate([
                'available_in' => 'required',
            ]);
        }

        // Profession
        $profession_request = $request->profession;
        $profession = ProfessionTranslation::where('profession_id', $profession_request)->orWhere('name', $profession_request)->first();

        if (! $profession) {
            $new_profession = Profession::create(['name' => $profession_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_profession->translateOrNew($language->code)->name = $profession_request;
            }
            $new_profession->save();

            $profession_id = $new_profession->id;
        } else {
            $profession_id = $profession->profession_id;
        }


        // Passport dates arrive from the date-picker as dd-mm-yyyy; the columns
        // are MySQL DATE (Y-m-d). Convert them or MySQL throws SQLSTATE[22007]
        // and the whole save (including the profile photo) is aborted.
        $passportIssueDate  = $this->toDbDate($request->passport_issue_date);
        $passportExpiryDate = $this->toDbDate($request->passport_expiry_date);

        $locationUpdate = [];
        if ($request->has('country')) {
            $locationUpdate['country'] = $request->country ?: null;
            if ($request->filled('country')) {
                $searchCountry = SearchCountry::where('name', $request->country)->first();
                if ($searchCountry) {
                    $locationUpdate['search_country_id'] = $searchCountry->id;
                }
            } else {
                $locationUpdate['search_country_id'] = null;
            }
        }
        if ($request->has('state')) {
            $locationUpdate['region'] = $request->state ?: null;
        }
        if ($request->has('district')) {
            $locationUpdate['district'] = $request->district ?: null;
        }

        $candidate->update(array_merge([
            'title' => $title,
            'experience_id' => $experience->id,
            'education_id' => $education->id,
            'website' => $request->website,
            'birth_date' => $date,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,

            'profession_id' => $profession_id,
            'status' => $request->status,
            'available_in' => $request->available_in ? Carbon::parse($request->available_in)->format('Y-m-d') : null,
            'passport_number' => $request->passport_number,
            'passport_issue_date' => $passportIssueDate,
            'passport_expiry_date' => $passportExpiryDate,
            'place_of_issue' => $request->place_of_issue,
            'cnic_number' => $request->cnic_number,
        ], $locationUpdate));

        // image
        if ($request->image) {
            $request->validate([
                'image' => 'image|mimes:jpeg,png,jpg',
            ]);

            deleteImage($candidate->photo);

            $path = 'uploads/images/candidates';
            // $image = uploadImage($request->image, $path, [164, 164]);
            $image = uploadImage($request->image, $path);


            $candidate->update([
                'photo' => $image,
            ]);
        }
        // cv
        if ($request->cv) {
            $request->validate([
                'cv' => 'mimetypes:application/pdf,jpeg,docs|max:5048',
            ]);
            $pdfPath = '/file/candidates/';
            $pdf = pdfUpload($request->cv, $pdfPath);

            $candidate->update([
                'cv' => $pdf,
            ]);
        }
        // Dynamic field values saved centrally in respond() via DynamicFieldService.

        // Map module writes geo fields from session; skip when the settings page
        // uses the country/state/city dropdowns (map_show off) — otherwise updateMap
        // overwrites the location we just saved with empty session placeholders.
        if (config('templatecookie.map_show')) {
            updateMap(auth()->user()->candidate);
        }

        return true;
    }


    public function jobRequirments($request, $candidate)
    {
        $jobs = cw_normalize_multi_input($request, 'jobs', 'jobs_payload');
        $industries = cw_normalize_multi_input($request, 'industries', 'industries_payload');
        $request->merge(['jobs' => $jobs, 'industries' => $industries]);

        $request->validate([
            'jobs' => 'required|array|min:1',
            'industries' => 'required|array|min:1',
            'region' => 'required|string',
            'currency' => 'required|string',
            'salary' => 'required|numeric|min:0',
            'country' => 'required',
            'state' => 'nullable',
            'district' => 'nullable',
        ]);

        $searchCountryId = null;
        $stateId = null;
        $cityId = null;

        if ($request->country !== 'anywhere') {
            $request->validate([
                'country' => 'integer|exists:search_countries,id',
                'state' => 'nullable|integer',
                'district' => 'nullable|integer',
            ]);
            $searchCountryId = (int) $request->country;
            $stateId = $request->filled('state') ? (int) $request->state : null;
            $cityId = $request->filled('district') ? (int) $request->district : null;
        }

        $candidate = auth()->user()->candidate;

        JobRequirement::updateOrCreate(
            ['candidate_id' => $candidate->id],
            [
                'jobs'              => cw_resolve_profession_ids($jobs),
                'industries'        => cw_resolve_industry_ids($industries),
                'region'            => $request->region,
                'currency'          => $request->currency,
                'salary'            => $request->salary,
                'search_country_id' => $searchCountryId,
                'state_id'          => $stateId,
                'city_id'           => $cityId,
            ]
        );
        return true;
    }
    public function candidateSummaryUpdate($request, $user, $candidate)
    {
        $request->validate([
            'bio' => 'required',
        ]);

        $candidate->update([

            'bio' => $request->bio,

        ]);
        return true;
    }
    public function candidateSkillUpdate($request, $candidate)
    {
        $request->validate([
            'skills' => 'required',

        ]);

        $skills = $request->skills;
        DB::table('candidate_skill')->where('candidate_id', $candidate->id)->delete();

        if ($skills) {
            $skillsArray = [];

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
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $candidate->skills()->attach($skillsArray);
        }

        return true;
    }

    public function candidateLanguageUpdate($request, $candidate)
    {
        $request->validate([

            'languages' => 'required'

        ]);
        $candidate->languages()->sync($request->languages);
        return true;
    }

    /**
     * Candidate profile setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function candidateProfileInfoUpdate($request, $candidate)
    {
        $request->validate([

            'marital_status' => 'required',
            'profession' => 'required',
            'status' => 'required',
        ]);

        if ($request->status == 'available_in') {
            $request->validate([
                'available_in' => 'required',
            ]);
        }

        // Profession
        $profession_request = $request->profession;
        $profession = ProfessionTranslation::where('profession_id', $profession_request)->orWhere('name', $profession_request)->first();

        if (! $profession) {
            $new_profession = Profession::create(['name' => $profession_request]);

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_profession->translateOrNew($language->code)->name = $profession_request;
            }
            $new_profession->save();

            $profession_id = $new_profession->id;
        } else {
            $profession_id = $profession->profession_id;
        }

        $candidate->update([
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'bio' => $request->bio,
            'profession_id' => $profession_id,
            'status' => $request->status,
            'available_in' => $request->available_in ? Carbon::parse($request->available_in)->format('Y-m-d') : null,
            'passport_number' => $request->passport_number,
            'passport_issue_date' => $request->passport_issue_date,
            'passport_expiry_date' => $request->passport_expiry_date,
            'place_of_issue' => $request->place_of_issue,
            'cnic_number' => $request->cnic_number,
            'language_code' => $request->language_code,


        ]);

        // skill & language
        $skills = $request->skills;
        DB::table('candidate_skill')->where('candidate_id', $candidate->id)->delete();

        if ($skills) {
            $skillsArray = [];

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
                    array_push($skillsArray, $skill_exists->skill_id);
                }
            }

            $candidate->skills()->attach($skillsArray);
        }
        if ($request->input('dynamic_inputs') != '' && $request->input('dynamic_inputs') != Null) {

            foreach ($request->input('dynamic_inputs') as $inputData) {

                $dynamicInput = CandidateAttribute::find($inputData['id']);

                if ($dynamicInput) {
                    $dynamicInput->attribute_value = $inputData['value']; // Update the value
                    $dynamicInput->save(); // Save the changes
                }
            }
        }

        $candidate->languages()->sync($request->languages);

        return true;
    }

    /**
     * Candidate contact setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function contactUpdate($request, $candidate)
    {
        $contact = ContactInfo::where('user_id', auth()->id())->first();

        if (empty($contact)) {
            ContactInfo::create([
                'user_id' => auth()->id(),
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'secondary_email' => $request->secondary_email,

            ]);
        } else {
            $contact->update([
                'phone' => $request->phone,
                'secondary_phone' => $request->secondary_phone,
                'email' => $request->email,
                'whatsapp_number' => $request->whatsapp_number,
                'secondary_email' => $request->secondary_email,

            ]);
        }

        if ($request->has('whatsapp_number')) {
            $candidate->update(['whatsapp_number' => $request->whatsapp_number]);
        }

        // Location


        return true;
    }

    /**
     * Candidate email setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     */
    public function emailUpdate($request): bool
    {
        $user = $request->user();
        $setting = Setting::query()->first();

        $validated = $request->validate([
            'account_email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        if ($validated['account_email'] === $user->email) {
            return false;
        }

        if (! $setting->email_verification) {
            $user->update([
                'email' => $validated['account_email'],
            ]);

            return false;
        }

        // user changed his email
        // if email verification is on in settings
        // then send verify email and mark email as un verified
        Mail::to($validated['account_email'])->send(new SendEmailUpdateVerification($user, $validated['account_email']));
        session()->put('requested_email', $validated['account_email']);

        return true;
    }

    /**
     * Candidate social setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function socialUpdate($request)
    {
        $user = User::find(auth()->id());

        $user->socialInfo()->delete();
        $social_medias = $request->social_media;
        $urls = $request->url;

        if ($social_medias && $urls) {
            foreach ($social_medias as $key => $value) {
                if ($value && $urls[$key]) {
                    $user->socialInfo()->create([
                        'social_media' => $value,
                        'url' => $urls[$key],
                    ]);
                }
            }
        }

        return true;
    }
    public function attachmentUpdate($request)
    {
        // Validate request input
        $request->validate([
            'passport_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'license_image'  => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $user = authUser();

        if (! $user?->candidate) {
            abort(404);
        }

        $candidate = $user->candidate;

        // Fetch the candidate's existing attachment or create a new one if it doesn't exist
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

        return true;
    }
    public function documentUpdate($request)
    {
        $allowedMimes = 'nullable|file|mimes:jpeg,jpg,png,gif,bmp,tiff|max:5120';

        $request->validate([
            'passport_image'              => $allowedMimes,
            'cnic_front'                  => $allowedMimes,
            'cnic_back'                   => $allowedMimes,
            'police_character_certificate'=> $allowedMimes,
            'medical'                     => $allowedMimes,
            'navtec_report'               => $allowedMimes,
            'license_image'               => $allowedMimes,
        ]);

        $user = authUser();

        if (! $user?->candidate) {
            abort(404);
        }

        $candidate = $user->candidate;
        $document->candidate_id = $candidate->id;

        $fields = [
            'passport_image',
            'cnic_front',
            'cnic_back',
            'police_character_certificate',
            'medical',
            'navtec_report',
            'license_image',
        ];

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $mime = $request->file($field)->getMimeType();
                if (! in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/tiff'])) {
                    continue;
                }

                if ($document->{$field}) {
                    deleteImage(public_path('storage/candidates/' . $document->{$field}));
                }

                $relativePath = uploadImage($request->file($field), 'storage/candidates');
                $document->{$field} = basename($relativePath);
            }
        }

        $document->save();

        return true;
    }

    /**
     * Candidate visibility setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function visibilityUpdate($request, $candidate)
    {
        $candidate->update([
            'visibility' => $request->profile_visibility ? 1 : 0,
            'cv_visibility' => $request->cv_visibility ? 1 : 0,
        ]);

        return true;
    }

    /**
     * Candidate password setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     * @return bool
     */
    public function passwordUpdate($request, $user, $candidate)
    {
        $request->validate([
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);
        auth()->logout();

        return true;
    }

    /**
     * Candidate account delete
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function accountDelete($user)
    {
        DB::table('candidate_cv_views')->whereIn('candidate_id', function ($query) use ($user) {
            $query->select('id')
                ->from('candidates')
                ->where('user_id', $user->id);
        })->delete();
        Candidate::where('user_id', $user->id)->delete();
        $user->delete();

        return true;
    }

    /**
     * Candidate alert setting update
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Candidate  $candidate
     */
    public function alertUpdate($request, $candidate): bool
    {
        if ($request->has('received_job_alert') && $request->alert_type == 'status') {
            $candidate->update([
                'role_id' => $request->role_id,
                'received_job_alert' => $request->received_job_alert ? 1 : 0,
            ]);
        }

        if ($request->has('job_roles')) {
            $candidate->jobRoleAlerts()->delete();

            foreach ($request->job_roles as $role) {
                $candidate->jobRoleAlerts()->create([
                    'job_role_id' => $role,
                ]);
            }
        }

        if (! $request->has('job_roles') && $request->alert_type == 'role' && count($candidate->jobRoleAlerts) > 0) {
            $candidate->jobRoleAlerts()->delete();
        }

        return true;
    }

    private function wantsJsonResponse($request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->ajax()
            || $request->header('X-Requested-With') === 'XMLHttpRequest';
    }

    private function completionPayload(Candidate $candidate): array
    {
        $candidate->refresh();

        return [
            'completionPercentage' => $candidate->calculateProfileCompletion(),
            'profileCompletionMissing' => array_map(static fn ($section) => [
                'key' => $section['key'],
                'label' => $section['label'],
                'hint' => $section['hint'],
                'anchor' => $section['anchor'],
            ], $candidate->profileCompletionMissing()),
        ];
    }

    private function sectionPayload(string $type, Candidate $candidate, User $user): array
    {
        $candidate->refresh();
        $user->refresh();

        return match ($type) {
            'basic' => [
                'preview' => [
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'whatsapp' => $candidate->whatsapp_number ?? $user->whatsapp,
                    'location' => collect([
                        $candidate->district,
                        $candidate->region,
                        $candidate->basicLocationCountry(),
                    ])->filter()->implode(', '),
                ],
            ],
            'summary' => [
                'preview' => ['bio' => $candidate->bio],
            ],
            'skill' => [
                'preview' => [
                    'skills' => $candidate->skills()->get()->map(fn ($s) => $s->name)->values()->all(),
                ],
            ],
            'language' => [
                'preview' => [
                    'languages' => $candidate->languages()->get()->map(fn ($l) => $l->name)->values()->all(),
                ],
            ],
            'contact' => [
                'preview' => [
                    'phone' => optional(ContactInfo::where('user_id', $user->id)->first())->phone,
                    'secondary_phone' => optional(ContactInfo::where('user_id', $user->id)->first())->secondary_phone,
                    'whatsapp' => $candidate->whatsapp_number,
                    'email' => optional(ContactInfo::where('user_id', $user->id)->first())->email,
                ],
            ],
            'visibility' => [
                'preview' => [
                    'visibility' => (bool) $candidate->visibility,
                    'cv_visibility' => (bool) $candidate->cv_visibility,
                ],
            ],
            'alert' => [
                'preview' => [
                    'received_job_alert' => (bool) $candidate->received_job_alert,
                ],
            ],
            default => [],
        };
    }

    private function respond($request, string $message, Candidate $candidate, string $type)
    {
        if ($request->has('dynamic_inputs') && is_array($request->input('dynamic_inputs'))) {
            DynamicFieldService::saveSeekerFieldValues($candidate, $request->input('dynamic_inputs'));
        }

        if ($this->wantsJsonResponse($request)) {
            $user = $request->user();

            return response()->json(array_merge([
                'success' => true,
                'message' => $message,
                'type' => $type,
            ], $this->completionPayload($candidate), $this->sectionPayload($type, $candidate, $user)));
        }

        flashSuccess($message);

        return back();
    }
}
