<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AI\AIRecruiterCopilotService;

class AIRecruiterCopilotController extends Controller
{
    protected $copilot;

    public function __construct(
        AIRecruiterCopilotService $copilot
    ) {

        $this->copilot = $copilot;
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        return view(
            'admin.ai.copilot'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    public function search(
        Request $request
    ) {

        $request->validate([

            'query'
                => 'required'
        ]);

        $results =
            $this->copilot
                ->search(
                    $request->query
                );

        $response =
            $this->copilot
                ->format(
                    $results
                );

        return response()->json([

            'success'
                => true,

            'response'
                => $response
        ]);
    }
}