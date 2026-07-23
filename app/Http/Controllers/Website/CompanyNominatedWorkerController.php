<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Job;
use App\Models\NominatedWorker;
use App\Models\NominatedWorkerBatch;
use App\Models\NominatedWorkerDocument;
use App\Models\SearchCountry;
use App\Services\NominatedWorkers\NominatedBatchService;
use App\Services\NominatedWorkers\NominatedWorkerMatchService;
use Illuminate\Http\Request;

class CompanyNominatedWorkerController extends Controller
{
    public function index()
    {
        $companyId = currentCompany()->id;
        $batches = NominatedWorkerBatch::query()
            ->where('company_id', $companyId)
            ->withCount('workers')
            ->with('flow')
            ->latest()
            ->paginate(20);

        $orphanWorkers = NominatedWorker::query()
            ->where('company_id', $companyId)
            ->whereNull('batch_id')
            ->withCount('documents')
            ->latest()
            ->limit(20)
            ->get();

        return view('frontend.pages.company.nominated-workers.batches-index', [
            'batches' => $batches,
            'orphanWorkers' => $orphanWorkers,
            'nwRoutePrefix' => 'company.nominated-workers',
        ]);
    }

    public function createBatch()
    {
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name', 'short_name']);
        $jobs = Job::query()
            ->where('company_id', currentCompany()->id)
            ->latest()
            ->limit(100)
            ->get(['id', 'title']);
        $agencies = Agency::query()->active()->with('user')->orderBy('id')->limit(200)->get();

        return view('frontend.pages.company.nominated-workers.batch-create', [
            'countries' => $countries,
            'jobs' => $jobs,
            'agencies' => $agencies,
            'nwRoutePrefix' => 'company.nominated-workers',
            'defaultDestinationCountry' => default_destination_country_name(),
        ]);
    }

    public function storeBatch(Request $request, NominatedBatchService $batches)
    {
        $data = $request->validate([
            'name' => 'required|string|max:180',
            'search_country_id' => 'required|integer|exists:search_countries,id',
            'job_id' => 'nullable|integer|exists:jobs,id',
            'assignment_mode' => 'required|in:direct,open_all',
            'agency_id' => 'nullable|required_if:assignment_mode,direct|integer|exists:agencies,id',
        ]);

        $country = SearchCountry::findOrFail($data['search_country_id']);

        $batch = NominatedWorkerBatch::create([
            'name' => $data['name'],
            'company_id' => currentCompany()->id,
            'search_country_id' => $country->id,
            'country_name' => $country->name,
            'job_id' => $data['job_id'] ?? null,
            'assignment_mode' => $data['assignment_mode'],
            'agency_id' => $data['assignment_mode'] === 'direct' ? ($data['agency_id'] ?? null) : null,
            'status' => NominatedWorkerBatch::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);

        try {
            $batches->attachPublishedFlow($batch);
        } catch (\InvalidArgumentException $e) {
            // Allow draft without flow; submit will re-check
            flashWarning($e->getMessage());
        }

        flashSuccess('Batch created. Add workers, then submit for admin approval.');

        return redirect()->route('company.nominated-workers.batches.show', $batch);
    }

    public function showBatch(NominatedWorkerBatch $batch)
    {
        $this->assertCompanyBatch($batch);
        $batch->load([
            'workers' => fn ($q) => $q->withCount('documents')->with('activeVisaCase.steps'),
            'flow.activeSteps',
            'agency',
            'job',
        ]);
        $countries = SearchCountry::query()->orderBy('name')->get(['id', 'name', 'short_name']);

        return view('frontend.pages.company.nominated-workers.batch-show', [
            'batch' => $batch,
            'countries' => $countries,
            'defaultDestinationCountry' => $batch->country_name,
            'nwRoutePrefix' => 'company.nominated-workers',
            'liabilityOnly' => false,
        ]);
    }

    public function submitBatch(NominatedWorkerBatch $batch, NominatedBatchService $batches)
    {
        $this->assertCompanyBatch($batch);
        try {
            $batches->submitForApproval($batch);
            flashSuccess('Batch submitted for admin approval.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_id' => 'required|integer|exists:nominated_worker_batches,id',
            'full_name' => 'required|string|max:180',
            'passport_number' => 'nullable|string|max:60',
            'nationality' => 'nullable|string|max:80',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:120',
            'destination_country' => 'nullable|string|max:120',
            'job_title' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $batch = NominatedWorkerBatch::findOrFail($data['batch_id']);
        $this->assertCompanyBatch($batch);
        if (! $batch->isEditableByEmployer()) {
            flashError('Workers can only be added while the batch is a draft or returned.');

            return back();
        }

        NominatedWorker::create(array_merge($data, [
            'company_id' => currentCompany()->id,
            'created_by' => auth()->id(),
            'status' => 'pending_docs',
            'destination_country' => $data['destination_country'] ?? $batch->country_name,
        ]));

        flashSuccess('Nominated worker added.');

        return back();
    }

    public function import(Request $request, NominatedWorkerMatchService $matcher)
    {
        $request->validate([
            'batch_id' => 'required|integer|exists:nominated_worker_batches,id',
            'batch_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $batch = NominatedWorkerBatch::findOrFail($request->integer('batch_id'));
        $this->assertCompanyBatch($batch);
        if (! $batch->isEditableByEmployer()) {
            flashError('Import is only allowed for draft/returned batches.');

            return back();
        }

        $contents = file_get_contents($request->file('batch_file')->getRealPath());
        $count = $matcher->importCsv($contents, currentCompany()->id, auth()->id(), null, $batch->id);
        flashSuccess($count.' workers imported.');

        return back();
    }

    public function show(NominatedWorker $worker)
    {
        abort_if((int) $worker->company_id !== (int) currentCompany()->id, 403);
        $worker->load(['documents', 'activeVisaCase.steps.requirements.answer', 'activeVisaCase.steps.requirements.file', 'batch']);

        return view('frontend.pages.company.nominated-workers.show', [
            'worker' => $worker,
            'nwRoutePrefix' => 'company.nominated-workers',
            'visaCase' => $worker->activeVisaCase,
        ]);
    }

    public function uploadDocuments(Request $request, NominatedWorkerMatchService $matcher)
    {
        $request->validate([
            'batch_id' => 'nullable|integer|exists:nominated_worker_batches,id',
            'nominated_worker_id' => 'nullable|integer|exists:nominated_workers,id',
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|max:10240',
        ]);

        $workerId = $request->input('nominated_worker_id');
        if ($request->filled('batch_id')) {
            $batch = NominatedWorkerBatch::findOrFail($request->integer('batch_id'));
            $this->assertCompanyBatch($batch);
        } elseif ($workerId) {
            $worker = NominatedWorker::findOrFail($workerId);
            abort_if((int) $worker->company_id !== (int) currentCompany()->id, 403);
        } else {
            flashError('Batch or worker is required.');

            return back();
        }

        foreach ($request->file('documents') as $file) {
            $matcher->storeDocument($file, currentCompany()->id, auth()->id(), $workerId ? (int) $workerId : null, null, null);
        }
        flashSuccess('Documents uploaded and matched where possible.');

        return back();
    }

    public function rematch(NominatedWorkerDocument $document, NominatedWorkerMatchService $matcher)
    {
        abort_if((int) $document->company_id !== (int) currentCompany()->id, 403);
        $matcher->runOcrAndMatch($document);
        flashSuccess('Document rematched.');

        return back();
    }

    public function submitVisaStep(Request $request, NominatedWorker $worker, \App\Services\VisaProcessing\VisaProcessingService $visa)
    {
        abort_if((int) $worker->company_id !== (int) currentCompany()->id, 403);
        $case = $worker->activeVisaCase;
        abort_if(! $case, 404);
        $step = $case->activeStep();
        abort_if(! $step, 404);

        try {
            $visa->submitStep($case, $step, $request->input('answers', []), $request->file('files', []) ?: [], auth()->id(), 'employer');
            flashSuccess('Step submitted.');
        } catch (\InvalidArgumentException $e) {
            flashError($e->getMessage());
        }

        return back();
    }

    protected function assertCompanyBatch(NominatedWorkerBatch $batch): void
    {
        abort_if((int) $batch->company_id !== (int) currentCompany()->id, 403);
    }
}
