<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateAttribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'candidate_id',
        'section',
        'definition_id',
        'attribute_name',
        'input_type',
        'attribute_value',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
