<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileRequest;
use App\Http\Traits\UploadAble;
use App\Models\Admin;
use App\Models\IndustryType;
use Illuminate\Support\Str;


class ProfileController extends Controller
{
    use UploadAble;

    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'profile_update',
        ]);
    }

    /**
     * Profile View.
     *
     * @return void
     */
    public function profile()
    {
        try {
            $user = auth('admin')->user();

            return view('backend.profile.index', compact('user'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Profile Setting.
     *
     * @return void
     */
    public function setting()
    {
        try {
            $user = auth('admin')->user();
            $industry_types = IndustryType::all()->sortBy('name');


            return view('backend.profile.setting', compact('user', 'industry_types'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());

            return redirect()->route('admin.dashboard');
        }
    }

    /**
     * Profile Update.
     *
     * @return \Illuminate\Http\Response
     */
    public function profile_update(ProfileRequest $request)
    {
        try {
            $data = $request->only(['name', 'email', 'username', 'industry_type_id', 'website', 'bio', 'district', 'country', 'state','whatsapp']);
            $user = auth('admin')->user();

            if (! $user instanceof Admin) {
                return redirect()->route('login.admin');
            }

            $data['username'] = $request->username ?? Str::slug($data['name']) . '_' . time();
            $data['industry_type_id'] = $request->industry_type_id;
            $data['whatsapp'] = $request->whatsapp;
            $data['website'] = $request->website;
            $data['bio'] = $request->bio;
            $data['district'] = $request->district;
            $data['region'] = $request->state;
            $data['country'] = $request->country;

            // Handle image uploads
            if ($request->hasFile('image')) {
                $data['image'] = uploadImage($request->image, 'user');
                deleteFile($user->image);
            }

            if ($request->hasFile('id_card_image')) {
                $data['id_card_image'] = uploadImage($request->id_card_image, 'contract');
                deleteFile($user->id_card_image);
            }

            if ($request->hasFile('lisence_image')) {
                $data['lisence_image'] = uploadImage($request->lisence_image, 'contract');
                deleteFile($user->lisence_image);
            }

            if ($request->hasFile('comapny_certificate_image')) {
                $data['comapny_certificate_image'] = uploadImage($request->comapny_certificate_image, 'contract');
                deleteFile($user->comapny_certificate_image);
            }

            if ($request->hasFile('passport_image')) {
                $data['passport_image'] = uploadImage($request->passport_image, 'contract');
                deleteFile($user->passport_image);
            }

            if ($request->isPasswordChange == 1) {
                $data['password'] = bcrypt($request->password);
            }

            // Check if all required fields are not null
            $requiredFields = ['name', 'email', 'username', 'industry_type_id', 'website', 'bio', 'district', 'country', 'region', 'image','whatsapp'];
            $isComplete = true;

            foreach ($requiredFields as $field) {
                if (empty($data[$field] ?? $user->{$field})) {
                    $isComplete = false;
                    break;
                }
            }

            $data['is_profile_compeleted'] = $isComplete ? 1 : 0;

            $user->update($data);

            return back()->with('success', __('profile_update_successfully'));
        } catch (\Exception $e) {
            flashError('An error occurred: ' . $e->getMessage());
            return back();
        }
    }
}
