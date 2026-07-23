<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $table = 'commissions';

    protected $fillable = [
        'contract_id',
        'agent_id',
        'agency_id',
        'applied_job_id',
        'candidate_id',
        'job_id',
        'amount',
        'rate',
        'currency',
        'type',
        'status',
        'notes',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'float',
        'rate' => 'float',
        'paid_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_PAID = 'paid';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    public function appliedJob()
    {
        return $this->belongsTo(AppliedJob::class, 'applied_job_id');
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER METHODS
    |--------------------------------------------------------------------------
    */

    public function isPaid()
    {
        return $this->status === self::STATUS_PAID;
    }

    public function markAsPaid()
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function badgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PAID => 'badge bg-success',
            self::STATUS_APPROVED => 'badge bg-primary',
            default => 'badge bg-warning text-dark',
        };
    }

    public function formattedAmount()
    {
        return number_format($this->amount, 2);
    }
}
