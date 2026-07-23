<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Broker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'profile_completion' => 'boolean',
        'is_profile_verified' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function demands(): HasMany
    {
        return $this->hasMany(BrokerDemand::class);
    }
}
