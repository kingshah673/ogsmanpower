<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatHandover extends Model
{
    protected $fillable = [

        'chatbot_session_id',

        'user_id',

        'channel',

        'reason',

        'status',

        'assigned_to'
    ];
}