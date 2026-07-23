<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\WorkflowService;
use App\Services\SLAService;

class VisaCase extends Model
{
    protected $table = 'visa_cases';

    protected $fillable = [
        'candidate_id',
        'company_id',
        'agency_id',
        'agent_id',
        'created_by',
        'job_id',
        'country',
        'visa_type',
        'status',
        'stage_order',
        'current_stage_key',
        'stage_started_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function documents()
    {
        return $this->hasMany(VisaDocument::class, 'case_id');
    }

    public function tasks()
    {
        return $this->hasMany(CaseTask::class, 'case_id');
    }

    public function logs()
    {
        return $this->hasMany(VisaCaseLog::class, 'case_id');
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
    
    public function getDelayAttribute()
{
    return SLAService::checkDelay($this);
}

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    // Dynamic Progress %
    public function getProgressAttribute()
    {
        return WorkflowService::getProgress($this->current_stage_key);
    }
}