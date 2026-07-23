<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppLead extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'last_message',
        'status'
    ];
}