<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaFlowStep extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function flow(): BelongsTo
    {
        return $this->belongsTo(VisaFlow::class, 'visa_flow_id');
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VisaFlowRequirement::class)->orderBy('sort_order');
    }

    public function activeRequirements(): HasMany
    {
        return $this->requirements()->where('is_active', true);
    }
}
