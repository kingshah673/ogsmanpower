<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpCaseFile extends Model
{
    protected $table = 'vp_case_files';

    protected $guarded = [];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VpCase::class, 'vp_case_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(VpCaseRequirement::class, 'vp_case_requirement_id');
    }
}
