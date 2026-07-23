<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\User;
use App\Models\Job;
use App\Models\AppliedJob;
use App\Models\InterviewSchedule;
use App\Models\CandidateJobMatch;
use App\Models\FailedAIMessage;
use App\Models\ChatbotSession;

class AIDashboardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        $stats = [

            'total_candidates'
                => User::where(
                    'role',
                    'candidate'
                )->count(),

            'active_jobs'
                => Job::where(
                    'status',
                    1
                )->count(),

            'applications'
                => AppliedJob::count(),

            'interviews'
                => InterviewSchedule::count(),

            'ai_matches'
                => CandidateJobMatch::count(),

            'shortlisted'
                => CandidateJobMatch::where(
                    'score',
                    '>=',
                    80
                )->count(),

            'failed_ai'
                => FailedAIMessage::count(),

            'live_chats'
                => ChatbotSession::count()
        ];

        /*
        |--------------------------------------------------------------------------
        | TOP CANDIDATES
        |--------------------------------------------------------------------------
        */

        $topCandidates =
            CandidateJobMatch::query()

            ->with([

                'candidate',

                'job'
            ])

            ->latest()

            ->take(10)

            ->get();

        /*
        |--------------------------------------------------------------------------
        | INTERVIEWS
        |--------------------------------------------------------------------------
        */

        $interviews =
            InterviewSchedule::query()

            ->with('candidate')

            ->latest()

            ->take(10)

            ->get();

        /*
        |--------------------------------------------------------------------------
        | FAILED AI
        |--------------------------------------------------------------------------
        */

        $failedAI =
            FailedAIMessage::latest()

            ->take(10)

            ->get();

        /*
        |--------------------------------------------------------------------------
        | LIVE CHATS
        |--------------------------------------------------------------------------
        */

        $liveChats =
            ChatbotSession::latest()

            ->take(10)

            ->get();

        /*
        |--------------------------------------------------------------------------
        | VIEW
        |--------------------------------------------------------------------------
        */

        return view(

            'backend.ai.dashboard',

            compact(

                'stats',

                'topCandidates',

                'interviews',

                'failedAI',

                'liveChats'
            )
        );
    }
}