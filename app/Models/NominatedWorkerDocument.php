<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominatedWorkerDocument extends Model
{
    protected $guarded = [];

    protected $casts = [
        'extracted_fields' => 'array',
        'match_confidence' => 'float',
    ];

    public function worker(): BelongsTo
    {
        return $this->belongsTo(NominatedWorker::class, 'nominated_worker_id');
    }

    public function matchedWorker(): BelongsTo
    {
        return $this->belongsTo(NominatedWorker::class, 'matched_worker_id');
    }
}
