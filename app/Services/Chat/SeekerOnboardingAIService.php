<?php

namespace App\Services\Chat;

use App\Services\OpenAI\OpenAIService;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Uses OpenAI to interpret natural-language user input during seeker registration.
 * Server-side validation (email format, OTP verify, password length) still applies.
 */
class SeekerOnboardingAIService
{
    protected ?OpenAIService $openai = null;

    public function isAvailable(): bool
    {
        return ! empty(config('services.openai.key'));
    }

    protected function openai(): ?OpenAIService
    {
        if ($this->openai !== null) {
            return $this->openai;
        }

        try {
            return $this->openai = app(OpenAIService::class);
        } catch (Throwable $e) {
            Log::warning('[SeekerAI] OpenAI unavailable: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{intent: string, email: ?string, otp_code: ?string, password: ?string, reply: ?string}|null
     */
    public function interpret(string $step, string $message, array $context = []): ?array
    {
        $openai = $this->openai();
        if (! $openai || ! $this->isAvailable()) {
            return null;
        }

        $message = trim($message);
        if ($message === '') {
            return null;
        }

        $json = $openai->askJson(
            json_encode([
                'step' => $step,
                'user_message' => $message,
                'context' => $context,
            ], JSON_UNESCAPED_UNICODE),
            $this->systemPrompt($step),
            'seeker_onboarding_intent'
        );

        if (! is_array($json) || empty($json['intent'])) {
            return null;
        }

        $email = isset($json['email']) && is_string($json['email'])
            ? strtolower(trim($json['email']))
            : null;

        if ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $otp = isset($json['otp_code'])
            ? preg_replace('/\D/', '', (string) $json['otp_code'])
            : null;

        if ($otp !== null && strlen($otp) !== 6) {
            $otp = null;
        }

        return [
            'intent' => strtolower(trim((string) $json['intent'])),
            'email' => $email,
            'otp_code' => $otp,
            'password' => isset($json['password']) && is_string($json['password']) ? $json['password'] : null,
            'reply' => isset($json['reply']) && is_string($json['reply']) ? trim($json['reply']) : null,
        ];
    }

    protected function systemPrompt(string $step): string
    {
        $base = "You interpret user messages during Job Seeker registration on Career WorkForce (Sophia chatbot).\n"
            ."Return ONLY valid JSON: {\"intent\":\"...\",\"email\":null,\"otp_code\":null,\"password\":null,\"reply\":null}\n"
            ."- email: normalized address when extracted\n"
            ."- otp_code: exactly 6 digits when user provides verification code\n"
            ."- password: user's chosen password verbatim when they provide one (never invent)\n"
            ."- reply: short friendly answer when intent is clarify (under 60 words)\n\n";

        return match ($step) {
            'seeker_confirm' => $base
                ."STEP: seeker_confirm — user reviews CV/passport details before account creation.\n"
                ."Intents: confirm (yes, go for it, looks good, proceed, sure), decline (not right, change something), clarify (question about process), unknown.\n"
                ."Understand casual English, Roman Urdu, and mixed phrases.",

            'seeker_email' => $base
                ."STEP: seeker_email — user must provide registration email.\n"
                ."Intents: provide_email, clarify, unknown.\n"
                ."Extract email from natural text: 'use gptc8989@gmail.com for email', 'my email is …', voice 'name at gmail dot com'.\n"
                ."Never merge extra words into the domain (e.g. 'for email' is NOT part of the address).",

            'seeker_password' => $base
                ."STEP: seeker_password — user sets password or asks to generate one.\n"
                ."Intents: generate_password (create/generate/suggest password), provide_password (user typed password), clarify, unknown.\n"
                ."Put the exact password string in password when intent is provide_password.",

            'seeker_otp' => $base
                ."STEP: seeker_otp — email verification code entry.\n"
                ."Intents: provide_otp (6-digit code), change_email (wrong email, send to different address — extract new email), resend_otp, clarify, unknown.\n"
                ."Context includes current_email. Examples:\n"
                ."- 'no no send email to gptc8980@gmail.com' -> change_email\n"
                ."- '123456' -> provide_otp\n"
                ."- 'resend the code' -> resend_otp",

            default => $base."Unknown step — return intent unknown.",
        };
    }
}
