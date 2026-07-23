<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractParty extends Model
{
    protected $table = 'contract_parties';

    protected $fillable = [
        'contract_id',
        'user_id',
        'role',
        'is_signed'
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