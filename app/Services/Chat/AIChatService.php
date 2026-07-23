<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Models\FailedAIMessage;

use App\Services\AI\LanguageService;
use App\Services\AI\AITranslatorService;
use App\Services\AI\AISettingsService;
use App\Services\AI\PromptSecurityService;
use App\Services\Chat\SessionService;
use App\Services\Chat\IntentService;

class AIChatService
{
    protected $conversation;

    protected $language;

    protected $translator;

    protected $settings;

    protected $security;
    protected $session;
    protected $intent;

    public function __construct(

        ConversationService $conversation,

        LanguageService $language,

        AITranslatorService $translator,

        AISettingsService $settings,

        PromptSecurityService $security,
        SessionService $session,
        IntentService $intent

    ) {

        $this->conversation = $conversation;

        $this->language = $language;

        $this->translator = $translator;

        $this->settings = $settings;

        $this->security = $security;
        $this->session = $session;
        $this->intent = $intent;
    }

    /*
    |--------------------------------------------------------------------------
    | HANDLE CHAT
    |--------------------------------------------------------------------------
    */

    public function handle(

        $message,

        $channel = 'web',

        $phone = null,

        $userId = null

    ) {

        try {

            /*
            |--------------------------------------------------------------------------
            | CLEAN MESSAGE
            |--------------------------------------------------------------------------
            */

            $message =
                $this->clean(
                    $message
                );

            /*
            |--------------------------------------------------------------------------
            | VALIDATE
            |--------------------------------------------------------------------------
            */

            if (

                !$this->valid(
                    $message
                )

            ) {

                return
                    'Invalid message.';
            }

            /*
            |--------------------------------------------------------------------------
            | AI ENABLED
            |--------------------------------------------------------------------------
            */

            if (

                !$this->settings
                    ->enabled(
                        'ai_enabled'
                    )

            ) {

                return
                'AI assistant is currently unavailable.';
            }

            /*
            |--------------------------------------------------------------------------
            | PROMPT SECURITY
            |--------------------------------------------------------------------------
            */

            if (

                !$this->security
                    ->clean(
                        $message
                    )

            ) {

                return
                'Invalid request detected.';
            }

            /*
            |--------------------------------------------------------------------------
            | USER
            |--------------------------------------------------------------------------
            */

            $user = null;

            if ($userId) {

                $user =
                    User::find(
                        $userId
                    );
            }

            if (

                !$user
                &&
                $phone

            ) {

                $user =
                    User::where(

                        'whatsapp',

                        $phone

                    )->first();
            }

            /*
            |--------------------------------------------------------------------------
            | LANGUAGE
            |--------------------------------------------------------------------------
            */

            $lang =
                $this->language
                    ->detect(
                        $message
                    );

            $originalLanguage =
                $this->language
                    ->name(
                        $lang
                    );

            /*
            |--------------------------------------------------------------------------
            | TRANSLATE
            |--------------------------------------------------------------------------
            */

            $englishMessage =
                $message;

            if (

                $lang !== 'en'

                &&

                $this->settings
                    ->enabled(
                        'multilingual_enabled'
                    )

            ) {

                $translated =
                    $this->translator
                        ->translate(

                            $message,

                            'English'
                        );

                if ($translated) {

                    $englishMessage =
                        $translated;
                }
            }
            
            

          /*
|--------------------------------------------------------------------------
| INTENT DETECTION
|--------------------------------------------------------------------------
*/

$intent =
    $this->intent->detect(
        $englishMessage
    );

\Log::info('INTENT DETECTED', [

    'message' => $englishMessage,

    'intent'  => $intent

]);

if ($intent === 'menu_option') {

    $identifier =
        $phone
            ? $phone
            : ('web_' . ($user?->id ?? 'guest'));

    $chatSession =
        $this->session->get(

            $channel,

            $identifier

        );

    return
        $this->conversation
            ->handle(

                $chatSession,

                $message

            );
}

/*
|--------------------------------------------------------------------------
| MAIN MENU
|--------------------------------------------------------------------------
*/

if (

    $intent === 'greeting'

    ||

    $intent === 'main_menu'

) {

    $identifier =
        $phone
            ? $phone
            : ('web_' . ($user?->id ?? 'guest'));

    $chatSession =
        $this->session->get(

            $channel,

            $identifier

        );

    $chatSession->update([

        'current_step' => 'main_menu',

        'status' => 'active',

        'intent' => null

    ]);

    return

        "👋 Welcome to Career Workforce AI Assistant\n\n"

        .

        "I can help you with:\n\n"

        .

        "1️⃣ Find Jobs\n"

        .

        "2️⃣ Hire Staff\n"

        .

        "3️⃣ Visa Services\n"

        .

        "4️⃣ Register Account\n"

        .

        "5️⃣ Check Application Status\n"

        .

        "6️⃣ Talk to Consultant\n\n"

        .

        "You can also ask me a question directly.";
}
            
            
/*
|--------------------------------------------------------------------------
| CHAT SESSION
|--------------------------------------------------------------------------
*/

$identifier =
    $phone
        ? $phone
        : ('web_' . ($user?->id ?? 'guest'));

$chatSession =
    $this->session->get(

        $channel,

        $identifier

    );

/*
|--------------------------------------------------------------------------
| CONVERSATION
|--------------------------------------------------------------------------
*/

$reply =
    $this->conversation
        ->handle(

            $chatSession,

            $englishMessage

        );

            /*
            |--------------------------------------------------------------------------
            | FALLBACK
            |--------------------------------------------------------------------------
            */

            if (!$reply) {

                $reply =
                    'Sorry, I could not understand your request.';
            }

            /*
            |--------------------------------------------------------------------------
            | TRANSLATE BACK
            |--------------------------------------------------------------------------
            */

            if (

                $lang !== 'en'

                &&

                $this->settings
                    ->enabled(
                        'multilingual_enabled'
                    )

            ) {

                $translatedReply =
                    $this->translator
                        ->translate(

                            $reply,

                            $originalLanguage
                        );

                if ($translatedReply) {

                    $reply =
                        $translatedReply;
                }
            }

            return $reply;

        } catch (\Exception $e) {

            /*
            |--------------------------------------------------------------------------
            | SAVE FAILED AI
            |--------------------------------------------------------------------------
            */

            FailedAIMessage::create([

    'question' => $message,

    'message'  => $message,

    'reason'   => $e->getMessage(),

    'channel'  => $channel,

    'resolved' => 0

]);

            \Log::error(
                $e->getMessage()
            );

            return
                'AI assistant is temporarily unavailable.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CLEAN MESSAGE
    |--------------------------------------------------------------------------
    */

    public function clean(
        $message
    ) {

        $message =
            strip_tags($message);

        $message =
            trim($message);

        $message =
            preg_replace(

                '/\s+/',

                ' ',

                $message
            );

        return $message;
    }

    /*
    |--------------------------------------------------------------------------
    | VALID MESSAGE
    |--------------------------------------------------------------------------
    */

    public function valid(
        $message
    ) {

        if (!$message) {

            return false;
        }

        if (

            strlen($message)
            < 1

        ) {

            return false;
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | USER ROLE
    |--------------------------------------------------------------------------
    */

    public function role(
        $user
    ) {

        if (!$user) {

            return 'guest';
        }

        return
            $user->role
            ?? 'guest';
    }

    /*
    |--------------------------------------------------------------------------
    | IS WHATSAPP
    |--------------------------------------------------------------------------
    */

    public function isWhatsApp(
        $channel
    ) {

        return
            $channel === 'whatsapp';
    }

    /*
    |--------------------------------------------------------------------------
    | IS WEB
    |--------------------------------------------------------------------------
    */

    public function isWeb(
        $channel
    ) {

        return
            $channel === 'web';
    }
}