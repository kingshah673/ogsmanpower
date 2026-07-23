<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Job;
use App\Models\AppliedJob;
use App\Models\AIUsageLog;
use App\Models\CandidateJobMatch;
use App\Models\InterviewSchedule;
use App\Models\ChatbotSession;

class AnalyticsService
{
    /*
    |--------------------------------------------------------------------------
    | DASHBOARD STATS
    |--------------------------------------------------------------------------
    */

    public function dashboard()
    {

        return [

            /*
            |--------------------------------------------------------------------------
            | USERS
            |--------------------------------------------------------------------------
            */

            'total_candidates'
                => User::where(
                    'role',
                    'candidate'
                )->count(),

            'total_companies'
                => User::where(
                    'role',
                    'company'
                )->count(),

            'total_agencies'
                => User::where(
                    'role',
                    'agency'
                )->count(),

            /*
            |--------------------------------------------------------------------------
            | JOBS
            |--------------------------------------------------------------------------
            */

            'active_jobs'
                => Job::where(
                    'status',
                    1
                )->count(),

            /*
            |--------------------------------------------------------------------------
            | APPLICATIONS
            |--------------------------------------------------------------------------
            */

            'applications'
                => AppliedJob::count(),

            /*
            |--------------------------------------------------------------------------
            | INTERVIEWS
            |--------------------------------------------------------------------------
            */

            'interviews'
                => InterviewSchedule::count(),

            /*
            |--------------------------------------------------------------------------
            | AI
            |--------------------------------------------------------------------------
            */

            'ai_requests'
                => AIUsageLog::count(),

            'ai_tokens'
                => AIUsageLog::sum(
                    'total_tokens'
                ),

            'ai_cost'
                => AIUsageLog::sum(
                    'cost'
                ),

            /*
            |--------------------------------------------------------------------------
            | ATS
            |--------------------------------------------------------------------------
            */

            'avg_ats_score'
                => round(

                    CandidateJobMatch::avg(
                        'score'
                    ),

                    2
                ),

            /*
            |--------------------------------------------------------------------------
            | CHAT
            |--------------------------------------------------------------------------
            */

            'chat_sessions'
                => ChatbotSession::count()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AI USAGE BY MODULE
    |--------------------------------------------------------------------------
    */

    public function aiModules()
    {

        return AIUsageLog::query()

            ->selectRaw(

                'module,

                COUNT(*) as total_requests,

                SUM(total_tokens) as total_tokens,

                SUM(cost) as total_cost'

            )

            ->groupBy('module')

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | ATS PERFORMANCE
    |--------------------------------------------------------------------------
    */

    public function atsPerformance()
    {

        return CandidateJobMatch::query()

            ->selectRaw(

                'ROUND(score/10)*10 as range_score,

                COUNT(*) as total'

            )

            ->groupBy('range_score')

            ->orderBy('range_score')

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | INTERVIEW CONVERSION
    |--------------------------------------------------------------------------
    */

    public function interviewConversion()
    {

        $applications =
            AppliedJob::count();

        $interviews =
            InterviewSchedule::count();

        if ($applications <= 0) {

            return 0;
        }

        return round(

            ($interviews / $applications)
            * 100,

            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOP JOBS
    |--------------------------------------------------------------------------
    */

    public function topJobs()
    {

        return Job::query()

            ->withCount(
                'appliedJobs'
            )

            ->orderByDesc(
                'applied_jobs_count'
            )

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | TOP COMPANIES
    |--------------------------------------------------------------------------
    */

    public function topCompanies()
    {

        return User::query()

            ->where(
                'role',
                'company'
            )

            ->withCount('company')

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RECENT AI USAGE
    |--------------------------------------------------------------------------
    */

    public function recentAI()
    {

        return AIUsageLog::latest()

            ->take(20)

            ->get();
    }
}