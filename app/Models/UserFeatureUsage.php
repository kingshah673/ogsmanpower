<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserFeatureUsage extends Model
{
    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    public function user()
    {
        return $this->belongsTo(
            User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FEATURE
    |--------------------------------------------------------------------------
    */

    public function feature()
    {
        return $this->belongsTo(
            Feature::class
        );
    }
}