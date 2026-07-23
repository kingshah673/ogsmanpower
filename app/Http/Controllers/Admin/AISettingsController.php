<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Models\AISetting;
use App\Models\FailedAIMessage;
use App\Models\AIUsageLog;

class AISettingsController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        $settings =
            AISetting::pluck(
                'value',
                'key'
            );

        $failed =
            FailedAIMessage::latest()

            ->take(20)

            ->get();

        $usage =
            AIUsageLog::latest()

            ->take(20)

            ->get();

        return view(

            'backend.ai.settings',

            compact(

                'settings',

                'failed',

                'usage'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request
    ) {

        $data = [

            'ai_enabled',

            'auto_interview_enabled',

            'multilingual_enabled',

            'voice_ai_enabled',

            'ai_ats_threshold'
        ];

        foreach ($data as $key) {

            AISetting::updateOrCreate(

                [

                    'key'
                        => $key
                ],

                [

                    'value'
                        => $request->$key
                ]
            );
        }

        return back()->with(

            'success',

            'AI settings updated successfully.'
        );
    }
}