<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Candidate;

use App\Services\Chat\AIChatService;
use App\Services\Chat\LiveChatService;
use App\Services\Chat\SessionService;

use App\Services\WhatsApp\WhatsAppService;
use App\Services\WhatsApp\WhatsAppMediaService;

use App\Services\Chat\CandidateParserService;
use App\Services\Chat\OCRService;

use App\Services\AI\AIMatchingService;
use App\Services\AI\GPTCVParserService;

use App\Services\AI\SpeechToTextService;
use App\Services\AI\TextToSpeechService;

class WhatsAppWebhookController extends Controller
{
    protected $ai;

    protected $liveChat;

    protected $session;

    protected $whatsapp;

    protected $media;

    protected $parser;

    protected $ocr;

    protected $matching;

    protected $gptParser;

    protected $speech;

    protected $tts;

    public function __construct(

        AIChatService $ai,

        LiveChatService $liveChat,

        SessionService $session,

        WhatsAppService $whatsapp,

        WhatsAppMediaService $media,

        CandidateParserService $parser,

        OCRService $ocr,

        AIMatchingService $matching,

        GPTCVParserService $gptParser,

        SpeechToTextService $speech,

        TextToSpeechService $tts

    ) {

        $this->ai = $ai;

        $this->liveChat = $liveChat;

        $this->session = $session;

        $this->whatsapp = $whatsapp;

        $this->media = $media;

        $this->parser = $parser;

        $this->ocr = $ocr;

        $this->matching = $matching;

        $this->gptParser = $gptParser;

        $this->speech = $speech;

        $this->tts = $tts;
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE WEBHOOK
    |--------------------------------------------------------------------------
    */

    public function handle(
        Request $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | VERIFY WEBHOOK
        |--------------------------------------------------------------------------
        */

        if (
            $request->isMethod('GET')
        ) {

            $verifyToken =
                env(
                    'WHATSAPP_VERIFY_TOKEN'
                );

            if (

                $request->hub_verify_token
                ===
                $verifyToken

            ) {

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
        | RECEIVE DATA
        |--------------------------------------------------------------------------
        */

        $entry =
            $request->input(
                'entry.0'
            );

        if (!$entry) {

            return response(
                'No Entry',
                200
            );
        }

        $change =
            $entry['changes'][0]
            ?? null;

        if (!$change) {

            return response(
                'No Change',
                200
            );
        }

        $value =
            $change['value']
            ?? null;

        if (!$value) {

            return response(
                'No Value',
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | MESSAGES
        |--------------------------------------------------------------------------
        */

        $messages =
            $value['messages']
            ?? [];

        if (
            !count($messages)
        ) {

            return response(
                'No Messages',
                200
            );
        }

        $incoming =
            $messages[0];

        /*
        |--------------------------------------------------------------------------
        | PHONE
        |--------------------------------------------------------------------------
        */

        $phone =
            $incoming['from']
            ?? null;

        if (!$phone) {

            return response(
                'No Phone',
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TYPE
        |--------------------------------------------------------------------------
        */

        $type =
            $incoming['type']
            ?? 'text';

        /*
        |--------------------------------------------------------------------------
        | SESSION
        |--------------------------------------------------------------------------
        */

        $chatSession =
            $this->session->get(

                'whatsapp',

                $phone

            );

        /*
        |--------------------------------------------------------------------------
        | SAVE PHONE
        |--------------------------------------------------------------------------
        */

        if (
            !$chatSession->phone
        ) {

            $chatSession->update([

                'phone'
                    => $phone
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | FIND USER
        |--------------------------------------------------------------------------
        */

        $user =
            User::where(
                'whatsapp',
                $phone
            )->first();

        $candidate = null;

        if ($user) {

            $candidate =
                Candidate::where(
                    'user_id',
                    $user->id
                )->first();
        }

        /*
        |--------------------------------------------------------------------------
        | TEXT MESSAGE
        |--------------------------------------------------------------------------
        */

        if (
            $type === 'text'
        ) {

            $message =
                $incoming['text']['body']
                ?? null;

            if (!$message) {

                return response(
                    'No Message',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STORE USER MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->userMessage(

                    $chatSession,

                    $message
                );

            /*
            |--------------------------------------------------------------------------
            | AI REPLY
            |--------------------------------------------------------------------------
            */

            $reply =
                $this->ai
                    ->handle(

                        $message,

                        'whatsapp',

                        $phone,

                        $user?->id
                    );

            /*
            |--------------------------------------------------------------------------
            | STORE AI MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->aiReply(

                    $chatSession,

                    $reply
                );

            /*
            |--------------------------------------------------------------------------
            | SEND WHATSAPP
            |--------------------------------------------------------------------------
            */

            $this->whatsapp
                ->sendMessage(

                    $phone,

                    $reply
                );

            return response(
                'TEXT_PROCESSED',
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | AUDIO MESSAGE
        |--------------------------------------------------------------------------
        */

        if (
            $type === 'audio'
        ) {

            $fileId =
                $incoming['audio']['id']
                ?? null;

            if (!$fileId) {

                return response(
                    'No Audio ID',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DOWNLOAD AUDIO
            |--------------------------------------------------------------------------
            */

            $path =
                $this->media
                    ->download($fileId);

            if (!$path) {

                return response(
                    'Audio Download Failed',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SPEECH TO TEXT
            |--------------------------------------------------------------------------
            */

            $text =
                $this->speech
                    ->transcribe($path);

            if (!$text) {

                $reply =
                    '❌ Unable to understand voice message.';

                $this->whatsapp
                    ->sendMessage(

                        $phone,

                        $reply
                    );

                return response(
                    'VOICE_FAILED',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | STORE MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->userMessage(

                    $chatSession,

                    $text
                );

            /*
            |--------------------------------------------------------------------------
            | AI RESPONSE
            |--------------------------------------------------------------------------
            */

            $reply =
                $this->ai
                    ->handle(

                        $text,

                        'whatsapp',

                        $phone,

                        $user?->id
                    );

            /*
            |--------------------------------------------------------------------------
            | GENERATE VOICE
            |--------------------------------------------------------------------------
            */

            $voice =
                $this->tts
                    ->generate($reply);

            /*
            |--------------------------------------------------------------------------
            | STORE AI MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->aiReply(

                    $chatSession,

                    $reply
                );

            /*
            |--------------------------------------------------------------------------
            | SEND TEXT
            |--------------------------------------------------------------------------
            */

            $this->whatsapp
                ->sendMessage(

                    $phone,

                    $reply
                );

            /*
            |--------------------------------------------------------------------------
            | OPTIONAL AUDIO SEND
            |--------------------------------------------------------------------------
            */

            /*
            if ($voice) {

                $this->whatsapp
                    ->sendAudio(

                        $phone,

                        $voice
                    );
            }
            */

            return response(
                'VOICE_PROCESSED',
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | DOCUMENT / IMAGE
        |--------------------------------------------------------------------------
        */

        if (

            in_array(

                $type,

                [

                    'document',

                    'image'

                ]
            )

        ) {

            /*
            |--------------------------------------------------------------------------
            | OTP GATE — unknown users cannot upload files
            |--------------------------------------------------------------------------
            */

            if (!$user) {

                $this->whatsapp->sendMessage(
                    $phone,
                    "⚠️ Please register or link your WhatsApp number at " .
                    config('app.url') .
                    " before uploading documents."
                );

                return response('USER_UNVERIFIED', 200);
            }

            /*
            |--------------------------------------------------------------------------
            | FILE ID
            |--------------------------------------------------------------------------
            */

            $fileId =
                $incoming[$type]['id']
                ?? null;

            if (!$fileId) {

                return response(
                    'No File ID',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | DEDUPLICATION — ignore replayed webhooks
            |--------------------------------------------------------------------------
            */

            $messageId = $incoming['id'] ?? null;

            if (
                $messageId &&
                \App\Models\CandidateDocument::where('message_id', $messageId)->exists()
            ) {
                return response('DUPLICATE', 200);
            }

            /*
            |--------------------------------------------------------------------------
            | MIME VALIDATION
            |--------------------------------------------------------------------------
            */

            $mimeType = $incoming[$type]['mime_type'] ?? '';
            $allowedMimes = [
                'application/pdf',
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/tiff',
            ];

            if (!in_array($mimeType, $allowedMimes)) {

                $this->whatsapp->sendMessage(
                    $phone,
                    '❌ Unsupported file type. Please send a PDF, JPG, PNG, or TIFF file only.'
                );

                return response('INVALID_MIME', 200);
            }

            /*
            |--------------------------------------------------------------------------
            | STORE USER MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->userMessage(

                    $chatSession,

                    strtoupper($type)
                    .
                    ' uploaded'
                );

            /*
            |--------------------------------------------------------------------------
            | DOWNLOAD FILE
            |--------------------------------------------------------------------------
            */

            $path =
                $this->media
                    ->download($fileId);

            if (!$path) {

                $reply =
                    '❌ Failed to download file.';

                $this->whatsapp
                    ->sendMessage(

                        $phone,

                        $reply
                    );

                return response(
                    'DOWNLOAD_FAILED',
                    200
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SAVE CANDIDATE DOCUMENT RECORD
            |--------------------------------------------------------------------------
            */

            \App\Models\CandidateDocument::create([
                'candidate_id'  => $candidate?->id,
                'file_reference'=> $path,
                'document_type' => $type === 'document' ? 'cv' : 'passport',
                'source_channel'=> 'whatsapp',
                'mime_type'     => $mimeType,
                'message_id'    => $messageId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | DEFAULT REPLY
            |--------------------------------------------------------------------------
            */

            $reply =
                "📁 File received successfully.\n";

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT (CV)
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'document'
            ) {

                /*
                |--------------------------------------------------------------------------
                | BASIC PARSER
                |--------------------------------------------------------------------------
                */

                $parsed =
                    $this->parser
                        ->parse($path);

                /*
                |--------------------------------------------------------------------------
                | GPT PARSER
                |--------------------------------------------------------------------------
                */

                $gpt =
                    $this->gptParser
                        ->parse(

                            $parsed['raw_text']
                            ?? ''
                        );

                /*
                |--------------------------------------------------------------------------
                | SAVE CANDIDATE
                |--------------------------------------------------------------------------
                */

                if ($candidate) {

                    $candidate->update([

                        'cv'
                            => $path,

                        'profession'
                            => $gpt['Profession']
                            ?? null,

                        'skills'
                            => json_encode(
                                $gpt['Skills']
                                ?? []
                            ),

                        'experience'
                            => $gpt['Years of Experience']
                            ?? null,

                        'country'
                            => $gpt['Country']
                            ?? null
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | AI MATCHING
                |--------------------------------------------------------------------------
                */

                $matches = [];

                if (

                    $candidate
                    &&
                    !empty(
                        $parsed['raw_text']
                    )

                ) {

                    $matches =
                        $this->matching
                            ->match(

                                $candidate,

                                $parsed['raw_text']
                            );
                }

                /*
                |--------------------------------------------------------------------------
                | REPLY
                |--------------------------------------------------------------------------
                */

                $reply .=

                    "\n"

                    .

                    "👤 Name: "

                    .

                    (
                        $gpt['Full Name']
                        ??
                        'N/A'
                    )

                    .

                    "\n"

                    .

                    "💼 Profession: "

                    .

                    (
                        $gpt['Profession']
                        ??
                        'N/A'
                    )

                    .

                    "\n"

                    .

                    "📈 Experience: "

                    .

                    (
                        $gpt['Years of Experience']
                        ??
                        'N/A'
                    )

                    .

                    "\n"

                    .

                    "🌍 Country: "

                    .

                    (
                        $gpt['Country']
                        ??
                        'N/A'
                    );

                /*
                |--------------------------------------------------------------------------
                | SKILLS
                |--------------------------------------------------------------------------
                */

                if (

                    isset(
                        $gpt['Skills']
                    )

                ) {

                    $skills =
                        is_array(
                            $gpt['Skills']
                        )

                        ?

                        implode(
                            ', ',
                            $gpt['Skills']
                        )

                        :

                        $gpt['Skills'];

                    $reply .=

                        "\n"

                        .

                        "🛠 Skills: "

                        .

                        $skills;
                }

                /*
                |--------------------------------------------------------------------------
                | JOB MATCHES
                |--------------------------------------------------------------------------
                */

                if (
                    count($matches)
                ) {

                    $reply .=

                        "\n\n"

                        .

                        "🤖 Top AI Job Matches:\n";

                    foreach (

                        array_slice(
                            $matches,
                            0,
                            3
                        )

                        as $match

                    ) {

                        $reply .=

                            "\n"

                            .

                            "✅ "

                            .

                            $match['job']->title

                            .

                            " → "

                            .

                            round(
                                $match['score']
                            )

                            .

                            "% Match";
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | IMAGE OCR
            |--------------------------------------------------------------------------
            */

            if (
                $type === 'image'
            ) {

                $ocr =
                    $this->ocr
                        ->scan($path);

                if (
                    $candidate
                    &&
                    $ocr
                ) {

                    $candidate->update([

                        'passport_file'
                            => $path,

                        'passport_number'
                            => $ocr['passport_no']
                            ?? null
                    ]);
                }

                if ($ocr) {

                    $reply .=

                        "\n\n"

                        .

                        "🛂 Passport No: "

                        .

                        (
                            $ocr['passport_no']
                            ??
                            'Not Found'
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | STORE AI MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->liveChat
                ->aiReply(

                    $chatSession,

                    $reply
                );

            /*
            |--------------------------------------------------------------------------
            | SEND MESSAGE
            |--------------------------------------------------------------------------
            */

            $this->whatsapp
                ->sendMessage(

                    $phone,

                    $reply
                );

            return response(
                'FILE_PROCESSED',
                200
            );
        }

        /*
        |--------------------------------------------------------------------------
        | UNSUPPORTED
        |--------------------------------------------------------------------------
        */

        return response(
            'Unsupported Type',
            200
        );
    }
}