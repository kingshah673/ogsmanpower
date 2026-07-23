<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Job;
use App\Models\AppliedJob;
use App\Models\CandidateJobMatch;
use App\Models\InterviewSchedule;
use App\Models\ChatbotSession;
use App\Models\FailedAIMessage;
use App\Models\LiveChatMessage;

class RecruiterDashboardService
{
    /*
    |--------------------------------------------------------------------------
    | Dashboard Stats
    |--------------------------------------------------------------------------
    */

    public function stats()
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

            'shortlisted'
                => AppliedJob::where(
                    'status',
                    'shortlisted'
                )->count(),

            'interviews'
                => InterviewSchedule::count(),

            /*
            |--------------------------------------------------------------------------
            | AI
            |--------------------------------------------------------------------------
            */

            'ai_matches'
                => CandidateJobMatch::count(),

            'failed_ai'
                => FailedAIMessage::count(),

            /*
            |--------------------------------------------------------------------------
            | LIVE CHAT
            |--------------------------------------------------------------------------
            */

            'live_chats'
                => ChatbotSession::count(),

            'messages'
                => LiveChatMessage::count()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TOP ATS CANDIDATES
    |--------------------------------------------------------------------------
    */

    public function topCandidates()
    {

        return CandidateJobMatch::query()

            ->with([

                'candidate',

                'job'
            ])

            ->orderByDesc('score')

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RECENT INTERVIEWS
    |--------------------------------------------------------------------------
    */

    public function interviews()
    {

        return InterviewSchedule::latest()

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | RECENT APPLICATIONS
    |--------------------------------------------------------------------------
    */

    public function applications()
    {

        return AppliedJob::latest()

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | FAILED AI QUESTIONS
    |--------------------------------------------------------------------------
    */

    public function failedAI()
    {

        return FailedAIMessage::latest()

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | LIVE CHATS
    |--------------------------------------------------------------------------
    */

    public function liveChats()
    {

        return ChatbotSession::latest()

            ->take(10)

            ->get();
    }
}