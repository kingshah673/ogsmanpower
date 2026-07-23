<?php

namespace App\Services\Agency;

use App\Models\AppliedJob;
use App\Models\Commission;

class CommissionAccrualService
{
    public const DEFAULT_RATE_PERCENT = 10.0;

    /**
     * Accrue a pending commission for the sourcing agency when a candidate
     * they placed is marked Selected. Idempotent — one commission per
     * applied_job_id.
     */
    public function accrueForSelection(AppliedJob $application): ?Commission
    {
        if (! $application->agency_id) {
            return null;
        }

        $existing = Commission::where('applied_job_id', $application->id)->first();
        if ($existing) {
            return $existing;
        }

        $application->loadMissing('job');
        $job = $application->job;
        $salaryBase = (float) ($job?->max_salary ?: $job?->min_salary ?: 0);
        $rate = (float) (setting('agency_commission_rate') ?: self::DEFAULT_RATE_PERCENT);
        $amount = round($salaryBase * $rate / 100, 2);

        return Commission::create([
            'agency_id' => $application->agency_id,
            'applied_job_id' => $application->id,
            'candidate_id' => $application->candidate_id,
            'job_id' => $application->job_id,
            'amount' => $amount,
            'rate' => $rate,
            'currency' => setting('currency_symbol') ?: 'USD',
            'type' => 'placement',
            'status' => Commission::STATUS_PENDING,
        ]);
    }
}
