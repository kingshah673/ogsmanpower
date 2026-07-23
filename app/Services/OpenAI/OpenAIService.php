<?php

namespace App\Services\OpenAI;

use OpenAI;
use App\Models\AIUsageLog;

class OpenAIService
{
    protected $client = null;

    protected function client()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $apiKey = config('services.openai.key');

        if (! $apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY is not configured.');
        }

        return $this->client = OpenAI::client($apiKey);
    }

    public function __construct()
    {
        // Lazy-init client so resolving this service does not throw when the key is missing.
    }

    /*
    |--------------------------------------------------------------------------
    | ASK GPT
    |--------------------------------------------------------------------------
    */

    public function ask(

        $prompt,

        $module = 'general',

        $userId = null

    ) {

        /*
        |--------------------------------------------------------------------------
        | CACHE
        |--------------------------------------------------------------------------
        */

        $cacheKey =
            md5($prompt);

        if (

            cache()->has(
                $cacheKey
            )

        ) {

            return cache()->get(
                $cacheKey
            );
        }

        try {

            $response =
                $this->client()
                    ->chat()
                    ->create([

                        'model'
                            => config('services.openai.model'),

                        'messages' => [

                            [

                                'role'
                                    => 'system',

                                'content'
                                    =>
                                    'You are an AI recruitment assistant.'
                            ],

                            [

                                'role'
                                    => 'user',

                                'content'
                                    => $prompt
                            ]
                        ]
                    ]);

            $content =

                $response

                ->choices[0]

                ->message

                ->content;

            /*
            |--------------------------------------------------------------------------
            | TOKENS
            |--------------------------------------------------------------------------
            */

            $promptTokens =
                $response
                    ->usage
                    ->promptTokens
                    ?? 0;

            $completionTokens =
                $response
                    ->usage
                    ->completionTokens
                    ?? 0;

            $totalTokens =
                $response
                    ->usage
                    ->totalTokens
                    ?? 0;

            /*
            |--------------------------------------------------------------------------
            | COST ESTIMATE
            |--------------------------------------------------------------------------
            */

            $cost =
                ($totalTokens / 1000)
                * 0.001;

            /*
            |--------------------------------------------------------------------------
            | SAVE LOG
            |--------------------------------------------------------------------------
            */

            try {
                AIUsageLog::create([

                    'user_id'
                        => $userId,

                    'module'
                        => $module,

                    'model'
                        => config('services.openai.model'),

                    'prompt_tokens'
                        => $promptTokens,

                    'completion_tokens'
                        => $completionTokens,

                    'total_tokens'
                        => $totalTokens,

                    'cost'
                        => $cost,

                    'prompt'
                        => $prompt,

                    'response'
                        => $content
                ]);
            } catch (\Exception $logEx) {
                \Log::warning('OpenAIService::ask — usage log failed: ' . $logEx->getMessage());
            }

            /*
            |--------------------------------------------------------------------------
            | CACHE RESULT
            |--------------------------------------------------------------------------
            */

            cache()->put(

                $cacheKey,

                $content,

                now()->addHours(12)
            );

            return $content;

        } catch (\Exception $e) {

            \Log::error(
                $e->getMessage()
            );

            return null;
        }
    }

    /**
     * Structured JSON response (best for CV parsing).
     */
    public function askJson(
        string $prompt,
        string $system = 'You extract structured recruitment data. Return valid JSON only.',
        string $module = 'json',
        ?int $userId = null,
        ?string $model = null,
        bool $useCache = true,
    ): ?array {
        $cacheKey = 'json:' . md5(($model ?? '') . $system . $prompt);

        if ($useCache && cache()->has($cacheKey)) {
            $cached = cache()->get($cacheKey);

            return is_array($cached) ? $cached : json_decode((string) $cached, true);
        }

        $model = $model ?: config('services.openai.model');

        try {
            $response = $this->client()->chat()->create([
                'model' => $model,
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            $content = trim($response->choices[0]->message->content ?? '');
            $content = preg_replace('/```json|```/i', '', $content);
            $json    = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($json)) {
                return null;
            }

            try {
                AIUsageLog::create([
                    'user_id'           => $userId,
                    'module'            => $module,
                    'model'             => $model,
                    'prompt_tokens'     => $response->usage->promptTokens ?? 0,
                    'completion_tokens' => $response->usage->completionTokens ?? 0,
                    'total_tokens'      => $response->usage->totalTokens ?? 0,
                    'cost'              => (($response->usage->totalTokens ?? 0) / 1000) * 0.001,
                    'prompt'            => $prompt,
                    'response'          => $content,
                ]);
            } catch (\Exception $logEx) {
                \Log::warning('OpenAIService::askJson — usage log failed: ' . $logEx->getMessage());
            }

            if ($useCache) {
                cache()->put($cacheKey, $json, now()->addHours(6));
            }

            return $json;
        } catch (\Exception $e) {
            \Log::error('OpenAIService::askJson — ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Multi-turn chat (portal assistant, copilot, etc.).
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chatMessages(
        array $messages,
        string $module = 'chat',
        ?int $userId = null,
        float $temperature = 0.55,
        int $maxTokens = 500,
        bool $useCache = true,
    ): ?string {
        if ($messages === []) {
            return null;
        }

        $cacheKey = 'chat:' . md5(json_encode($messages) . $temperature . $maxTokens);

        if ($useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        try {
            $response = $this->client()->chat()->create([
                'model' => config('services.openai.model'),
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ]);

            $content = trim($response->choices[0]->message->content ?? '');

            try {
                AIUsageLog::create([
                    'user_id' => $userId,
                    'module' => $module,
                    'model' => config('services.openai.model'),
                    'prompt_tokens' => $response->usage->promptTokens ?? 0,
                    'completion_tokens' => $response->usage->completionTokens ?? 0,
                    'total_tokens' => $response->usage->totalTokens ?? 0,
                    'cost' => (($response->usage->totalTokens ?? 0) / 1000) * 0.001,
                    'prompt' => mb_substr(json_encode($messages), 0, 4000),
                    'response' => $content,
                ]);
            } catch (\Exception $logEx) {
                \Log::warning('OpenAIService::chatMessages — usage log failed: ' . $logEx->getMessage());
            }

            if ($content !== '' && $useCache) {
                cache()->put($cacheKey, $content, now()->addMinutes(30));
            }

            return $content !== '' ? $content : null;
        } catch (\Exception $e) {
            \Log::error('OpenAIService::chatMessages — ' . $e->getMessage());

            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VISION — send a base64 image to GPT-4o for extraction
    |--------------------------------------------------------------------------
    */

    public function vision(
        string $base64,
        string $mimeType,
        string $systemPrompt,
        string $userText = 'Extract the details from this document.',
        ?int $userId = null,
        string $module = 'vision'
    ): ?string {

        try {

            $response = $this->client()->chat()->create([

                'model' => config('services.openai.model'),

                'messages' => [
                    [
                        'role'    => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role'    => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $userText,
                            ],
                            [
                                'type'      => 'image_url',
                                'image_url' => [
                                    'url' => 'data:' . $mimeType . ';base64,' . $base64,
                                ],
                            ],
                        ],
                    ],
                ],

                'temperature' => 0.1,
            ]);

            $content = $response->choices[0]->message->content ?? null;

            $totalTokens = $response->usage->totalTokens ?? 0;

            try {
                AIUsageLog::create([
                    'user_id'           => $userId,
                    'module'            => $module,
                    'model'             => config('services.openai.model'),
                    'prompt_tokens'     => $response->usage->promptTokens ?? 0,
                    'completion_tokens' => $response->usage->completionTokens ?? 0,
                    'total_tokens'      => $totalTokens,
                    'cost'              => ($totalTokens / 1000) * 0.001,
                    'prompt'            => '[vision: ' . $mimeType . ']',
                    'response'          => $content,
                ]);
            } catch (\Exception $logEx) {
                \Log::warning('OpenAIService::vision — usage log failed: ' . $logEx->getMessage());
            }

            return $content;

        } catch (\Exception $e) {

            \Log::error('OpenAIService::vision — ' . $e->getMessage());

            return null;
        }
    }
}