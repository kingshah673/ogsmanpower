<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $table = 'contracts';

    protected $fillable = [
        'contract_no',
        'title',
        'content',
        'status',
        'created_by'
    ];
    

   
    // Creator (Employer / Admin)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // All parties in contract
    public function parties()
    {
        return $this->hasMany(ContractParty::class);
    }
    

    // Signatures
    public function signatures()
    {
        return $this->hasMany(ContractSignature::class);
    }
    public function logs() {
    return $this->hasMany(ContractLog::class);
   }

    // Get worker
    public function worker()
    {
        return $this->hasOne(ContractParty::class)->where('role', 'worker');
    }

    // Get employer
    public function employer()
    {
        return $this->hasOne(ContractParty::class)->where('role', 'employer');
    }
    public function getUserRole($userId)
{
    $party = $this->parties()->where('user_id', $userId)->first();
    return $party ? $party->role : null;
}
}