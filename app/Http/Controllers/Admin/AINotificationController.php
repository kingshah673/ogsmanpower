<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\AINotification;

class AINotificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $notifications =

            AINotification::query()

            ->latest()

            ->paginate(50);

        /*
        |--------------------------------------------------------------------------
        | MARK AS READ
        |--------------------------------------------------------------------------
        */

        AINotification::query()
            ->update([
                'is_read' => 1
            ]);

        return view(

            'backend.ai.notifications.index',

            compact(
                'notifications'
            )
        );
    }
}