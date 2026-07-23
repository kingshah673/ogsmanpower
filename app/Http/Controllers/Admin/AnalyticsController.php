<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;

class AnalyticsController extends Controller
{
    protected $analytics;

    public function __construct(
        AnalyticsService $analytics
    ) {

        $this->analytics = $analytics;
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function index()
    {

        return view(

            'backend.analytics.index',

            [

                'stats'
                    => $this->analytics
                        ->dashboard(),

                'modules'
                    => $this->analytics
                        ->aiModules(),

                'ats'
                    => $this->analytics
                        ->atsPerformance(),

                'conversion'
                    => $this->analytics
                        ->interviewConversion(),

                'jobs'
                    => $this->analytics
                        ->topJobs(),

                'companies'
                    => $this->analytics
                        ->topCompanies(),

                'recentAI'
                    => $this->analytics
                        ->recentAI()
            ]
        );
    }
}