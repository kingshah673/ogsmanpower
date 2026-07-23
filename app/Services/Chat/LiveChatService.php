<?php

namespace App\Services\Chat;

use App\Models\LiveChatMessage;
use App\Events\LiveChatMessageEvent;
use App\Services\WhatsApp\WhatsAppService;

class LiveChatService
{
    protected $whatsapp;

    public function __construct(
        WhatsAppService $whatsapp
    ) {

        $this->whatsapp = $whatsapp;
    }

    /*
    |--------------------------------------------------------------------------
    | Store Message
    |--------------------------------------------------------------------------
    */

    public function store(

        $session,

        $message,

        $senderType = 'agent',

        $senderId = null

    ) {

        $chatMessage =
            LiveChatMessage::create([

                'chatbot_session_id'
                    => $session->id,

                'sender_id'
                    => $senderId,

                'sender_type'
                    => $senderType,

                'message'
                    => $message,

                'channel'
                    => $session->channel
            ]);
            
            

        /*
        |--------------------------------------------------------------------------
        | BROADCAST REALTIME EVENT
        |--------------------------------------------------------------------------
        */

        broadcast(
            new LiveChatMessageEvent(
                $chatMessage
            )
        )->toOthers();

        return $chatMessage;
    }

    /*
    |--------------------------------------------------------------------------
    | Send Agent Reply
    |--------------------------------------------------------------------------
    */

    public function reply(

        $session,

        $message,

        $senderId = null

    ) {

        /*
        |--------------------------------------------------------------------------
        | STORE MESSAGE
        |--------------------------------------------------------------------------
        */

        $chatMessage =
            $this->store(

                $session,

                $message,

                'agent',

                $senderId
            );

        /*
        |--------------------------------------------------------------------------
        | SEND WHATSAPP MESSAGE
        |--------------------------------------------------------------------------
        */

        if (
            $session->channel
            === 'whatsapp'
            &&
            $session->phone
        ) {

            $this->whatsapp
                ->sendMessage(

                    $session->phone,

                    $message
                );
        }

        return $chatMessage;
    }

    /*
    |--------------------------------------------------------------------------
    | Store AI Reply
    |--------------------------------------------------------------------------
    */

    public function aiReply(

        $session,

        $message

    ) {

        return $this->store(

            $session,

            $message,

            'ai',

            null
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Store User Message
    |--------------------------------------------------------------------------
    */

    public function userMessage(

        $session,

        $message,

        $userId = null

    ) {

        return $this->store(

            $session,

            $message,

            'user',

            $userId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Get Session Messages
    |--------------------------------------------------------------------------
    */

    public function messages(
        $sessionId
    ) {

        return LiveChatMessage::query()

            ->where(
                'chatbot_session_id',
                $sessionId
            )

            ->latest()

            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Mark Chat As Read
    |--------------------------------------------------------------------------
    */

    public function markRead(
        $session
    ) {

        $session->update([

            'last_message_at'
                => now()
        ]);

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Agent
    |--------------------------------------------------------------------------
    */

    public function assignAgent(

        $handover,

        $agentId

    ) {

        $handover->update([

            'assigned_to'
                => $agentId,

            'status'
                => 'assigned'
        ]);

        return $handover;
    }

    /*
    |--------------------------------------------------------------------------
    | Resolve Chat
    |--------------------------------------------------------------------------
    */

    public function resolve(
        $handover
    ) {

        $handover->update([

            'status'
                => 'resolved'
        ]);

        return true;
    }
}