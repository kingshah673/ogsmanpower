<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\VpCase;
use App\Services\VisaProcessing\VisaProcessingService;
use Illuminate\Http\Request;

class CandidateVisaProcessingController extends Controller
{
    public function index(VisaProcessingService $service)
    {
        $candidate = currentCandidate();
        abort_if(! $candidate, 403);

        $cases = VpCase::query()
            ->where('candidate_id', $candidate->id)
            ->with(['steps.requirements.answer', 'steps.requirements.file', 'job'])
            ->latest()
            ->get();

        return view('frontend.pages.candidate.visa-processing.index', compact('cases'));
    }

    public function submitStep(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $candidate = currentCandidate();
        abort_if(! $candidate || (int) $vp_case->candidate_id !== (int) $candidate->id, 403);

        $step = $vp_case->activeStep();
        abort_if(! $step || $step->assignee !== 'seeker', 403);

        try {
            $service->submitStep(
                $vp_case,
                $step,
                $request->input('answers', []),
                $request->file('files', []),
                auth()->id(),
                'seeker'
            );
            flashSuccess('Documents saved and step completed.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }
}
