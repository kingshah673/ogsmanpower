<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseTask extends Model
{
    protected $table = 'case_tasks';

    protected $fillable = [
        'case_id',
        'assigned_to',
        'role',
        'title',
        'description',
        'status',
        'is_completed',
        'completed_at'
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
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isCompleted()
    {
        return $this->is_completed == 1;
    }
}