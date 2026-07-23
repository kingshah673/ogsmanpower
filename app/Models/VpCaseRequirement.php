<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VpCaseRequirement extends Model
{
    protected $table = 'vp_case_requirements';

    protected $guarded = [];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(VpCaseStep::class, 'vp_case_step_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(VpCaseAnswer::class, 'vp_case_requirement_id');
    }

    public function file(): HasOne
    {
        return $this->hasOne(VpCaseFile::class, 'vp_case_requirement_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }
}
