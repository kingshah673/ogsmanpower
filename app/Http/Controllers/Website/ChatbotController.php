<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chat\AIChatService;
use App\Models\ChatMessage;
use App\Models\ChatLead;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;

class ChatbotController extends Controller
{
    protected $chat;

    public function __construct(AIChatService $chat)
    {
        $this->chat = $chat;
    }

    /* ===============================
       SEND MESSAGE
    =============================== */
    public function send(Request $request)
{
    $request->validate([
        'message' => 'required|string|max:5000'
    ]);

    $sessionId = session()->getId();

    $message = trim($request->message);

    ChatMessage::create([
        'session_id' => $sessionId,
        'role'       => 'user',
        'message'    => $message
    ]);

    $reply = $this->chat->handle($message);

    ChatMessage::create([
        'session_id' => $sessionId,
        'role'       => 'assistant',
        'message'    => $reply
    ]);

    return response()->json([
        'success' => true,
        'reply'   => $reply
    ]);
}

    /* ===============================
       LOAD CHAT HISTORY
    =============================== */
    public function history()
{
    $sessionId = session()->getId();

    $rows = ChatMessage::where(
            'session_id',
            $sessionId
        )
        ->orderBy('id', 'asc')   // oldest first
        ->take(100)             // more history
        ->get();

    return response()->json([
        'success' => true,
        'messages' => $rows
    ]);
}

    /* ===============================
       FILE UPLOAD
    =============================== */
public function upload(Request $request)
{
    try {

        /* =====================================
           VALIDATION
        ===================================== */

        $request->validate([
            'file' => 'required|file|max:10240'
        ]);

        if (!$request->hasFile('file')) {

            return response()->json([
                'success' => false,
                'reply'   => 'No file selected.'
            ]);
        }

        $file = $request->file('file');

        $ext = strtolower(
            $file->getClientOriginalExtension()
        );

        $allowed = [
            'pdf','doc','docx',
            'jpg','jpeg','png'
        ];

        if (!in_array($ext, $allowed)) {

            return response()->json([
                'success' => false,
                'reply' =>
                'Only PDF, DOC, DOCX, JPG, PNG files allowed.'
            ]);
        }

        /* =====================================
           STORE FILE
        ===================================== */

        $name = time() . '_' .
            preg_replace(
                '/[^A-Za-z0-9\.\-_]/',
                '',
                $file->getClientOriginalName()
            );

        $path = $file->storeAs(
            'chatbot',
            $name,
            'public'
        );

        $sessionId = session()->getId();

        $reply = 'File uploaded successfully.';

        /* =====================================
           CV FILES
        ===================================== */

        if (in_array($ext, ['pdf','doc','docx'])) {

            $fullPath = storage_path(
                'app/public/' . $path
            );

            $cvText = '';

            try {

                /* PDF Parse */
                if ($ext === 'pdf') {

                    $parser = new \Smalot\PdfParser\Parser();

                    $pdf = $parser->parseFile(
                        $fullPath
                    );

                    $cvText = $pdf->getText();
                }

                /* DOC / DOCX fallback */
                if (in_array($ext, ['doc','docx'])) {

                    $cvText =
                    'CV document uploaded';
                }

            } catch (\Exception $e) {

                \Log::error(
                    'CV Parse Error: ' .
                    $e->getMessage()
                );

                $cvText =
                'CV uploaded successfully';
            }

            $text = strtoupper($cvText);

            /* EMAIL */
            preg_match(
                '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i',
                $cvText,
                $em
            );

            $email = $em[0] ?? null;

            /* PHONE */
            preg_match(
                '/(\+92|92|0)?3[0-9]{9}/',
                preg_replace('/\s+/', '', $cvText),
                $ph
            );

            $phone = $ph[0] ?? null;

            /* NAME */
            $lines = preg_split(
                "/\r\n|\n|\r/",
                $cvText
            );

            $fullName = null;

            foreach ($lines as $line) {

                $line = trim($line);

                if (
                    strlen($line) > 4 &&
                    strlen($line) < 35 &&
                    preg_match('/^[A-Za-z ]+$/', $line)
                ) {
                    $fullName = $line;
                    break;
                }
            }

            /* JOB TITLE */
            $jobTitle =
                $this->chat
                ->detectJobTitle($text)
                ?? 'General Worker';

            ChatLead::updateOrCreate(

                [
                    'session_id' => $sessionId
                ],

                [
                    'full_name' => $fullName,
                    'phone'     => $phone,
                    'email'     => $email,

                    'category'  => 'jobs',
                    'status'    => 'new',
                    'priority'  => 'high',

                    'desired_job_title'
                        => $jobTitle,

                    'message'
                        => 'CV uploaded',

                    'source'
                        => 'website'
                ]
            );

            $reply =
"✅ CV received successfully.

👤 Name: " .
($fullName ?: '-') . "

📞 Phone: " .
($phone ?: '-') . "

💼 Profession: " .
($jobTitle ?: '-') . "

Our recruitment team will contact you shortly.";
        }

        /* =====================================
           IMAGE / PASSPORT OCR
        ===================================== */

        if (in_array($ext, ['jpg','jpeg','png'])) {

            $fullPath = storage_path(
                'app/public/' . $path
            );

            $ocrText = '';

            try {

                $response = Http::timeout(90)
                    ->attach(
                        'file',
                        file_get_contents($fullPath),
                        basename($fullPath)
                    )
                    ->post(
                        'https://api.ocr.space/parse/image',
                        [
                            'apikey' =>
                            env('OCR_API_KEY'),

                            'language' => 'eng',
                            'OCREngine' => 2
                        ]
                    );

                $json = $response->json();

                $ocrText =
                $json['ParsedResults'][0]['ParsedText']
                ?? '';

            } catch (\Exception $e) {

                \Log::error(
                    'OCR Error: ' .
                    $e->getMessage()
                );
            }

            preg_match(
                '/[A-Z]{1,2}[0-9]{6,8}/',
                strtoupper($ocrText),
                $m
            );

            $passportNo = $m[0] ?? null;

            ChatLead::updateOrCreate(

                [
                    'session_id' => $sessionId
                ],

                [
                    'passport_no'
                        => $passportNo,

                    'category'
                        => 'visa',

                    'status'
                        => 'new',

                    'priority'
                        => 'high',

                    'message'
                        => 'Passport uploaded',

                    'source'
                        => 'website'
                ]
            );

            $reply =
"✅ Passport uploaded successfully.

🛂 Passport No: " .
($passportNo ?: 'Detected') . "

Our visa consultant will contact you shortly.";
        }

        return response()->json([
            'success' => true,
            'reply'   => $reply,
            'path'    => $path
        ]);

    } catch (\Exception $e) {

        \Log::error(
            'Upload Error: ' .
            $e->getMessage()
        );

        return response()->json([
            'success' => false,
            'reply' =>
            'Unable to process file. Please try again.'
        ]);
    }
}

private function mrzDate($value)
{
    if (!$value || strlen($value) != 6) {
        return null;
    }

    $yy = substr($value, 0, 2);
    $mm = substr($value, 2, 2);
    $dd = substr($value, 4, 2);

    $year = ($yy > date('y'))
        ? '19' . $yy
        : '20' . $yy;

    return $year . '-' . $mm . '-' . $dd;
}

    /* ===============================
       HUMAN HANDOVER
    =============================== */
    public function handover()
    {
        ChatLead::updateOrCreate(
            [
                'session_id' => session()->getId()
            ],
            [
                'priority' => 'high',
                'status'   => 'new',
                'category' => 'support',
                'message'  => 'User requested human support'
            ]
        );

        return response()->json([
            'success' => true,
            'reply'   => 'Our consultant will contact you shortly.'
        ]);
    }
}