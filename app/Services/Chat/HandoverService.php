<?php

namespace App\Services\Chat;

use App\Models\ChatHandover;
use App\Services\Notifications\NotificationService;

class HandoverService
{
    protected $notification;

    public function __construct(
        NotificationService $notification
    ) {

        $this->notification = $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Handover
    |--------------------------------------------------------------------------
    */

    public function create(

        $chatSession,

        $reason = null

    ) {

        $handover =
            ChatHandover::create([

                'chatbot_session_id'
                    => $chatSession->id,

                'user_id'
                    => $chatSession->user_id,

                'channel'
                    => $chatSession->channel,

                'reason'
                    => $reason,

                'status'
                    => 'pending'
            ]);

        /*
        |--------------------------------------------------------------------------
        | NOTIFY ADMINS
        |--------------------------------------------------------------------------
        */

        $admins =
            \App\Models\User::where(
                'role',
                'admin'
            )->get();

        foreach ($admins as $admin) {

            $this->notification
                ->send(

                    $admin,

                    'New AI handover request received.',

                    'AI Chat Handover'
                );
        }

        return $handover;
    }
}