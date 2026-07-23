<?php

namespace App\Services\Chat;

use App\Models\FailedAIMessage;

class FailedAIService
{
    /*
    |--------------------------------------------------------------------------
    | Store Failed AI Response
    |--------------------------------------------------------------------------
    */

    public function log(

        $question,

        $reply = null,

        $intent = null,

        $channel = 'web',

        $userId = null

    ) {

        return FailedAIMessage::create([

            'user_id'
                => $userId,

            'channel'
                => $channel,

            'question'
                => $question,

            'ai_reply'
                => $reply,

            'intent'
                => $intent
        ]);
    }
}