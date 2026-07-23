<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLead extends Model
{
    protected $table = 'chat_leads';

    protected $fillable = [
        'full_name',
        'phone',
        'email',

        'session_id',
        'source',

        'category',
        'status',
        'priority',
        'assigned_to',

        'message',

        'country',
        'city',

        'next_followup_at',
    ];

    protected $casts = [
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
        'next_followup_at' => 'datetime',
    ];

    /* =========================================
       RELATIONS
    ========================================= */

    public function messages()
    {
        return $this->hasMany(
            \App\Models\LeadMessage::class,
            'lead_id'
        );
    }

    /* =========================================
       SCOPES
    ========================================= */

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            'closed',
            'spam'
        ]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate(
            'created_at',
            now()->toDateString()
        );
    }

    /* =========================================
       ACCESSORS
    ========================================= */

    public function getWhatsappLinkAttribute()
    {
        if (!$this->phone) {
            return null;
        }

        $phone = preg_replace('/[^0-9]/', '', $this->phone);

        if (substr($phone, 0, 1) == '0') {
            $phone = '92' . substr($phone, 1);
        }

        return 'https://wa.me/' . $phone;
    }

    public function getPriorityBadgeAttribute()
    {
        return match($this->priority) {
            'high'   => 'danger',
            'low'    => 'secondary',
            default  => 'warning',
        };
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'new'        => 'primary',
            'contacted'  => 'info',
            'interested' => 'warning',
            'closed'     => 'success',
            'spam'       => 'dark',
            default      => 'secondary',
        };
    }
}