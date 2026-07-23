<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\AIHandoverRequest;

class AIHandoverController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $requests =

            AIHandoverRequest::query()

            ->latest()

            ->paginate(50);

        return view(

            'backend.ai.handover.index',

            compact(
                'requests'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REPLY
    |--------------------------------------------------------------------------
    */

    public function reply(
        Request $request,
        $id
    ) {

        $request->validate([

            'admin_reply'
                => 'required'
        ]);

        $handover =

            AIHandoverRequest::findOrFail($id);

        $handover->update([

            'admin_reply'
                => $request->admin_reply,

            'status'
                => 'resolved'
        ]);

        return back()->with(

            'success',

            'Reply submitted successfully.'
        );
    }
}