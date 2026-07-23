<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Website\AIChatService;

class WhatsAppController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | VERIFY WEBHOOK
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        if (
            $request->get('hub_mode') === 'subscribe' &&
            $request->get('hub_verify_token') === env('WHATSAPP_VERIFY_TOKEN')
        ) {
            return response($request->get('hub_challenge'), 200);
        }

        return response('Invalid token', 403);
    }

    /*
    |--------------------------------------------------------------------------
    | RECEIVE MESSAGE
    |--------------------------------------------------------------------------
    */
    public function receive(Request $request)
    {
        try {

            // ✅ Extract only needed data (NO deep nesting)
            $data = $request->input('entry.0.changes.0.value');
            if (isset($data['statuses'])) {

    $status = $data['statuses'][0]['status'] ?? null;

    \Log::info('WHATSAPP DELIVERY STATUS', [
        'phone' => $data['statuses'][0]['recipient_id'] ?? null,
        'status' => $status,
        'error'  => $data['statuses'][0]['errors'][0] ?? null
    ]);
}

            Log::info('Webhook Hit', [
                'has_messages' => isset($data['messages']),
                'has_statuses' => isset($data['statuses'])
            ]);

            // ✅ Ignore delivery/status events
            if (!isset($data['messages'])) {
                return response('ok', 200);
            }

            $msg   = $data['messages'][0];
            $from  = $msg['from'] ?? null;
            $type  = $msg['type'] ?? null;

            if (!$from || !$type) {
                return response('ok', 200);
            }

            $reply = null;

            /*
            |--------------------------------------------------------------------------
            | TEXT MESSAGE
            |--------------------------------------------------------------------------
            */
            if ($type === 'text') {

                $text  = trim($msg['text']['body'] ?? '');
                $lower = strtolower($text);

                if ($text === '') {
                    return response('ok', 200);
                }

                // ===== SIMPLE COMMANDS =====
                if (in_array($lower, ['hi', 'hello', 'start'])) {

                    $reply = "👋 Welcome to Career Workforce 🌍

How can we assist you?

1. Jobs Abroad
2. Visa Services
3. Register Account
4. Contact Agent

Reply with a number or message.";
                }

                elseif (str_contains($lower, 'website')) {

                    $reply = "🌐 Visit our website:
https://careerworkforce.com";
                }

                elseif (str_contains($lower, 'agent')) {

                    $reply = "👨‍💼 Our agent will contact you shortly.";
                }

                // ===== AI HANDLING =====
                else {

                    $ai = app(AIChatService::class)->handle(
                        $from,
                        $text,
                        'whatsapp',
                        ['phone' => $from]
                    );

                    // ✅ Force string
                    $reply = is_string($ai) ? $ai : json_encode($ai);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | DOCUMENT (CV / PASSPORT)
            |--------------------------------------------------------------------------
            */
            elseif ($type === 'document') {

                $filename = $msg['document']['filename'] ?? 'file';

                $ai = app(AIChatService::class)->handle(
                    $from,
                    '',
                    'file_upload',
                    [
                        'phone' => $from,
                        'type'  => 'document',
                        'file'  => $filename
                    ]
                );

                $reply = is_string($ai) ? $ai : "📄 File received successfully.";
            }

            /*
            |--------------------------------------------------------------------------
            | IMAGE
            |--------------------------------------------------------------------------
            */
            elseif ($type === 'image') {

                $ai = app(AIChatService::class)->handle(
                    $from,
                    '',
                    'file_upload',
                    [
                        'phone' => $from,
                        'type'  => 'image'
                    ]
                );

                $reply = is_string($ai) ? $ai : "🖼️ Image received.";
            }

            /*
            |--------------------------------------------------------------------------
            | AUDIO
            |--------------------------------------------------------------------------
            */
            elseif ($type === 'audio') {

                $ai = app(AIChatService::class)->handle(
                    $from,
                    'voice message',
                    'voice',
                    ['phone' => $from]
                );

                $reply = is_string($ai) ? $ai : "🎤 Voice message received.";
            }

            /*
            |--------------------------------------------------------------------------
            | UNKNOWN TYPE
            |--------------------------------------------------------------------------
            */
            else {
                $reply = "⚠️ Unsupported message type.";
            }

            /*
            |--------------------------------------------------------------------------
            | SEND MESSAGE (SAFE)
            |--------------------------------------------------------------------------
            */
            if ($reply) {
                $this->sendMessage($from, $reply);
            }

            return response('ok', 200);

        } catch (\Exception $e) {

            Log::error('WhatsApp Error: ' . $e->getMessage());
            return response('ok', 200);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SEND MESSAGE (SAFE VERSION)
    |--------------------------------------------------------------------------
    */
    private function sendMessage($to, $message)
    {
        try {

            // ✅ Force string + limit length
            $message = is_string($message)
                ? $message
                : json_encode($message);

            $message = substr($message, 0, 3000);

            $url = "https://graph.facebook.com/" .
                env('WHATSAPP_VERSION') . "/" .
                env('WHATSAPP_PHONE_ID') . "/messages";

            $response = Http::withToken(env('WHATSAPP_TOKEN'))
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message
                    ]
                ]);

            Log::info('WhatsApp Sent', [
                'to' => $to,
                'status' => $response->status()
            ]);

        } catch (\Exception $e) {

            Log::error('Send Message Error: ' . $e->getMessage());
        }
    }
}