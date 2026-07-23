<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Modules\Blog\Entities\PostComment;

use Spatie\Permission\Traits\HasRoles;

use App\Models\Feature;
use App\Models\UserPlan;
use App\Models\UserFeatureUsage;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /*
    |--------------------------------------------------------------------------
    | GUARDED
    |--------------------------------------------------------------------------
    */

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | APPENDS
    |--------------------------------------------------------------------------
    */

    protected $appends = [

        'image_url'
    ];

    /*
    |--------------------------------------------------------------------------
    | HIDDEN
    |--------------------------------------------------------------------------
    */

    protected $hidden = [

        'password',

        'remember_token',

        'otp_code'
    ];

    /*
    |--------------------------------------------------------------------------
    | CASTS
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'email_verified_at'
            => 'datetime',

        'otp_expiry'
            => 'datetime',

        'is_otp_verified'
            => 'boolean',

        'status'
            => 'boolean',

        'recent_activities_alert'
            => 'boolean',

        'job_expired_alert'
            => 'boolean',

        'new_job_alert'
            => 'boolean',

        'shortlisted_alert'
            => 'boolean'
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOTED
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::created(function ($user) {

            if ($user->is_demo_field) {

                return;
            }

            /*
            |--------------------------------------------------------------------------
            | COMPANY
            |--------------------------------------------------------------------------
            */

            if ($user->role == 'company') {

                $autoActivate = (bool) setting('employer_auto_activation');

                if (!$autoActivate) {
                    $user->update(['status' => 0]);
                }

                $user->company()->create([
                    'industry_type_id'       => IndustryType::first()->id ?? null,
                    'organization_type_id'   => OrganizationType::first()->id ?? null,
                    'team_size_id'           => TeamSize::first()->id ?? null,
                    'is_profile_verified'    => $autoActivate,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | AGENCY
            |--------------------------------------------------------------------------
            */

            elseif ($user->role == 'agency') {

                $autoActivate = (bool) setting('employer_auto_activation');

                if (!$autoActivate) {
                    $user->update(['status' => 0]);
                }

                $user->agency()->create([
                    'industry_type_id'       => IndustryType::first()->id ?? null,
                    'organization_type_id'   => OrganizationType::first()->id ?? null,
                    'team_size_id'           => TeamSize::first()->id ?? null,
                    'is_profile_verified'    => $autoActivate,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | BROKER / DEMAND PARTNER
            |--------------------------------------------------------------------------
            */

            elseif ($user->role == 'broker') {
                $user->broker()->create([
                    'organization_name' => $user->name,
                    'is_profile_verified' => false,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | CANDIDATE
            |--------------------------------------------------------------------------
            */

            elseif ($user->role == 'candidate') {

                if (!setting('candidate_account_auto_activation')) {

                    $user->update([

                        'status' => 0
                    ]);
                }

                $user->candidate()->create([

                    'role_id'
                        => JobRole::first()->id ?? null,

                    'profession_id'
                        => Profession::first()->id ?? null,

                    'experience_id'
                        => Experience::first()->id ?? null,

                    'education_id'
                        => Education::first()->id ?? null
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | CONTACT INFO
            |--------------------------------------------------------------------------
            */

            $user->contactInfo()->create([

                'phone' => '',

                'secondary_phone' => '',

                'email' => '',

                'secondary_email' => ''
            ]);
        });

        static::updated(function ($user) {
            if ($user->role !== 'candidate' || ! $user->wasChanged('name')) {
                return;
            }

            $candidate = $user->candidate;
            if (! $candidate) {
                return;
            }

            $latestJob = optional(
                \App\Models\AppliedJob::query()
                    ->with('job')
                    ->where('candidate_id', $candidate->id)
                    ->where('status', 'selected')
                    ->latest('id')
                    ->first()
            )->job;

            $latestCase = \App\Models\VpCase::query()
                ->where('candidate_id', $candidate->id)
                ->latest('id')
                ->first();

            app(\App\Services\Candidates\CandidatePublicCodeService::class)
                ->sync($candidate, $latestJob, $latestCase);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE ACCESSOR
    |--------------------------------------------------------------------------
    */

    public function getImageAttribute($value)
    {
        return $value
            ?: 'backend/image/default.png';
    }

    /*
    |--------------------------------------------------------------------------
    | IMAGE URL
    |--------------------------------------------------------------------------
    */

    public function getImageUrlAttribute()
    {
        return $this->image

            ? asset($this->image)

            : asset(
                'backend/image/default.png'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY
    |--------------------------------------------------------------------------
    */

    public function company(): HasOne
    {
        return $this->hasOne(
            Company::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AGENCY
    |--------------------------------------------------------------------------
    */

    public function agency(): HasOne
    {
        return $this->hasOne(

            Agency::class,

            'user_id'
        );
    }

    public function broker(): HasOne
    {
        return $this->hasOne(Broker::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | CANDIDATE
    |--------------------------------------------------------------------------
    */

    public function candidate(): HasOne
    {
        return $this->hasOne(

            Candidate::class

        )->withCount(
            'bookmarkCandidates'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONTACT INFO
    |--------------------------------------------------------------------------
    */

    public function contactInfo(): HasOne
    {
        return $this->hasOne(

            ContactInfo::class,

            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SOCIAL INFO
    |--------------------------------------------------------------------------
    */

    public function socialInfo(): HasMany
    {
        return $this->hasMany(

            SocialLink::class,

            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMMENTS
    |--------------------------------------------------------------------------
    */

    public function comments()
    {
        return $this->hasMany(

            PostComment::class,

            'author_id',

            'id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER PLANS
    |--------------------------------------------------------------------------
    */

    public function plans(): HasMany
    {
        return $this->hasMany(

            UserPlan::class,

            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE PLAN
    |--------------------------------------------------------------------------
    */

    public function activePlan(): HasOne
    {
        return $this->hasOne(

            UserPlan::class,

            'user_id'

        )->where(

            'status',
            'active'

        )->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE USAGES
    |--------------------------------------------------------------------------
    */

    public function featureUsages(): HasMany
    {
        return $this->hasMany(

            UserFeatureUsage::class,

            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APPLICATION GROUPS
    |--------------------------------------------------------------------------
    */

    public function application_groups()
    {
        return $this->belongsToMany(

            ApplicationGroup::class,

            'application_group_user',

            'application_group_id',

            'user_id'

        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICATION CODES
    |--------------------------------------------------------------------------
    */

    public function verificationCodes()
    {
        return $this->hasMany(

            VerificationCode::class,

            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MESSAGES
    |--------------------------------------------------------------------------
    */

    public function messages()
    {
        return $this->hasMany(

            Messenger::class,

            'from'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ASSIGNED AGENT JOBS
    |--------------------------------------------------------------------------
    */

    public function assignedAgentJobs()
    {
        return $this->belongsToMany(

            \App\Models\Job::class,

            'job_agents',

            'agent_id',

            'job_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AGENT CANDIDATES
    |--------------------------------------------------------------------------
    */

    public function candidates()
    {
        return $this->hasMany(

            \App\Models\Candidate::class,

            'agent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CONTRACTS
    |--------------------------------------------------------------------------
    */

    public function contracts()
    {
        return $this->hasMany(

            Contract::class,

            'created_by'
        );
    }

    // Agency → sub-agents: all agent users that belong to this agency
    public function agentUsers()
    {
        return $this->hasMany(\App\Models\User::class, 'agency_id');
    }

    // Agent → parent agency user
    public function parentAgencyUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'agency_id');
    }

    // Invites sent by this agency to prospective agents
    public function agentInvites()
    {
        return $this->hasMany(\App\Models\AgentInvite::class, 'agency_user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | CONTRACT PARTIES
    |--------------------------------------------------------------------------
    */

    public function contractParties()
    {
        return $this->hasMany(
            ContractParty::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SIGNATURES
    |--------------------------------------------------------------------------
    */

    public function signatures()
    {
        return $this->hasMany(
            ContractSignature::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    public function companyId()
    {
        return $this->company->id ?? null;
    }

    public function agencyId()
    {
        return $this->agency->id ?? null;
    }

    public function candidateId()
    {
        return $this->candidate->id ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | ROLE HELPERS
    |--------------------------------------------------------------------------
    */

    public function isCompany()
    {
        return $this->role === 'company';
    }

    public function isAgency()
    {
        return $this->role === 'agency';
    }

    public function isCandidate()
    {
        return $this->role === 'candidate';
    }

    public function isAgent()
    {
        return $this->role === 'agent';
    }

    public function isBroker()
    {
        return $this->role === 'broker';
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE VALUE
    |--------------------------------------------------------------------------
    */

    public function feature($slug)
    {
        $activePlan = $this->activePlan;

        if (!$activePlan) {

            return null;
        }

        if (!$activePlan->plan) {

            return null;
        }

        $feature = $activePlan->plan
            ->features()
            ->where('slug', $slug)
            ->first();

        if (!$feature) {

            return null;
        }

        return $feature->pivot->value;
    }

    /*
    |--------------------------------------------------------------------------
    | HAS FEATURE
    |--------------------------------------------------------------------------
    */

    public function hasFeature($slug)
    {
        return $this->feature($slug) == 1;
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE LIMIT
    |--------------------------------------------------------------------------
    */

    public function featureLimit($slug)
    {
        return (int)
            $this->feature($slug);
    }

    /*
    |--------------------------------------------------------------------------
    | UNLIMITED FEATURE
    |--------------------------------------------------------------------------
    */

    public function hasUnlimitedFeature($slug)
    {
        return $this->featureLimit($slug)
            >= 999999;
    }

    /*
    |--------------------------------------------------------------------------
    | CAN USE FEATURE
    |--------------------------------------------------------------------------
    */

    public function canUseFeature($slug)
    {
        $limit = $this->featureLimit($slug);

        if ($limit >= 999999) {

            return true;
        }

        $feature = Feature::where(
            'slug',
            $slug
        )->first();

        if (!$feature) {

            return false;
        }

        $usage = UserFeatureUsage::where([

            'user_id' => $this->id,

            'feature_id' => $feature->id

        ])->first();

        $used = $usage
            ? $usage->used
            : 0;

        return $used < $limit;
    }

    /*
    |--------------------------------------------------------------------------
    | INCREASE FEATURE USAGE
    |--------------------------------------------------------------------------
    */

    public function increaseFeatureUsage(
        $slug,
        $count = 1
    )
    {
        $feature = Feature::where(
            'slug',
            $slug
        )->first();

        if (!$feature) {

            return false;
        }

        $usage = UserFeatureUsage::firstOrCreate([

            'user_id' => $this->id,

            'feature_id' => $feature->id

        ]);

        $usage->increment(
            'used',
            $count
        );

        return true;
    }
    
    /*
|--------------------------------------------------------------------------
| REMAINING FEATURE LIMIT
|--------------------------------------------------------------------------
*/

public function remainingFeatureLimit($slug)
{
    $limit = $this->featureLimit($slug);

    /*
    |--------------------------------------------------------------------------
    | UNLIMITED
    |--------------------------------------------------------------------------
    */

    if ($limit >= 999999) {

        return 'Unlimited';
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE
    |--------------------------------------------------------------------------
    */

    $feature = Feature::where(
        'slug',
        $slug
    )->first();

    if (!$feature) {

        return 0;
    }

    /*
    |--------------------------------------------------------------------------
    | USAGE
    |--------------------------------------------------------------------------
    */

    $usage = UserFeatureUsage::where([

        'user_id' => $this->id,

        'feature_id' => $feature->id

    ])->first();

    $used = $usage
        ? $usage->used
        : 0;

    return max(
        0,
        $limit - $used
    );
}

    /*
    |--------------------------------------------------------------------------
    | RESET FEATURE USAGE
    |--------------------------------------------------------------------------
    */

    public function resetFeatureUsage($slug)
    {
        $feature = Feature::where(
            'slug',
            $slug
        )->first();

        if (!$feature) {

            return false;
        }

        UserFeatureUsage::where([

            'user_id' => $this->id,

            'feature_id' => $feature->id

        ])->delete();

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | OTP VERIFIED
    |--------------------------------------------------------------------------
    */

    public function isOtpVerified()
    {
        return (bool)
            $this->is_otp_verified;
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE
    |--------------------------------------------------------------------------
    */

    public function isActive()
    {
        return (bool)
            $this->status;
    }
}