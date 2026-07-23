<?php

namespace App\Services\AI;

use OpenAI;
use OpenAI\Client;
use RuntimeException;
use Illuminate\Support\Facades\Storage;

class TextToSpeechService
{
    protected ?Client $client = null;

    protected function client(): Client
    {
        if ($this->client) {
            return $this->client;
        }

        $apiKey = (string) (config('services.openai.key') ?: env('OPENAI_API_KEY') ?: '');
        if ($apiKey === '') {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $this->client = OpenAI::client($apiKey);

        return $this->client;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE AUDIO
    |--------------------------------------------------------------------------
    */

    public function generate($text)
    {
        try {
            $response = $this->client()
                ->audio()
                ->speech([
                    'model' => 'gpt-4o-mini-tts',
                    'voice' => 'alloy',
                    'input' => $text,
                ]);

            $name = 'voice_'.time().'.mp3';
            $path = 'voices/'.$name;

            Storage::disk('public')->put($path, $response);

            return $path;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return null;
        }
    }
}
