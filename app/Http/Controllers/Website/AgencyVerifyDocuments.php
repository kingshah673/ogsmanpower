<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Notifications\SendProfileVerificationDocumentSubmittedNotification;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AgencyVerifyDocuments extends Controller
{
    public function index()
    {
        try {
            $agency = auth()->user()->agency->load('media');

            return view('frontend.pages.agency.verify-documents', [
                'agency' => $agency,
            ]);
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
        //
    }

    public function store(Request $request)
    {
        try {
            // validation permission
            $agency = auth()->user()->agency->load('media');
            $request->validate([
                'document' => 'required|image|max:2000',
            ]);
            $agency
                ->addMedia($request->file('document'))
                ->toMediaCollection('document');

            // send notification to admins
            $adminRole = Role::query()->where('name', 'superadmin')->first();

            $adminRole->users->each(function ($admin) use ($agency) {
                $admin->notify(new SendProfileVerificationDocumentSubmittedNotification($agency));
            });

            flashSuccess(__('document_uploaded_success'));

            return redirect()->back();
        } catch (\Exception $e) {

            flashError('An error occurred: '.$e->getMessage());

            return back();
        }

        //        if ($request->hasFile('nid_front'))
        //        {
        //            $request->validate([
        //               'nid_front' => 'mimes:jpg,jpeg,png,pdf|max:2000'
        //            ]);
        //
        //            // if he already has an document then add new one
        //            // and mark as unverified
        //            if($agency->hasMedia('nid_front'))
        //            {
        //                $agency->nid_front_verified_at = null ;
        //            }
        //
        //            $agency
        //                ->addMedia($request->file('nid_front'))
        //                ->toMediaCollection('nid_front');
        //        }
        //
        //        if ($request->hasFile('nid_back'))
        //        {
        //            $request->validate([
        //                'nid_back' => 'mimes:jpg,jpeg,png,pdf|max:2000'
        //            ]);
        //
        //            // if he already has an document then add new one
        //            // and mark as unverified
        //            if($agency->hasMedia('nid_back'))
        //            {
        //                $agency->nid_back_verified_at = null ;
        //            }
        //
        //            $agency
        //                ->addMedia($request->file('nid_back'))
        //                ->toMediaCollection('nid_back');
        //        }
        //
        //        if ($request->hasFile('tin'))
        //        {
        //            $request->validate([
        //                'tin' => 'mimes:jpg,jpeg,png,pdf|max:2000'
        //            ]);
        //
        //            // if he already has an document then add new one
        //            // and mark as unverified
        //            if($agency->hasMedia('tin'))
        //            {
        //                $agency->tin_verified_at = null ;
        //            }
        //
        //            $agency
        //                ->addMedia($request->file('tin'))
        //                ->toMediaCollection('tin');
        //        }
        //
        //        if ($agency->tin_verified_at && $agency->nid_front_verified_at && $agency->nid_back_verified_at)
        //        {
        //            $agency->is_profile_verified = true ;
        //        }else {
        //            $agency->is_profile_verified = false ;
        //        }

        //        $agency->save();
        //
        //
        //        return  redirect()->back();

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
