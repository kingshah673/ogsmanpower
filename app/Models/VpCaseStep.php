<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpCaseStep extends Model
{
    protected $table = 'vp_case_steps';

    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VpCase::class, 'vp_case_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VpCaseRequirement::class, 'vp_case_step_id')->orderBy('sort_order');
    }
}
