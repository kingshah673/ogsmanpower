<?php

namespace App\Services\Website\Agency;

use App\Models\Agency;
use App\Models\ContactInfo;
use App\Models\IndustryType;
use App\Models\IndustryTypeTranslation;
use App\Models\OrganizationType;
use App\Models\OrganizationTypeTranslation;
use App\Models\User;
use Illuminate\Support\Str;

class AgencyAccountProgressService
{
    /**
     * Get agency account progress
     *
     * @return void
     */
    public function execute($request)
    {
        $agency = currentAgency();

        switch ($request->field) {
            case 'personal':
                $image_validation = $agency->logo ? 'sometimes|image|mimes:jpeg,png,jpg' : 'required|image|mimes:jpeg,png,jpg';
                $banner_validation = $agency->banner ? 'sometimes|image|mimes:jpeg,png,jpg' : 'required|image|mimes:jpeg,png,jpg';

                $request->validate([
                    'image' => $image_validation,
                    'banner' => $banner_validation,
                    'name' => 'nullable|max:255',
                    'bio' => 'required',
                ], [
                    'image.required' => 'The logo field is required.',
                ]);

                $update = $this->personalProfileUpdate($request);
                if ($update) {
                    return redirect()
                        ->route('agency.account-progress', [], false)
                        ->with('success', __('Branding saved successfully.'))
                        ->withFragment('section-profile');
                }

                return back();
                break;
            case 'profile':
                $request->validate([
                    'organization_type_id' => 'required|string',
                    'industry_type_id' => 'required|string',
                    'establishment_date' => 'nullable',
                    'website' => 'nullable|url',
                    'vision' => 'required',
                ]);

                $update = $this->agencyProfileUpdate($request);
                if ($update) {
                    return redirect()
                        ->route('agency.account-progress', [], false)
                        ->with('success', __('Agency profile saved successfully.'))
                        ->withFragment('section-social');
                }

                return back()->send();
                break;
            case 'social':
                $update = $this->socialProfileUpdate($request);
                if ($update) {
                    return redirect()
                        ->route('agency.account-progress', [], false)
                        ->with('success', __('Social links saved successfully.'))
                        ->withFragment('section-contact');
                }

                return back()->send();
                break;
            case 'contact':
                $request->validate([
                    'email' => 'required|email',
                    'phone' => 'required',
                ]);

                $location = session()->get('location');
                if (! $location) {
                    $request->validate([
                        'location' => 'required',
                    ]);
                }

                $request->validate([
                    'phone' => 'required|min:4|max:16',
                    'email' => 'required|email',
                ]);

                $update = $this->contactProfileUpdate($request);
                if ($update) {
                    return redirect()->route('agency.dashboard')
                        ->with('success', __('congratulations_you_profile_is_complete'));
                }

                return back()->send();
                break;
            case 'complete':
                return view('frontend.pages.agency.account-progress.complete');
                break;
            default:
                return back()->send();
        }
    }

    /**
     * Personal Profile Update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    // public function personalProfileUpdate($request)
    // {
    //     $user = User::findOrFail(auth()->user()->id);
    //     $agency = agency::where('user_id', $user->id)->firstOrFail();
    //     $name = $request->name ?? fake()->name();
    //     $newUsername = Str::slug($name);
    //     $user->update(['name' => $name,  'username' => $newUsername]);

    //     if ($request->hasFile('image')) {
    //         $image = uploadImage($request->image, 'images/agency');
    //         $agency->logo = $image;
    //     } else {
    //         if (! $agency->logo) {
    //             $agency->logo = createAvatar($name, 'uploads/images/agency');
    //         }
    //     }

    //     if ($request->hasFile('banner')) {
    //         $banner = uploadImage($request->banner, 'images/agency');
    //         $agency->banner = $banner;
    //     } else {
    //         if (! $agency->banner) {
    //             $agency->banner = createAvatar($name, 'uploads/images/agency');
    //         }
    //     }

    //     $agency->bio = $request->bio;
    //     $agency->save();

    //     return true;
    // }
    public function personalProfileUpdate($request)
    {
        $user = authUser();

        if (! $user) {
            abort(401);
        }
        $agency = Agency::where('user_id', $user->id)->firstOrFail();
        $name = $request->name;
        $newUsername = Str::slug($name);
        $user->update(['name' => $name,  'username' => $newUsername]);

        if ($request->hasFile('image')) {
            // $image = uploadImage($request->image, 'images/agency');

            $path = 'uploads/images/agency';

            $image = uploadImage($request->image, $path, [68, 68]);

            $agency->logo = $image;
        } else {
            if (! $agency->logo) {
                // $agency->logo = createAvatar($name, 'uploads/images/agency');

                $setDimension = [100, 100]; //Here needs to be [68, 68] but avatar image not looks good in view that's why increase value 100 from 68
                $path = 'uploads/images/agency';
                $image = createAvatar($name, $path, $setDimension);
            }
        }

        if ($request->hasFile('banner')) {
            // $banner = uploadImage($request->banner, 'images/agency');

            $path = 'uploads/images/agency';
            $banner = uploadImage($request->banner, $path, [1920, 312]);

            $agency->banner = $banner;
        } else {
            if (! $agency->banner) {
                // $agency->banner = createAvatar($name, 'uploads/images/agency');
                $setDimension = [1920, 312];
                $path = 'uploads/images/agency';
                $banner = createAvatar($name, $path, $setDimension);
            }
        }

        $agency->bio = $request->bio;
        $agency->save();

        return true;
    }

    /**
     * Contact Profile Update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function agencyProfileUpdate($request)
    {
        // Organization Type
        $organization_request = $request->organization_type_id;
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
        $industry_request = $request->industry_type_id;
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

        $agency = Agency::where('user_id', auth()->user()->id);
        $agency->update([
            'organization_type_id' => $organization_type_id,
            'industry_type_id' => $industry_type_id,
            'establishment_date' => $request->establishment_date ? date('Y-m-d', strtotime($request->establishment_date)) : null,
            'team_size_id' => $request->team_size_id,
            'website' => $request->website,
            'vision' => $request->vision,
        ]);

        return $agency;
    }

    /**
     * Social Profile Update
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function socialProfileUpdate($request)
    {
        $social_medias = $request->social_media;
        $urls = $request->url;

        $user = User::find(auth()->id());
        $user->socialInfo()->delete();

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
     * Contact Profile Update
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function contactProfileUpdate($request): bool
    {
        $user = authUser();

        if (! $user) {
            abort(401);
        }

        ContactInfo::where('user_id', $user->id)
            ->update($request->only('phone', 'email'));

        if (empty(config('templatecookie.map_show')) && $request->filled('country')) {
            seedLocationSessionFromNames(
                $request->input('country'),
                $request->input('state'),
                $request->input('district')
            );
        }

        updateMap($user->agency());

        Agency::where('user_id', $user->id)->update([
            'profile_completion' => 1,
        ]);

        return true;
    }
}
