<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpCase extends Model
{
    protected $table = 'vp_cases';

    protected $guarded = [];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'flight_date' => 'date',
        'deployed_at' => 'datetime',
    ];

    public function isDeployed(): bool
    {
        return (bool) $this->deployed_at;
    }

    public function steps(): HasMany
    {
        return $this->hasMany(VpCaseStep::class, 'vp_case_id')->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VpCaseEvent::class, 'vp_case_id')->latest();
    }

    public function files(): HasMany
    {
        return $this->hasMany(VpCaseFile::class, 'vp_case_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function appliedJob(): BelongsTo
    {
        return $this->belongsTo(AppliedJob::class);
    }

    public function nominatedWorker(): BelongsTo
    {
        return $this->belongsTo(NominatedWorker::class, 'nominated_worker_id');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(VisaFlow::class, 'visa_flow_id');
    }

    public function activeStep(): ?VpCaseStep
    {
        return $this->steps->firstWhere('status', 'active')
            ?? $this->steps()->where('status', 'active')->first();
    }

    public function progressPercent(): int
    {
        $total = $this->steps()->count();
        if ($total === 0) {
            return 0;
        }
        $done = $this->steps()->where('status', 'completed')->count();

        return (int) round(($done / $total) * 100);
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }
}
