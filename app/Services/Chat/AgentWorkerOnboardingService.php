<?php

namespace App\Services\Chat;

use App\Models\Candidate;
use App\Models\User;
use App\Services\AI\CvDataNormalizer;
use App\Services\AI\GPTCVParserService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Field worker registration for Recruitment Agents (Sub-Agent module §7).
 * CV + passport → confirm → create candidate linked to agent_id only.
 */
class AgentWorkerOnboardingService
{
    protected function cvParser(): ?GPTCVParserService
    {
        try {
            return app(GPTCVParserService::class);
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function ocr(): ?OCRService
    {
        try {
            return app(OCRService::class);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function isActive(): bool
    {
        return str_starts_with((string) $this->getStep(), 'agent_worker_');
    }

    public function currentStep(): ?string
    {
        return $this->getStep();
    }

    public function start(): string
    {
        $agent = authUser();
        if (! $agent || $agent->role !== 'agent') {
            return 'Please log in as an Agent / Facilitator to register workers.';
        }

        if (! $agent->agency_id) {
            return '⚠️ Your agent account is not linked to a parent agency yet. Accept your agency invite or contact support.';
        }

        $this->clearTemp();
        $this->setStep('agent_worker_cv');

        return "👷 **Register a worker** for your agency.\n\n"
            ."📄 Upload their *CV* (PDF or clear image) using the 📎 button below.";
    }

    /**
     * @return string|array{reply: string, redirect?: string, agent_worker_active?: bool}
     */
    public function handle(string $message, ?string $attachmentRelPath = null): string|array
    {
        if ($this->matchesCommand($message, ['cancel'])) {
            $this->reset();

            return '🔙 Worker registration cancelled.';
        }

        return match ($this->getStep()) {
            'agent_worker_cv' => $this->handleCv($attachmentRelPath),
            'agent_worker_passport' => $this->handlePassport($attachmentRelPath),
            'agent_worker_confirm' => $this->handleConfirm($message),
            'agent_worker_email' => $this->handleEmail($message),
            default => $this->start(),
        };
    }

    protected function handleCv(?string $path): string
    {
        if (! $path) {
            return '📄 Please upload the worker\'s *CV* using the 📎 button.';
        }

        $text = $this->extractText($path);
        if (mb_strlen(trim($text)) < 20) {
            return '⚠️ Could not read that CV. Please upload a clearer PDF or image.';
        }

        $parser = $this->cvParser();
        if (! $parser) {
            return '⚠️ Document reader unavailable. Try again shortly.';
        }

        $data = $parser->parse($text);
        if (empty($data['is_cv'])) {
            return '🤔 That does not look like a CV. Please upload a resume file.';
        }

        $this->tempSet('cv', $data);
        $this->tempSet('cv_path', $path);
        $this->tempSet('cv_raw_text', $text);
        $this->setStep('agent_worker_passport');

        $name = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? ''));

        return "✅ *CV received!*\n\n"
            .($name ? "👤 {$name}\n" : '')
            ."🛂 Now upload the worker's *passport* (photo or scan) using 📎.";
    }

    protected function handlePassport(?string $path): string
    {
        if (! $path) {
            return '🛂 Please upload the worker\'s *passport* using the 📎 button.';
        }

        $ocr = $this->ocr();
        $scan = $ocr ? $ocr->scan($path) : null;
        $rawText = is_array($scan) ? ($scan['raw_text'] ?? '') : '';

        $parser = $this->cvParser();
        $data = ($parser && mb_strlen(trim($rawText)) >= 10)
            ? $parser->parsePassport($rawText)
            : [];
        $data = is_array($data) ? $data : [];

        if (empty($data['passport_number']) && ! empty($scan['passport_no'])) {
            $data['passport_number'] = $scan['passport_no'];
        }

        $this->tempSet('passport', $data);
        $this->tempSet('passport_path', $path);

        $profile = $this->mergeProfile();
        $this->tempSet('profile', $profile);
        $this->setStep('agent_worker_confirm');

        return "🛂 *Passport scanned!*\n\n"
            .$this->renderProfile($profile)
            ."\nType *confirm* (or say *yes*) to register this worker, or *cancel*.";
    }

    protected function handleConfirm(string $message): string|array
    {
        $ai = app(SeekerOnboardingAIService::class);
        $confirmed = false;

        if ($ai->isAvailable()) {
            $parsed = $ai->interpret('seeker_confirm', $message, [
                'profile_summary' => $this->renderProfile($this->tempGet('profile') ?: []),
            ]);
            $confirmed = ($parsed['intent'] ?? '') === 'confirm';
        }

        if (! $confirmed && ! $this->isAffirmative($message)) {
            return "Here's what I have:\n\n"
                .$this->renderProfile($this->tempGet('profile') ?: [])
                ."\nType *confirm* to register, or *cancel*.";
        }

        $profile = $this->tempGet('profile') ?: [];
        if (empty($profile['email'])) {
            $this->setStep('agent_worker_email');

            return "📧 No email found on the CV.\n\nEnter the worker's *email*, or type *skip* to auto-generate one.";
        }

        return $this->createWorker($profile['email']);
    }

    protected function handleEmail(string $message): string|array
    {
        if ($this->matchesCommand($message, ['skip', 'generate'])) {
            $email = 'worker.'.Str::lower(Str::random(10)).'@workers.careerworkforce.local';

            return $this->createWorker($email);
        }

        $email = app(SeekerOnboardingAIService::class)->isAvailable()
            ? (app(SeekerOnboardingAIService::class)->interpret('seeker_email', $message)['email'] ?? null)
            : null;

        $email ??= $this->extractEmail($message);

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '⚠️ Please enter a valid email, or type *skip*.';
        }

        if (User::where('email', $email)->exists()) {
            return "ℹ️ *{$email}* is already registered. Enter a different email or *skip*.";
        }

        return $this->createWorker($email);
    }

    /**
     * @return array{reply: string, redirect: string, agent_worker_active: false}
     */
    protected function createWorker(string $email): array
    {
        $agent = authUser();
        if (! $agent || $agent->role !== 'agent') {
            $this->reset();

            return ['reply' => 'Session expired. Please log in again.', 'agent_worker_active' => false];
        }

        $profile = $this->tempGet('profile') ?: [];
        $cnic = $profile['passport_number'] ?? null;
        $passport = $profile['passport_number'] ?? null;

        $duplicate = Candidate::query()
            ->when($cnic, fn ($q) => $q->orWhere('cnic_number', $cnic))
            ->when($passport, fn ($q) => $q->orWhere('passport_number', $passport))
            ->first();

        if ($duplicate) {
            return [
                'reply' => '⚠️ A worker with this passport/CNIC already exists on the platform. Check **My Candidates**.',
                'agent_worker_active' => true,
            ];
        }

        try {
            DB::beginTransaction();

            $name = trim(($profile['first_name'] ?? '').' '.($profile['last_name'] ?? ''));
            if ($name === '') {
                $name = Str::title(Str::before($email, '@'));
            }

            $password = Str::random(12);

            $user = User::create([
                'name' => $name,
                'username' => $this->uniqueUsername($name),
                'email' => strtolower($email),
                'whatsapp' => $profile['phone'] ?? null,
                'password' => Hash::make($password),
                'role' => 'candidate',
                'status' => 1,
                'auth_type' => 'email',
            ]);

            $candidate = $user->candidate ?? Candidate::create(['user_id' => $user->id]);

            $normalized = $this->buildNormalizedCandidateData(strtolower($email));
            $writer = app(SeekerProfileWriter::class);
            $writer->applyCv($candidate, $normalized, $this->tempGet('cv_path'));
            $writer->applyPassport($candidate, $this->tempGet('passport') ?: [], $this->tempGet('passport_path'));

            $candidate->update([
                'agent_id' => $agent->id,
                'owner_type' => 'agent',
                'owner_id' => $agent->id,
                'status' => 'submitted',
            ]);

            DB::commit();
            $this->reset();

            return [
                'reply' => "🎉 Worker **{$name}** registered under your account!\n\n"
                    ."👉 <a href='".route('agent.candidates.index')."'>View my candidates</a>",
                'redirect' => route('agent.candidates.index'),
                'agent_worker_active' => false,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[AgentWorkerOnboarding] '.$e->getMessage());

            return [
                'reply' => '⚠️ Could not save worker. Please try again or use **Add Candidate** on your dashboard.',
                'agent_worker_active' => true,
            ];
        }
    }

    protected function buildNormalizedCandidateData(string $email): array
    {
        $cv = $this->tempGet('cv') ?: [];
        $pp = $this->tempGet('passport') ?: [];

        if (! empty($pp['given_names'])) {
            $cv['first_name'] = Str::title(trim(explode(' ', trim((string) $pp['given_names']))[0]));
        }
        if (! empty($pp['surname'])) {
            $cv['last_name'] = Str::title(trim((string) $pp['surname']));
        }

        foreach ([
            'passport_number' => 'passport_number',
            'date_of_birth' => 'date_of_birth',
            'date_of_issue' => 'passport_issue_date',
            'date_of_expiry' => 'passport_expiry_date',
            'place_of_issue' => 'place_of_issue',
            'nationality' => 'nationality',
            'national_id' => 'cnic_number',
            'gender' => 'gender',
        ] as $ppKey => $cvKey) {
            if (! empty($pp[$ppKey])) {
                $cv[$cvKey] = $pp[$ppKey];
            }
        }

        $cv['is_cv'] = true;
        $normalizer = app(CvDataNormalizer::class);
        $cv = $normalizer->normalize($cv);

        $rawText = (string) ($this->tempGet('cv_raw_text') ?? '');
        if ($rawText !== '') {
            $cv = $normalizer->supplementLanguages($cv, $rawText);
        }

        $cv['email'] = $email;

        return $cv;
    }

    protected function mergeProfile(): array
    {
        $cv = $this->tempGet('cv') ?: [];
        $pp = $this->tempGet('passport') ?: [];

        return [
            'first_name' => $pp['given_names'] ?? $cv['first_name'] ?? null,
            'last_name' => $pp['surname'] ?? $cv['last_name'] ?? null,
            'email' => $cv['email'] ?? null,
            'phone' => $cv['phone'] ?? null,
            'nationality' => $pp['nationality'] ?? $cv['nationality'] ?? null,
            'passport_number' => $pp['passport_number'] ?? null,
            'date_of_birth' => $pp['date_of_birth'] ?? $cv['date_of_birth'] ?? null,
            'profession' => $cv['profession'] ?? null,
        ];
    }

    protected function renderProfile(array $p): string
    {
        $rows = [
            'Name' => trim(($p['first_name'] ?? '').' '.($p['last_name'] ?? '')),
            'Email' => $p['email'] ?? null,
            'Phone' => $p['phone'] ?? null,
            'Nationality' => $p['nationality'] ?? null,
            'Passport' => $p['passport_number'] ?? null,
            'Profession' => $p['profession'] ?? null,
        ];

        $out = '';
        foreach ($rows as $label => $value) {
            if ($value) {
                $out .= "• {$label}: {$value}\n";
            }
        }

        return $out ?: "• (No details read)\n";
    }

    protected function extractText(string $relPath): string
    {
        $full = storage_path('app/public/'.$relPath);
        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            try {
                return (new PdfParser())->parseFile($full)->getText();
            } catch (Throwable) {
                return '';
            }
        }

        $ocr = $this->ocr();
        $scan = $ocr ? $ocr->scan($relPath) : null;

        return is_array($scan) ? ($scan['raw_text'] ?? '') : '';
    }

    protected function extractEmail(string $raw): ?string
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $raw, $m)) {
            return strtolower($m[0]);
        }

        return null;
    }

    protected function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'worker';
        do {
            $username = $base.rand(100, 9999);
        } while (User::where('username', $username)->exists());

        return $username;
    }

    protected function isAffirmative(string $message): bool
    {
        $m = strtolower(trim($message));
        $words = ['confirm', 'yes', 'y', 'ok', 'sure', 'go for it', 'go ahead', 'correct'];

        foreach ($words as $w) {
            if ($m === $w || str_contains($m, $w)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesCommand(string $message, array $words): bool
    {
        return in_array(strtolower(trim($message)), $words, true);
    }

    protected function key(): string
    {
        return 'agent_worker_onboard_'.session()->getId();
    }

    protected function getStep(): ?string
    {
        return Cache::get($this->key().'_step');
    }

    protected function setStep(string $step): void
    {
        Cache::put($this->key().'_step', $step, 3600);
    }

    protected function tempSet(string $k, $v): void
    {
        $d = Cache::get($this->key().'_temp', []);
        $d[$k] = $v;
        Cache::put($this->key().'_temp', $d, 3600);
    }

    protected function tempGet(string $k)
    {
        return Cache::get($this->key().'_temp', [])[$k] ?? null;
    }

    protected function clearTemp(): void
    {
        Cache::forget($this->key().'_temp');
    }

    public function reset(): void
    {
        Cache::forget($this->key().'_step');
        Cache::forget($this->key().'_temp');
    }
}
