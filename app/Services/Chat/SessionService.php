<?php

namespace App\Services\Chat;

use App\Models\ChatbotSession;

class SessionService
{
    public function get(
        $channel,
        $identifier
    ) {

        return ChatbotSession::firstOrCreate(

            [
                'session_key' => $identifier
            ],

            [
                'channel' => $channel,
                'status'  => 'active'
            ]
        );
    }

    public function updateStep(
        $session,
        $step
    ) {

        $session->update([
            'current_step' => $step
        ]);
    }

    public function saveData(
        $session,
        array $data
    ) {

        $old = $session->data ?? [];

        if (is_string($old)) {
            $old = json_decode($old, true);
        }

        $session->update([
            'data' => array_merge(
                $old ?: [],
                $data
            )
        ]);
    }

    public function complete(
        $session
    ) {

        $session->update([
            'status' => 'completed'
        ]);
    }
}