<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Plan\Entities\Plan;

class UserPlan extends Model
{
    use HasFactory;

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(
            Company::class,
            'company_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AGENCY
    |--------------------------------------------------------------------------
    */

    public function agency(): BelongsTo
    {
        return $this->belongsTo(
            Agency::class,
            'agency_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PLAN
    |--------------------------------------------------------------------------
    */

    public function plan(): BelongsTo
    {
        return $this->belongsTo(
            Plan::class,
            'plan_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE SCOPE
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            'active'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER DATA
    |--------------------------------------------------------------------------
    */

    public function scopeUserData(
        $query,
        $user_id = null
    )
    {
        return $query->where(
            'user_id',
            $user_id ?? auth()->id()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | COMPANY DATA
    |--------------------------------------------------------------------------
    */

    public function scopeCompanyData(
        $query,
        $company_id = null
    )
    {
        return $query->where(
            'company_id',
            $company_id ?? currentCompany()->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | AGENCY DATA
    |--------------------------------------------------------------------------
    */

    public function scopeAgencyData(
        $query,
        $agency_id = null
    )
    {
        return $query->where(
            'agency_id',
            $agency_id ?? currentAgency()->id
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EXPIRED CHECK
    |--------------------------------------------------------------------------
    */

    public function isExpired()
    {
        if (!$this->expires_at) {

            return false;
        }

        return now()->gt(
            $this->expires_at
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACTIVE CHECK
    |--------------------------------------------------------------------------
    */

    public function isActive()
    {
        return $this->status == 'active'
            && !$this->isExpired();
    }
}