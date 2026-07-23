<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AINotification;
use App\Models\AIHandoverRequest;
use App\Models\AIKnowledge;
use App\Models\AIChatMessage;
use App\Models\Job;
use App\Services\Chat\SeekerOnboardingService;
use App\Services\Chat\EmployerOnboardingService;
use App\Services\Chat\AgencyOnboardingService;
use App\Services\Chat\AgentAccountOnboardingService;
use App\Services\Chat\BrokerOnboardingService;
use App\Services\AI\PortalAssistantService;
use App\Services\AI\SophiaContextService;

class WebChatController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CONTEXT (role-aware bootstrap for Sophia widget)
    |--------------------------------------------------------------------------
    */

    public function context()
    {
        return response()->json(
            app(SophiaContextService::class)->build()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHAT
    |--------------------------------------------------------------------------
    */

    public function chat(
        Request $request
    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | VALIDATE
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'message'
                    => 'nullable',

                'attachment'
                    => 'nullable|file|max:10240'
            ]);

            /*
            |--------------------------------------------------------------------------
            | SEEKER ONBOARDING (guided Job Seeker registration)
            | Diverts only seeker-role / in-progress traffic; everything else
            | falls through to the normal KB / default-reply path untouched.
            |--------------------------------------------------------------------------
            */

            $seeker = app(SeekerOnboardingService::class);
            $employer = app(EmployerOnboardingService::class);
            $agencyOnboard = app(AgencyOnboardingService::class);
            $agentAccount = app(AgentAccountOnboardingService::class);
            $brokerOnboard = app(BrokerOnboardingService::class);

            // Role pick from Sophia's "Who are you?" picker.
            if ($request->input('role') === 'seeker') {
                return response()->json(
                    $this->withSeekerFlags($seeker, ['reply' => $seeker->start()])
                );
            }

            if ($request->input('role') === 'employer') {
                return response()->json(
                    $this->withEmployerFlags($employer, ['reply' => $employer->start()])
                );
            }

            if ($request->input('role') === 'agency') {
                return response()->json(
                    $this->withAgencyOnboardFlags($agencyOnboard, ['reply' => $agencyOnboard->start()])
                );
            }

            if ($request->input('role') === 'agent') {
                $inviteToken = $request->input('invite_token')
                    ?: session('agent_invite_token');

                return response()->json(
                    $this->withAgentAccountFlags($agentAccount, [
                        'reply' => $agentAccount->start($inviteToken ? (string) $inviteToken : null),
                    ])
                );
            }

            if ($request->input('role') === 'broker') {
                return response()->json(
                    $this->withBrokerOnboardFlags($brokerOnboard, ['reply' => $brokerOnboard->start()])
                );
            }

            if ($agencyOnboard->isActive()) {
                try {
                    $rawMessage = trim($request->input('message') ?? '');
                    $this->detectAndRememberLang($rawMessage);

                    $attachment = $request->hasFile('attachment')
                        ? $request->file('attachment')->store('ai-chat', 'public')
                        : null;

                    $logMessage = $agencyOnboard->currentStep() === 'agency_password'
                        ? '••••••'
                        : ($attachment ? '📎 (document uploaded)' : $rawMessage);

                    $out = $agencyOnboard->handle($rawMessage, $attachment);
                    $rawReply = is_array($out) ? ($out['reply'] ?? '') : (string) $out;
                    $replyText = (is_array($out) && ! empty($out['no_localize']))
                        ? $rawReply
                        : $this->localize($rawReply);

                    try {
                        AIChatMessage::create($this->chatLogPayload($logMessage, $replyText, $attachment));
                    } catch (\Throwable $logEx) {
                        \Log::warning('[WebChat] agency onboard log skipped: '.$logEx->getMessage());
                    }

                    $payload = is_array($out) ? $out : [];
                    $payload['reply'] = $replyText;

                    return response()->json($this->withAgencyOnboardFlags($agencyOnboard, $payload));
                } catch (\Throwable $e) {
                    \Log::error('[AgencyOnboard] '.$e->getMessage());

                    return response()->json($this->withAgencyOnboardFlags($agencyOnboard, [
                        'reply' => '⚠️ Something went wrong. Please try again or type *cancel*.',
                    ]));
                }
            }

            if ($agentAccount->isActive()) {
                try {
                    $rawMessage = trim($request->input('message') ?? '');
                    $this->detectAndRememberLang($rawMessage);

                    $logMessage = $agentAccount->currentStep() === 'agent_acct_password'
                        ? '••••••'
                        : $rawMessage;

                    $out = $agentAccount->handle($rawMessage);
                    $rawReply = is_array($out) ? ($out['reply'] ?? '') : (string) $out;
                    $replyText = (is_array($out) && ! empty($out['no_localize']))
                        ? $rawReply
                        : $this->localize($rawReply);

                    try {
                        AIChatMessage::create($this->chatLogPayload($logMessage, $replyText));
                    } catch (\Throwable $logEx) {
                        \Log::warning('[WebChat] agent account log skipped: '.$logEx->getMessage());
                    }

                    $payload = is_array($out) ? $out : [];
                    $payload['reply'] = $replyText;

                    return response()->json($this->withAgentAccountFlags($agentAccount, $payload));
                } catch (\Throwable $e) {
                    \Log::error('[AgentAccount] '.$e->getMessage());

                    return response()->json($this->withAgentAccountFlags($agentAccount, [
                        'reply' => '⚠️ Something went wrong. Please try again or type *cancel*.',
                    ]));
                }
            }

            if ($brokerOnboard->isActive()) {
                try {
                    $rawMessage = trim($request->input('message') ?? '');
                    $this->detectAndRememberLang($rawMessage);

                    $logMessage = $brokerOnboard->currentStep() === 'broker_password'
                        ? '••••••'
                        : $rawMessage;

                    $out = $brokerOnboard->handle($rawMessage);
                    $rawReply = is_array($out) ? ($out['reply'] ?? '') : (string) $out;
                    $replyText = (is_array($out) && ! empty($out['no_localize']))
                        ? $rawReply
                        : $this->localize($rawReply);

                    try {
                        AIChatMessage::create($this->chatLogPayload($logMessage, $replyText));
                    } catch (\Throwable $logEx) {
                        \Log::warning('[WebChat] broker onboard log skipped: '.$logEx->getMessage());
                    }

                    $payload = is_array($out) ? $out : [];
                    $payload['reply'] = $replyText;

                    return response()->json($this->withBrokerOnboardFlags($brokerOnboard, $payload));
                } catch (\Throwable $e) {
                    \Log::error('[BrokerOnboard] '.$e->getMessage());

                    return response()->json($this->withBrokerOnboardFlags($brokerOnboard, [
                        'reply' => '⚠️ Something went wrong. Please try again or type *cancel*.',
                    ]));
                }
            }

            if ($employer->isActive()) {
                try {
                    $rawMessage = trim($request->input('message') ?? '');
                    $this->detectAndRememberLang($rawMessage);

                    $employerAttachment = $request->hasFile('attachment')
                        ? $request->file('attachment')->store('ai-chat', 'public')
                        : null;

                    $logMessage = $employer->currentStep() === 'employer_password'
                        ? '••••••'
                        : ($employerAttachment ? '📎 (document uploaded)' : $rawMessage);

                    $out = $employer->handle($rawMessage, $employerAttachment);
                    $rawReply = is_array($out) ? ($out['reply'] ?? '') : (string) $out;
                    $replyText = (is_array($out) && ! empty($out['no_localize']))
                        ? $rawReply
                        : $this->localize($rawReply);

                    try {
                        AIChatMessage::create([
                            'session_id' => session()->getId(),
                            'user_message' => $logMessage,
                            'ai_reply' => $replyText,
                            'attachment' => $employerAttachment,
                            'ip_address' => request()->ip(),
                            'source' => 'webchat',
                        ]);
                    } catch (\Throwable $logEx) {
                        \Log::warning('[WebChat] employer log skipped: '.$logEx->getMessage());
                    }

                    $payload = is_array($out) ? $out : [];
                    $payload['reply'] = $replyText;

                    return response()->json($this->withEmployerFlags($employer, $payload));
                } catch (\Throwable $e) {
                    \Log::error('[Employer] '.$e->getMessage());

                    return response()->json($this->withEmployerFlags($employer, [
                        'reply' => '⚠️ Something went wrong. Please try again or type *cancel*.',
                    ]));
                }
            }

            if ($seeker->isActive()) {

                try {

                    // Use the RAW message (passwords & OTP are case/character sensitive).
                    $rawMessage = trim($request->input('message') ?? '');

                    // Detect language from every message so the bot can switch
                    // language mid-flow (e.g. user types "Urdu mein baat karo").
                    $this->detectAndRememberLang($rawMessage);

                    // ── Language-switch command mid-flow ──────────────────────
                    // Re-ask the current step in the new language WITHOUT
                    // advancing the flow. Context is fully preserved.
                    if ($this->extractLanguageCommand($rawMessage)) {
                        $lang  = ucfirst(session('chatbot_lang', 'English'));
                        $ack   = "✅ Switched to {$lang}!\n\n" . $seeker->repromptCurrentStep();
                        try {
                            AIChatMessage::create([
                                'session_id'   => session()->getId(),
                                'user_message' => $rawMessage,
                                'ai_reply'     => $ack,
                                'attachment'   => null,
                                'ip_address'   => request()->ip(),
                                'source'       => 'webchat',
                            ]);
                        } catch (\Throwable $logEx) {}
                        return response()->json(
                            $this->withSeekerFlags($seeker, ['reply' => $this->localize($ack)])
                        );
                    }

                    $seekerAttachment = $request->hasFile('attachment')
                        ? $request->file('attachment')->store('ai-chat', 'public')
                        : null;

                    // Mask the password step in the stored chat log.
                    $logMessage = $seeker->currentStep() === 'seeker_password'
                        ? '••••••'
                        : ($seekerAttachment ? '📎 (document uploaded)' : $rawMessage);

                    $out = $seeker->handle($rawMessage, $seekerAttachment);

                    $rawReply  = is_array($out) ? ($out['reply'] ?? '') : (string) $out;

                    // Reply in the user's spoken/typed language (no-op for English).
                    // Skip localisation when the step asked us to (e.g. a generated
                    // password whose literal characters must not be translated).
                    $replyText = (is_array($out) && !empty($out['no_localize']))
                        ? $rawReply
                        : $this->localize($rawReply);

                    // Logging must never break the flow — wrap the insert so the
                    // real reply (and any redirect) is still returned on failure.
                    try {
                        AIChatMessage::create([
                            'session_id'   => session()->getId(),
                            'user_message' => $logMessage,
                            'ai_reply'     => $replyText,
                            'attachment'   => $seekerAttachment,
                            'ip_address'   => request()->ip(),
                            'source'       => 'webchat',
                        ]);
                    } catch (\Throwable $logEx) {
                        \Log::warning('[WebChat] seeker log skipped: ' . $logEx->getMessage());
                    }

                    $payload = is_array($out) ? $out : [];
                    $payload['reply'] = $replyText;

                    return response()->json($this->withSeekerFlags($seeker, $payload));

                } catch (\Throwable $e) {

                    \Log::error('[Seeker] ' . $e->getMessage(), [
                        'step'  => $seeker->currentStep(),
                        'trace' => $e->getTraceAsString(),
                    ]);

                    // Never bubble a 500 to the widget — keep the flow alive.
                    return response()->json($this->withSeekerFlags($seeker, [
                        'reply' => '⚠️ Something went wrong on our side. Please try again in a moment, or type *cancel*.',
                    ]));
                }
            }

            /*
            |--------------------------------------------------------------------------
            | AGENT WORKER REGISTRATION (logged-in Sub-Agent / field agent)
            | Manual §7 — register workers via CV + passport in Sophia chat.
            |--------------------------------------------------------------------------
            */

            $agentWorker = app(\App\Services\Chat\AgentWorkerOnboardingService::class);
            $authUser = authUser();

            if ($authUser?->role === 'agent') {
                if ($request->input('portal_action') === 'start_agent_worker') {
                    return response()->json(
                        $this->withAgentWorkerFlags($agentWorker, ['reply' => $agentWorker->start()])
                    );
                }

                if ($agentWorker->isActive()) {
                    try {
                        $rawMessage = trim($request->input('message') ?? '');
                        $this->detectAndRememberLang($rawMessage);

                        $attachment = $request->hasFile('attachment')
                            ? $request->file('attachment')->store('ai-chat', 'public')
                            : null;

                        $logMessage = $attachment ? '📎 (document uploaded)' : $rawMessage;
                        $out = $agentWorker->handle($rawMessage, $attachment);
                        $rawReply = is_array($out) ? ($out['reply'] ?? '') : (string) $out;
                        $replyText = $this->localize($rawReply);

                        try {
                            AIChatMessage::create(
                                $this->chatLogPayload($logMessage, $replyText, $attachment)
                            );
                        } catch (\Throwable $logEx) {
                            \Log::warning('[WebChat] agent worker log skipped: '.$logEx->getMessage());
                        }

                        $payload = is_array($out) ? $out : [];
                        $payload['reply'] = $replyText;

                        return response()->json($this->withAgentWorkerFlags($agentWorker, $payload));
                    } catch (\Throwable $e) {
                        \Log::error('[AgentWorker] '.$e->getMessage());

                        return response()->json($this->withAgentWorkerFlags($agentWorker, [
                            'reply' => '⚠️ Something went wrong. Please try again or type *cancel*.',
                        ]));
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | PORTAL ASSISTANT (logged-in candidate / employer / agency / admin)
            |--------------------------------------------------------------------------
            */

            $portal = app(PortalAssistantService::class);

            if ($portal->shouldHandle($request)
                && ! $seeker->isActive()
                && ! $employer->isActive()
                && ! $agencyOnboard->isActive()
                && ! $agentAccount->isActive()
                && ! $brokerOnboard->isActive()
                && ! $agentWorker->isActive()
            ) {

                $rawMessage = trim($request->input('message') ?? '');
                $this->detectAndRememberLang($rawMessage);

                $portalAttachment = $request->hasFile('attachment')
                    ? $request->file('attachment')->store('ai-chat', 'public')
                    : null;

                $out = $portal->handle($request, $portalAttachment);

                $rawReply = $out['reply'] ?? '';
                $replyText = $this->localize($rawReply);

                try {
                    AIChatMessage::create(
                        $this->chatLogPayload(
                            $portalAttachment ? '📎 (document uploaded)' : ($rawMessage ?: '(action)'),
                            $replyText,
                            $portalAttachment
                        )
                    );
                } catch (\Throwable $logEx) {
                    \Log::warning('[WebChat] portal log skipped: ' . $logEx->getMessage());
                }

                $payload = $out;
                $payload['reply'] = $replyText;

                return response()->json($payload);
            }

            /*
            |--------------------------------------------------------------------------
            | MESSAGE
            |--------------------------------------------------------------------------
            */

            $message =

                strtolower(

                    trim(
                        $request->message ?? ''
                    )
                );

            // Detect user's language from every typed/voice message so all
            // replies can be localised — covers mid-conversation switches.
            $this->detectAndRememberLang($request->message ?? '');

            // ── Pre-role language switch ──────────────────────────────────────
            // User says "Urdu mein baat karo" / "speak in Arabic" before
            // picking any role. Acknowledge in the new language and re-show
            // the role-picker prompt so the user knows what to do next.
            // Short-circuits BEFORE isSeekerIntent() to avoid a wasted GPT call.
            if ($this->extractLanguageCommand($message)) {
                $lang  = session('chatbot_lang', '');
                $reply = ($lang && !in_array($lang, ['en', 'english'], true))
                    ? "✅ Got it! I'll speak in " . ucfirst($lang) . " from now on. 😊\n\n"
                      . "Who are you? Please choose your role:\n\n"
                      . "🔹 *Job Seeker* — looking for overseas work\n"
                      . "🔹 *Employer* — posting jobs\n"
                      . "🔹 *Agency / Recruiter*\n"
                      . "🔹 *Work Permit Specialist*"
                    : "✅ Switched back to English! How can I help you today? 😊";
                try {
                    AIChatMessage::create([
                        'session_id'   => session()->getId(),
                        'user_message' => $message,
                        'ai_reply'     => $reply,
                        'attachment'   => null,
                        'ip_address'   => request()->ip(),
                        'source'       => 'webchat',
                    ]);
                } catch (\Throwable $logEx) {}
                return response()->json(['reply' => $this->localize($reply)]);
            }

            /*
            |--------------------------------------------------------------------------
            | ATTACHMENT
            |--------------------------------------------------------------------------
            */

            $attachment = null;

            if (

                $request->hasFile(
                    'attachment'
                )

            ) {

                $attachment =

                    $request

                    ->file('attachment')

                    ->store(

                        'ai-chat',

                        'public'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | SEEKER INTENT FROM VOICE / TEXT
            | If the user says "I'm a job seeker" (in any language) before picking
            | a role, auto-start the seeker onboarding flow.
            |--------------------------------------------------------------------------
            */

            if (!$seeker->isActive() && $this->isSeekerIntent($message)) {
                $startReply = $seeker->start();
                try {
                    AIChatMessage::create([
                        'session_id'   => session()->getId(),
                        'user_message' => $message,
                        'ai_reply'     => $startReply,
                        'attachment'   => null,
                        'ip_address'   => request()->ip(),
                        'source'       => 'webchat',
                    ]);
                } catch (\Throwable $e) {
                    \Log::warning('[WebChat] seeker-intent log skipped: ' . $e->getMessage());
                }
                return response()->json(
                    $this->withSeekerFlags($seeker, ['reply' => $this->localize($startReply)])
                );
            }

            /*
            |--------------------------------------------------------------------------
            | SMART KNOWLEDGE SEARCH
            |--------------------------------------------------------------------------
            */

            $knowledge =

                AIKnowledge::query()

                ->where(
                    'status',
                    1
                )

                ->get()

                ->filter(function($item) use ($message) {

                    $question =

                        strtolower(
                            $item->question ?? ''
                        );

                    $title =

                        strtolower(
                            $item->title ?? ''
                        );

                    $category =

                        strtolower(
                            $item->category ?? ''
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | EXACT MATCH
                    |--------------------------------------------------------------------------
                    */

                    if (

                        str_contains(
                            $message,
                            $question
                        )

                    ) {

                        return true;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | WORD MATCH
                    |--------------------------------------------------------------------------
                    */

                    $words =

                        explode(
                            ' ',
                            $message
                        );

                    foreach ($words as $word) {

                        if (

                            strlen($word) < 3

                        ) {

                            continue;
                        }

                        if (

                            str_contains(
                                $question,
                                $word
                            )

                            ||

                            str_contains(
                                $title,
                                $word
                            )

                            ||

                            str_contains(
                                $category,
                                $word
                            )

                        ) {

                            return true;
                        }
                    }

                    return false;

                })

                ->first();

            /*
            |--------------------------------------------------------------------------
            | KNOWLEDGE FOUND
            |--------------------------------------------------------------------------
            */

            if ($knowledge) {

                try {

                    AIChatMessage::create([

                        'session_id'
                            => session()->getId(),

                        'user_message'
                            => $message,

                        'ai_reply'
                            => $knowledge->answer,

                        'attachment'
                            => $attachment,

                        'ip_address'
                            => request()->ip(),

                        'source'
                            => 'webchat'
                    ]);

                    /*
                    |--------------------------------------------------------------------------
                    | NOTIFICATION
                    |--------------------------------------------------------------------------
                    */

                    AINotification::create([

                        'title'
                            => 'New AI Chat Message',

                        'message'
                            => $message,

                        'type'
                            => 'chat'
                    ]);

                } catch (\Throwable $logEx) {
                    \Log::warning('[WebChat] kb log skipped: ' . $logEx->getMessage());
                }

                return response()->json([

                    'reply'
                        => $this->localize($knowledge->answer),

                    'attachment'
                        => $attachment
                            ? asset(
                                'storage/' . $attachment
                            )
                            : null
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | DEFAULT REPLY
            |--------------------------------------------------------------------------
            */

            $reply =

                $this->defaultReply(
                    $message
                );

            /*
            |--------------------------------------------------------------------------
            | SAVE CHAT
            |--------------------------------------------------------------------------
            */

            try {

                AIChatMessage::create([

                    'session_id'
                        => session()->getId(),

                    'user_message'
                        => $message,

                    'ai_reply'
                        => $reply,

                    'attachment'
                        => $attachment,

                    'ip_address'
                        => request()->ip(),

                    'source'
                        => 'webchat'
                ]);

            } catch (\Throwable $logEx) {
                \Log::warning('[WebChat] default log skipped: ' . $logEx->getMessage());
            }

            /*
            |--------------------------------------------------------------------------
            | RESPONSE
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'reply'
                    => $this->localize($reply),

                'attachment'
                    => $attachment
                        ? asset(
                            'storage/' . $attachment
                        )
                        : null
            ]);

        } catch (\Exception $e) {

            \Log::error('[WebChat] ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([

                'reply'
                    => '⚠️ I hit a temporary problem. Please try again in a moment.'

            ],500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VOICE → TEXT (Whisper, any language)
    |--------------------------------------------------------------------------
    | The widget records audio and posts it here. Whisper auto-detects the
    | spoken language; we remember it so the bot replies in the same language.
    */

    public function transcribe(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|max:25600', // 25 MB
        ]);

        try {

            $path = $request->file('audio')->store('temp/ai-voice', 'public');

            $result = app(\App\Services\AI\SpeechToTextService::class)
                ->transcribeWithLanguage($path);

            // We have the transcript now — drop the temp audio.
            try { \Storage::disk('public')->delete($path); } catch (\Throwable $e) {}

            $text = trim((string) ($result['text'] ?? ''));
            $lang = $result['language'] ?? null;

            // Whisper sometimes mislabels accented English as another language.
            // If every character in the transcript is ASCII (Latin), the user
            // spoke English — ignore Whisper's non-English label and clear any
            // previous non-English session language so replies stay in English.
            if ($lang && !in_array(strtolower($lang), ['en', 'english'], true)) {
                $hasNonAscii = preg_match('/[^\x00-\x7F]/', $text);
                if (!$hasNonAscii) {
                    $lang = null;
                    session()->forget('chatbot_lang'); // clear stale non-English lang
                }
            }

            if ($lang) {
                session(['chatbot_lang' => $lang]);
            }

            if ($text === '') {
                return response()->json([
                    'error' => 'unrecognized',
                    'reply' => "Sorry, I couldn't catch that. Please try again.",
                ]);
            }

            return response()->json(['text' => $text, 'lang' => $lang]);

        } catch (\Throwable $e) {

            \Log::error('[WebChat] transcribe: ' . $e->getMessage());

            return response()->json([
                'error' => 'failed',
                'reply' => 'Voice transcription failed — please type your message instead.',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LOCALISE A REPLY into the user's language (no-op for English)
    |--------------------------------------------------------------------------
    */

    private function localize($text)
    {
        if (!is_string($text) || trim($text) === '') {
            return $text;
        }

        $lang = trim((string) session('chatbot_lang'));

        if ($lang === '' || in_array(strtolower($lang), ['en', 'english'], true)) {
            return $text;
        }

        try {
            $translated = app(\App\Services\AI\AITranslatorService::class)
                ->translate($text, ucfirst($lang));

            return ($translated && trim($translated) !== '') ? $translated : $text;
        } catch (\Throwable $e) {
            \Log::warning('[WebChat] localize: ' . $e->getMessage());
            return $text;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LANGUAGE DETECTION — called on every incoming message
    |
    | Priority:
    |   1. Explicit switch command ("Urdu mein baat karo", "speak in Arabic")
    |      → detected without any API call, updates the session immediately.
    |   2. Non-ASCII text with no session language yet → translateWithDetect()
    |      (one GPT call) to infer the language.
    |   3. All-ASCII message that doesn't match a switch command → no action
    |      (avoids unnecessary API calls for normal English chat).
    |
    | Side-effect only: sets session('chatbot_lang') so localize() works.
    |--------------------------------------------------------------------------
    */

    private function detectAndRememberLang(string $msg): void
    {
        if (trim($msg) === '') return;

        // 1. Explicit language-switch command — no API call needed.
        $lang = $this->extractLanguageCommand($msg);
        if ($lang) {
            if (in_array(strtolower($lang), ['en', 'english'], true)) {
                session()->forget('chatbot_lang');
            } else {
                session(['chatbot_lang' => strtolower($lang)]);
            }
            return;
        }

        // 2. Non-ASCII + lang not yet in session → detect via GPT.
        if (!session('chatbot_lang') && preg_match('/[^\x00-\x7F]/', $msg)) {
            try {
                $result = app(\App\Services\AI\AITranslatorService::class)
                    ->translateWithDetect($msg);
                $detected = strtolower(trim($result['language'] ?? ''));
                if ($detected && !in_array($detected, ['en', 'english'], true)) {
                    session(['chatbot_lang' => $detected]);
                }
            } catch (\Throwable $e) {}
        }
    }

    /*
     * Extract a language name from an explicit switch request.
     * Matches patterns in any Latin-script form:
     *   "Urdu mein baat karo" / "speak in Urdu" / "reply in Arabic" / "in French please"
     * Returns the language name (string) or null if no command found.
     */
    private function extractLanguageCommand(string $msg): ?string
    {
        $supported = [
            'urdu', 'hindi', 'arabic', 'english', 'french', 'spanish',
            'german', 'turkish', 'persian', 'farsi', 'pashto', 'bengali',
            'punjabi', 'sindhi', 'balochi', 'chinese', 'russian', 'italian',
        ];

        $lower = strtolower(trim($msg));

        foreach ($supported as $lang) {
            // "X mein baat karo" / "X mein" (Roman Urdu/Hindi pattern)
            if (preg_match('/\b' . $lang . '\s+mein\b/i', $lower)) return $lang;

            // "speak/reply/respond/talk/write/answer in X"
            if (preg_match('/\b(speak|reply|respond|talk|write|answer|communicate|chat|continue)\s+in\s+' . $lang . '\b/i', $lower)) return $lang;

            // "in X please" / "please reply in X"
            if (preg_match('/\bin\s+' . $lang . '(\s+please)?\b/i', $lower)) return $lang;

            // "X language" / "use X"
            if (preg_match('/\b(use|switch\s+to)\s+' . $lang . '\b/i', $lower)) return $lang;
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | SEEKER INTENT DETECTION
    | Works for any language: English keywords first, then translate non-ASCII
    | text and re-check. Covers voice phrases like "I'm looking for a job".
    |--------------------------------------------------------------------------
    */

    private function isSeekerIntent(string $msg): bool
    {
        $keywords = [
            // English
            'seeker', 'job seeker', 'seeking job', 'seeking work', 'seeking employment',
            'looking for job', 'looking for work', 'need job', 'find job', 'want job',
            'job search', 'find work', 'searching for job', 'i am seeker', 'i am a seeker',
            'overseas job', 'abroad job', 'foreign job', 'register as',
            // Roman Urdu / Hindi transliterations (Latin script — NOT non-ASCII)
            'naukri', 'naukari', 'rozgar', 'mulazmat', 'talash',
            'job chahie', 'job chahiye', 'naukri chahie', 'naukari chahie',
            'naukri chahiye', 'naukari chahiye',
            'job sikar', 'job sekar', 'sikar', 'sekar',   // Roman Urdu "seeker"
            'account banana',                              // "want to create account"
        ];

        $lower = strtolower($msg);
        foreach ($keywords as $k) {
            if (str_contains($lower, $k)) return true;
        }

        // For any unmatched text, use ONE GPT call that both translates to English
        // AND detects the source language. The detected language is stored in the
        // session so localize() will reply in the user's own language for the
        // entire seeker flow (covers Roman Urdu, Arabic script, etc.).
        try {
            $result = app(\App\Services\AI\AITranslatorService::class)
                ->translateWithDetect($msg);

            $en   = strtolower($result['translation'] ?? '');
            $lang = trim($result['language'] ?? '');

            if ($lang && !in_array(strtolower($lang), ['en', 'english'], true)) {
                session(['chatbot_lang' => strtolower($lang)]);
            }

            if ($en && $en !== $lower) {
                foreach ($keywords as $k) {
                    if (str_contains($en, $k)) return true;
                }
            }
        } catch (\Throwable $e) {}

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | DEFAULT REPLIES
    |--------------------------------------------------------------------------
    */

    protected function defaultReply(
        $message
    ) {

        /*
        |--------------------------------------------------------------------------
        | JOB SEARCH
        |--------------------------------------------------------------------------
        */

        $jobKeywords = [

            'job',

            'jobs',

            'vacancy',

            'vacancies',

            'work',

            'career'
        ];

        foreach ($jobKeywords as $keyword) {

            if (

                str_contains(
                    $message,
                    $keyword
                )

            ) {

                $jobs =

                    Job::query()

                    ->where(
                        'status',
                        1
                    )

                    ->latest()

                    ->take(5)

                    ->get();

                if (
                    !$jobs->count()
                ) {

                    return
                        '❌ No jobs available currently.';
                }

                $response =

                    "🌍 Latest Overseas Jobs:\n\n";

                foreach ($jobs as $job) {

                    $response .=

                        "🔹 "

                        .

                        ($job->title ?? 'Job')

                        .

                        "\n";

                    if (
                        isset($job->country)
                    ) {

                        $response .=

                            "📍 "

                            .

                            $job->country

                            .

                            "\n";
                    }

                    if (
                        isset($job->salary)
                    ) {

                        $response .=

                            "💰 "

                            .

                            $job->salary

                            .

                            "\n";
                    }

                    $response .= "\n";
                }

                return $response;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | VISA
        |--------------------------------------------------------------------------
        */

        if (

            str_contains(
                $message,
                'visa'
            )

        ) {

            return

                '🛂 Visa processing usually takes 7 to 14 working days.';
        }

        /*
        |--------------------------------------------------------------------------
        | INTERVIEW
        |--------------------------------------------------------------------------
        */

        if (

            str_contains(
                $message,
                'interview'
            )

        ) {

            return

                '🎤 Our HR team will contact you shortly.';
        }

        /*
        |--------------------------------------------------------------------------
        | APPLY
        |--------------------------------------------------------------------------
        */

        if (

            str_contains(
                $message,
                'apply'
            )

        ) {

            return

                "📄 Required Documents:\n\n"

                .

                "✅ CV\n"

                .

                "✅ Passport Copy\n"

                .

                "✅ Passport Size Photo";
        }

        /*
        |--------------------------------------------------------------------------
        | HELLO
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(hello|hi|hey)\b/', $message)) {

            return

                '👋 Hello! Welcome to OGS AI Assistant.';
        }

        /*
        |--------------------------------------------------------------------------
        | HANDOVER
        |--------------------------------------------------------------------------
        */

        try {

            AIHandoverRequest::create([

                'session_id'
                    => session()->getId(),

                'user_message'
                    => $message,

                'status'
                    => 'pending',

                'ip_address'
                    => request()->ip()
            ]);

            /*
            |--------------------------------------------------------------------------
            | NOTIFICATION
            |--------------------------------------------------------------------------
            */

            AINotification::create([

                'title'
                    => 'New Handover Request',

                'message'
                    => $message,

                'type'
                    => 'handover'
            ]);

        } catch (\Throwable $logEx) {
            \Log::warning('[WebChat] handover log skipped: ' . $logEx->getMessage());
        }

        return

            "🤖 I could not fully understand your request.\n\n"

            .

            "✅ Your message has been forwarded to our consultant team.";
    }

    /*
    |--------------------------------------------------------------------------
    | HUMAN MODE
    |--------------------------------------------------------------------------
    */

    public function humanMode(
        $session
    ) {

        $human =

            AIChatMessage::query()

            ->where(
                'session_id',
                $session
            )

            ->where(
                'human_mode',
                1
            )

            ->exists();

        return response()->json([

            'human_mode'
                => $human
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FETCH ADMIN REPLIES
    |--------------------------------------------------------------------------
    */

    public function replies(
        $session
    ) {

        $messages =

            AIChatMessage::query()

            ->where(
                'session_id',
                $session
            )

            ->where(
                'is_admin',
                1
            )

            ->latest()

            ->get();

        return response()->json(
            $messages
        );
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withSeekerFlags(SeekerOnboardingService $seeker, array $payload = []): array
    {
        if ($seeker->isActive()) {
            $payload['seeker_active'] = true;
            $payload['seeker_step'] = $seeker->currentStep();
            $payload['accepts_documents'] = true;
            $payload['document_hint'] = match ($seeker->currentStep()) {
                'seeker_cv' => 'Attach your CV with the paperclip button below.',
                'seeker_passport' => 'Attach your passport image with the paperclip button below.',
                default => 'Attach documents with the paperclip button below.',
            };
        } else {
            $payload['seeker_active'] = false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withAgentWorkerFlags(\App\Services\Chat\AgentWorkerOnboardingService $worker, array $payload = []): array
    {
        if ($worker->isActive()) {
            $payload['agent_worker_active'] = true;
            $payload['seeker_active'] = false;
            $payload['accepts_documents'] = true;
            $payload['document_hint'] = match ($worker->currentStep()) {
                'agent_worker_cv' => 'Attach the worker\'s CV with the paperclip button below.',
                'agent_worker_passport' => 'Attach the worker\'s passport image with the paperclip button below.',
                default => 'Attach documents with the paperclip button below.',
            };
        } else {
            $payload['agent_worker_active'] = $payload['agent_worker_active'] ?? false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withEmployerFlags(EmployerOnboardingService $employer, array $payload = []): array
    {
        if ($employer->isActive()) {
            $payload['employer_active'] = true;
            $payload['employer_step'] = $employer->currentStep();
            $payload['seeker_active'] = false;
            $payload['accepts_documents'] = true;
            $payload['document_hint'] = match ($employer->currentStep()) {
                'employer_trade_license' => 'Attach your trade license with the paperclip button below.',
                default => 'Attach documents with the paperclip button below.',
            };
        } else {
            $payload['employer_active'] = false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withAgencyOnboardFlags(AgencyOnboardingService $agency, array $payload = []): array
    {
        if ($agency->isActive()) {
            $payload['agency_active'] = true;
            $payload['agency_step'] = $agency->currentStep();
            $payload['seeker_active'] = false;
            $payload['employer_active'] = false;
            $payload['accepts_documents'] = true;
            $payload['document_hint'] = match ($agency->currentStep()) {
                'agency_license' => 'Enter your license number and/or attach a license scan with the paperclip.',
                default => 'Type your answer or attach documents with the paperclip button below.',
            };
        } else {
            $payload['agency_active'] = false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withAgentAccountFlags(AgentAccountOnboardingService $agent, array $payload = []): array
    {
        if ($agent->isActive()) {
            $payload['agent_account_active'] = true;
            $payload['agent_account_step'] = $agent->currentStep();
            $payload['seeker_active'] = false;
            $payload['employer_active'] = false;
            $payload['agency_active'] = false;
            $payload['accepts_documents'] = false;
        } else {
            $payload['agent_account_active'] = false;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function withBrokerOnboardFlags(BrokerOnboardingService $broker, array $payload = []): array
    {
        if ($broker->isActive()) {
            $payload['broker_active'] = true;
            $payload['broker_step'] = $broker->currentStep();
            $payload['seeker_active'] = false;
            $payload['employer_active'] = false;
            $payload['agency_active'] = false;
            $payload['accepts_documents'] = false;
        } else {
            $payload['broker_active'] = false;
        }

        return $payload;
    }

    protected function chatLogPayload(string $userMessage, string $aiReply, ?string $attachment = null): array
    {
        $user = authUser();
        $admin = auth('admin')->user();
        $ctx = app(SophiaContextService::class)->build();

        return [
            'session_id' => session()->getId(),
            'user_id' => $user?->id,
            'admin_id' => $admin?->id,
            'portal_role' => $ctx['role'] ?? null,
            'user_message' => $userMessage,
            'ai_reply' => $aiReply,
            'attachment' => $attachment,
            'ip_address' => request()->ip(),
            'source' => 'webchat',
        ];
    }
}