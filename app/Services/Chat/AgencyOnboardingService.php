<?php

namespace App\Services\Chat;

use App\Models\Agency;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\OTPService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Guided Recruitment Agency registration via Sophia (manual §6).
 */
class AgencyOnboardingService
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
            Log::error('[Agency] Email verification unavailable: '.$e->getMessage());

            return null;
        }
    }

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
        return str_starts_with((string) $this->getStep(), 'agency_');
    }

    public function currentStep(): ?string
    {
        return $this->getStep();
    }

    public function start(): string
    {
        $this->clearTemp();
        $this->setStep('agency_name');

        return "Welcome, Recruitment Agency! 👋\n\n"
            ."I'll help you create your agency account right here.\n\n"
            ."**Step 1:** What is your **agency name**?";
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
            'agency_name' => $this->handleAgencyName($message),
            'agency_license' => $this->handleLicense($message, $attachmentRelPath),
            'agency_email' => $this->handleEmail($message),
            'agency_password' => $this->handlePassword($message),
            'agency_otp' => $this->handleOtp($message),
            default => $this->start(),
        };
    }

    protected function handleAgencyName(string $message): string
    {
        if (mb_strlen($message) < 2) {
            return 'Please type your **agency name** (at least 2 characters).';
        }

        $this->tempSet('agency_name', $message);
        $this->setStep('agency_license');

        return "✅ Agency: **{$message}**\n\n"
            ."**Step 2:** Enter your **MPD / agency license number**, or upload a license scan with 📎.\n\n"
            .'You can also type the number *and* attach a file. Type *skip* to add the license later.';
    }

    protected function handleLicense(string $message, ?string $path): string
    {
        if ($this->matchesCommand($message, ['skip', 'later'])) {
            $this->setStep('agency_email');

            return $this->askEmail();
        }

        $licenseNumber = null;
        if ($message !== '' && ! $this->matchesCommand($message, ['skip', 'later'])) {
            $licenseNumber = trim($message);
            if (mb_strlen($licenseNumber) < 3) {
                return 'Please enter a valid **license number** (at least 3 characters), upload a scan with 📎, or type *skip*.';
            }
            $this->tempSet('license_number', $licenseNumber);
        }

        if ($path) {
            $this->tempSet('license_path', $path);
        }

        if (! $this->tempGet('license_number') && ! $this->tempGet('license_path')) {
            return '📎 Please enter your **license number**, upload a license scan, or type *skip*.';
        }

        $this->setStep('agency_email');

        $ack = $this->tempGet('license_number')
            ? '✅ License number saved'.($path ? ' and document received' : '').'.'
            : '✅ License document received.';

        return $ack."\n\n".$this->askEmail();
    }

    protected function askEmail(): string
    {
        return '**Step 3:** What **email** should we use for your agency account?';
    }

    protected function handleEmail(string $message): string
    {
        $email = $this->extractEmail($message);

        if (! $email) {
            return 'Please type a valid **email address** for your agency account.';
        }

        if (User::where('email', $email)->exists()) {
            return "ℹ️ That email (*{$email}*) is already registered.\n\n"
                .'Type a **different email**, or log in at /login.';
        }

        $this->tempSet('email', strtolower($email));
        $this->setStep('agency_password');

        return "🔐 **Step 4:** Set your account password.\n\n"
            ."• Type a password (at least 8 characters), or\n"
            .'• Type *generate* and I\'ll create a strong one for you.';
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
                    return '⚠️ Password must be at least **8 characters**. Try again, or type *generate*.';
                }
                $password = $message;
            }
        } elseif (! $generated && mb_strlen($password) < 8) {
            return '⚠️ Password must be at least **8 characters**. Try again, or type *generate*.';
        }

        try {
            $user = $this->createAccount($password);
        } catch (Throwable $e) {
            Log::error('[AgencyOnboarding] createAccount: '.$e->getMessage());

            return '⚠️ Something went wrong creating your account. Please try again or type *cancel*.';
        }

        if (! $user) {
            $this->reset();

            return '⚠️ Could not create the account (email may already be registered). Please log in at /login.';
        }

        $this->tempSet('user_id', $user->id);
        $this->setStep('agency_otp');

        try {
            $this->emailVerification()?->send($user);
        } catch (Throwable $e) {
            Log::error('[AgencyOnboarding] OTP email: '.$e->getMessage());
        }

        $reply = $shown
            .'🎉 Welcome! Your agency account for **'.($this->tempGet('agency_name') ?? 'your agency')."** has been created.\n\n"
            ."📧 I've emailed a 6-digit verification code to **{$user->email}**.\n"
            .'Please type the code here to finish.';

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

            return '⚠️ Session expired. Please tap **Who are you?** to start again.';
        }

        $code = preg_replace('/\D/', '', $message);

        if (strlen($code) !== 6) {
            if ($this->matchesCommand($message, ['resend'])) {
                try {
                    $this->emailVerification()?->send($user);
                } catch (Throwable $e) {
                    Log::error('[AgencyOnboarding] resend OTP: '.$e->getMessage());
                }

                return "📧 A new code has been sent to **{$user->email}**. Please type it here.";
            }

            return 'Please type the **6-digit code** from your email, or type *resend* for a new code.';
        }

        $otp = $this->otp();
        if (! $otp) {
            return '⚠️ Verification is temporarily unavailable. Please try again in a moment.';
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
            'reply' => "✅ Verified! Welcome to Career WorkForce.\n\nTaking you to your agency dashboard…",
            'redirect' => route('agency.dashboard'),
        ];
    }

    protected function createAccount(string $password): ?User
    {
        $email = $this->tempGet('email');
        $agencyName = $this->tempGet('agency_name');

        if (! $email || User::where('email', $email)->exists()) {
            return null;
        }

        $username = $this->uniqueUsername($agencyName ?: 'agency');

        $user = User::create([
            'name' => $agencyName ?: Str::title(Str::before($email, '@')),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'agency',
            'status' => 1,
            'is_otp_verified' => 0,
            'email_verified_at' => now(),
        ]);

        $licenseNumber = $this->tempGet('license_number');
        if ($licenseNumber && $user->agency) {
            $user->agency->update(['license_number' => $licenseNumber]);
        } elseif ($licenseNumber) {
            Agency::firstOrCreate(
                ['user_id' => $user->id],
                ['license_number' => $licenseNumber]
            );
        }

        $licensePath = $this->tempGet('license_path');
        if ($licensePath && $user->fresh()->agency) {
            try {
                $full = Storage::disk('public')->path($licensePath);
                if (is_file($full)) {
                    $user->agency->addMedia($full)->toMediaCollection('document');
                }
            } catch (Throwable $e) {
                Log::warning('[AgencyOnboarding] license store: '.$e->getMessage());
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
        $base = Str::slug($name) ?: 'agency';
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
        return 'agency_onboard:'.session()->getId();
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
        foreach (['agency_name', 'email', 'license_number', 'license_path', 'user_id'] as $key) {
            Cache::forget($this->cacheKey().':data:'.$key);
        }
    }

    public function reset(): void
    {
        $this->clearTemp();
    }
}
