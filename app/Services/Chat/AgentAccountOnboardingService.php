<?php

namespace App\Services\Chat;

use App\Models\AgentInvite;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use App\Services\Auth\OTPService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Invite-gated Agent / Facilitator account signup via Sophia (manual §7).
 * Separate from AgentWorkerOnboardingService (worker registration for logged-in agents).
 */
class AgentAccountOnboardingService
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
            Log::error('[AgentAccount] Email verification unavailable: '.$e->getMessage());

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
        return str_starts_with((string) $this->getStep(), 'agent_acct_');
    }

    public function currentStep(): ?string
    {
        return $this->getStep();
    }

    public function start(?string $inviteToken = null): string
    {
        $this->clearTemp();

        if ($inviteToken) {
            $invite = $this->findValidInvite($inviteToken);
            if ($invite) {
                $this->tempSet('invite_token', $invite->token);
                $this->tempSet('invite_email', $invite->agent_email);
                $this->tempSet('invite_name', $invite->agent_name);
                $this->setStep('agent_acct_name');

                $agencyName = $invite->agencyUser?->name ?? 'your agency';

                return "Welcome, Agent / Facilitator! 👋\n\n"
                    ."You've been invited by **{$agencyName}**.\n\n"
                    ."**Step 1:** Confirm your **full name** (or type it if different).\n"
                    .'Suggested: *'.($invite->agent_name ?: 'your name').'*';
            }
        }

        $this->setStep('agent_acct_invite');

        return "Welcome, Agent / Facilitator! 👋\n\n"
            ."To create an Agent / Facilitator account you need an **invite link** from a Recruitment Agency.\n\n"
            ."**Step 1:** Paste your invite token or the full invite URL here.\n\n"
            .'Or open the invite link from your email — it looks like `/agent/invite/...`.';
    }

    /**
     * @return string|array{reply: string, redirect?: string, no_localize?: bool}
     */
    public function handle(string $message): string|array
    {
        $message = trim($message);

        if ($this->matchesCommand($message, ['cancel'])) {
            $this->reset();

            return '🔙 Cancelled. Tap **Who are you?** anytime to start again.';
        }

        return match ($this->getStep()) {
            'agent_acct_invite' => $this->handleInvite($message),
            'agent_acct_name' => $this->handleName($message),
            'agent_acct_email' => $this->handleEmail($message),
            'agent_acct_password' => $this->handlePassword($message),
            'agent_acct_otp' => $this->handleOtp($message),
            default => $this->start(),
        };
    }

    protected function handleInvite(string $message): string
    {
        $token = $this->extractInviteToken($message);
        if (! $token) {
            return 'Please paste a valid **invite token** or the full invite URL from your agency email.';
        }

        $invite = $this->findValidInvite($token);
        if (! $invite) {
            return '⚠️ That invite is invalid, already used, or expired. Ask your Recruitment Agency to send a new invite.';
        }

        $this->tempSet('invite_token', $invite->token);
        $this->tempSet('invite_email', $invite->agent_email);
        $this->tempSet('invite_name', $invite->agent_name);
        $this->setStep('agent_acct_name');

        $agencyName = $invite->agencyUser?->name ?? 'your agency';

        return "✅ Invite accepted from **{$agencyName}**.\n\n"
            ."**Step 2:** Confirm your **full name**.\n"
            .'Suggested: *'.($invite->agent_name ?: 'your name').'*';
    }

    protected function handleName(string $message): string
    {
        $name = mb_strlen(trim($message)) >= 2 ? trim($message) : (string) $this->tempGet('invite_name');

        if (mb_strlen($name) < 2) {
            return 'Please type your **full name** (at least 2 characters).';
        }

        $this->tempSet('name', $name);
        $this->setStep('agent_acct_email');

        $suggested = $this->tempGet('invite_email');

        return "✅ Name: **{$name}**\n\n"
            ."**Step 3:** Confirm your **email** for this Agent / Facilitator account.\n"
            .($suggested ? "Invite email: *{$suggested}* — type it to confirm, or use a different email." : 'Type your email address.');
    }

    protected function handleEmail(string $message): string
    {
        $email = $this->extractEmail($message) ?: $this->tempGet('invite_email');

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Please type a valid **email address**.';
        }

        $email = strtolower($email);
        $inviteEmail = strtolower((string) $this->tempGet('invite_email'));

        if ($inviteEmail && $email !== $inviteEmail) {
            return "⚠️ This invite was sent to *{$inviteEmail}*. Please use that email, or ask your agency for a new invite.";
        }

        if (User::where('email', $email)->exists()) {
            return "ℹ️ That email (*{$email}*) is already registered.\n\nLog in at /login, or ask for a new invite to a different email.";
        }

        $this->tempSet('email', $email);
        $this->setStep('agent_acct_password');

        return "🔐 **Step 4:** Set your password.\n\n"
            ."• Type a password (at least 8 characters), or\n"
            .'• Type *generate* for a strong password.';
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
                    $password = Str::password(12);
                    $shown = "🎲 Your generated password is: *{$password}*\nPlease save it somewhere safe.\n\n";
                    $generated = true;
                } elseif ($parsed['intent'] === 'provide_password' && $parsed['password']) {
                    $password = $parsed['password'];
                }
            }
        }

        if ($password === null) {
            if ($this->matchesCommand($message, ['generate'])) {
                $password = Str::password(12);
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
            Log::error('[AgentAccountOnboarding] createAccount: '.$e->getMessage());

            return '⚠️ Something went wrong creating your account. Please try again or type *cancel*.';
        }

        if (! $user) {
            $this->reset();

            return '⚠️ Could not create the account. The invite may have expired — ask your agency for a new one.';
        }

        $this->tempSet('user_id', $user->id);
        $this->setStep('agent_acct_otp');

        try {
            $this->emailVerification()?->send($user);
        } catch (Throwable $e) {
            Log::error('[AgentAccountOnboarding] OTP email: '.$e->getMessage());
        }

        $reply = $shown
            ."🎉 Your **Agent / Facilitator** account has been created.\n\n"
            ."📧 I've emailed a 6-digit code to **{$user->email}**.\n"
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
                    Log::error('[AgentAccountOnboarding] resend OTP: '.$e->getMessage());
                }

                return "📧 A new code has been sent to **{$user->email}**. Please type it here.";
            }

            return 'Please type the **6-digit code** from your email, or type *resend*.';
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
            'reply' => "✅ Verified! Welcome, Agent / Facilitator.\n\nTaking you to your dashboard…",
            'redirect' => route('agent.dashboard'),
        ];
    }

    protected function createAccount(string $password): ?User
    {
        $email = $this->tempGet('email');
        $name = $this->tempGet('name');
        $token = $this->tempGet('invite_token');

        $invite = $this->findValidInvite((string) $token);
        if (! $invite || ! $email || User::where('email', $email)->exists()) {
            return null;
        }

        $username = $this->uniqueUsername($name ?: 'agent');

        $user = User::create([
            'name' => $name ?: Str::title(Str::before($email, '@')),
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'agent',
            'agency_id' => $invite->agency_user_id,
            'status' => 1,
            'is_otp_verified' => 0,
            'email_verified_at' => now(),
        ]);

        $invite->update(['accepted_at' => now()]);

        return $user;
    }

    protected function findValidInvite(string $token): ?AgentInvite
    {
        return AgentInvite::query()
            ->where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    protected function extractInviteToken(string $message): ?string
    {
        if (preg_match('#/agent/invite/([A-Za-z0-9]+)#', $message, $m)) {
            return $m[1];
        }

        $token = trim($message);
        if (preg_match('/^[A-Za-z0-9]{32,128}$/', $token)) {
            return $token;
        }

        return null;
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

    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'agent';
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
        return 'agent_acct_onboard:'.session()->getId();
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
        foreach (['invite_token', 'invite_email', 'invite_name', 'name', 'email', 'user_id'] as $key) {
            Cache::forget($this->cacheKey().':data:'.$key);
        }
    }

    public function reset(): void
    {
        $this->clearTemp();
    }
}
