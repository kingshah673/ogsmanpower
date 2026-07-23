<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\Agency\AgencySummaryAIService;
use App\Services\Agency\CandidateJobMatcherService;
use App\Services\Agency\VisaDelayPredictorService;

class AgencyAIController extends Controller
{
    public function summary(AgencySummaryAIService $service)
    {
        $agency = currentAgency();
        $summary = $service->generate($agency);

        return view('frontend.pages.agency.ai.summary', compact('summary'));
    }

    public function generateSummary(AgencySummaryAIService $service)
    {
        $agency = currentAgency();

        cache()->forget('agency_ai_summary_'.$agency->id.'_'.now()->format('Y-m-d'));
        $summary = $service->generate($agency);

        if (request()->expectsJson()) {
            return response()->json(['summary' => $summary]);
        }

        flashSuccess('Summary regenerated.');

        return redirect()->route('agency.ai.summary');
    }

    public function candidateMatcher(int $job, CandidateJobMatcherService $service)
    {
        $agency = currentAgency();
        $jobModel = Job::where('agency_id', $agency->id)->findOrFail($job);

        $matches = $service->topMatches($agency, $jobModel);

        return view('frontend.pages.agency.ai.candidate-matcher', [
            'job' => $jobModel,
            'matches' => $matches,
        ]);
    }

    public function visaDelayForecast(VisaDelayPredictorService $service)
    {
        $agency = currentAgency();
        $forecast = $service->forecast($agency);

        return view('frontend.pages.agency.ai.visa-delay-forecast', $forecast);
    }
}
