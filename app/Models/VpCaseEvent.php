<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpCaseEvent extends Model
{
    protected $table = 'vp_case_events';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VpCase::class, 'vp_case_id');
    }
}
