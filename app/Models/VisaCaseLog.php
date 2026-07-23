<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisaCaseLog extends Model
{
    protected $table = 'visa_case_logs';

    protected $fillable = [
        'case_id',
        'action',
        'description',
        'user_id'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function case()
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}