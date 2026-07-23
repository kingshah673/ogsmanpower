<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Models\Candidate;
use App\Services\AI\GPTCVParserService;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\OTPService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * SeekerOnboardingService
 * ─────────────────────────────────────────────────────────────────
 * Guided "Job Seeker" registration for the Sophia web widget.
 *
 * Flow (state stored in Cache, keyed by web session id):
 *   seeker_cv  → seeker_passport → seeker_confirm
 *              → [seeker_email]  → seeker_password → seeker_otp → done
 *
 * Reuses already-built services: GPTCVParserService (CV + passport
 * parsing), OCRService (image OCR), EmailVerificationService + OTPService
 * (email OTP). Builds only the orchestration that did not exist.
 * ─────────────────────────────────────────────────────────────────
 */
class SeekerOnboardingService
{
    /*
     * NOTE: dependencies are resolved LAZILY (not via the constructor) on purpose.
     * GPTCVParserService pulls in OpenAIService, whose constructor throws when
     * OPENAI_API_KEY is missing. Eager injection would make merely *constructing*
     * this service (which WebChatController does on every /ai/chat request) blow up
     * the whole endpoint. Lazy + guarded resolution keeps the widget alive and lets
     * us degrade gracefully when the AI backend is unavailable.
     */

    /** @return GPTCVParserService|null  null when the AI backend can't be built */
    protected function cvParser(): ?GPTCVParserService
    {
        try {
            return app(GPTCVParserService::class);
        } catch (Throwable $e) {
            Log::error('[Seeker] AI parser unavailable: ' . $e->getMessage());
            return null;
        }
    }

    protected function ocr(): ?OCRService
    {
        try {
            return app(OCRService::class);
        } catch (Throwable $e) {
            Log::error('[Seeker] OCR unavailable: ' . $e->getMessage());
            return null;
        }
    }

    protected function emailVerification(): ?EmailVerificationService
    {
        try {
            return app(EmailVerificationService::class);
        } catch (Throwable $e) {
            Log::error('[Seeker] Email verification unavailable: ' . $e->getMessage());
            return null;
        }
    }

    protected function otp(): ?OTPService
    {
        try {
            return app(OTPService::class);
        } catch (Throwable $e) {
            Log::error('[Seeker] OTP service unavailable: ' . $e->getMessage());
            return null;
        }
    }

    /** @return SeekerOnboardingAIService|null */
    protected function onboardingAi(): ?SeekerOnboardingAIService
    {
        try {
            return app(SeekerOnboardingAIService::class);
        } catch (Throwable $e) {
            Log::warning('[Seeker] AI interpreter unavailable: ' . $e->getMessage());

            return null;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       PUBLIC API (called by WebChatController)
    ═══════════════════════════════════════════════════════════ */

    /** True while a seeker_* step is active for this browser session. */
    public function isActive(): bool
    {
        return str_starts_with((string) $this->getStep(), 'seeker_');
    }

    public function currentStep(): ?string
    {
        return $this->getStep();
    }

    /**
     * Re-ask the question for the current step in the same language context.
     * Called when the user issues a language-switch command mid-flow so the
     * step is NOT advanced — only the reply language changes.
     */
    public function repromptCurrentStep(): string
    {
        return match ($this->getStep()) {
            'seeker_cv'       => "📄 Please upload your *CV* (PDF or a clear image) using the 📎 button below.",
            'seeker_passport' => "🪪 Please upload your *passport image* using the 📎 button below.",
            'seeker_confirm'  => "Please type *confirm* to save your profile, or *cancel* to restart.",
            'seeker_email'    => "📧 Please type your *email address*:",
            'seeker_password' => "🔐 Please set your *password* (at least 8 characters), or type *generate*:",
            'seeker_otp'      => "📬 Please enter the *OTP* sent to your email:",
            default           => "How can I help you?",
        };
    }

    /** Entry point from the "Job Seeker" role pick. */
    public function start(): string
    {
        $this->clearTemp();
        $this->setStep('seeker_cv');

        return "Welcome, Job Seeker! 👋\n\n"
             . "Let's set up your account automatically from your documents.\n\n"
             . "📄 Please upload your *CV* (PDF or a clear image) using the 📎 button below.";
    }

    /**
     * Drive the active step.
     *
     * @param  string       $message            user text (may be empty on file turns)
     * @param  string|null  $attachmentRelPath  path on the 'public' disk, e.g. "ai-chat/x.pdf"
     * @return string|array  string reply, or ['reply'=>..., 'redirect'=>...]
     */
    public function handle(string $message, ?string $attachmentRelPath = null): string|array
    {
        $step    = $this->getStep();
        $message = trim($message);

        if ($this->matchesCommand($message, ['cancel'])) {
            $this->reset();
            return "🔙 Cancelled. Tap *Who are you?* anytime to start again.";
        }

        return match ($step) {
            'seeker_cv'       => $this->handleCv($attachmentRelPath),
            'seeker_passport' => $this->handlePassport($attachmentRelPath),
            'seeker_confirm'  => $this->handleConfirm($message),
            'seeker_email'    => $this->handleEmail($message),
            'seeker_password' => $this->handlePassword($message),
            'seeker_otp'      => $this->handleOtp($message),
            default           => $this->start(),
        };
    }

    /* ═══════════════════════════════════════════════════════════
       STEP: CV UPLOAD
    ═══════════════════════════════════════════════════════════ */

    protected function handleCv(?string $path): string
    {
        if (!$path) {
            return "📄 Please upload your *CV* (PDF or a clear image) using the 📎 button.";
        }

        $text = $this->extractText($path);

        if (mb_strlen(trim($text)) < 20) {
            return "⚠️ I couldn't read that file. Please upload a clearer *CV* (PDF preferred).";
        }

        $parser = $this->cvParser();
        if (!$parser) {
            return "⚠️ Our document reader is temporarily unavailable. Please try again in a moment, or type *cancel*.";
        }

        $data = $parser->parse($text);

        // Empty result means the AI call itself failed (not "this isn't a CV").
        if (empty($data) || !is_array($data)) {
            return "⚠️ I couldn't read your CV right now. Please try again in a moment, or type *cancel*.";
        }

        if (!($data['is_cv'] ?? false)) {
            return "🤔 That doesn't look like a CV. Please upload your *CV / resume* (PDF or image).";
        }

        $this->tempSet('cv', $data);
        $this->tempSet('cv_path', $path);
        $this->tempSet('cv_raw_text', $text);
        $this->setStep('seeker_passport');

        $name    = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        $summary = "✅ *CV received!*\n\n";
        $summary .= $name                  ? "👤 Name: {$name}\n" : '';
        $summary .= ($data['profession'] ?? null) ? "💼 Profession: {$data['profession']}\n" : '';
        $summary .= ($data['email'] ?? null)      ? "📧 Email: {$data['email']}\n" : '';
        $summary .= "\n🛂 Now please upload your *Passport* (photo or scan) using the 📎 button.";

        return $summary;
    }

    /* ═══════════════════════════════════════════════════════════
       STEP: PASSPORT UPLOAD
    ═══════════════════════════════════════════════════════════ */

    protected function handlePassport(?string $path): string
    {
        if (!$path) {
            return "🛂 Please upload your *Passport* (photo or scan) using the 📎 button.";
        }

        $ocr  = $this->ocr();
        $scan = $ocr ? $ocr->scan($path) : null;
        $scan = is_array($scan) ? $scan : [];
        $rawText = $scan['raw_text'] ?? '';

        $parser = $this->cvParser();

        $data = ($parser && mb_strlen(trim($rawText)) >= 10)
            ? $parser->parsePassport($rawText)
            : [];
        $data = is_array($data) ? $data : [];

        // OCR regex fallback for the passport number if the LLM missed it.
        if (empty($data['passport_number']) && !empty($scan['passport_no'])) {
            $data['passport_number'] = $scan['passport_no'];
        }

        // If neither OCR nor the parser could read anything, let the user retry
        // rather than silently moving on with an empty passport.
        if (empty($data) && trim($rawText) === '') {
            return "⚠️ I couldn't read your passport. Please upload a clearer photo/scan, or type *cancel*.";
        }

        $this->tempSet('passport', $data);
        $this->tempSet('passport_path', $path);

        $profile = $this->mergeProfile();
        $this->tempSet('profile', $profile);
        $this->setStep('seeker_confirm');

        return "🛂 *Passport scanned!*\n\n"
             . $this->renderProfile($profile)
             . "\nDoes everything look right? Type *confirm* to continue (or *cancel* to stop).";
    }

    /* ═══════════════════════════════════════════════════════════
       STEP: CONFIRM
    ═══════════════════════════════════════════════════════════ */

    protected function handleConfirm(string $message): string
    {
        $profile = $this->tempGet('profile') ?: [];
        $ai = $this->onboardingAi();

        if ($ai?->isAvailable()) {
            $parsed = $ai->interpret('seeker_confirm', $message, [
                'profile_summary' => $this->renderProfile($profile),
            ]);

            if ($parsed) {
                if ($parsed['intent'] === 'clarify' && $parsed['reply']) {
                    return $parsed['reply']."\n\n"
                        ."When you're ready, type *confirm* (or say *yes*) to continue.";
                }

                if ($parsed['intent'] === 'decline' || $parsed['intent'] === 'unknown') {
                    return "No problem — here's what I have:\n\n"
                        .$this->renderProfile($profile)
                        ."\nType *confirm* (or say *yes*) to continue, or *cancel* to start over.";
                }

                if ($parsed['intent'] === 'confirm') {
                    return $this->proceedAfterConfirm($profile);
                }
            }
        }

        if (! $this->isAffirmative($message)) {
            return "No problem — here's what I have:\n\n"
                .$this->renderProfile($profile)
                ."\nType *confirm* (or say *yes*) to continue, or *cancel* to start over.";
        }

        return $this->proceedAfterConfirm($profile);
    }

    protected function proceedAfterConfirm(array $profile): string
    {
        if (empty($profile['email'])) {
            $this->setStep('seeker_email');

            return "📧 I couldn't find your email in the documents.\n\nPlease type your *email address*:";
        }

        return $this->askPassword($profile['email']);
    }

    /*
     * Convert voice-spoken email patterns into a valid email string.
     *
     * Handles:
     *   "salman ashraf at the rate of gmail.com"  → salmanashraf@gmail.com
     *   "salman ashraf @ gmail.com"               → salmanashraf@gmail.com
     *   "salman ashraf at gmail dot com"          → salmanashraf@gmail.com
     *   "john.doe at example dot com"             → john.doe@example.com
     */
    protected function normalizeEmail(string $raw): string
    {
        $extracted = $this->extractEmail($raw);
        if ($extracted) {
            return $extracted;
        }

        $s = strtolower(trim($raw));

        if (filter_var($s, FILTER_VALIDATE_EMAIL)) {
            return $s;
        }

        // Normalize @ symbol from common voice phrases
        $s = preg_replace('/\bat\s+the\s+rate\s+of\b/i', '@', $s);
        $s = preg_replace('/\bat\s+the\s+rate\b/i', '@', $s);
        $s = preg_replace('/\s*@\s*/u', '@', $s);

        // "dot" → "." when surrounded by word chars
        $s = preg_replace('/(\w)\s+dot\s+(\w)/i', '$1.$2', $s);

        // "at" as @ when a domain follows (e.g. "salman at gmail.com")
        $s = preg_replace('/\bat\b(?=\s*\S+\.\S+)/i', '@', $s);

        // If we now have exactly one @, collapse spaces in local part
        if (substr_count($s, '@') === 1) {
            [$local, $domain] = explode('@', $s, 2);
            $local = preg_replace('/\s+/', '', $local);
            $domain = preg_split('/\s+/', trim($domain))[0] ?? '';
            $domain = preg_replace('/\s+dot\s+/i', '.', $domain);
            $domain = preg_replace('/\s+/', '', $domain);
            $s = $local.'@'.$domain;
        }

        return $s;
    }

    protected function extractEmail(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $raw, $match)) {
            $email = strtolower($match[0]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $email;
            }
        }

        return null;
    }

    protected function handleEmail(string $message): string
    {
        $email = null;
        $ai = $this->onboardingAi();

        if ($ai?->isAvailable()) {
            $parsed = $ai->interpret('seeker_email', $message);

            if ($parsed) {
                if ($parsed['intent'] === 'clarify' && $parsed['reply']) {
                    return $parsed['reply']."\n\n📧 Please type your *email address*:";
                }

                if ($parsed['intent'] === 'provide_email' && $parsed['email']) {
                    $email = $parsed['email'];
                }
            }
        }

        $email ??= $this->extractEmail($message) ?? $this->normalizeEmail($message);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "⚠️ That doesn't look right. Please type a valid *email address* (e.g. name@gmail.com):";
        }

        if (User::where('email', $email)->exists()) {
            return "ℹ️ That email (*{$email}*) is already registered.\n\n"
                 ."Please type a *different email* to continue, or log in at /login.";
        }

        $profile = $this->tempGet('profile') ?: [];
        $profile['email'] = strtolower($email);
        $this->tempSet('profile', $profile);

        return $this->askPassword($profile['email']);
    }

    protected function askPassword(string $email): string
    {
        if (User::where('email', $email)->exists()) {
            // Don't dead-end: let them register with another email instead.
            $this->setStep('seeker_email');
            return "ℹ️ An account already exists for *{$email}*.\n\n"
                 . "Do you have *another email*? Type it here to continue — otherwise log in at /login.";
        }

        $this->setStep('seeker_password');

        return "🔐 Almost done! Set your account password.\n\n"
             . "• Type a password (at least 8 characters), or\n"
             . "• Type *generate* and I'll create a strong one for you.";
    }

    /* ═══════════════════════════════════════════════════════════
       STEP: PASSWORD → CREATE ACCOUNT → SEND OTP
    ═══════════════════════════════════════════════════════════ */

    protected function handlePassword(string $message): string|array
    {
        $generated = false;
        $password = null;
        $shown = '';
        $ai = $this->onboardingAi();

        if ($ai?->isAvailable()) {
            $parsed = $ai->interpret('seeker_password', $message);

            if ($parsed) {
                if ($parsed['intent'] === 'clarify' && $parsed['reply']) {
                    return $parsed['reply']."\n\n"
                        ."Type a password (8+ characters) or say *generate* for a strong one.";
                }

                if ($parsed['intent'] === 'generate_password') {
                    $password = $this->strongPassword();
                    $shown = "🎲 Your generated password is: *{$password}*\nPlease save it somewhere safe.\n\n";
                    $generated = true;
                } elseif ($parsed['intent'] === 'provide_password' && $parsed['password']) {
                    $password = $parsed['password'];
                }
            }
        }

        if ($password === null) {
            if ($this->matchesCommand($message, ['generate'])) {
                $password = $this->strongPassword();
                $shown = "🎲 Your generated password is: *{$password}*\nPlease save it somewhere safe.\n\n";
                $generated = true;
            } else {
                if (mb_strlen($message) < 8) {
                    return "⚠️ Password must be at least *8 characters*. Try again, or type *generate*.";
                }
                $password = $message;
            }
        } elseif (! $generated && mb_strlen($password) < 8) {
            return "⚠️ Password must be at least *8 characters*. Try again, or type *generate*.";
        }

        try {
            $user = $this->createAccount($password);
        } catch (Throwable $e) {
            Log::error('[SeekerOnboarding] createAccount: ' . $e->getMessage());

            return "⚠️ Something went wrong creating your account. Please try again or type *cancel*.";
        }

        if (! $user) {
            $this->reset();

            return "⚠️ I couldn't create the account (email may already be registered). Please log in at /login.";
        }

        $this->tempSet('user_id', $user->id);
        $this->setStep('seeker_otp');

        try {
            $this->emailVerification()?->send($user);
        } catch (Throwable $e) {
            Log::error('[SeekerOnboarding] OTP email: ' . $e->getMessage());
        }

        $first = explode(' ', trim($user->name))[0] ?: 'there';

        $reply = $shown
             ."🎉 Welcome, {$first}! Your account has been created.\n\n"
             ."📧 I've emailed a 6-digit verification code to *{$user->email}*.\n"
             ."Please type the code here to finish.";

        return $generated ? ['reply' => $reply, 'no_localize' => true] : $reply;
    }

    /* ═══════════════════════════════════════════════════════════
       STEP: OTP → LOGIN → REDIRECT
    ═══════════════════════════════════════════════════════════ */

    protected function handleOtp(string $message): string|array
    {
        $user = User::find($this->tempGet('user_id'));

        if (! $user) {
            $this->reset();

            return "⚠️ Session expired. Please tap *Who are you?* to start again.";
        }

        $ai = $this->onboardingAi();
        $parsed = ($ai?->isAvailable())
            ? $ai->interpret('seeker_otp', $message, ['current_email' => $user->email])
            : null;

        if ($parsed) {
            if ($parsed['intent'] === 'clarify' && $parsed['reply']) {
                return $parsed['reply']."\n\n"
                    ."Type the 6-digit code, *resend*, or tell me the correct email.";
            }

            if ($parsed['intent'] === 'resend_otp') {
                try {
                    $this->emailVerification()?->send($user);
                } catch (Throwable $e) {
                    Log::error('[SeekerOnboarding] resend OTP: ' . $e->getMessage());
                }

                return "📧 A new code has been sent to *{$user->email}*. Please type it here.";
            }

            if ($parsed['intent'] === 'change_email' && $parsed['email']) {
                return $this->changeUserEmail($user, $parsed['email']);
            }

            if ($parsed['intent'] === 'provide_otp' && $parsed['otp_code']) {
                return $this->verifyOtpAndLogin($user, $parsed['otp_code']);
            }
        }

        if ($this->matchesCommand($message, ['resend'])) {
            try {
                $this->emailVerification()?->send($user);
            } catch (Throwable $e) {
                Log::error('[SeekerOnboarding] resend OTP: ' . $e->getMessage());
            }

            return "📧 A new code has been sent to *{$user->email}*. Please type it here.";
        }

        $newEmail = $this->extractEmail($message);
        if ($newEmail && $this->looksLikeEmailChange($message, $newEmail)) {
            return $this->changeUserEmail($user, $newEmail);
        }

        $code = preg_replace('/\D/', '', $message);

        return $this->verifyOtpAndLogin($user, $code);
    }

    protected function verifyOtpAndLogin(User $user, string $code): string|array
    {
        $otp = $this->otp();
        if (! $otp) {
            return "⚠️ Verification is temporarily unavailable. Please try again in a moment.";
        }

        if (! $otp->verify($user, $code)) {
            return "❌ That code is incorrect or expired.\n\n"
                ."Please try again, type *resend*, or tell me the correct email (e.g. *send to name@gmail.com*).";
        }

        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        Auth::guard('user')->login($user);

        $first = explode(' ', trim($user->name))[0] ?: 'there';
        $this->reset();

        return [
            'reply' => "✅ Verified! You're all set, {$first}. Taking you to your profile to finish up…",
            'redirect' => route('candidate.setting'),
        ];
    }

    protected function changeUserEmail(User $user, string $newEmail): string
    {
        $newEmail = strtolower($newEmail);

        if ($newEmail === strtolower((string) $user->email)) {
            return "📧 The code was already sent to *{$user->email}*. Type the 6-digit code, or type *resend*.";
        }

        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return "ℹ️ *{$newEmail}* is already registered.\n\nPlease type a *different email*, or log in at /login.";
        }

        $user->forceFill(['email' => $newEmail])->save();

        $profile = $this->tempGet('profile') ?: [];
        $profile['email'] = $newEmail;
        $this->tempSet('profile', $profile);

        try {
            $this->emailVerification()?->send($user);
        } catch (Throwable $e) {
            Log::error('[SeekerOnboarding] change email OTP: ' . $e->getMessage());

            return "⚠️ Email updated to *{$newEmail}* but I couldn't send the code. Type *resend* to try again.";
        }

        return "📧 Got it — I've updated your email to *{$newEmail}* and sent a new verification code.\n\nPlease type the 6-digit code here.";
    }

    protected function looksLikeEmailChange(string $message, string $email): bool
    {
        if (str_contains($message, '@')) {
            $digitsOnly = preg_replace('/\D/', '', $message);
            if (strlen($digitsOnly) === 6 && ! str_contains(strtolower($message), $email)) {
                return false;
            }
        }

        $lower = strtolower($message);

        if ($this->extractEmail($message) && ! preg_match('/^\d{6}$/', trim($message))) {
            $changeHints = [
                'send', 'email', 'use ', 'change', 'wrong', 'correct', 'instead',
                'not ', 'no no', 'update', 'different', 'mistake', 'typo',
            ];
            foreach ($changeHints as $hint) {
                if (str_contains($lower, $hint)) {
                    return true;
                }
            }

            // Bare email at OTP step — treat as correction.
            return (bool) preg_match(
                '/^[\s\w.@+\-]*'.preg_quote($email, '/').'[\s\w.@+\-]*$/i',
                $lower
            );
        }

        return false;
    }

    protected function isAffirmative(string $message): bool
    {
        if ($this->matchesCommand($message, [
            'confirm', 'yes', 'y', 'ok', 'okay', 'correct', 'continue',
            'sure', 'yeah', 'yep', 'yup', 'proceed', 'perfect', 'absolutely',
        ])) {
            return true;
        }

        $m = strtolower(trim($message));
        if ($m === '') {
            return false;
        }

        $phrases = [
            'go for it', 'go ahead', 'looks good', 'look good', 'all good',
            'that\'s correct', 'thats correct', 'sounds good', 'yes please',
            'that is correct', 'all correct', 'everything is correct',
        ];
        foreach ($phrases as $phrase) {
            if (str_contains($m, $phrase)) {
                return true;
            }
        }

        return (bool) preg_match('/^(yes|yeah|yep|yup|ok|okay|sure|confirm|correct)\b/i', $m);
    }

    /* ═══════════════════════════════════════════════════════════
       ACCOUNT CREATION
    ═══════════════════════════════════════════════════════════ */

    protected function createAccount(string $password): ?User
    {
        $profile = $this->tempGet('profile') ?: [];
        $email   = $profile['email'] ?? null;

        if (!$email || User::where('email', $email)->exists()) {
            return null;
        }

        $name = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
        if ($name === '') {
            $name = Str::title(Str::before($email, '@'));
        }

        $user = User::create([
            'name'              => $name,
            'username'          => $this->uniqueUsername($name),
            'email'             => $email,
            'whatsapp'          => $profile['phone'] ?? null,
            'password'          => Hash::make($password),
            'role'              => 'candidate',
            'status'            => 1,
            'is_otp_verified'   => 0,
            'email_verified_at' => now(),
        ]);

        $candidate = Candidate::firstOrCreate(['user_id' => $user->id]);

        try {
            $writer = app(SeekerProfileWriter::class);
            $normalized = $this->buildNormalizedCandidateData($email);
            $writer->applyCv($candidate, $normalized, $this->tempGet('cv_path'));
            $writer->applyPassport($candidate, $this->tempGet('passport') ?: [], $this->tempGet('passport_path'));

            $candidate->refresh();
            if ($candidate->user && trim((string) $candidate->first_name) !== '') {
                $candidate->user->update([
                    'name' => trim($candidate->first_name.' '.($candidate->last_name ?? '')),
                ]);
            }
        } catch (Throwable $e) {
            Log::error('[SeekerOnboarding] profile write: '.$e->getMessage());
        }

        return $user;
    }

    /**
     * Merge CV + passport, normalize like /candidate/settings save, registration email wins.
     *
     * @return array<string, mixed>
     */
    protected function buildNormalizedCandidateData(string $registrationEmail): array
    {
        $cv = $this->tempGet('cv') ?: [];
        $pp = $this->tempGet('passport') ?: [];

        if (! empty($pp['given_names'])) {
            $cv['first_name'] = Str::title(trim(explode(' ', trim((string) $pp['given_names']))[0]));
        }
        if (! empty($pp['surname'])) {
            $cv['last_name'] = Str::title(trim((string) $pp['surname']));
        }

        $passportMap = [
            'passport_number' => 'passport_number',
            'date_of_birth' => 'date_of_birth',
            'date_of_issue' => 'passport_issue_date',
            'date_of_expiry' => 'passport_expiry_date',
            'place_of_issue' => 'place_of_issue',
            'nationality' => 'nationality',
            'national_id' => 'cnic_number',
            'gender' => 'gender',
        ];

        foreach ($passportMap as $ppKey => $cvKey) {
            if (! empty($pp[$ppKey])) {
                $cv[$cvKey] = $pp[$ppKey];
            }
        }

        $cv['is_cv'] = true;
        $normalizer = app(\App\Services\AI\CvDataNormalizer::class);
        $cv = $normalizer->normalize($cv);

        $rawText = (string) ($this->tempGet('cv_raw_text') ?? '');
        if ($rawText !== '') {
            $cv = $normalizer->supplementLanguages($cv, $rawText);
        }

        $cv['email'] = strtolower($registrationEmail);

        return $cv;
    }

    /* ═══════════════════════════════════════════════════════════
       HELPERS
    ═══════════════════════════════════════════════════════════ */

    /** Merge CV + passport data; passport wins on identity fields. */
    protected function mergeProfile(): array
    {
        $cv = $this->tempGet('cv') ?: [];
        $pp = $this->tempGet('passport') ?: [];

        $profile = [
            'first_name'     => $this->firstNonEmpty($pp['given_names'] ?? null, $cv['first_name'] ?? null),
            'last_name'      => $this->firstNonEmpty($pp['surname'] ?? null, $cv['last_name'] ?? null),
            'email'          => $cv['email'] ?? null,
            'phone'          => $cv['phone'] ?? null,
            'gender'         => $this->firstNonEmpty($pp['gender'] ?? null, $cv['gender'] ?? null),
            'nationality'    => $this->firstNonEmpty($pp['nationality'] ?? null, $cv['nationality'] ?? null),
            'date_of_birth'  => $this->firstNonEmpty($pp['date_of_birth'] ?? null, $cv['date_of_birth'] ?? null),
            'profession'     => $cv['profession'] ?? null,
            'bio'            => $cv['bio'] ?? null,
            'passport_number'=> $pp['passport_number'] ?? null,
            'date_of_issue'  => $pp['date_of_issue'] ?? null,
            'date_of_expiry' => $pp['date_of_expiry'] ?? null,
            'place_of_issue' => $pp['place_of_issue'] ?? null,
            'national_id'    => $pp['national_id'] ?? null,
        ];

        // "given_names" may contain first + middle; keep just the first token as first_name.
        if (!empty($profile['first_name'])) {
            $profile['first_name'] = Str::title(trim(explode(' ', trim($profile['first_name']))[0]));
        }
        if (!empty($profile['last_name'])) {
            $profile['last_name'] = Str::title(trim($profile['last_name']));
        }

        return $profile;
    }

    protected function renderProfile(array $p): string
    {
        $rows = [
            'Name'        => trim(($p['first_name'] ?? '') . ' ' . ($p['last_name'] ?? '')),
            'Email'       => $p['email'] ?? null,
            'Phone'       => $p['phone'] ?? null,
            'Nationality' => $p['nationality'] ?? null,
            'Passport No' => $p['passport_number'] ?? null,
            'Date of Birth' => $p['date_of_birth'] ?? null,
            'Profession'  => $p['profession'] ?? null,
        ];

        $out = '';
        foreach ($rows as $label => $value) {
            if (!empty($value)) {
                $out .= "• {$label}: {$value}\n";
            }
        }

        return $out ?: "• (No details could be read)\n";
    }

    /** PDF → text via Smalot; images → OCR.space. */
    protected function extractText(string $relPath): string
    {
        $full = storage_path('app/public/' . $relPath);
        $ext  = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            try {
                return (new PdfParser())->parseFile($full)->getText();
            } catch (Throwable $e) {
                Log::warning('[SeekerOnboarding] PDF parse: ' . $e->getMessage());
                return '';
            }
        }

        // Images (and anything else OCR.space can read)
        $ocr  = $this->ocr();
        $scan = $ocr ? $ocr->scan($relPath) : null;
        return is_array($scan) ? ($scan['raw_text'] ?? '') : '';
    }

    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'seeker';
        do {
            $username = $base . rand(100, 9999);
        } while (User::where('username', $username)->exists());

        return $username;
    }

    protected function strongPassword(int $length = 12): string
    {
        $sets = [
            'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'abcdefghijkmnpqrstuvwxyz',
            '23456789',
            '!@#$%&*?',
        ];
        $pw = '';
        foreach ($sets as $s) {
            $pw .= $s[random_int(0, strlen($s) - 1)];
        }
        $all = implode('', $sets);
        for ($i = strlen($pw); $i < $length; $i++) {
            $pw .= $all[random_int(0, strlen($all) - 1)];
        }
        return str_shuffle($pw);
    }

    protected function firstNonEmpty(...$values)
    {
        foreach ($values as $v) {
            if (!is_null($v) && $v !== '') {
                return $v;
            }
        }
        return null;
    }

    /**
     * True if $message matches one of the English command $words — directly, or
     * (for a non-English voice/text session) after translating it to English.
     * This keeps the multilingual UX working WITHOUT ever touching value inputs:
     * email / password / OTP are matched on the raw text elsewhere, never here.
     */
    protected function matchesCommand(string $message, array $words): bool
    {
        $m = strtolower(trim($message));
        if ($m === '') {
            return false;
        }
        if (in_array($m, $words, true)) {
            return true;
        }

        $lang = strtolower((string) session('chatbot_lang'));
        if ($lang === '' || in_array($lang, ['en', 'english'], true)) {
            return false;
        }

        try {
            $en = app(\App\Services\AI\AITranslatorService::class)->translate($message, 'English');
            $en = strtolower(trim((string) $en));
            foreach ($words as $w) {
                if ($w !== '' && ($en === $w || str_contains($en, $w))) {
                    return true;
                }
            }
        } catch (Throwable $e) {
            // English-only matching already failed; treat as no match.
        }

        return false;
    }

    /* ═══════════════════════════════════════════════════════════
       STATE (Cache, keyed by web session id)
    ═══════════════════════════════════════════════════════════ */

    protected function key(): string
    {
        return 'seeker_onboard_' . session()->getId();
    }

    protected function getStep(): ?string
    {
        return Cache::get($this->key() . '_step');
    }

    protected function setStep(string $step): void
    {
        Cache::put($this->key() . '_step', $step, 3600);
    }

    protected function tempSet(string $k, $v): void
    {
        $d = Cache::get($this->key() . '_temp', []);
        $d[$k] = $v;
        Cache::put($this->key() . '_temp', $d, 3600);
    }

    protected function tempGet(string $k)
    {
        return Cache::get($this->key() . '_temp', [])[$k] ?? null;
    }

    protected function clearTemp(): void
    {
        Cache::forget($this->key() . '_temp');
    }

    protected function reset(): void
    {
        Cache::forget($this->key() . '_step');
        Cache::forget($this->key() . '_temp');
    }
}
