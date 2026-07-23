<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AINotification extends Model
{
    protected $table =
        'ai_notifications';

    protected $fillable = [

        'title',

        'message',

        'type',

        'is_read'
    ];
}