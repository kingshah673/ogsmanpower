<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    public function sendMessage(
        $phone,
        $message
    ) {

        $url =
            'https://graph.facebook.com/'
            .
            env('WHATSAPP_VERSION', 'v25.0')
            .
            '/'
            .
            env('WHATSAPP_PHONE_ID')
            .
            '/messages';

        return Http::withToken(

            env('WHATSAPP_TOKEN')

        )->post(

            $url,

            [

                'messaging_product'
                    => 'whatsapp',

                'to'
                    => $phone,

                'type'
                    => 'text',

                'text' => [

                    'body'
                        => $message

                ]

            ]
        );
    }
}