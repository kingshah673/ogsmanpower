<?php

namespace App\Services\Notifications;

use App\Services\WhatsApp\WhatsAppService;

class WhatsAppNotificationService
{
    protected $whatsapp;

    public function __construct(
        WhatsAppService $whatsapp
    ) {

        $this->whatsapp = $whatsapp;
    }

    /*
    |--------------------------------------------------------------------------
    | Send WhatsApp Notification
    |--------------------------------------------------------------------------
    */

    public function send(
        $phone,
        $message
    ) {

        return
            $this->whatsapp
                ->sendMessage(
                    $phone,
                    $message
                );
    }
}