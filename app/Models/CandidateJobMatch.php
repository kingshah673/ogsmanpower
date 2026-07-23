<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CandidateJobMatch extends Model
{
    protected $fillable = [

        'candidate_id',

        'job_id',

        'score',

        'skills',

        'remarks'
    ];

    protected $casts = [

        'skills' => 'array'
    ];
}