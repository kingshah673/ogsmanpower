<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use App\Models\Candidate;
use App\Models\CandidateJobMatch;

class CopilotController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        return view(
            'backend.ai.copilot'
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

        try {

            $query =
                strtolower(
                    $request->query
                );

            /*
            |--------------------------------------------------------------------------
            | SEARCH CANDIDATES
            |--------------------------------------------------------------------------
            */

            $candidates =
                Candidate::query()

                ->where(

                    'title',

                    'LIKE',

                    "%{$query}%"

                )

                ->orWhere(

                    'bio',

                    'LIKE',

                    "%{$query}%"

                )

                ->orWhere(

                    'skills',

                    'LIKE',

                    "%{$query}%"

                )

                ->take(10)

                ->get();

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            $response = '';

            if (

                $candidates->count()
                <= 0

            ) {

                $response =
                    "No matching candidates found.";

            } else {

                foreach ($candidates as $candidate) {

                    $response .=

                        "Candidate: "
                        .
                        ($candidate->user->name ?? 'N/A')
                        .
                        "\n";

                    $response .=

                        "Title: "
                        .
                        ($candidate->title ?? 'N/A')
                        .
                        "\n";

                    $response .=

                        "Skills: "
                        .
                        ($candidate->skills ?? 'N/A')
                        .
                        "\n";

                    $response .=

                        "Experience: "
                        .
                        ($candidate->experience ?? 'N/A')
                        .
                        "\n";

                    /*
                    |--------------------------------------------------------------------------
                    | ATS SCORE
                    |--------------------------------------------------------------------------
                    */

                    $match =
                        CandidateJobMatch::query()

                        ->where(
                            'candidate_id',
                            $candidate->id
                        )

                        ->orderByDesc('score')

                        ->first();

                    if ($match) {

                        $response .=

                            "ATS Score: "
                            .
                            round($match->score)
                            .
                            "%\n";
                    }

                    $response .=
                        "----------------------------------\n\n";
                }
            }

            return response()->json([

                'success'
                    => true,

                'response'
                    => $response
            ]);

        } catch (\Exception $e) {

            return response()->json([

                'success'
                    => false,

                'message'
                    => $e->getMessage()
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RECRUITER DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {

        $matches =
            CandidateJobMatch::query()

            ->with([

                'candidate',

                'job'
            ])

            ->latest()

            ->take(20)

            ->get();

        return view(

            'backend.ai.recruiter-dashboard',

            compact(
                'matches'
            )
        );
    }
}