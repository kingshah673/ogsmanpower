<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;

use App\Models\WhatsAppMessage;
use App\Models\WhatsAppChatRoom;
use App\Models\AIKnowledge;
use App\Models\AILead;

class AIWhatsAppController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | VERIFY WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function verify(
        Request $request
    ) {

        $verifyToken =
            env('WHATSAPP_VERIFY_TOKEN');

        if(

            $request->hub_verify_token
            ==
            $verifyToken

        ){

            return response(
                $request->hub_challenge,
                200
            );
        }

        return response(
            'Invalid Verify Token',
            403
        );
    }

    /*
    |--------------------------------------------------------------------------
    | WEBHOOK
    |--------------------------------------------------------------------------
    */

 public function webhook(
    Request $request
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | LOG HIT
        |--------------------------------------------------------------------------
        */

        \Log::info(

            'WHATSAPP CHATBOT HIT'
        );

        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $data =
            $request->all();

        /*
        |--------------------------------------------------------------------------
        | LOG DATA
        |--------------------------------------------------------------------------
        */

        \Log::info(

            'WHATSAPP DATA',

            $data
        );

        /*
        |--------------------------------------------------------------------------
        | CHECK ENTRY
        |--------------------------------------------------------------------------
        */

        if(

            !isset($data['entry'])

        ){

            return response()->json([

                'success' => false
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | LOOP
        |--------------------------------------------------------------------------
        */

        foreach(

            $data['entry']
            as
            $entry

        ){

            foreach(

                $entry['changes']
                as
                $change

            ){

                /*
                |--------------------------------------------------------------------------
                | VALUE
                |--------------------------------------------------------------------------
                */

                $value =
                    $change['value'];

                /*
                |--------------------------------------------------------------------------
                | NO MESSAGES
                |--------------------------------------------------------------------------
                */

                if(

                    !isset(
                        $value['messages']
                    )

                ){

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | LOOP MESSAGES
                |--------------------------------------------------------------------------
                */

                foreach(

                    $value['messages']
                    as
                    $messageData

                ){

                    /*
                    |--------------------------------------------------------------------------
                    | ONLY TEXT
                    |--------------------------------------------------------------------------
                    */

                    if(

                        !isset(
                            $messageData['text']
                        )

                    ){

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PHONE
                    |--------------------------------------------------------------------------
                    */

                    $phone =
                        $messageData['from'];

                    /*
                    |--------------------------------------------------------------------------
                    | MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        $messageData['text']['body']
                        ??
                        '';

                    /*
                    |--------------------------------------------------------------------------
                    | CLEAN
                    |--------------------------------------------------------------------------
                    */

                    $message =
                        trim(
                            strtolower($message)
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | LOG MESSAGE
                    |--------------------------------------------------------------------------
                    */

                    \Log::info(

                        'WHATSAPP MESSAGE',

                        [

                            'phone'
                                => $phone,

                            'message'
                                => $message
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | ROOM
                    |--------------------------------------------------------------------------
                    */

                    $room =

                        WhatsAppChatRoom::firstOrCreate([

                            'phone'
                                => $phone
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE ROOM
                    |--------------------------------------------------------------------------
                    */

                    $room->update([

                        'last_message_at'
                            => now(),

                        'unread_count'
                            => $room->unread_count + 1
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | SAVE INCOMING
                    |--------------------------------------------------------------------------
                    */

                    WhatsAppMessage::create([

                        'phone'
                            => $phone,

                        'message'
                            => $message,

                        'direction'
                            => 'incoming',

                        'status'
                            => 'sent'
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | HUMAN MODE
                    |--------------------------------------------------------------------------
                    */

                    if(

                        $room->status
                        ==
                        'human'

                    ){

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | AI REPLY
                    |--------------------------------------------------------------------------
                    */

                    $reply =
                        $this->generateReply(
                            $message
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | LOG REPLY
                    |--------------------------------------------------------------------------
                    */

                    \Log::info(

                        'AI REPLY',

                        [

                            'reply'
                                => $reply
                        ]
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | SEND
                    |--------------------------------------------------------------------------
                    */

                    $response =
                        $this->sendMessage(

                            $phone,

                            $reply
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | SAVE OUTGOING
                    |--------------------------------------------------------------------------
                    */

                    WhatsAppMessage::create([

                        'phone'
                            => $phone,

                        'reply'
                            => $reply,

                        'direction'
                            => 'outgoing',

                        'status'
                            => 'sent',

                        'message_id'
                            =>
                            $response['messages'][0]['id']
                            ??
                            null
                    ]);
                }
            }
        }

        return response()->json([

            'success'
                => true
        ]);

    } catch (\Exception $e) {

        \Log::error(

            'WHATSAPP CHATBOT ERROR',

            [

                'message'
                    => $e->getMessage(),

                'line'
                    => $e->getLine(),

                'file'
                    => $e->getFile()
            ]
        );

        return response()->json([

            'success'
                => false
        ]);
    }
}
    /*
    |--------------------------------------------------------------------------
    | GENERATE AI REPLY
    |--------------------------------------------------------------------------
    */

    protected function generateReply(
        $message
    ) {

        /*
        |--------------------------------------------------------------------------
        | KNOWLEDGE BASE
        |--------------------------------------------------------------------------
        */

        $knowledge =

            AIKnowledge::query()

            ->where(
                'status',
                1
            )

            ->get()

            ->filter(function($item) use ($message){

                $question =

                    strtolower(
                        $item->question
                    );

                return str_contains(

                    $question,

                    $message
                );
            })

            ->first();

        /*
        |--------------------------------------------------------------------------
        | KNOWLEDGE MATCH
        |--------------------------------------------------------------------------
        */

        if($knowledge){

            return
                $knowledge->answer;
        }

        /*
        |--------------------------------------------------------------------------
        | JOBS
        |--------------------------------------------------------------------------
        */

        if(

            str_contains(
                $message,
                'job'
            )

            ||

            str_contains(
                $message,
                'vacancy'
            )

        ){

            return

                "🌍 We have overseas jobs available for:\n\n"

                .

                "✅ Saudi Arabia\n"

                .

                "✅ UAE\n"

                .

                "✅ Qatar\n"

                .

                "✅ Romania\n\n"

                .

                "📄 Please send your CV.";
        }

        /*
        |--------------------------------------------------------------------------
        | VISA
        |--------------------------------------------------------------------------
        */

        if(

            str_contains(
                $message,
                'visa'
            )

        ){

            return

                "🛂 Visa processing time:\n\n"

                .

                "✅ 7 to 14 working days\n\n"

                .

                "📄 Required:\n"

                .

                "• Passport\n"

                .

                "• CV\n"

                .

                "• Photo";
        }

        /*
        |--------------------------------------------------------------------------
        | INTERVIEW
        |--------------------------------------------------------------------------
        */

        if(

            str_contains(
                $message,
                'interview'
            )

        ){

            return

                "🎤 Interview Tips:\n\n"

                .

                "✅ Speak confidently\n"

                .

                "✅ Dress professionally\n"

                .

                "✅ Carry documents";
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY
        |--------------------------------------------------------------------------
        */

        if(

            str_contains(
                $message,
                'apply'
            )

        ){

            return

                "📄 To apply please send:\n\n"

                .

                "✅ Updated CV\n"

                .

                "✅ Passport Copy\n"

                .

                "✅ Passport Photo";
        }

        /*
        |--------------------------------------------------------------------------
        | GREETING
        |--------------------------------------------------------------------------
        */

        if(

            str_contains(
                $message,
                'hi'
            )

            ||

            str_contains(
                $message,
                'hello'
            )

            ||

            str_contains(
                $message,
                'assalam'
            )

        ){

            return

                "👋 Welcome to OGS AI Recruitment Assistant.\n\n"

                .

                "I can help you with:\n\n"

                .

                "✅ Overseas Jobs\n"

                .

                "✅ Visa Process\n"

                .

                "✅ Recruitment\n"

                .

                "✅ Interview Guidance";
        }

        /*
        |--------------------------------------------------------------------------
        | DEFAULT
        |--------------------------------------------------------------------------
        */

        return

            "🤖 Thank you for contacting OGS Group.\n\n"

            .

            "Please tell us:\n\n"

            .

            "✅ Which country are you interested in?\n"

            .

            "✅ Which job are you looking for?";
    }

    /*
    |--------------------------------------------------------------------------
    | SEND WHATSAPP MESSAGE
    |--------------------------------------------------------------------------
    */

    protected function sendMessage(
        $phone,
        $message
    ) {

        $token =
            env('WHATSAPP_TOKEN');

        $phoneId =
            env('WHATSAPP_PHONE_ID');

        $response =

            Http::withToken($token)

            ->post(

                'https://graph.facebook.com/v18.0/'

                .

                $phoneId

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
                            => $message
                    ]
                ]
            );

        /*
        |--------------------------------------------------------------------------
        | LOG RESPONSE
        |--------------------------------------------------------------------------
        */

        \Log::info(

            'WHATSAPP SEND RESPONSE',

            $response->json()
        );

        return
            $response->json();
    }
}