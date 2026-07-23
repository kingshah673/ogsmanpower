<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractSignature extends Model
{
    protected $table = 'contract_signatures';

    protected $fillable = [
        'contract_id',
        'user_id',
        'otp',
        'verified_at'
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}