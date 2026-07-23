<?php

namespace App\Services\NominatedWorkers;

use App\Models\Agency;
use App\Models\NominatedBatchAgencyResponse;
use App\Models\NominatedWorkerBatch;
use App\Models\VisaFlow;
use App\Services\VisaProcessing\VisaProcessingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class NominatedBatchService
{
    public function __construct(
        protected VisaProcessingService $visa
    ) {}

    public function attachPublishedFlow(NominatedWorkerBatch $batch): NominatedWorkerBatch
    {
        $flow = $this->visa->findActiveFlowForCountry($batch->country_name, $batch->search_country_id);
        if (! $flow) {
            throw new InvalidArgumentException(
                'No published visa flow for '.$batch->country_name.'. Ask admin to configure and publish one.'
            );
        }

        $batch->update([
            'visa_flow_id' => $flow->id,
        ]);

        return $batch->fresh('flow.steps');
    }

    public function submitForApproval(NominatedWorkerBatch $batch): NominatedWorkerBatch
    {
        if (! $batch->isEditableByEmployer()) {
            throw new InvalidArgumentException('This batch cannot be submitted in its current status.');
        }
        if ($batch->workers()->count() < 1) {
            throw new InvalidArgumentException('Add at least one nominated worker before submitting.');
        }

        $this->attachPublishedFlow($batch);

        $batch->update([
            'status' => NominatedWorkerBatch::STATUS_PENDING_APPROVAL,
            'admin_comment' => null,
        ]);

        return $batch->fresh();
    }

    public function approve(NominatedWorkerBatch $batch, int $adminUserId): NominatedWorkerBatch
    {
        if ($batch->status !== NominatedWorkerBatch::STATUS_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Only pending batches can be approved.');
        }

        $flow = VisaFlow::query()->with('activeSteps')->find($batch->visa_flow_id)
            ?: $this->visa->findActiveFlowForCountry($batch->country_name, $batch->search_country_id);

        if (! $flow || $flow->activeSteps->isEmpty()) {
            throw new InvalidArgumentException('Cannot approve: no published visa flow with steps.');
        }

        $nextStatus = NominatedWorkerBatch::STATUS_AWAITING_AGENCY;
        if ($batch->assignment_mode === 'direct' && $batch->agency_id) {
            // Still await explicit accept unless we auto-activate — plan says accept → active
            $nextStatus = NominatedWorkerBatch::STATUS_AWAITING_AGENCY;
        }

        $batch->update([
            'visa_flow_id' => $flow->id,
            'frozen_flow_version' => max(1, (int) $flow->version),
            'status' => $nextStatus,
            'approved_by' => $adminUserId,
            'approved_at' => now(),
            'admin_comment' => null,
        ]);

        return $batch->fresh();
    }

    public function returnToEmployer(NominatedWorkerBatch $batch, string $comment): NominatedWorkerBatch
    {
        if ($batch->status !== NominatedWorkerBatch::STATUS_PENDING_APPROVAL) {
            throw new InvalidArgumentException('Only pending batches can be returned.');
        }
        if (trim($comment) === '') {
            throw new InvalidArgumentException('A comment is required when returning a batch.');
        }

        $batch->update([
            'status' => NominatedWorkerBatch::STATUS_RETURNED,
            'admin_comment' => $comment,
        ]);

        return $batch->fresh();
    }

    public function agencyRespond(
        NominatedWorkerBatch $batch,
        Agency $agency,
        string $decision,
        ?string $reason,
        int $userId
    ): NominatedWorkerBatch {
        if ($batch->status !== NominatedWorkerBatch::STATUS_AWAITING_AGENCY) {
            throw new InvalidArgumentException('This batch is not awaiting agency response.');
        }

        if ($batch->assignment_mode === 'direct' && (int) $batch->agency_id !== (int) $agency->id) {
            throw new InvalidArgumentException('This batch is assigned to another agency.');
        }

        $decision = strtolower($decision);
        if (! in_array($decision, ['accepted', 'declined'], true)) {
            throw new InvalidArgumentException('Invalid decision.');
        }
        if ($decision === 'declined' && trim((string) $reason) === '') {
            throw new InvalidArgumentException('A reason is required when declining.');
        }

        return DB::transaction(function () use ($batch, $agency, $decision, $reason, $userId) {
            NominatedBatchAgencyResponse::updateOrCreate(
                ['batch_id' => $batch->id, 'agency_id' => $agency->id],
                [
                    'status' => $decision,
                    'reason' => $reason,
                    'responded_by' => $userId,
                ]
            );

            if ($decision === 'accepted') {
                $batch->update([
                    'agency_id' => $agency->id,
                    'status' => NominatedWorkerBatch::STATUS_ACTIVE,
                ]);
                $batch->workers()->update(['agency_id' => $agency->id]);
                $batch = $batch->fresh(['workers']);
                $this->visa->startCasesForBatch($batch, $userId);
            }

            return $batch->fresh(['workers', 'agencyResponses']);
        });
    }

    /** Open-all batches visible to any approved agency that has not declined. */
    public function openBatchesQueryForAgency(int $agencyId)
    {
        return NominatedWorkerBatch::query()
            ->where('status', NominatedWorkerBatch::STATUS_AWAITING_AGENCY)
            ->where(function ($q) use ($agencyId) {
                $q->where(function ($q2) use ($agencyId) {
                    $q2->where('assignment_mode', 'direct')->where('agency_id', $agencyId);
                })->orWhere('assignment_mode', 'open_all');
            })
            ->whereDoesntHave('agencyResponses', function ($q) use ($agencyId) {
                $q->where('agency_id', $agencyId)->where('status', 'declined');
            });
    }
}
