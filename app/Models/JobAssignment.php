<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobAssignment extends Model
{
    protected $guarded = [];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_to_agent_id');
    }

    public function subAgency()
    {
        return $this->belongsTo(Agency::class, 'assigned_to_agency_id');
    }
}