<?php

namespace App\Services\VisaProcessing;

use App\Models\AppliedJob;
use App\Models\NominatedWorker;
use App\Models\NominatedWorkerBatch;
use App\Models\VisaFlow;
use App\Models\VpCase;
use App\Models\VpCaseAnswer;
use App\Models\VpCaseEvent;
use App\Models\VpCaseFile;
use App\Models\VpCaseRequirement;
use App\Models\VpCaseStep;
use App\Services\Candidates\CandidatePublicCodeService;
use App\Support\VisaLiability;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class VisaProcessingService
{
    public function __construct(
        protected VisaProcessingNotifier $notifier
    ) {}

    public function findActiveFlowForCountry(string $countryName, ?int $searchCountryId = null): ?VisaFlow
    {
        $query = VisaFlow::query()
            ->active()
            ->published()
            ->with(['activeSteps.activeRequirements']);

        if ($searchCountryId) {
            $byId = (clone $query)->where('search_country_id', $searchCountryId)->first();
            if ($byId) {
                return $byId;
            }
        }

        $normalized = trim($countryName);
        if ($normalized === '') {
            return null;
        }

        return $query
            ->whereRaw('LOWER(country_name) = ?', [mb_strtolower($normalized)])
            ->first();
    }

    /**
     * Snapshot a VisaFlow onto a VpCase (shared by selected-job and nominated paths).
     */
    protected function snapshotFlowOntoCase(VpCase $case, VisaFlow $flow): void
    {
        $flow->loadMissing(['activeSteps.activeRequirements']);
        $index = 0;
        foreach ($flow->activeSteps as $flowStep) {
            $caseStep = VpCaseStep::create([
                'vp_case_id' => $case->id,
                'source_step_id' => $flowStep->id,
                'name' => $flowStep->name,
                'description' => $flowStep->description,
                'assignee' => $flowStep->assignee,
                'sort_order' => $index,
                'status' => $index === 0 ? 'active' : 'pending',
            ]);

            $parentMap = [];
            foreach ($flowStep->activeRequirements as $req) {
                $created = VpCaseRequirement::create([
                    'vp_case_step_id' => $caseStep->id,
                    'parent_id' => $req->parent_id ? ($parentMap[$req->parent_id] ?? null) : null,
                    'source_requirement_id' => $req->id,
                    'label' => $req->label,
                    'type' => $req->type,
                    'is_required' => $req->is_required,
                    'sort_order' => $req->sort_order,
                ]);
                $parentMap[$req->id] = $created->id;
            }
            $index++;
        }
    }

    /**
     * Start visa cases for every worker in an active nominated batch.
     *
     * @return list<VpCase>
     */
    public function startCasesForBatch(NominatedWorkerBatch $batch, int $startedByUserId): array
    {
        if ($batch->status !== NominatedWorkerBatch::STATUS_ACTIVE) {
            throw new InvalidArgumentException('Visa cases can only start for an active batch.');
        }

        $flow = VisaFlow::query()
            ->with(['activeSteps.activeRequirements'])
            ->find($batch->visa_flow_id);

        if (! $flow || $flow->activeSteps->isEmpty()) {
            throw new InvalidArgumentException('Batch has no frozen visa flow with steps.');
        }

        $cases = [];
        foreach ($batch->workers as $worker) {
            $existing = VpCase::query()
                ->where('nominated_worker_id', $worker->id)
                ->where('status', 'in_progress')
                ->first();
            if ($existing) {
                $cases[] = $existing;

                continue;
            }
            $cases[] = $this->startNominatedCase($worker, $batch, $startedByUserId, $flow);
        }

        return $cases;
    }

    public function startNominatedCase(
        NominatedWorker $worker,
        NominatedWorkerBatch $batch,
        int $startedByUserId,
        ?VisaFlow $flow = null
    ): VpCase {
        $existing = VpCase::query()
            ->where('nominated_worker_id', $worker->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('An in-progress visa case already exists for this worker.');
        }

        $flow = $flow ?: VisaFlow::query()
            ->with(['activeSteps.activeRequirements'])
            ->find($batch->visa_flow_id);

        if (! $flow || $flow->activeSteps->isEmpty()) {
            throw new InvalidArgumentException(
                'No visa processing flow is configured for '.$batch->country_name.'.'
            );
        }

        return DB::transaction(function () use ($worker, $batch, $startedByUserId, $flow) {
            $case = VpCase::create([
                'visa_flow_id' => $flow->id,
                'company_id' => $batch->company_id,
                'agency_id' => $batch->agency_id,
                'candidate_id' => null,
                'job_id' => $batch->job_id,
                'applied_job_id' => null,
                'nominated_worker_id' => $worker->id,
                'country_name' => $batch->country_name ?: $flow->country_name,
                'status' => 'in_progress',
                'current_step_index' => 0,
                'started_by' => $startedByUserId,
            ]);

            $this->snapshotFlowOntoCase($case, $flow);
            $this->log($case, 'started', 'Nominated worker visa processing started for '.$case->country_name, $startedByUserId);
            $worker->update(['status' => 'visa_in_progress']);
            $case->load(['steps.requirements', 'nominatedWorker', 'job']);
            $this->notifier->caseStarted($case);

            return $case;
        });
    }

    /**
     * Start a case from Selected application — snapshots the country flow.
     */
    public function startCase(
        AppliedJob $application,
        string $countryName,
        int $startedByUserId,
        ?int $companyId = null,
        ?int $agencyId = null,
        ?int $searchCountryId = null
    ): VpCase {
        if ($application->status !== 'selected') {
            throw new InvalidArgumentException('Visa processing can only start for Selected candidates.');
        }

        $existing = VpCase::query()
            ->where('applied_job_id', $application->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('An in-progress visa case already exists for this application.');
        }

        $flow = $this->findActiveFlowForCountry($countryName, $searchCountryId);
        if (! $flow || $flow->activeSteps->isEmpty()) {
            throw new InvalidArgumentException(
                'No visa processing flow is configured for '.$countryName.'. Ask an admin to set one up first.'
            );
        }

        $resolvedCountry = $flow->country_name ?: $countryName;

        return DB::transaction(function () use ($application, $resolvedCountry, $startedByUserId, $companyId, $agencyId, $flow) {
            $case = VpCase::create([
                'visa_flow_id' => $flow->id,
                'company_id' => $companyId ?: $application->company_id ?: optional($application->job)->company_id,
                'agency_id' => $agencyId ?: $application->agency_id,
                'candidate_id' => $application->candidate_id,
                'job_id' => $application->job_id,
                'applied_job_id' => $application->id,
                'country_name' => $resolvedCountry,
                'status' => 'in_progress',
                'current_step_index' => 0,
                'started_by' => $startedByUserId,
            ]);

            $this->snapshotFlowOntoCase($case, $flow);

            $this->log($case, 'started', 'Visa processing started for '.$resolvedCountry, $startedByUserId);
            $case->load(['steps.requirements', 'candidate.user', 'job']);
            if ($case->candidate) {
                app(CandidatePublicCodeService::class)->sync($case->candidate, $case->job, $case);
            }
            $this->notifier->caseStarted($case);

            return $case;
        });
    }

    public function submitStep(VpCase $case, VpCaseStep $step, array $answers, array $files, int $actorId, string $actorSide): VpCase
    {
        $this->assertCaseActionable($case);
        if ($step->status !== 'active' || (int) $step->vp_case_id !== (int) $case->id) {
            throw new InvalidArgumentException('This step is not active.');
        }
        if (! VisaLiability::actorCanAct($actorSide, $step->assignee)) {
            throw new InvalidArgumentException('It is not your turn on this step.');
        }

        $step->load('requirements');

        foreach ($step->requirements as $req) {
            if ($req->type === 'file') {
                /** @var UploadedFile|null $upload */
                $upload = $files[$req->id] ?? null;
                if ($req->is_required && ! $upload && ! $req->file) {
                    throw new InvalidArgumentException('Required file missing: '.$req->label);
                }
                if ($upload instanceof UploadedFile) {
                    $this->storeFile($case, $req, $upload, $actorId, $actorSide);
                }
            } else {
                $value = $answers[$req->id] ?? null;
                if ($req->type === 'checkbox') {
                    $value = ! empty($value) ? '1' : '0';
                }
                if ($req->is_required && ($value === null || $value === '')) {
                    throw new InvalidArgumentException('Required field missing: '.$req->label);
                }
                VpCaseAnswer::updateOrCreate(
                    ['vp_case_requirement_id' => $req->id],
                    [
                        'submitted_by' => $actorId,
                        'value' => $value,
                        'review_status' => $actorSide === 'seeker' ? 'pending' : 'approved',
                        'review_reason' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                    ]
                );
            }
        }

        $step->update([
            'status' => 'completed',
            'completed_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->log($case, 'step_completed', 'Completed step: '.$step->name, $actorId, [
            'step_id' => $step->id,
        ]);

        return $this->advanceOrComplete($case, $actorId, $step, $actorSide);
    }

    /**
     * Employer verifies a seeker-completed step that was waiting review —
     * in this model, seeker submit already advances. Verify is used when
     * the active step is employer "review" style OR to explicitly accept
     * and move forward after seeker filled previous. Spec: employer can
     * Reject & send back the candidate's previous step.
     */
    public function verifyAndContinue(VpCase $case, int $actorId, string $actorSide = 'employer'): VpCase
    {
        $this->assertCaseActionable($case);
        $step = $case->activeStep();
        if (! $step || ! VisaLiability::actorCanAct($actorSide, $step->assignee)) {
            throw new InvalidArgumentException('No '.$actorSide.'-liable step is active to verify.');
        }

        // If this employer step has requirements, they must already be filled via submitStep.
        // verifyAndContinue is an alias for completing an employer review step with no extra fields,
        // or advancing when requirements already satisfied.
        $step->load('requirements.answer', 'requirements.file');
        foreach ($step->requirements as $req) {
            if (! $req->is_required) {
                continue;
            }
            if ($req->type === 'file' && ! $req->file) {
                throw new InvalidArgumentException('Complete required uploads before continuing.');
            }
            if ($req->type !== 'file' && (! $req->answer || $req->answer->value === null || $req->answer->value === '')) {
                throw new InvalidArgumentException('Complete required fields before continuing.');
            }
        }

        $step->update(['status' => 'completed', 'completed_at' => now()]);
        $this->log($case, 'verified', 'Verified & continued: '.$step->name, $actorId);

        return $this->advanceOrComplete($case, $actorId, $step, $actorSide);
    }

    public function rejectAndSendBack(VpCase $case, string $reason, int $actorId, string $actorSide = 'employer'): VpCase
    {
        $this->assertCaseActionable($case);
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        $active = $case->activeStep();
        if (! $active || ! VisaLiability::actorCanAct($actorSide, $active->assignee)) {
            throw new InvalidArgumentException('You can only send back while your review step is active.');
        }

        $previous = $case->steps()
            ->where('sort_order', '<', $active->sort_order)
            ->where('assignee', 'seeker')
            ->orderByDesc('sort_order')
            ->first();

        if (! $previous) {
            throw new InvalidArgumentException('No seeker step to send back.');
        }

        $active->update(['status' => 'pending', 'rejection_reason' => null]);
        $previous->update([
            'status' => 'active',
            'rejection_reason' => $reason,
            'completed_at' => null,
        ]);
        $case->update(['current_step_index' => $previous->sort_order]);

        $this->log($case, 'sent_back', $reason, $actorId, [
            'reopened_step_id' => $previous->id,
        ]);

        $case->load(['steps', 'candidate.user', 'job']);
        $this->notifier->stepSentBack($case, $previous, $reason);

        return $case->fresh(['steps.requirements.answer', 'steps.requirements.file']);
    }

    public function reviewRequirement(VpCase $case, int $requirementId, string $decision, ?string $reason, int $actorId): VpCase
    {
        $this->assertCaseActionable($case);

        $requirement = VpCaseRequirement::query()
            ->with(['step', 'file', 'answer'])
            ->whereHas('step', fn ($q) => $q->where('vp_case_id', $case->id))
            ->findOrFail($requirementId);

        if ($requirement->step->assignee !== VisaLiability::SEEKER) {
            throw new InvalidArgumentException('Only candidate submissions can be reviewed.');
        }

        if (! in_array($decision, ['approve', 'reject'], true)) {
            throw new InvalidArgumentException('Invalid review decision.');
        }

        if ($decision === 'reject' && trim((string) $reason) === '') {
            throw new InvalidArgumentException('A reason is required when rejecting a document.');
        }

        $hasSubmission = ($requirement->type === 'file' && $requirement->file)
            || ($requirement->type !== 'file' && $requirement->answer);
        if (! $hasSubmission) {
            throw new InvalidArgumentException('Nothing has been submitted for this requirement yet.');
        }

        $reviewData = [
            'review_status' => $decision === 'approve' ? 'approved' : 'rejected',
            'review_reason' => $decision === 'reject' ? trim((string) $reason) : null,
            'reviewed_by' => $actorId,
            'reviewed_at' => now(),
        ];

        if ($requirement->type === 'file') {
            $requirement->file->update($reviewData);
        } else {
            $requirement->answer->update($reviewData);
        }

        if ($decision === 'reject') {
            $this->reopenSeekerStepForReview($case, $requirement->step, $reviewData['review_reason'], $actorId, $requirement);
        } else {
            $this->log($case, 'doc_approved', 'Approved candidate submission: '.$requirement->label, $actorId, [
                'requirement_id' => $requirement->id,
            ]);
            $case->load(['candidate.user', 'company.user', 'job', 'nominatedWorker']);
            $this->notifier->documentApproved($case, $requirement->step, $requirement);
        }

        return $case->fresh(['steps.requirements.answer', 'steps.requirements.file', 'candidate.user', 'job']);
    }

    protected function reopenSeekerStepForReview(VpCase $case, VpCaseStep $seekerStep, string $reason, int $actorId, VpCaseRequirement $requirement): void
    {
        $case->load('steps');

        foreach ($case->steps as $step) {
            if ($step->sort_order > $seekerStep->sort_order && in_array($step->status, ['active', 'completed'], true)) {
                $step->update([
                    'status' => 'pending',
                    'completed_at' => null,
                    'rejection_reason' => null,
                ]);
            }
        }

        $seekerStep->update([
            'status' => 'active',
            'completed_at' => null,
            'rejection_reason' => $reason,
        ]);

        $case->update(['current_step_index' => $seekerStep->sort_order]);

        $this->log($case, 'doc_rejected', 'Rejected candidate submission: '.$requirement->label.' — '.$reason, $actorId, [
            'requirement_id' => $requirement->id,
            'step_id' => $seekerStep->id,
        ]);

        $case->load(['candidate.user', 'job']);
        $this->notifier->documentRejected($case, $seekerStep, $requirement, $reason);
    }

    public function cancelCase(VpCase $case, string $reason, int $actorId): VpCase
    {
        if ($case->status !== 'in_progress') {
            throw new InvalidArgumentException('Only in-progress cases can be cancelled.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A cancel reason is required.');
        }

        $case->update([
            'status' => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_at' => now(),
        ]);

        $case->steps()->whereIn('status', ['active', 'pending'])->update(['status' => 'frozen']);

        $this->log($case, 'cancelled', $reason, $actorId);
        $case->load(['candidate.user', 'company.user', 'job']);
        $this->notifier->caseCancelled($case, $reason);

        return $case;
    }

    /**
     * Restart after cancel: purge files from cancelled attempt and snapshot fresh case.
     */
    public function restartCase(VpCase $cancelled, int $startedByUserId): VpCase
    {
        if ($cancelled->status !== 'cancelled') {
            throw new InvalidArgumentException('Only cancelled cases can be restarted.');
        }

        $application = AppliedJob::find($cancelled->applied_job_id);
        if (! $application) {
            throw new InvalidArgumentException('Original application not found.');
        }

        $this->purgeCaseFiles($cancelled);

        return $this->startCase(
            $application,
            $cancelled->country_name,
            $startedByUserId,
            $cancelled->company_id,
            $cancelled->agency_id
        );
    }

    public function purgeCaseFiles(VpCase $case): void
    {
        foreach ($case->files as $file) {
            $relative = ltrim((string) $file->path, '/');
            if (Storage::disk('public')->exists($relative)) {
                Storage::disk('public')->delete($relative);
            } elseif (file_exists(public_path($relative))) {
                @unlink(public_path($relative));
            }
            $file->delete();
        }
    }

    protected function advanceOrComplete(VpCase $case, int $actorId, ?VpCaseStep $completedStep = null, string $completedBySide = 'employer'): VpCase
    {
        $case->load('steps');
        $next = $case->steps->where('status', 'pending')->sortBy('sort_order')->first();

        if (! $next) {
            $case->update([
                'status' => 'completed',
                'completed_at' => now(),
                'current_step_index' => $case->steps->count() - 1,
            ]);
            if ($case->nominated_worker_id) {
                NominatedWorker::where('id', $case->nominated_worker_id)->update(['status' => 'visa_completed']);
            }
            $this->log($case, 'completed', 'All visa processing steps completed', $actorId);
            $case->load(['candidate.user', 'company.user', 'job', 'nominatedWorker']);
            if ($completedStep) {
                $this->notifier->stepProgressed($case, $completedStep, null, $completedBySide);
            }
            $this->notifier->caseCompleted($case);

            return $case->fresh(['steps.requirements.answer', 'steps.requirements.file']);
        }

        $next->update(['status' => 'active']);
        $case->update(['current_step_index' => $next->sort_order]);
        $this->log($case, 'turn', 'Active step: '.$next->name, $actorId, [
            'step_id' => $next->id,
            'assignee' => $next->assignee,
        ]);

        $case->load(['steps', 'candidate.user', 'company.user', 'job', 'nominatedWorker']);
        if ($completedStep) {
            $this->notifier->stepProgressed($case, $completedStep, $next, $completedBySide);
        } else {
            $this->notifier->stepBecameActive($case, $next);
        }

        return $case->fresh(['steps.requirements.answer', 'steps.requirements.file']);
    }

    protected function storeFile(VpCase $case, VpCaseRequirement $req, UploadedFile $upload, int $actorId, string $actorSide = 'employer'): VpCaseFile
    {
        $existing = $req->file;
        if ($existing) {
            $rel = ltrim((string) $existing->path, '/');
            if (Storage::disk('public')->exists($rel)) {
                Storage::disk('public')->delete($rel);
            }
            $existing->delete();
        }

        $path = $upload->store('visa-processing/'.$case->id, 'public');

        return VpCaseFile::create([
            'vp_case_id' => $case->id,
            'vp_case_requirement_id' => $req->id,
            'uploaded_by' => $actorId,
            'original_name' => $upload->getClientOriginalName(),
            'path' => $path,
            'size' => $upload->getSize() ?: 0,
            'mime' => $upload->getClientMimeType(),
            'review_status' => $actorSide === 'seeker' ? 'pending' : 'approved',
            'review_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => $actorSide === 'seeker' ? null : now(),
        ]);
    }

    protected function assertCaseActionable(VpCase $case): void
    {
        if ($case->status !== 'in_progress') {
            throw new InvalidArgumentException('This visa case is frozen or finished.');
        }
    }

    protected function log(VpCase $case, string $type, string $message, ?int $actorId = null, array $meta = []): void
    {
        VpCaseEvent::create([
            'vp_case_id' => $case->id,
            'event_type' => $type,
            'message' => $message,
            'actor_id' => $actorId,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Record flight/departure details and mark a completed case as Deployed;
     * auto-notifies the employer. Callable by employer or agency owners.
     */
    public function markDeployed(VpCase $case, array $flightDetails, int $actorId): VpCase
    {
        if ($case->status !== 'completed') {
            throw new InvalidArgumentException('Only a completed visa case can be marked as deployed.');
        }

        if ($case->deployed_at) {
            throw new InvalidArgumentException('This case is already marked as deployed.');
        }

        $case->update([
            'flight_airline' => $flightDetails['flight_airline'] ?? null,
            'flight_ticket_number' => $flightDetails['flight_ticket_number'] ?? null,
            'flight_date' => $flightDetails['flight_date'] ?? null,
            'deployed_at' => now(),
        ]);

        if ($case->nominated_worker_id) {
            NominatedWorker::where('id', $case->nominated_worker_id)->update(['status' => 'deployed']);
        }

        $this->log($case, 'deployed', 'Worker marked as deployed to '.$case->country_name, $actorId, $flightDetails);

        $case->load(['candidate.user', 'company.user', 'job', 'nominatedWorker']);
        $this->notifier->workerDeployed($case);

        return $case->fresh();
    }

    public function caseForCandidate(int $candidateId): ?VpCase
    {
        return VpCase::query()
            ->where('candidate_id', $candidateId)
            ->with(['steps.requirements.answer', 'steps.requirements.file', 'job'])
            ->latest()
            ->first();
    }
}
