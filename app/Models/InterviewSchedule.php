<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterviewSchedule extends Model
{
    protected $fillable = [

        'candidate_id',

        'job_id',

        'company_id',

        'created_by',

        'interview_at',

        'meeting_link',

        'platform',

        'status',

        'remarks'
    ];

    protected $dates = [

        'interview_at'
    ];
}