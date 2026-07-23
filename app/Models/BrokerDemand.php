<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokerDemand extends Model
{
    protected $guarded = [];

    protected $casts = [
        'routed_at' => 'datetime',
    ];

    public function broker(): BelongsTo
    {
        return $this->belongsTo(Broker::class);
    }

    public function routedAgencyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'routed_agency_user_id');
    }
}
