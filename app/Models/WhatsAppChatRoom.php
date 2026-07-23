<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppChatRoom extends Model
{
    /*
    |--------------------------------------------------------------------------
    | TABLE
    |--------------------------------------------------------------------------
    */

    protected $table =
        'whatsapp_chat_rooms';

    /*
    |--------------------------------------------------------------------------
    | FILLABLE
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'phone',

        'name',

        'status',

        'last_message_at',

        'unread_count'
    ];
}