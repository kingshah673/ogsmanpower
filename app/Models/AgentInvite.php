<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentInvite extends Model
{
    protected $table = 'agent_invites';
    protected $guarded = [];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function agencyUser()
    {
        return $this->belongsTo(User::class, 'agency_user_id');
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && is_null($this->accepted_at);
    }
}
