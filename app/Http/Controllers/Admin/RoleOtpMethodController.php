<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use App\Models\Setting;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class RoleOtpMethodController extends Controller
{
    use ValidatesRequests;

    public function __construct()
    {
        $this->middleware('access_limitation')->only([
            'destroy',
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            abort_if(! userCan('role.view'), 403);

            $roles = Role::get();

            return view('backend.roleOtpMethods.index', [
                'roles' => $roles,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Save per-role OTP toggle settings (Seeker & Employer).
     *
     * @return \Illuminate\Http\Response
     */
    public function saveOtpSettings(Request $request)
    {
        abort_if(! userCan('role.edit'), 403);

        try {
            Setting::first()->update([
                'candidate_email_otp'    => $request->has('candidate_email_otp')    ? 1 : 0,
                'candidate_whatsapp_otp' => $request->has('candidate_whatsapp_otp') ? 1 : 0,
                'employer_email_otp'     => $request->has('employer_email_otp')     ? 1 : 0,
                'employer_whatsapp_otp'  => $request->has('employer_whatsapp_otp')  ? 1 : 0,
            ]);

            flashSuccess('OTP settings updated successfully');

            return redirect()->route('roles.otp-methods.index');
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        try {
            abort_if(! userCan('role.edit'), 403);

            return view('backend.roleOtpMethods.edit', [
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        abort_if(! userCan('role.edit'), 403);

        try {
            // Validate the request
            $validated = $request->validate([
                'otp_methods'   => 'nullable|array',
                'otp_methods.*' => 'exists:otp_methods,id',
            ]);

            // Sync OTP methods (detach all if none selected)
            $role->syncOtpMethods($validated['otp_methods'] ?? []);

            flashSuccess(__('role_updated_successfully'));

            return redirect()->route('roles.otp-methods.index');
        } catch (\Throwable $th) {
            flashError($th->getMessage());
            return back()->withInput();
        }
    }
}
