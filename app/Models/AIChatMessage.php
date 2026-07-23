<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIChatMessage extends Model
{
    protected $table =
        'ai_chat_messages';

    protected $fillable = [

    'session_id',

    'user_id',

    'portal_role',

    'admin_id',

    'user_message',

    'ai_reply',

    'ip_address',

    'source',

    'sender',

    'is_admin',

    'human_mode',
    
    'attachment',
    
    'voice_message'
];
}