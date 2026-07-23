<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\WhatsAppChatRoom;

use App\Models\WhatsAppMessage;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

class WhatsAppChatController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CHAT LIST
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $rooms =

            WhatsAppChatRoom::latest(

                'last_message_at'

            )->get();

        return view(

            'backend.ai.whatsapp.index',

            compact('rooms')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT ROOM
    |--------------------------------------------------------------------------
    */

    public function show(
    $phone
) {

    $room =

        \App\Models\WhatsAppChatRoom::query()

        ->where(
            'phone',
            $phone
        )

        ->firstOrFail();
        
        /*
|--------------------------------------------------------------------------
| RESET UNREAD
|--------------------------------------------------------------------------
*/

$room->update([

    'unread_count'
        => 0
]);

    $messages =

        \App\Models\WhatsAppMessage::query()

        ->where(
            'phone',
            $phone
        )

        ->latest()

        ->get();

    return view(

        'backend.ai.whatsapp.show',

        compact(
            'room',
            'messages'
        )
    );
}
/*
|--------------------------------------------------------------------------
| FETCH MESSAGES
|--------------------------------------------------------------------------
*/

public function messages(
    $phone
) {

    $messages =

        \App\Models\WhatsAppMessage::query()

        ->where(
            'phone',
            $phone
        )

        ->latest()

        ->get();

    return response()->json(
        $messages
    );
}

    /*
    |--------------------------------------------------------------------------
    | SEND REPLY
    |--------------------------------------------------------------------------
    */

    public function reply(
        Request $request,
        $phone
    ) {

        /*
        |--------------------------------------------------------------------------
        | SEND TO META
        |--------------------------------------------------------------------------
        */

        Http::withToken(

            env('WHATSAPP_TOKEN')

        )->post(

            'https://graph.facebook.com/v25.0/'

            .

            env('WHATSAPP_PHONE_ID')

            .

            '/messages',

            [

                'messaging_product'
                    => 'whatsapp',

                'to'
                    => $phone,

                'type'
                    => 'text',

                'text' => [

                    'body'
                        => $request->message
                ]
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | SAVE
        |--------------------------------------------------------------------------
        */

        WhatsAppMessage::create([

            'phone'
                => $phone,

            'reply'
                => $request->message,

            'admin_reply'
                => $request->message,

            'direction'
                => 'outgoing',

            'status'
                => 'sent'
        ]);

        return back();
    }

    /*
    |--------------------------------------------------------------------------
    | SWITCH HUMAN MODE
    |--------------------------------------------------------------------------
    */

    public function humanMode(
        $phone
    ) {

        $room =

            WhatsAppChatRoom::query()

            ->where(
                'phone',
                $phone
            )

            ->firstOrFail();

        $room->update([

            'status'
                =>
                $room->status == 'ai'
                ? 'human'
                : 'ai'
        ]);

        return back();
    }
}