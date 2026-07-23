<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIHandoverRequest extends Model
{
    protected $table =
        'ai_handover_requests';

    protected $fillable = [

        'session_id',

        'user_message',

        'status',

        'admin_reply',

        'ip_address'
    ];
}