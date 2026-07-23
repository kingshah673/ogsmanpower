<?php

namespace App\Services\AI;

use App\Services\OpenAI\OpenAIService;

class AITranslatorService
{
    protected $openai;

    public function __construct(
        OpenAIService $openai
    ) {

        $this->openai = $openai;
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSLATE
    |--------------------------------------------------------------------------
    */

    public function translate(

        $text,

        $target = 'English'

    ) {

        $prompt = "

Translate this text into {$target}.

Return ONLY translated text.

Text:

".$text;

        return
            $this->openai
                ->ask(
                    $prompt,
                    'translation'
                );
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSLATE + DETECT LANGUAGE — one GPT call, returns both.
    |
    | Returns: ['language' => 'Urdu', 'translation' => 'I want a job']
    | The 'language' key is the source language in English (e.g. "Urdu",
    | "Arabic", "Hindi", "English"). Use this when you need to know WHAT
    | language the user wrote/spoke in so you can reply in the same language.
    |--------------------------------------------------------------------------
    */

    public function translateWithDetect(string $text): array
    {
        $prompt = "You receive a chat message. Do two things:\n"
            . "1. Detect the language it is written in.\n"
            . "2. Translate it to English.\n\n"
            . "Reply in EXACTLY this format (two lines, nothing else):\n"
            . "LANG: <language name in English, e.g. Urdu, Arabic, Hindi, English>\n"
            . "TEXT: <English translation>\n\n"
            . "Message:\n" . $text;

        $raw = $this->openai->ask($prompt, 'translate_detect');

        $lang        = null;
        $translation = $text;

        if ($raw && preg_match('/^LANG:\s*(.+)$/mi', $raw, $m)) {
            $lang = trim($m[1]);
        }
        if ($raw && preg_match('/^TEXT:\s*(.+)$/mi', $raw, $m)) {
            $translation = trim($m[1]);
        }

        return ['language' => $lang, 'translation' => $translation];
    }
}