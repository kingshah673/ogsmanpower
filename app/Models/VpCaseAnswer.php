<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpCaseAnswer extends Model
{
    protected $table = 'vp_case_answers';

    protected $guarded = [];

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(VpCaseRequirement::class, 'vp_case_requirement_id');
    }
}
