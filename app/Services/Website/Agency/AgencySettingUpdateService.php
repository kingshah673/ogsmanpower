<?php

namespace App\Services\Website\Agency;

use App\Mail\SendEmailUpdateVerification;
use App\Models\Agency;
use App\Models\ContactInfo;
use App\Models\IndustryType;
use App\Models\IndustryTypeTranslation;
use App\Models\OrganizationType;
use App\Models\OrganizationTypeTranslation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AgencySettingUpdateService
{
    /**
     * Update agency setting
     */
    public function update($request): mixed
    {
        $user = authUser();

        if (! $user) {
            abort(401);
        }
        $request->session()->put('type', $request->type);

        if ($request->type == 'personal') {
            $this->personalUpdate($request, $user);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'profile') {
            $this->profileUpdate($request);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'social') {

            $this->socialUpdate($request);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'contact') {

            $this->contactUpdate($request);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'account') {

            $this->emailUpdate($request) ? flashSuccess(__('Mail Verification Sent')) : flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'password') {
            $this->passwordUpdate($request, $user);
            flashSuccess(__('profile_updated'));

            return back();
        }

        if ($request->type == 'account-delete') {
            $this->accountDelete($user);
            flashSuccess(__('profile_updated'));

            return back();
        }
    }

    /**
     * agency personal info update
     *
     * @param  Request  $request
     */
    // public function personalUpdate($request, $user): bool
    // {
    //     $request->validate([
    //         'name' => 'required|unique:users,name,'.auth()->id(),
    //     ]);

    //     $agency = Agency::where('user_id', auth()->id())->first();

    //     if ($request->image) {
    //         $request->validate([
    //             'image' => 'required|image|mimes:jpeg,png,jpg,gif',
    //         ]);

    //         deleteImage($user->agency->logo);
    //         $path = 'images/agency';
    //         $image = uploadImage($request->image, $path);

    //         if ($agency) {
    //             $agency->update(['logo' => $image]);
    //         }
    //     }

    //     if ($request->banner) {
    //         $request->validate([
    //             'banner' => 'required|image|mimes:jpeg,png,jpg,gif',
    //         ]);

    //         deleteImage($user->agency->banner);
    //         $path = 'images/agency';
    //         $banner = uploadImage($request->banner, $path);

    //         if ($agency) {
    //             $agency->update(['banner' => $banner]);
    //         }
    //     }

    //     $user->update([
    //         'name' => $request->name,
    //         'username' => Str::slug($request->name),
    //     ]);

    //     if ($agency) {
    //         $agency->update(['bio' => $request->about_us]);
    //     }

    //     return true;
    // }

    public function personalUpdate($request, $user): bool
    {
        $nameRules = ['required', 'string', 'max:255'];

        $submittedName = trim((string) $request->input('name', ''));
        $currentName = trim((string) $user->name);

        if (strcasecmp($submittedName, $currentName) !== 0) {
            $nameRules[] = Rule::unique('users', 'name')
                ->where(fn ($query) => $query->where('role', 'agency'))
                ->ignore($user->id);
        }

        $request->validate([
            'name' => $nameRules,
        ]);

        $agency = Agency::where('user_id', $user->id)->first();
        $imageMimes = 'jpeg,png,jpg,gif,webp,jfif,bmp';

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $request->validate([
                'image' => 'required|image|mimes:'.$imageMimes.'|max:5120',
            ]);

            deleteImage($user->agency->logo);
            $image = uploadImage($request->file('image'), 'uploads/images/agency', [68, 68]);

            if ($agency) {
                $agency->update(['logo' => $image]);
            }
        }

        if ($request->hasFile('banner') && $request->file('banner')->isValid()) {
            $request->validate([
                'banner' => 'required|image|mimes:'.$imageMimes.'|max:5120',
            ]);

            deleteImage($user->agency->banner);
            $banner = uploadImage($request->file('banner'), 'uploads/images/agency', [1920, 312]);

            if ($agency) {
                $agency->update(['banner' => $banner]);
            }
        }

        $userData = ['name' => $request->name];

        if ($request->name !== $user->name) {
            $username = Str::slug($request->name) ?: 'agency-'.$user->id;
            while (User::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = Str::slug($request->name).'-'.uniqid();
            }
            $userData['username'] = $username;
        }

        $user->update($userData);

        if ($agency) {
            $agency->update(['bio' => $request->about_us]);
        }

        return true;
    }

    /**
     * agency profile update
     *
     * @param  Request  $request
     * @return Response
     */
    public function profileUpdate($request)
    {
        $request->validate([
            'organization_type' => 'required',
            'industry_type' => 'required',
            'team_size' => 'required',
            'establishment_date' => 'nullable',
        ]);

        $agency = Agency::where('user_id', auth()->id())->first();

        // Organization Type
        $organization_request = $request->organization_type;
        $organization_type = OrganizationTypeTranslation::where('organization_type_id', $organization_request)->orWhere('name', $organization_request)->first();

        if (! $organization_type) {
            $new_organization_type = new OrganizationType;

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_organization_type->translateOrNew($language->code)->name = $organization_type;
            }
            $new_organization_type->save();

            $organization_type_id = $new_organization_type->id;
        } else {
            $organization_type_id = $organization_type->organization_type_id;
        }

        // Industry Type
        $industry_request = $request->industry_type;
        $industry_type = IndustryTypeTranslation::where('industry_type_id', $industry_request)->orWhere('name', $industry_request)->first();

        if (! $industry_type) {
            $new_industry_type = new IndustryType;

            $languages = loadLanguage();
            foreach ($languages as $language) {
                $new_industry_type->translateOrNew($language->code)->name = $industry_type;
            }
            $new_industry_type->save();

            $industry_type_id = $new_industry_type->id;
        } else {
            $industry_type_id = $industry_type->industry_type_id;
        }

        if ($agency) {
            $agency->update([
                'organization_type_id' => $organization_type_id,
                'industry_type_id' => $industry_type_id,
                'team_size_id' => $request->team_size,
                'establishment_date' => $request->establishment_date ?? null,
                'website' => $request->website,
                'vision' => $request->vision,
            ]);
        }

        return true;
    }

    /**
     * agency social info update
     *
     * @param  Request  $request
     * @return Response
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

    /**
     * agency contact info update
     *
     * @param  Request  $request
     * @return Response
     */
    public function contactUpdate($request)
    {
        $contact = ContactInfo::where('user_id', auth()->id())->first();
        if (empty($contact)) {
            ContactInfo::create([
                'user_id' => auth()->id(),
                'phone' => $request->phone,
                'email' => $request->email,
            ]);
        } else {
            $contact->update([
                'phone' => $request->phone,
                'email' => $request->email,
            ]);
        }

        if (empty(config('templatecookie.map_show')) && $request->filled('country')) {
            seedLocationSessionFromNames(
                $request->input('country'),
                $request->input('state'),
                $request->input('district')
            );
        }

        // =========== Location ===========
        updateMap(auth()->user()->agency);

        return true;
    }

    /**
     * agency email update
     *
     * @param  Request  $request
     * @return Response
     */
    public function emailUpdate($request): bool
    {
        $user = $request->user();
        $setting = Setting::query()->first();

        $validated = $request->validate([
            'account_email' => 'required|email|unique:users,email,'.$user->id,
            'username' => 'required|unique:users,username,'.$user->id,
        ]);

        $user->update([
            'username' => $validated['username'],
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
     * Password Update
     *
     * @param  Request  $request
     * @param  User  $user
     * @return bool
     */
    public function passwordUpdate($request, $user)
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
     * agency account delete
     *
     * @param  Request  $request
     * @return Response
     */
    public function accountDelete($user)
    {

        DB::table('jobs')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        DB::table('agency_bookmark_categories')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        DB::table('bookmark_agency')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        DB::table('agency_questions')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        DB::table('candidate_cv_views')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        DB::table('bookmark_candidate_agency')->whereIn('agency_id', function ($query) use ($user) {
            $query->select('id')
                ->from('companies')
                ->where('user_id', $user->id);
        })->delete();

        Agency::where('user_id', $user->id)->delete();

        $user->delete();

        return true;
    }
}
