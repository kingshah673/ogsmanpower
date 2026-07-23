<?php

namespace App\Services\Admin\Agency;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\AgencyCreateApprovalPendingNotification;
use App\Notifications\AgencyCreatedNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AgencyCreateService
{
    /**
     * Create agency
     */

    // public function execute($request): void
    // {
    //     // location validation
    //     $this->locationValidation($request);

    //     // create user
    //     $name = $request->name ?? fake()->name();
    //     $username = $request->username ?? Str::slug($name).'_'.time();

    //     $agency = User::create([
    //         'name' => $name,
    //         'username' => $username,
    //         'email' => $request->email,
    //         'password' => bcrypt($request->password),
    //         'role' => 'agency',
    //     ]);

    //     // insert logo
    //     if ($request->logo) {
    //         $logo_url = uploadImage($request->logo, 'agency');
    //     } else {
    //         $logo_url = createAvatar($name, 'uploads/images/agency');
    //     }

    //     // insert banner
    //     if ($request->image) {
    //         $banner_url = uploadImage($request->image, 'agency');
    //     } else {
    //         $banner_url = createAvatar($name, 'uploads/images/agency');
    //     }

    //     // format date
    //     $dateTime = Carbon::parse($request->establishment_date);
    //     $date = $request['establishment_date'] = $dateTime->format('Y-m-d H:i:s') ?? null;

    //     // insert agency
    //     $agency->agency()->update([
    //         'industry_type_id' => $request->industry_type_id,
    //         'organization_type_id' => $request->organization_type_id,
    //         'team_size_id' => $request->team_size_id,
    //         'establishment_date' => $date,
    //         'logo' => $logo_url ?? '',
    //         'banner' => $banner_url ?? '',
    //         'website' => $request->website,
    //         'bio' => $request->bio,
    //         'vision' => $request->vision,
    //     ]);

    //     // agency contact info update
    //     $agency->contactInfo()->update([
    //         'phone' => $request->contact_phone,
    //         'email' => $request->contact_email,
    //     ]);

    //     // Social media insert
    //     $social_medias = $request->social_media;
    //     $urls = $request->url;

    //     foreach ($social_medias as $key => $value) {
    //         if ($value && $urls[$key]) {
    //             $agency->socialInfo()->create([
    //                 'social_media' => $value ?? '',
    //                 'url' => $urls[$key] ?? '',
    //             ]);
    //         }
    //     }

    //     // Location insert
    //     updateMap($agency->agency());

    //     // make Notification
    //     $data[] = $agency;
    //     $data[] = $request->password;

    //     // send mail notification
    //     $this->sendMailNotification($agency, $request);
    // }

    public function execute($request): void
    {
        // location validation
        $this->locationValidation($request);

        // create user
        $name = $request->name;
        $username = $request->username ? Str::slug($request->username) : Str::slug($name).'_'.time();

        // Check if the username is unique
        while (User::where('username', $username)->exists()) {
            $username = Str::slug($name).'_'.time();
        }

        $agency = User::create([
            'name' => $name,
            'username' => $username,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'agency',
        ]);

        // insert logo
        if ($request->logo) {

            $path = 'uploads/images/agency';

            $logo_url = uploadImage($request->logo, $path, [68, 68]);
        } else {
            $setDimension = [100, 100]; //Here needs to be [68, 68] but avatar image not looks good in view that's why increase value 100 from 68
            $path = 'uploads/images/agency';
            $logo_url = createAvatar($name, $path, $setDimension);
        }

        // insert banner
        if ($request->image) {

            $path = 'uploads/images/agency';

            $banner_url = uploadImage($request->image, $path, [1920, 312]);
        } else {
            $setDimension = [1920, 312];
            $path = 'uploads/images/agency';
            $banner_url = createAvatar($name, $path, $setDimension);
        }

        // format date
        $dateTime = Carbon::parse($request->establishment_date);
        $date = $request['establishment_date'] = $dateTime->format('Y-m-d H:i:s') ?? null;

        // insert agency
        $agency->agency()->update([
            'industry_type_id' => $request->industry_type_id,
            'organization_type_id' => $request->organization_type_id,
            'team_size_id' => $request->team_size_id,
            'establishment_date' => $date,
            'logo' => $logo_url ?? '',
            'banner' => $banner_url ?? '',
            'website' => $request->website,
            'bio' => $request->bio,
            'vision' => $request->vision,
        ]);

        // agency contact info update
        $agency->contactInfo()->update([
            'phone' => $request->contact_phone,
            'email' => $request->contact_email,
        ]);

        // Social media insert
        $social_medias = $request->social_media;
        $urls = $request->url;

        foreach ($social_medias as $key => $value) {
            if ($value && $urls[$key]) {
                $agency->socialInfo()->create([
                    'social_media' => $value ?? '',
                    'url' => $urls[$key] ?? '',
                ]);
            }
        }

        // Location insert
        updateMap($agency->agency());

        // make Notification
        $data[] = $agency;
        $data[] = $request->password;

        // send mail notification
        $this->sendMailNotification($agency, $request);
    }

    /**
     * Send mail notification
     *
     * @return void
     */
    protected function sendMailNotification($agency, $request)
    {
        // if mail is configured
        if (checkMailConfig()) {
            $employer_auto_activation_enabled = Setting::where('employer_auto_activation', 1)->count();

            // if employer activation enabled, send account created mail else, send will be activated mail.
            if ($employer_auto_activation_enabled) {
                Notification::route('mail', $agency->email)->notify(new AgencyCreatedNotification($agency, $request->password));
            } else {
                Notification::route('mail', $agency->email)->notify(new AgencyCreateApprovalPendingNotification($agency, $request->password));
            }
        }
    }

    /**
     * Location validation
     *
     * @return void
     */
    protected function locationValidation($request)
    {
        $location = session()->get('location');
        if (! $location) {
            $request->validate(['location' => 'required']);
        }
    }
}
