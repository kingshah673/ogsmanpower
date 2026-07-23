<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use App\Models\VpCase;
use App\Models\VpCaseFile;
use App\Models\VpCaseRequirement;
use App\Services\VisaProcessing\VisaProcessingService;
use App\Support\VisaCasePayload;
use Illuminate\Http\Request;

class CompanyVisaProcessingController extends Controller
{
    public function index()
    {
        $companyId = currentCompany()->id;
        $cases = VpCase::query()
            ->where('company_id', $companyId)
            ->with(['candidate.user', 'job', 'steps'])
            ->latest()
            ->paginate(20);

        return view('frontend.pages.company.visa-processing.index', [
            'cases' => $cases,
            'vpRoutePrefix' => 'company.visa-processing',
        ]);
    }

    public function show(VpCase $vp_case)
    {
        $this->assertOwns($vp_case);
        $vp_case->load([
            'steps.requirements.answer',
            'steps.requirements.file',
            'steps.requirements.step',
            'candidate.user',
            'job',
            'events',
        ]);
        return view('frontend.pages.company.visa-processing.show', [
            'case' => $vp_case,
            'vpRoutePrefix' => 'company.visa-processing',
        ]);
    }

    public function start(Request $request, VisaProcessingService $service)
    {
        $data = $request->validate([
            'applied_job_id' => 'required|integer',
            'country_name' => 'nullable|string|max:120',
            'search_country_id' => 'nullable|integer|exists:search_countries,id',
        ]);

        $application = AppliedJob::with('job')->findOrFail($data['applied_job_id']);
        $companyId = (int) currentCompany()->id;
        $owns = (int) $application->company_id === $companyId
            || (int) optional($application->job)->company_id === $companyId;
        abort_if(! $owns, 403);

        $searchCountryId = isset($data['search_country_id']) ? (int) $data['search_country_id'] : null;
        $countryName = $data['country_name'] ?? null;
        if ($searchCountryId) {
            $country = \App\Models\SearchCountry::find($searchCountryId);
            $countryName = $country?->name ?: $countryName;
        }
        if (! $countryName) {
            flashError('Please choose a destination country.');

            return back();
        }

        try {
            $case = $service->startCase(
                $application,
                $countryName,
                auth()->id(),
                $companyId,
                null,
                $searchCountryId
            );
            flashSuccess('Visa processing started for '.$countryName.'.');

            return redirect()->route('company.visa-processing.show', $case->id);
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());

            return back();
        }
    }

    public function submitStep(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        $step = $vp_case->activeStep();
        abort_if(! $step || $step->assignee !== 'employer', 403);

        try {
            $service->submitStep($vp_case, $step, $request->input('answers', []), $request->file('files', []), auth()->id(), 'employer');
            flashSuccess('Documents saved and step completed.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function verify(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        $step = $vp_case->activeStep();
        abort_if(! $step || $step->assignee !== 'employer', 403);

        try {
            if ($request->hasFile('files') || $request->filled('answers')) {
                $service->submitStep(
                    $vp_case,
                    $step,
                    $request->input('answers', []),
                    $request->file('files', []),
                    auth()->id(),
                    'employer'
                );
                flashSuccess('Documents saved and step completed.');
            } else {
                $service->verifyAndContinue($vp_case, auth()->id());
                flashSuccess('Step completed.');
            }
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function sendBack(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        $data = $request->validate(['reason' => 'required|string|min:3|max:2000']);
        try {
            $service->rejectAndSendBack($vp_case, $data['reason'], auth()->id());
            flashSuccess('Sent back to candidate.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function markDeployed(Request $request, VpCase $vp_case, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        $data = $request->validate([
            'flight_airline' => 'nullable|string|max:120',
            'flight_ticket_number' => 'nullable|string|max:120',
            'flight_date' => 'nullable|date',
        ]);

        try {
            $service->markDeployed($vp_case, $data, auth()->id());
            flashSuccess('Worker marked as deployed.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function restart(VpCase $vp_case, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        try {
            $new = $service->restartCase($vp_case, auth()->id());
            flashSuccess('Visa processing restarted from step 1.');

            return redirect()->route('company.visa-processing.show', $new->id);
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());

            return back();
        }
    }

    public function downloadFile(VpCase $vp_case, int $fileId)
    {
        $this->assertOwns($vp_case);
        $file = VpCaseFile::where('vp_case_id', $vp_case->id)->where('id', $fileId)->firstOrFail();
        $path = storage_path('app/public/'.ltrim($file->path, '/'));
        abort_unless(is_file($path), 404);

        return response()->download($path, $file->original_name);
    }

    public function viewFile(VpCase $vp_case, int $fileId)
    {
        $this->assertOwns($vp_case);
        $file = VpCaseFile::where('vp_case_id', $vp_case->id)->where('id', $fileId)->firstOrFail();
        $path = storage_path('app/public/'.ltrim($file->path, '/'));
        abort_unless(is_file($path), 404);

        $mime = $file->mime ?: (mime_content_type($path) ?: 'application/octet-stream');

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($file->original_name).'"',
        ]);
    }

    public function reviewRequirement(Request $request, VpCase $vp_case, VpCaseRequirement $requirement, VisaProcessingService $service)
    {
        $this->assertOwns($vp_case);
        abort_if((int) $requirement->step->vp_case_id !== (int) $vp_case->id, 404);

        $requirement->loadMissing('step');

        $data = $request->validate([
            'decision' => 'required|in:approve,reject',
            'reason' => 'nullable|string|max:2000',
        ]);

        try {
            $service->reviewRequirement(
                $vp_case,
                (int) $requirement->id,
                $data['decision'],
                $data['reason'] ?? null,
                auth()->id()
            );
            $message = $data['decision'] === 'approve'
                ? 'Document approved.'
                : 'Document rejected — candidate notified.';

            if ($request->expectsJson()) {
                $vp_case->load([
                    'steps.requirements.answer',
                    'steps.requirements.file',
                    'steps.requirements.step',
                    'candidate.user',
                    'job',
                    'events',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'payload' => VisaCasePayload::forCase($vp_case, 'company.visa-processing', 'employer'),
                ]);
            }

            flashSuccess($message);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            flashError($e->getMessage());
        }

        return back();
    }

    protected function assertOwns(VpCase $case): void
    {
        abort_if((int) $case->company_id !== (int) currentCompany()->id, 403);
    }
}
