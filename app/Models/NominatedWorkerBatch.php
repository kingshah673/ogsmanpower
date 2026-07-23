<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NominatedWorkerBatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
        'frozen_flow_version' => 'integer',
    ];

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_AWAITING_AGENCY = 'awaiting_agency';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_RETURNED = 'returned';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function flow(): BelongsTo
    {
        return $this->belongsTo(VisaFlow::class, 'visa_flow_id');
    }

    public function searchCountry(): BelongsTo
    {
        return $this->belongsTo(SearchCountry::class, 'search_country_id');
    }

    public function workers(): HasMany
    {
        return $this->hasMany(NominatedWorker::class, 'batch_id');
    }

    public function agencyResponses(): HasMany
    {
        return $this->hasMany(NominatedBatchAgencyResponse::class, 'batch_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditableByEmployer(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_RETURNED], true);
    }
}
