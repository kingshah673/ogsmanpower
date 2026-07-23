<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Language\Entities\Language;
use Modules\Location\Entities\Country;

class Candidate extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $appends = ['full_address'];

    protected $casts = [
        'date_of_birth' => 'datetime',
        'allow_in_search' => 'boolean',
        'public_code_meta' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($candidate) {
            $candidate->profile_complete = $candidate->calculateProfileCompletion();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getPhotoAttribute($photo)
    {
        if ($photo == null) {
            return asset('backend/image/default.png');
        }

        return asset($photo);
    }

    public function getBirthDateAttribute($value)
    {
        // Support both 'birth_date' column and legacy 'dob' column
        return $value ?? $this->getRawOriginal('dob') ?? null;
    }

    /**
     * Seeker age for job age-range matching (profile age column or birth date).
     */
    public function resolvedAge(): ?int
    {
        $attrs = $this->getAttributes();

        if (array_key_exists('age', $attrs) && filled($attrs['age']) && (int) $attrs['age'] > 0) {
            return (int) $attrs['age'];
        }

        $birth = $this->birth_date;
        if (! filled($birth)) {
            return null;
        }

        try {
            $age = Carbon::parse($birth)->age;

            return $age > 0 ? $age : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Whether this seeker fits a job's min/max age (when the job enforces age).
     */
    public function fitsJobAgeRange(Job $job): bool
    {
        return $job->acceptsCandidateAge($this->resolvedAge());
    }

    public function getCountryAttribute($value)
    {
        // Raw DB column wins if populated
        if ($value) {
            return $value;
        }
        // Fall back to the related Country model name
        return $this->expected_country?->name ?? null;
    }

    public function getFullAddressAttribute()
    {
        $country = $this->country;
        $region = $this->region;

        $extra = $region ? ' , ' : '';

        return $region . $extra . $country;
    }

    public function getCvUrlAttribute()
    {
        if ($this->cv == null) {
            return '';
        }

        return route('website.candidate.download.cv', $this->id);
    }

    /*
    |--------------------------------------------------------------------------
    | Profile Completion
    |--------------------------------------------------------------------------
    */

    public function calculateProfileCompletion()
    {
        $sections = $this->profileCompletionSections();
        $total = count($sections);
        $done = count(array_filter($sections, fn ($s) => $s['complete']));

        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }

    /**
     * Profile sections used for the completion bar (matches settings UI).
     *
     * @return array<int, array{key: string, label: string, hint: string, anchor: string, complete: bool}>
     */
    public function profileCompletionSections(): array
    {
        $jr = JobRequirement::where('candidate_id', $this->id)->first();
        $jobIds = cw_json_array($jr?->jobs);
        $industryIds = cw_json_array($jr?->industries);

        $sections = [
            [
                'key' => 'basic',
                'label' => 'Basic information',
                'hint' => $this->basicInfoHint(),
                'anchor' => 'basic-info-sec',
                'complete' => $this->hasBasicInfo(),
            ],
            [
                'key' => 'location',
                'label' => 'Location',
                'hint' => 'Select your country, state/region, and city in Basic Information.',
                'anchor' => 'basic-info-sec',
                'complete' => $this->hasLocation(),
            ],
            [
                'key' => 'photo',
                'label' => 'Profile photo',
                'hint' => 'Upload a profile picture in Basic Information.',
                'anchor' => 'basic-info-sec',
                'complete' => filled($this->getRawOriginal('photo')),
            ],
            [
                'key' => 'passport',
                'label' => 'Passport details',
                'hint' => 'Add passport number, issue/expiry dates, and place of issue in Basic Information.',
                'anchor' => 'basic-info-sec',
                'complete' => filled($this->passport_number),
            ],
            [
                'key' => 'cv',
                'label' => 'CV / Resume file',
                'hint' => 'Upload your CV using Smart Profile Auto-Builder at the top of Settings.',
                'anchor' => 'cv-upload-sec',
                'complete' => $this->hasCvOnFile(),
            ],
            [
                'key' => 'summary',
                'label' => 'Professional summary',
                'hint' => 'Write a short bio in the Summary section.',
                'anchor' => 'pro-details-sec',
                'complete' => filled(strip_tags((string) ($this->bio ?? ''))),
            ],
            [
                'key' => 'skills',
                'label' => 'Skills',
                'hint' => 'Add your skills in the Skills section.',
                'anchor' => 'skills-sec',
                'complete' => $this->skills()->exists(),
            ],
            [
                'key' => 'languages',
                'label' => 'Languages',
                'hint' => 'Add languages you speak in the Language section.',
                'anchor' => 'languages-sec',
                'complete' => $this->languages()->exists(),
            ],
            [
                'key' => 'experience',
                'label' => 'Work experience',
                'hint' => 'Add at least one work experience entry.',
                'anchor' => 'work-exp-sec',
                'complete' => $this->experiences()->exists(),
            ],
            [
                'key' => 'education',
                'label' => 'Education history',
                'hint' => 'Add at least one education record (or set education level in Basic Information).',
                'anchor' => 'work-exp-sec',
                'complete' => $this->educations()->exists() || (bool) $this->education_id,
            ],
            [
                'key' => 'contact',
                'label' => 'Contact details',
                'hint' => 'Add phone number and email in Contact Setting.',
                'anchor' => 'contact-sec',
                'complete' => $this->hasContactInfo(),
            ],
            [
                'key' => 'job_preferences',
                'label' => 'Job requirements',
                'hint' => $this->jobPreferencesHint($jr, $jobIds, $industryIds),
                'anchor' => 'job-requirements-sec',
                'complete' => $this->hasJobPreferences(),
            ],
        ];

        return $sections;
    }

    /**
     * Incomplete sections only — for “complete your profile” prompts.
     *
     * @return array<int, array{key: string, label: string, hint: string, anchor: string, complete: bool}>
     */
    public function profileCompletionMissing(): array
    {
        return array_values(array_filter(
            $this->profileCompletionSections(),
            fn ($section) => ! $section['complete']
        ));
    }

    protected function jobPreferencesHint(?JobRequirement $jr, array $jobIds, array $industryIds): string
    {
        $parts = [];
        if (! $jr || ! filled($jr->region)) {
            $parts[] = 'select a region';
        }
        if (! $jr || $jr->salary === null || $jr->salary === '') {
            $parts[] = 'enter expected salary';
        }
        if (count($jobIds) === 0) {
            $parts[] = 'add job titles (at least one)';
        }
        if (count($industryIds) === 0) {
            $parts[] = 'add industries (at least one)';
        }

        if ($parts === []) {
            return 'Complete job titles, industries, region, and salary in Job Requirements.';
        }

        return 'In Job Requirements: '.implode(', ', $parts).'.';
    }

    protected function basicInfoHint(): string
    {
        $parts = [];
        if (! $this->profession_id) {
            $parts[] = 'profession';
        }
        if (! $this->experience_id) {
            $parts[] = 'experience level';
        }
        if (! $this->education_id) {
            $parts[] = 'education level';
        }
        if (! filled($this->gender)) {
            $parts[] = 'gender';
        }
        if (! filled($this->marital_status)) {
            $parts[] = 'marital status';
        }
        if (! filled($this->birth_date)) {
            $parts[] = 'date of birth';
        }
        if (! filled($this->status)) {
            $parts[] = 'availability';
        }

        if ($parts === []) {
            return 'Open Basic Information, confirm all fields, and click Save.';
        }

        return 'In Basic Information: save '.implode(', ', $parts).'.';
    }

    protected function hasBasicInfo(): bool
    {
        return (bool) $this->user_id
            && (bool) $this->profession_id
            && (bool) $this->experience_id
            && (bool) $this->education_id
            && filled($this->gender)
            && filled($this->marital_status)
            && filled($this->birth_date)
            && filled($this->status);
    }

    /**
     * Country name for the basic-info location cascade (raw column, then search_country_id, then country_id).
     */
    public function basicLocationCountry(): ?string
    {
        if (filled($this->getRawOriginal('country'))) {
            return $this->getRawOriginal('country');
        }

        if ($this->search_country_id) {
            return SearchCountry::find($this->search_country_id)?->name;
        }

        return $this->expected_country?->name;
    }

    protected function hasLocation(): bool
    {
        $hasCountry = filled($this->getRawOriginal('country'))
            || filled($this->search_country_id)
            || filled($this->country_id);

        return $hasCountry
            && filled($this->region)
            && filled($this->district);
    }

    protected function hasCvOnFile(): bool
    {
        return filled($this->getRawOriginal('cv'))
            || filled($this->resume_format)
            || $this->resumes()->exists();
    }

    protected function hasContactInfo(): bool
    {
        if (! $this->user_id) {
            return false;
        }

        $contact = ContactInfo::where('user_id', $this->user_id)->first();

        return $contact
            && filled($contact->phone)
            && filled($contact->email);
    }

    protected function hasJobPreferences(): bool
    {
        $jr = JobRequirement::where('candidate_id', $this->id)->first();

        if (! $jr) {
            return false;
        }

        return filled($jr->region)
            && ($jr->salary !== null && $jr->salary !== '')
            && count(cw_json_array($jr->jobs)) > 0
            && count(cw_json_array($jr->industries)) > 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('visibility', 1)->whereHas('user', function ($q) {
            $q->whereStatus(1);
        });
    }

    public function scopeInactive($query)
    {
        return $query->where('visibility', 0)->whereHas('user', function ($q) {
            $q->whereStatus(0);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expected_country()
    {
        return $this->belongsTo(Country::class,'country_id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class,'profession_id');
    }

    public function experience()
    {
        return $this->belongsTo(Experience::class,'experience_id');
    }

    public function education()
    {
        return $this->belongsTo(Education::class,'education_id');
    }

    public function agent()
    {
        return $this->belongsTo(Admin::class,'admin_id');
    }

    public function bookmarkJobs()
    {
        return $this->belongsToMany(Job::class,'bookmark_candidate_job')
            ->with('company','category','job_type:id');
    }

    public function bookmarkCompanies()
    {
        return $this->belongsToMany(Company::class,'bookmark_candidate_company');
    }

    public function attributes()
    {
        return $this->hasMany(CandidateAttribute::class);
    }

    public function bookmarkCandidates()
    {
        return $this->belongsToMany(Company::class,'bookmark_company')->withTimestamps();
    }

    public function appliedJobs()
    {
        return $this->belongsToMany(Job::class,'applied_jobs')
            ->with('company','job_type:id')
            ->withTimestamps();
    }

    public function resumes()
    {
        return $this->hasMany(CandidateResume::class,'candidate_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class,'candidate_skill');
    }

    public function languages()
    {
        return $this->belongsToMany(CandidateLanguage::class,'candidate_language');
    }

    public function experiences()
    {
        return $this->hasMany(CandidateExperience::class,'candidate_id');
    }

    public function educations()
    {
        return $this->hasMany(CandidateEducation::class,'candidate_id');
    }
    public function jobRole()
{
    return $this->belongsTo(JobRole::class,'job_role_id');
}

    public function coverLetter()
    {
        return $this->hasOne(AppliedJob::class);
    }

    public function socialInfo(): HasMany
    {
        return $this->hasMany(SocialLink::class,'user_id');
    }

    public function already_views()
    {
        return $this->hasMany(CandidateCvView::class,'candidate_id','id');
    }

    public function jobRoleAlerts()
    {
        return $this->hasMany(CandidateJobAlert::class,'candidate_id','id');
    }

    public function candidateSubscription()
    {
        return $this->hasOne(CandidateSubscription::class,'candidate_id');
    }

    public function getCVPath()
    {
        return $this->hasOne(CandidateResume::class);
    }

    public function documentRecord(): HasMany
    {
        return $this->hasMany(CandidateDocument::class, 'candidate_id');
    }
}