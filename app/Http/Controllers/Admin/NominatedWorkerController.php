<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NominatedWorker;
use App\Models\NominatedWorkerBatch;
use App\Models\NominatedWorkerDocument;
use App\Services\NominatedWorkers\NominatedBatchService;
use App\Services\NominatedWorkers\NominatedWorkerMatchService;
use Illuminate\Http\Request;

class NominatedWorkerController extends Controller
{
    public function indexBatches()
    {
        $batches = NominatedWorkerBatch::query()
            ->with(['company', 'flow'])
            ->withCount('workers')
            ->latest()
            ->paginate(30);

        return view('backend.nominated-workers.batches-index', compact('batches'));
    }

    public function showBatch(NominatedWorkerBatch $batch)
    {
        $batch->load([
            'company.user',
            'agency.user',
            'flow.activeSteps',
            'workers' => fn ($q) => $q->withCount('documents'),
            'agencyResponses.agency.user',
        ]);

        return view('backend.nominated-workers.batch-show', compact('batch'));
    }

    public function approveBatch(NominatedWorkerBatch $batch, NominatedBatchService $batches)
    {
        try {
            $batches->approve($batch, auth()->id());
            flashSuccess('Batch approved. Waiting for agency acceptance.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function returnBatch(Request $request, NominatedWorkerBatch $batch, NominatedBatchService $batches)
    {
        $data = $request->validate([
            'admin_comment' => 'required|string|max:2000',
        ]);

        try {
            $batches->returnToEmployer($batch, $data['admin_comment']);
            flashSuccess('Batch returned to employer.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function index()
    {
        $workers = NominatedWorker::with(['company', 'batch'])->withCount('documents')->latest()->paginate(30);

        return view('backend.nominated-workers.index', compact('workers'));
    }

    public function show(NominatedWorker $worker)
    {
        $worker->load(['documents', 'company', 'batch', 'activeVisaCase.steps']);

        return view('backend.nominated-workers.show', compact('worker'));
    }

    public function rematch(NominatedWorkerDocument $document, NominatedWorkerMatchService $matcher)
    {
        $matcher->runOcrAndMatch($document);
        flashSuccess('Rematch complete.');

        return back();
    }

    public function confirmMatch(Request $request, NominatedWorkerDocument $document)
    {
        $data = $request->validate([
            'nominated_worker_id' => 'required|integer|exists:nominated_workers,id',
        ]);
        $document->update([
            'matched_worker_id' => $data['nominated_worker_id'],
            'nominated_worker_id' => $data['nominated_worker_id'],
            'match_status' => 'matched',
            'match_confidence' => 100,
        ]);
        flashSuccess('Match confirmed.');

        return back();
    }
}
