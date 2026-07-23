<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NominatedWorker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(NominatedWorkerDocument::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NominatedWorkerBatch::class, 'batch_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activeVisaCase(): HasOne
    {
        return $this->hasOne(VpCase::class, 'nominated_worker_id')->where('status', 'in_progress');
    }

    public function visaCases(): HasMany
    {
        return $this->hasMany(VpCase::class, 'nominated_worker_id');
    }
}
