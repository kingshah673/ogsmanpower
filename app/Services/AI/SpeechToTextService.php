<?php

namespace App\Services\AI;

use OpenAI;
use OpenAI\Client;
use RuntimeException;

class SpeechToTextService
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
    | TRANSCRIBE AUDIO
    |--------------------------------------------------------------------------
    */

    public function transcribe($path)
    {
        try {
            $response = $this->client()
                ->audio()
                ->transcribe([
                    'model' => 'whisper-1',
                    'file' => fopen(
                        storage_path('app/public/'.$path),
                        'r'
                    ),
                ]);

            return $response->text ?? null;
        } catch (\Exception $e) {
            \Log::error($e->getMessage());

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSCRIBE + DETECT LANGUAGE
    |--------------------------------------------------------------------------
    | Returns ['text' => ..., 'language' => ...]. Whisper auto-detects the
    | spoken language; verbose_json exposes it so the bot can reply in kind.
    */

    public function transcribeWithLanguage($path): array
    {
        try {
            $response = $this->client()
                ->audio()
                ->transcribe([
                    'model' => 'whisper-1',
                    'response_format' => 'verbose_json',
                    'file' => fopen(
                        storage_path('app/public/'.$path),
                        'r'
                    ),
                ]);

            return [
                'text' => $response->text ?? null,
                'language' => $response->language ?? null,
            ];
        } catch (\Exception $e) {
            \Log::error('SpeechToText: '.$e->getMessage());

            return ['text' => null, 'language' => null];
        }
    }
}
