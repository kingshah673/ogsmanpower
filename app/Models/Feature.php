<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | PLANS
    |--------------------------------------------------------------------------
    */

    public function plans()
    {
        return $this->belongsToMany(

            \Modules\Plan\Entities\Plan::class,

            'plan_features'

        )->withPivot('value');
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE USAGES
    |--------------------------------------------------------------------------
    */

    public function usages()
    {
        return $this->hasMany(
            UserFeatureUsage::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where(
            'status',
            1
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isBoolean()
    {
        return $this->type == 'boolean';
    }

    public function isLimit()
    {
        return $this->type == 'limit';
    }

    public function isText()
    {
        return $this->type == 'text';
    }
}