<?php

namespace App\Services\AI;

class FallbackAIService
{
    protected $openai;

    public function __construct(
        OpenAIService $openai
    ) {

        $this->openai = $openai;
    }

    /*
    |--------------------------------------------------------------------------
    | Handle AI Fallback
    |--------------------------------------------------------------------------
    */

    public function reply(
        $message
    ) {

        return
            $this->openai
                ->ask($message);
    }
}