<?php

namespace App\Events;

use App\Models\LiveChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class LiveChatMessageEvent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    public function __construct(
        LiveChatMessage $message
    ) {

        $this->message = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Broadcast Channel
    |--------------------------------------------------------------------------
    */

    public function broadcastOn()
    {

        return new Channel(
            'live-chat'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Event Name
    |--------------------------------------------------------------------------
    */

    public function broadcastAs()
    {

        return 'new-message';
    }

    /*
    |--------------------------------------------------------------------------
    | Broadcast Data
    |--------------------------------------------------------------------------
    */

    public function broadcastWith()
    {

        return [

            'id'
                => $this->message->id,

            'chatbot_session_id'
                => $this->message->chatbot_session_id,

            'sender_type'
                => $this->message->sender_type,

            'message'
                => $this->message->message,

            'channel'
                => $this->message->channel,

            'created_at'
                => $this->message->created_at
                    ->format('Y-m-d H:i:s')
        ];
    }
}