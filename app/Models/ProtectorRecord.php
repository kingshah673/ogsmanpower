<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProtectorRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expiry_date' => 'date',
        'submitted_at' => 'datetime',
        'cleared_at' => 'datetime',
    ];

    public const SUBMISSION_STATUSES = ['not_submitted', 'submitted', 'under_review'];

    public const CLEARANCE_STATUSES = ['pending', 'cleared', 'rejected'];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function clearanceBadgeClass(): string
    {
        return match ($this->clearance_status) {
            'cleared' => 'badge bg-success',
            'rejected' => 'badge bg-danger',
            default => 'badge bg-warning text-dark',
        };
    }
}
