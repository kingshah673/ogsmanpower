<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\NominatedWorker;
use App\Models\NominatedWorkerBatch;
use App\Models\NominatedWorkerDocument;
use App\Models\VpCase;
use App\Services\NominatedWorkers\NominatedBatchService;
use App\Services\NominatedWorkers\NominatedWorkerMatchService;
use App\Services\VisaProcessing\VisaProcessingService;
use App\Support\VisaLiability;
use Illuminate\Http\Request;

class AgencyNominatedWorkerController extends Controller
{
    protected string $routePrefix = 'agency.nominated-workers';

    public function index(NominatedBatchService $batches)
    {
        $agencyId = currentAgency()->id;

        $invites = $batches->openBatchesQueryForAgency($agencyId)
            ->withCount('workers')
            ->with('company')
            ->latest()
            ->get();

        $activeBatches = NominatedWorkerBatch::query()
            ->where('agency_id', $agencyId)
            ->where('status', NominatedWorkerBatch::STATUS_ACTIVE)
            ->withCount('workers')
            ->latest()
            ->paginate(20);

        $liabilityCases = VpCase::query()
            ->where('agency_id', $agencyId)
            ->where('status', 'in_progress')
            ->whereNotNull('nominated_worker_id')
            ->with(['nominatedWorker', 'steps' => fn ($q) => $q->where('status', 'active')])
            ->latest()
            ->limit(50)
            ->get()
            ->filter(function (VpCase $case) {
                $step = $case->steps->first();

                return $step && VisaLiability::actorCanAct('agency', $step->assignee);
            });

        return view('frontend.pages.company.nominated-workers.agency-batches', [
            'invites' => $invites,
            'activeBatches' => $activeBatches,
            'liabilityCases' => $liabilityCases,
            'nwRoutePrefix' => $this->routePrefix,
        ]);
    }

    public function showBatch(NominatedWorkerBatch $batch)
    {
        $agencyId = currentAgency()->id;
        $allowed = (int) $batch->agency_id === (int) $agencyId
            || ($batch->status === NominatedWorkerBatch::STATUS_AWAITING_AGENCY
                && ($batch->assignment_mode === 'open_all'
                    || (int) $batch->agency_id === (int) $agencyId));
        abort_unless($allowed, 403);

        $batch->load([
            'workers' => fn ($q) => $q->withCount('documents')->with('activeVisaCase.steps'),
            'flow.activeSteps',
            'company',
        ]);

        return view('frontend.pages.company.nominated-workers.batch-show', [
            'batch' => $batch,
            'countries' => collect(),
            'defaultDestinationCountry' => $batch->country_name,
            'nwRoutePrefix' => $this->routePrefix,
            'liabilityOnly' => true,
            'agencyMode' => true,
        ]);
    }

    public function respondBatch(Request $request, NominatedWorkerBatch $batch, NominatedBatchService $batches)
    {
        $data = $request->validate([
            'decision' => 'required|in:accepted,declined',
            'reason' => 'nullable|string|max:500',
        ]);

        try {
            $batches->agencyRespond($batch, currentAgency(), $data['decision'], $data['reason'] ?? null, auth()->id());
            flashSuccess($data['decision'] === 'accepted' ? 'Batch accepted. Visa tracking started.' : 'Batch declined.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return redirect()->route($this->routePrefix.'.index');
    }

    public function show(NominatedWorker $worker)
    {
        $agencyId = currentAgency()->id;
        $ok = (int) $worker->agency_id === (int) $agencyId
            || ($worker->batch && (int) $worker->batch->agency_id === (int) $agencyId);
        abort_unless($ok, 403);

        $worker->load(['documents', 'activeVisaCase.steps.requirements.answer', 'activeVisaCase.steps.requirements.file', 'batch']);

        return view('frontend.pages.company.nominated-workers.show', [
            'worker' => $worker,
            'nwRoutePrefix' => $this->routePrefix,
            'visaCase' => $worker->activeVisaCase,
            'actorSide' => 'agency',
        ]);
    }

    public function submitVisaStep(Request $request, NominatedWorker $worker, VisaProcessingService $visa)
    {
        $agencyId = currentAgency()->id;
        abort_unless(
            (int) $worker->agency_id === (int) $agencyId
            || ($worker->batch && (int) $worker->batch->agency_id === (int) $agencyId),
            403
        );

        $case = $worker->activeVisaCase;
        abort_if(! $case, 404);
        $step = $case->activeStep();
        abort_if(! $step, 404);

        $answers = $request->input('answers', []);
        $files = $request->file('files', []);

        try {
            $visa->submitStep($case, $step, $answers, $files ?: [], auth()->id(), 'agency');
            flashSuccess('Step submitted.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function uploadDocuments(Request $request, NominatedWorkerMatchService $matcher)
    {
        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|max:10240',
            'nominated_worker_id' => 'nullable|integer',
        ]);

        $agencyId = currentAgency()->id;
        $workerId = $request->input('nominated_worker_id');
        if ($workerId) {
            $worker = NominatedWorker::where('agency_id', $agencyId)->findOrFail($workerId);
            $workerId = $worker->id;
        }

        foreach ($request->file('documents') as $file) {
            $matcher->storeDocument($file, null, auth()->id(), $workerId, null, $agencyId);
        }

        flashSuccess('Documents uploaded. AI match ran automatically.');

        return back();
    }

    public function rematch(NominatedWorkerDocument $document, NominatedWorkerMatchService $matcher)
    {
        abort_if((int) $document->agency_id !== (int) currentAgency()->id, 403);
        $matcher->runOcrAndMatch($document);
        flashSuccess('Rematch complete.');

        return back();
    }
}
