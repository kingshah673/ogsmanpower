<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AgencyCreateFormRequest;
use App\Http\Requests\AgencyUpdateFormRequest;
use App\Models\Agency;
use App\Models\AgencyAttribute;
use App\Models\IndustryType;
use App\Models\OrganizationType;
use App\Models\TeamSize;
use App\Models\User;
use App\Notifications\SendProfileVerifiedNotification;
use App\Services\Admin\Agency\AgencyCreateService;
use App\Services\Admin\Agency\AgencyListService;
use App\Services\Admin\Agency\AgencyUpdateService;
use Illuminate\Http\Request;
use Modules\Location\Entities\Country;

class AgencyController extends Controller
{
    public function dynamic_inputs()
    {
        $agency_attribute = AgencyAttribute::all();
        return view('backend.agency.dynamic-inputs', compact('agency_attribute'));
    }
    public function index(Request $request)
    {
        try {
            abort_if(! userCan('agency.view'), 403);

            $agencies = (new AgencyListService)->execute($request);
            $industry_types = IndustryType::all()->sortBy('name');
            $organization_types = OrganizationType::all()->sortBy('name');

            return view('backend.agency.index', compact('agencies', 'industry_types', 'organization_types'));
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
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
            abort_if(! userCan('agency.create'), 403);

            $data['countries'] = Country::all();
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();

            return view('backend.agency.create', $data);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(AgencyCreateFormRequest $request)
    {
        try {
            abort_if(! userCan('agency.create'), 403);

            (new AgencyCreateService)->execute($request);

            flashSuccess(__('agency_created_successfully'));

            return redirect()->route('agency.index');
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            abort_if(! userCan('agency.view'), 403);

            $agency = Agency::with([
                'jobs.appliedJobs',
                'user.socialInfo',
                'user.contactInfo',
                'jobs' => function ($job) {
                    return $job->latest()->with('category', 'role', 'job_type', 'salary_type');
                },
            ])->findOrFail($id);

            $pendingAgentInvites = \App\Models\AgentInvite::query()
                ->where('agency_user_id', $agency->user_id)
                ->whereNull('accepted_at')
                ->where('expires_at', '>', now())
                ->latest()
                ->get();

            return view('backend.agency.show', compact('agency', 'pendingAgentInvites'));
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        try {
            abort_if(! userCan('agency.update'), 403);

            $data['agency'] = Agency::findOrFail($id);
            $data['user'] = $data['agency']->user->load('socialInfo');
            $data['industry_types'] = IndustryType::all()->sortBy('name');
            $data['organization_types'] = OrganizationType::all()->sortBy('name');
            $data['team_sizes'] = TeamSize::all();
            $data['socials'] = $data['agency']->user->socialInfo;

            return view('backend.agency.edit', $data);
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
    public function update(AgencyUpdateFormRequest $request, agency $agency)
    {
        try {
            abort_if(! userCan('agency.update'), 403);

            (new AgencyUpdateService)->execute($request, agency);

            flashSuccess(__('agency_updated_successfully'));

            return redirect()->route('agency.index');
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            abort_if(! userCan('agency.delete'), 403);

            $agency = Agency::findOrFail($id);

            // agency image delete
            deleteFile($agency->logo);
            deleteFile($agency->banner);
            deleteFile($agency->user->image);

            // agency cv view items delete
            $agency->cv_views()->delete();
            $agency->user->delete();
            $agency->delete();

            flashSuccess(__('agency_deleted_successfully'));

            return back();
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function documents(Agency $agency)
    {
        try {
            $agency = $agency->load('media');

            return view('backend.agency.document', [
                'agency' => $agency,
            ]);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    public function downloadDocument(Request $request, Agency $agency)
    {
        try {
            $request->validate([
                'file_type' => 'required',
            ]);
            $media = $agency->getFirstMedia($request->get('file_type'));

            return response()->download($media->getPath(), $media->file_name);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change agency status
     *
     * @return void
     */
    public function statusChange(Request $request)
    {
        try {
            $user = User::findOrFail($request->id);

            $user->update(['status' => $request->status]);

            if ($request->status == 1) {
                return responseSuccess(__('agency_activated_successfully'));
            } else {
                return responseSuccess(__('agency_deactivated_successfully'));
            }
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change agency verification status
     *
     * @return void
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
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change agency profile verification status
     *
     * @return void
     */
    public function profileVerificationChange(Request $request)
    {
        try {
            $agency = Agency::findOrFail($request->id);

            if ($request->status) {

                $agency->document_verified_at = now();
                $agency->update(['is_profile_verified' => true]);
                $agency->user->notify(new SendProfileVerifiedNotification);
                $message = __('profile_verified_successfully');
            } else {

                $agency->document_verified_at = null;
                $agency->update(['is_profile_verified' => false]);
                $message = __('profile_unverified_successfully');
            }

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }

    /**
     * Change agency document verification status
     *
     * @param  Request  $request
     * @return void
     */
    public function toggle(Agency $agency)
    {
        try {
            if ($agency->document_verified_at) {
                $agency->update(['is_profile_verified' => false]);
                $agency->document_verified_at = null;
                $message = __('unverified').' '.__('successfully');
            } else {
                $agency->document_verified_at = now();
                $agency->update(['is_profile_verified' => true]);
                $agency->user->notify(new SendProfileVerifiedNotification);
                $message = __('verified').' '.__('successfully');
            }

            $agency->save();

            return responseSuccess($message);
        } catch (\Exception $e) {
            flashError('An error occurred: '.$e->getMessage());

            return back();
        }
    }
}
