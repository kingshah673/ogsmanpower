<?php

namespace App\Services\Chat;

use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\OTPService;
use App\Services\Company\CompanyDocumentVerificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guided employer (company) registration via the Sophia AI chatbot.
 * Distinct from the Sub-Agent / Facilitator portal role.
 */
class EmployerOnboardingService
{
    protected function otp(): ?OTPService
    {
        try {
            return app(OTPService::class);
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function emailVerification(): ?EmailVerificationService
    {
        try {
            return app(EmailVerificationService::class);
        } catch (Throwable $e) {
            Log::error('[Employer] Email verification unavailable: '.$e->getMessage());

            return null;
        }
    }

    /** @return SeekerOnboardingAIService|null */
    protected function onboardingAi(): ?SeekerOnboardingAIService
    {
        try {
            return app(SeekerOnboardingAIService::class);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function isActive(): bool
    {
        return str_starts_with((string) $this->getStep(), 'employer_');
    }

    public function currentStep(): ?string
    {
        return $this->getStep();
    }

    public function start(): string
    {
        $this->clearTemp();
        $this->setStep('employer_company_name');

        return "Welcome, Employer! 👋\n\n"
            ."I'll help you create your company account right here.\n\n"
            ."**Step 1:** What is your **company name**?";
    }

    /**
     * @return string|array{reply: string, redirect?: string, no_localize?: bool}
     */
    public function handle(string $message, ?string $attachmentRelPath = null): string|array
    {
        $message = trim($message);

        if ($this->matchesCommand($message, ['cancel'])) {
            $this->reset();

            return "🔙 Cancelled. Tap **Who are you?** anytime to start again.";
        }

        return match ($this->getStep()) {
            'employer_company_name' => $this->handleCompanyName($message),
            'employer_trade_license' => $this->handleTradeLicense($message, $attachmentRelPath),
            'employer_email' => $this->handleEmail($message),
            'employer_password' => $this->handlePassword($message),
            'employer_otp' => $this->handleOtp($message),
            default => $this->start(),
        };
    }

    protected function handleCompanyName(string $message): string
    {
        if (mb_strlen($message) < 2) {
            return "Please type your **company name** (at least 2 characters).";
        }

        $this->tempSet('company_name', $message);
        $this->setStep('employer_trade_license');

        return "✅ Company: **{$message}**\n\n"
            ."**Step 2:** Upload your **trade license** (PDF or image) using the 📎 button below.\n\n"
            ."Or type *skip* if you'll upload it later from your dashboard.";
    }

    protected function handleTradeLicense(string $message, ?string $path): string
    {
        if ($this->matchesCommand($message, ['skip', 'later'])) {
            $this->setStep('employer_email');

            return $this->askEmail();
        }

        if (! $path) {
            return "📎 Please upload your **trade license** (PDF or image), or type *skip* to continue without it.";
        }

        $this->tempSet('trade_license_path', $path);
        $this->setStep('employer_email');

        return "✅ Trade license received.\n\n".$this->askEmail();
    }

    protected function askEmail(): string
    {
        return "**Step 3:** What **email** should we use for your employer account?";
    }

    protected function handleEmail(string $message): string
    {
        $email = $this->extractEmail($message);

        if (! $email) {
            return "Please type a valid **email address** for your employer account.";
        }

        if (User::where('email', $email)->exists()) {
            return "ℹ️ That email (*{$email}*) is already registered.\n\n"
                ."Type a **different email**, or log in at /login.";
        }

        $this->tempSet('email', strtolower($email));
        $this->setStep('employer_password');

        return "🔐 **Step 4:** Set your account password.\n\n"
            ."• Type a password (at least 8 characters), or\n"
            ."• Type *generate* and I'll create a strong one for you.";
    }

    /**
     * @return string|array{reply: string, no_localize?: bool}
     */
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
                    return $parsed['reply']."\n\nType a password (8+ characters) or say *generate*.";
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
                    return "⚠️ Password must be at least **8 characters**. Try again, or type *generate*.";
                }
                $password = $message;
            }
        } elseif (! $generated && mb_strlen($password) < 8) {
            return "⚠️ Password must be at least **8 characters**. Try again, or type *generate*.";
        }

        try {
            $user = $this->createAccount($password);
        } catch (Throwable $e) {
            Log::error('[EmployerOnboarding] createAccount: '.$e->getMessage());

            return "⚠️ Something went wrong creating your account. Please try again or type *cancel*.";
        }

        if (! $user) {
            $this->reset();

            return "⚠️ Could not create the account (email may already be registered). Please log in at /login.";
        }

        $this->tempSet('user_id', $user->id);
        $this->setStep('employer_otp');

        try {
            $this->emailVerification()?->send($user);
        } catch (Throwable $e) {
            Log::error('[EmployerOnboarding] OTP email: '.$e->getMessage());
        }

        $reply = $shown
            ."🎉 Welcome! Your employer account for **".($this->tempGet('company_name') ?? 'your company')."** has been created.\n\n"
            ."📧 I've emailed a 6-digit verification code to **{$user->email}**.\n"
            ."Please type the code here to finish.";

        return $generated ? ['reply' => $reply, 'no_localize' => true] : $reply;
    }

    /**
     * @return string|array{reply: string, redirect?: string}
     */
    protected function handleOtp(string $message): string|array
    {
        $user = User::find($this->tempGet('user_id'));

        if (! $user) {
            $this->reset();

            return "⚠️ Session expired. Please tap **Who are you?** to start again.";
        }

        $code = preg_replace('/\D/', '', $message);

        if (strlen($code) !== 6) {
            if ($this->matchesCommand($message, ['resend'])) {
                try {
                    $this->emailVerification()?->send($user);
                } catch (Throwable $e) {
                    Log::error('[EmployerOnboarding] resend OTP: '.$e->getMessage());
                }

                return "📧 A new code has been sent to **{$user->email}**. Please type it here.";
            }

            return "Please type the **6-digit code** from your email, or type *resend* for a new code.";
        }

        $otp = $this->otp();
        if (! $otp) {
            return "⚠️ Verification is temporarily unavailable. Please try again in a moment.";
        }

        if (! $otp->verify($user, $code)) {
            return "❌ That code is incorrect or expired.\n\nPlease try again or type *resend*.";
        }

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'is_otp_verified' => 1,
        ])->save();

        Auth::guard('user')->login($user);
        $this->reset();

        return [
            'reply' => "✅ Verified! Welcome to Career WorkForce.\n\nTaking you to your employer dashboard…",
            'redirect' => route('company.dashboard'),
        ];
    }

    protected function createAccount(string $password): ?User
    {
        $email = $this->tempGet('email');
        $companyName = $this->tempGet('company_name');

        if (! $email || User::where('email', $email)->exists()) {
            return null;
        }

        $username = $this->uniqueUsername($companyName ?: 'employer');

        $user = User::create([
            'name' => $companyName ?: Str::title(Str::before($email, '@')),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'company',
            'status' => 1,
            'is_otp_verified' => 0,
            'email_verified_at' => now(),
        ]);

        $licensePath = $this->tempGet('trade_license_path');

        if ($licensePath && $user->company) {
            try {
                $full = Storage::disk('public')->path($licensePath);
                if (is_file($full)) {
                    CompanyDocumentVerificationService::storeDocument(
                        $user->company,
                        'trade_license',
                        $full
                    );
                    CompanyDocumentVerificationService::assignDefaultDocumentTypes($user->company->fresh());
                }
            } catch (Throwable $e) {
                Log::warning('[EmployerOnboarding] trade license store: '.$e->getMessage());
            }
        }

        return $user;
    }

    protected function extractEmail(string $message): ?string
    {
        $ai = $this->onboardingAi();
        if ($ai?->isAvailable()) {
            $parsed = $ai->interpret('seeker_email', $message);
            if (! empty($parsed['email'])) {
                return strtolower($parsed['email']);
            }
        }

        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $message, $m)) {
            return strtolower($m[0]);
        }

        return filter_var($message, FILTER_VALIDATE_EMAIL) ? strtolower($message) : null;
    }

    protected function strongPassword(): string
    {
        return Str::password(12);
    }

    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'employer';
        $username = $base;
        $i = 0;

        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.Str::random(4);
            if (++$i > 20) {
                break;
            }
        }

        return $username;
    }

  /**
   * @param  array<int, string>  $commands
   */
    protected function matchesCommand(string $message, array $commands): bool
    {
        $lower = strtolower(trim($message));

        foreach ($commands as $cmd) {
            if ($lower === strtolower($cmd) || str_starts_with($lower, strtolower($cmd).' ')) {
                return true;
            }
        }

        return false;
    }

    protected function cacheKey(): string
    {
        return 'employer_onboard:'.session()->getId();
    }

    protected function getStep(): ?string
    {
        return Cache::get($this->cacheKey().':step');
    }

    protected function setStep(string $step): void
    {
        Cache::put($this->cacheKey().':step', $step, now()->addHours(3));
    }

    protected function tempGet(string $key): mixed
    {
        return Cache::get($this->cacheKey().':data:'.$key);
    }

    protected function tempSet(string $key, mixed $value): void
    {
        Cache::put($this->cacheKey().':data:'.$key, $value, now()->addHours(3));
    }

    protected function clearTemp(): void
    {
        Cache::forget($this->cacheKey().':step');
        foreach (['company_name', 'email', 'trade_license_path', 'user_id'] as $key) {
            Cache::forget($this->cacheKey().':data:'.$key);
        }
    }

    public function reset(): void
    {
        $this->clearTemp();
    }
}
