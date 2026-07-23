<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BilangualResumeSubscription extends Model
{
    use HasFactory;

    protected $table = 'bilangual_resume_subscriptions';

    protected $guarded = [];

    protected $casts = [
        'price'       => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
