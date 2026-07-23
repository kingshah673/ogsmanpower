<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedAIMessage extends Model
{
    protected $table = 'failed_ai_messages';
    
    protected $fillable = [

        'user_id',

        'channel',

        'question',

        'ai_reply',

        'intent',

        'resolved',
        
         'message',

        'reason'
    ];
}