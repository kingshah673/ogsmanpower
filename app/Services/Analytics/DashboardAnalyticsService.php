<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\Job;
use App\Models\AppliedJob;
use App\Models\ChatbotSession;
use App\Models\FailedAIMessage;

class DashboardAnalyticsService
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

            'total_users'
                => User::count(),

            'candidates'
                => User::where(
                    'role',
                    'candidate'
                )->count(),

            'companies'
                => User::where(
                    'role',
                    'company'
                )->count(),

            'agencies'
                => User::where(
                    'role',
                    'agency'
                )->count(),

            /*
            |--------------------------------------------------------------------------
            | JOBS
            |--------------------------------------------------------------------------
            */

            'jobs'
                => Job::count(),

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
            | CHATBOT
            |--------------------------------------------------------------------------
            */

            'chat_sessions'
                => ChatbotSession::count(),

            'active_chat_sessions'
                => ChatbotSession::where(
                    'status',
                    'active'
                )->count(),

            /*
            |--------------------------------------------------------------------------
            | FAILED AI
            |--------------------------------------------------------------------------
            */

            'failed_ai_questions'
                => FailedAIMessage::count()
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Top Intents
    |--------------------------------------------------------------------------
    */

    public function topIntents()
    {

        return ChatbotSession::selectRaw(

                'intent, COUNT(*) as total'

            )

            ->groupBy('intent')

            ->orderByDesc('total')

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Failed AI
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
    | Recent Applications
    |--------------------------------------------------------------------------
    */

    public function recentApplications()
    {

        return AppliedJob::latest()

            ->take(10)

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Recent Chat Sessions
    |--------------------------------------------------------------------------
    */

    public function recentChats()
    {

        return ChatbotSession::latest()

            ->take(10)

            ->get();
    }
}