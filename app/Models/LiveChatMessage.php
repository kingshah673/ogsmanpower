<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveChatMessage extends Model
{
    protected $fillable = [

        'chatbot_session_id',

        'sender_id',

        'sender_type',

        'message',

        'channel'
    ];
}