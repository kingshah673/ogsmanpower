<?php

namespace App\Models;

use App\Events\JobDeleted;
use App\Events\JobSaved;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\Agency;

class Job extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['days_remaining', 'deadline_active', 'can_apply', 'full_address'];

    protected $casts = [
        'bookmarked' => 'boolean',
        'applied' => 'boolean',
        'can_apply' => 'boolean',
        'highlight_until' => 'date:Y-m-d',
        'featured_until' => 'date:Y-m-d',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'saved' => JobSaved::class,
        'deleted' => JobDeleted::class,
    ];

    /**
     * Set the job's title.
     *
     * @return void
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        $value_slug = Str::slug($value);
        $is_exists = Job::whereSlug($value_slug)->where('id', '!=', $this->id)->exists();

        if ($is_exists) {
            $this->attributes['slug'] = $value_slug.'-'.time().'-'.uniqid();
        } else {
            $this->attributes['slug'] = $value_slug;
        }
    }

    /**
     * Get the highlight attribute
     *
     * @return void
     */
    public function getHighlightAttribute($value)
    {
        if ($value) {
            $days = cache()->remember('highlight_job_days', 60 * 24 * 30, function () {
                return Setting::select('highlight_job_days')->value('highlight_job_days');
            });

            if ($days > 0 && $this->attributes['highlight_until']) {
                $is_active = Carbon::parse($this->attributes['highlight_until'])->isFuture();

                if (! $is_active) {
                    $this->update(['highlight' => 0]);

                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get the featured attribute
     *
     * @return void
     */
    public function getFeaturedAttribute($value)
    {
        if ($this->attributes['featured']) {
            $days = cache()->remember('featured_job_days', 60 * 24 * 30, function () {
                return Setting::select('featured_job_days')->value('featured_job_days');
            });

            if ($days > 0 && $this->attributes['featured_until']) {
                $is_active = Carbon::parse($this->attributes['featured_until'])->isFuture();

                if (! $is_active) {
                    $this->update(['featured' => 0]);

                    return false;
                }
            }

            return true;
        }

        return false;
    }
public function agencies()
{
    return $this->belongsToMany(
        \App\Models\Agency::class,
        'job_agencies',
        'job_id',
        'agency_id'
    )->withPivot(['status', 'decline_reason', 'responded_at'])->withTimestamps();
}
public function subAgencies()
{
    return $this->belongsToMany(
        \App\Models\Agency::class,
        'job_sub_agencies',
        'job_id',
        'sub_agency_id'
    );
}

public function agents()
{
    return $this->belongsToMany(
        \App\Models\User::class,
        'job_agents',
        'job_id',
        'agent_id'
    );
}


    /**
     * Get the job's full address.
     *
     * @return string
     */
    public function getFullAddressAttribute()
    {
        $country = $this->country;
        $region = $this->region;
        $extra = $region != null ? ' , ' : '';

        return $region.$extra.$country;
    }

    /**
     * Get the job's days remaining
     *
     * @return mixed
     */
    public function getDaysRemainingAttribute()
    {
        return Carbon::now(config('templatecookie.timezone'))->parse($this->deadline)->diffForHumans(null, true, true, 2);
    }

    /**
     * Get the can apply job's attribute.
     *
     * @return bool
     */
    public function getCanApplyAttribute()
    {
        if ($this->apply_on === 'app') {
            return true;
        } else {
            return false;
        }
    }
    public function assignments()
{
    return $this->hasMany(JobAssignment::class);
}

    /**
     * Get the deadline active attribute
     *
     * @return mixed
     */
    public function getDeadlineActiveAttribute()
    {
        return Carbon::parse($this->deadline)->format('Y-m-d') >= Carbon::now()->toDateString();
    }

    /**
     * Get the without edited job scope
     *
     * @return mixed
     */
    public function scopeWithoutEdited($query)
    {
        return $query->where('waiting_for_edit_approval', false);
    }

    /**
     * Get the edited job scope
     *
     * @return mixed
     */
    public function scopeEdited($query)
    {
        return $query->where('waiting_for_edit_approval', true);
    }

    /**
     * Get the active job scope
     *
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the expired job scope
     *
     * @return mixed
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Get the inactive job scope
     *
     * @return mixed
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the pending job scope
     *
     * @return mixed
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the open job scope
     *
     * @return mixed
     */
    public function scopeOpenPosition($query)
    {
        return $query->where('status', 'active')->where('deadline', '>=', Carbon::now()->toDateString());
    }

    /**
     * Jobs that should appear on the public /jobs listing.
     * Employer direct posts may have a null job_roles value; agency/admin posts use CSV roles.
     */
    public function scopePublicListing($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('job_roles')
                ->orWhere('job_roles', '')
                ->orWhereRaw("FIND_IN_SET('public', job_roles)");
        });
    }

    /**
     * Restrict listings for a seeker to jobs without an age gate, or jobs whose min/max age includes $age.
     * Age gate is only when age_limit is enabled (matches job eligibility / apply rules).
     * When $age is null, age-gated jobs are hidden.
     */
    public function scopeMatchingCandidateAge($query, ?int $age)
    {
        if (! \Illuminate\Support\Facades\Schema::hasColumn($this->getTable(), 'age_limit')) {
            return $query;
        }

        return $query->where(function ($q) use ($age) {
            // Jobs that do not enforce age
            $q->where(function ($open) {
                $open->whereNull('age_limit')->orWhere('age_limit', 0);
            });

            if ($age === null) {
                return;
            }

            // Age-gated jobs the seeker qualifies for
            $q->orWhere(function ($fit) use ($age) {
                $fit->where('age_limit', 1)
                    ->where(function ($min) use ($age) {
                        $min->whereNull('min_age')
                            ->orWhere('min_age', '<=', 0)
                            ->orWhere('min_age', '<=', $age);
                    })
                    ->where(function ($max) use ($age) {
                        $max->whereNull('max_age')
                            ->orWhere('max_age', '<=', 0)
                            ->orWhere('max_age', '>=', $age);
                    });
            });
        });
    }

    /**
     * Whether a seeker's age is allowed for this job.
     */
    public function acceptsCandidateAge(?int $age): bool
    {
        if ((int) ($this->age_limit ?? 0) !== 1) {
            return true;
        }

        if ($age === null) {
            return false;
        }

        $min = (int) ($this->min_age ?? 0);
        $max = (int) ($this->max_age ?? 0);

        if ($min > 0 && $age < $min) {
            return false;
        }

        if ($max > 0 && $age > $max) {
            return false;
        }

        return true;
    }

    /**
     * Get the company/agency jobs scope
     *
     * @return mixed
     */
    public function scopeCompanyJobs($query, $company_id)
    {
        return $query->where('company_id', $company_id);
    }
    
    public function scopeAgencyJobs($query, $agency_id)
    {
        return $query->where('agency_id', $agency_id);
    }

    /**
     * Get the new job scope
     *
     * @return mixed
     */
    public function scopeNewJobs($query)
    {
        return $query->where('status', 'active')->where('created_at', '>=', Carbon::now()->subDays(7)->toDateString());
    }

    /**
     * Get the category that owns the Job
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class);
    }

    /**
     * Get the role that owns the Job
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(JobRole::class, 'role_id');
    }

    /**
     * Get the company/agency that owns the Job
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class)->with('user');
    }
    
    public function agency(): BelongsTo
   {
    return $this->belongsTo(\App\Models\Agency::class)->with('user');
    }

    /**
     * Get all of the bookmarkedCandidates for the Job
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookmarkJobs()
    {
        return $this->belongsToMany(Candidate::class, 'bookmark_candidate_job');
    }

    /**
     * Get all of the appliedJobs for the Job
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function appliedJobs()
    {
        return $this->belongsToMany(Candidate::class, 'applied_jobs')->withPivot('job_id', 'candidate_id')->with('user')->withTimestamps();
    }

    public function allAppliedJobs()
    {
        return $this->hasMany(AppliedJob::class);
    }

    /**
     * Get the experience that owns the Job
     *
     * @return BelongsTo
     */
    public function experience()
    {
        return $this->belongsTo(Experience::class, 'experience_id');
    }

    /**
     * Get the education that owns the Job
     *
     * @return BelongsTo
     */
    public function education()
    {
        return $this->belongsTo(Education::class, 'education_id');
    }

    /**
     * Get the profession that owns the Job
     *
     * @return BelongsTo
     */
    public function profession()
    {
        return $this->belongsTo(Profession::class, 'profession_id');
    }

    /**
     * Get the job type that owns the Job
     *
     * @return BelongsTo
     */
    public function job_type()
    {
        return $this->belongsTo(JobType::class, 'job_type_id');
    }

    /**
     * Get the salary type that owns the Job
     *
     * @return BelongsTo
     */
    public function salary_type()
    {
        return $this->belongsTo(SalaryType::class, 'salary_type_id');
    }

    /**
     * Get the tags that owns the Job
     *
     * @return BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'job_tag');
    }

    /**
     * Get the benefits that owns the Job
     *
     * @return BelongsToMany
     */
    public function benefits()
    {
        return $this->belongsToMany(Benefit::class, 'job_benefit');
    }

    /**
     * Get the skills that owns the Job
     *
     * @return BelongsToMany
     */
    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skills');
    }

    public function questions()
    {
        return $this->belongsToMany(CompanyQuestion::class);
        return $this->belongsToMany(AgencyQuestion::class);
    }

    public function scopeDeadlineActive($query)
    {
        return $query->where('deadline', '>', now());
    }

    public function recordPublicView(): void
    {
        if (auth('user')->check() && authUser()->role === 'company') {
            $company = authUser()->company;
            if ($company && (int) $company->id === (int) $this->company_id) {
                return;
            }
        }

        $this->increment('total_views');
    }

    public function conversionRate(?int $applicants = null): float
    {
        $views = (int) ($this->total_views ?? 0);
        if ($views < 1) {
            return 0.0;
        }

        $applicantCount = $applicants ?? (int) ($this->applied_jobs_count ?? 0);

        return round(($applicantCount / $views) * 100, 1);
    }
}
