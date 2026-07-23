<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppliedJob extends Model
{
    use HasFactory;

    protected $table = 'applied_jobs';

    protected $guarded = [];

    /**
     * Columns written by controllers (see config/schema_ensure.php).
     * Keep that config in sync when adding fields here.
     *
     * @var list<string>
     */
    public const EXPECTED_COLUMNS = [
        'admin_id',
        'candidate_id',
        'agency_id',
        'company_id',
        'job_id',
        'agent_id',
        'candidate_resume_id',
        'application_group_id',
        'cover_letter',
        'resume_format',
        'cv_path',
        'answers',
        'years',
        'status',
        'order',
        'interview_date',
        'interview_location',
        'interview_outcome',
        'visa_status',
    ];

    protected $casts = [
        'answers' => 'array',
        'interview_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    /**
     * Job relation
     */
    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * Candidate relation
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class, 'candidate_id');
    }

    /**
     * Agency relation (who submitted)
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    /**
     * Agent relation (if submitted by agent)
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Company relation (job owner)
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function vpCase()
    {
        return $this->hasOne(VpCase::class, 'applied_job_id')->latestOfMany();
    }

    /**
     * Resume relation
     */
    public function resume()
    {
        return $this->belongsTo(CandidateResume::class, 'candidate_resume_id');
    }

    /**
     * Application group (pipeline stage)
     */
    public function applicationGroup()
    {
        return $this->belongsTo(ApplicationGroup::class, 'application_group_id');
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES (VERY USEFUL)
    |--------------------------------------------------------------------------
    */

    /**
     * Filter by job
     */
    public function scopeByJob($query, $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Filter by agency
     */
    public function scopeByAgency($query, $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Filter by agent
     */
    public function scopeByAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Filter by company
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Filter by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS (OPTIONAL BUT POWERFUL)
    |--------------------------------------------------------------------------
    */

    /**
     * Check if submitted by agency
     */
    public function isAgencySubmission()
    {
        return !is_null($this->agency_id) && is_null($this->agent_id);
    }

    /**
     * Check if submitted by agent
     */
    public function isAgentSubmission()
    {
        return !is_null($this->agent_id);
    }

    /**
     * Check if direct candidate apply
     */
    public function isDirectApply()
    {
        return is_null($this->agency_id) && is_null($this->agent_id);
    }
}