<?php

namespace App\Services\Admin\Agency;

use App\Notifications\UpdateAgencyPassNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class AgencyUpdateService
{
    /**
     * Update agency
     */

    // public function execute($request, $agency): void
    // {
    //     // update user
    //     $agency = $agency->user;
    //     $data['name'] = $request->name ?? fake()->name();
    //     $data['email'] = $request->email;
    //     $data['username'] = $request->username ?? Str::slug($data['name']).'_'.time();

    //     if ($request->password) {
    //         $data['password'] = bcrypt($request->password);
    //     }

    //     $agency->update($data);

    //     // update agency
    //     $agency->agency()->update([
    //         'industry_type_id' => $request->industry_type_id,
    //         'organization_type_id' => $request->organization_type_id,
    //         'team_size_id' => $request->team_size_id,
    //         'establishment_date' => Carbon::parse($request->establishment_date)->format('Y-m-d') ?? null,
    //         'website' => $request->website,
    //         'bio' => $request->bio,
    //         'vision' => $request->vision,
    //     ]);

    //     // update logo
    //     if ($request->logo) {

    //         $logo_url = uploadFileToPublic($request->logo, 'agency');
    //         $agency->agency()->update(['logo' => $logo_url]);
    //     }

    //     // update banner
    //     if ($request->image) {
    //         $banner_url = uploadFileToPublic($request->image, 'agency');
    //         $agency->agency()->update(['banner' => $banner_url]);
    //     }

    //     // update contact info
    //     $agency->contactInfo()->update([
    //         'phone' => $request->contact_phone,
    //         'email' => $request->contact_email,
    //     ]);

    //     // Social media update
    //     $agency->socialInfo()->delete();

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

    //     // Location
    //     updateMap($agency->agency());

    //     // Send mail notification
    //     $this->sendMailNotification($request, $agency);
    // }

    public function execute($request, $agency): void
    {
        // update user
        $agency = $agency->user;
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['username'] = $request->username ?? Str::slug($data['name']).'_'.time();

        if ($request->password) {
            $data['password'] = bcrypt($request->password);
        }

        $agency->update($data);

        // update agency
        $agency->agency()->update([
            'industry_type_id' => $request->industry_type_id,
            'organization_type_id' => $request->organization_type_id,
            'team_size_id' => $request->team_size_id,
            'establishment_date' => Carbon::parse($request->establishment_date)->format('Y-m-d') ?? null,
            'website' => $request->website,
            'bio' => $request->bio,
            'vision' => $request->vision,
        ]);

        // update logo
        if ($request->logo) {

            deleteImage($agency->agency->logo);
            $path = 'uploads/images/agency';
            $logo_url = uploadImage($request->logo, $path, [68, 68]);

            if ($agency) {
                $agency->agency()->update(['logo' => $logo_url]);
            }
        }

        // update banner
        if ($request->image) {

            deleteImage($agency->agency->image);
            $path = 'uploads/images/agency';
            $banner_url = uploadImage($request->image, $path, [1920, 312]);

            if ($agency) {
                $agency->agency()->update(['banner' => $banner_url]);
            }
        }

        // update contact info
        $agency->contactInfo()->update([
            'phone' => $request->contact_phone,
            'email' => $request->contact_email,
        ]);

        // Social media update
        $agency->socialInfo()->delete();

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

        // Location
        updateMap($agency->agency());

        // Send mail notification
        $this->sendMailNotification($request, $agency);
    }

    /**
     * Send mail notification
     */
    protected function sendMailNotification($request, $agency): void
    {
        if ($request->password) {
            $data[] = $agency;
            $data[] = $request->password;
            $data[] = 'Agency';

            checkMailConfig() ? Notification::route('mail', $agency->email)->notify(new UpdateAgencyPassNotification($data)) : '';
        }
    }
}
