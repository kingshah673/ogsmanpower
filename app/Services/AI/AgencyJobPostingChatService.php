<?php

namespace App\Services\AI;

use App\Models\User;
use App\Services\Website\Agency\AgencyStoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Guided agency job posting from advertisement uploads inside Sophia chat.
 */
class AgencyJobPostingChatService
{
    public function isActive(): bool
    {
        return Cache::has($this->cacheKey());
    }

    public function looksLikeJobIntent(string $message, ?string $attachmentPath = null): bool
    {
        $lower = strtolower(trim($message));
        $keywords = [
            'job', 'post', 'posting', 'advertisement', 'advert', 'vacancy',
            'vacancies', 'hiring', 'demand letter', 'demand', 'position', 'recruit',
            'upload job', 'parse job', 'extract job',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return true;
            }
        }

        if ($attachmentPath) {
            $ext = strtolower(pathinfo($attachmentPath, PATHINFO_EXTENSION));

            return in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'], true);
        }

        return false;
    }

    /**
     * @return array{reply: string, actions?: array}|null
     */
    public function handle(User $user, string $message, ?string $attachmentPath = null): ?array
    {
        if ($user->role !== 'agency' || ! $user->agency) {
            return null;
        }

        if ($attachmentPath) {
            return $this->handleAttachment($attachmentPath);
        }

        if (! $this->isActive()) {
            return null;
        }

        $message = trim($message);
        $lower = strtolower($message);

        if ($this->isCancel($lower)) {
            Cache::forget($this->cacheKey());

            return [
                'reply' => '🔙 Job posting cancelled. Upload another advertisement anytime, or open **Post a job** from your dashboard.',
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        if ($this->isAffirmative($lower)) {
            return $this->publishParsedJobs();
        }

        $cached = Cache::get($this->cacheKey());
        $summary = $this->formatParsedSummary($cached['parsed'] ?? []);

        return [
            'reply' => $summary."\n\nType *confirm* to publish these jobs, or *cancel* to discard.",
        ];
    }

    /**
     * @return array{reply: string, actions?: array}
     */
    protected function handleAttachment(string $attachmentPath): array
    {
        if (empty(config('services.openai.key'))) {
            return [
                'reply' => '⚠️ AI job extraction is not configured. Please use **Post a job** on your dashboard.',
            ];
        }

        try {
            $text = $this->extractText($attachmentPath);

            if (mb_strlen(trim($text)) < 30) {
                return [
                    'reply' => '⚠️ I could not read that file clearly. Please upload a sharper photo or text-based PDF.',
                ];
            }

            $parsed = app(GPTJobParserService::class)->parse($text);

            if (empty($parsed['is_job_posting'])) {
                return [
                    'reply' => "🤔 That doesn't look like a job advertisement.\n\n"
                        ."If it's a **verification document**, say *upload document*.\n"
                        .'For job ads, upload a PDF or clear image of the vacancy flyer.',
                ];
            }

            Cache::put($this->cacheKey(), [
                'parsed' => $parsed,
                'path' => $attachmentPath,
            ], now()->addHours(2));

            $summary = $this->formatParsedSummary($parsed);

            return [
                'reply' => "✅ **Job advertisement parsed!**\n\n{$summary}\n\n"
                    .'Type *confirm* to publish, or *cancel* to discard.',
                'actions' => [
                    ['key' => 'post_jobs', 'label' => 'Publish parsed jobs', 'type' => 'action', 'value' => 'confirm_job_posting'],
                    ['key' => 'post_job_page', 'label' => 'Review on Post Job page', 'type' => 'link', 'url' => route('agency.job.create')],
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[Sophia] agency job parse: '.$e->getMessage());

            return [
                'reply' => '⚠️ Something went wrong parsing that advertisement. Please try again or use **Post a job**.',
            ];
        }
    }

    /**
     * @return array{reply: string, actions?: array}
     */
    protected function publishParsedJobs(): array
    {
        $cached = Cache::get($this->cacheKey());

        if (! $cached || empty($cached['parsed']['jobs'])) {
            Cache::forget($this->cacheKey());

            return [
                'reply' => '⚠️ No parsed jobs in session. Please upload a job advertisement first.',
            ];
        }

        $parsed = $cached['parsed'];
        $jobs = $parsed['jobs'] ?? [];
        $shared = $parsed['shared'] ?? [];
        $store = app(AgencyStoreService::class);
        $created = [];
        $skipped = [];
        $failed = [];

        foreach ($jobs as $job) {
            $title = (string) ($job['job_title'] ?? 'Untitled');
            storePlanInformation();
            $limit = (int) (session('user_plan')->job_limit ?? 0);
            if ($limit < 1) {
                $skipped[] = $title;
                continue;
            }

            try {
                $stored = $store->createFromParsedJob($job, $shared);
                $created[] = $stored->title;
            } catch (Throwable $e) {
                Log::error('[Sophia] agency job publish: '.$e->getMessage());
                $failed[] = $title;
            }
        }

        Cache::forget($this->cacheKey());

        $lines = array_map(fn ($t) => "• **{$t}**", $created);
        $reply = empty($lines)
            ? '⚠️ No jobs were published. You may have reached your plan limit.'
            : '🎉 **Published '.count($lines)." job(s):**\n\n".implode("\n", $lines);

        if ($skipped !== []) {
            $reply .= "\n\n⏭️ Skipped: ".count($skipped).' (plan limit).';
        }
        if ($failed !== []) {
            $reply .= "\n\n❌ Failed: ".count($failed).'.';
        }

        try {
            $reply .= "\n\n👉 <a href='".route('agency.myjob')."'>View your jobs</a>";
        } catch (Throwable $e) {
        }

        return [
            'reply' => $reply,
            'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     */
    protected function formatParsedSummary(array $parsed): string
    {
        $jobs = $parsed['jobs'] ?? [];
        $count = count($jobs);

        if ($count === 0) {
            return 'No job positions were detected.';
        }

        $lines = ["Found **{$count}** position(s):"];
        foreach (array_slice($jobs, 0, 8) as $job) {
            $title = $job['job_title'] ?? 'Untitled';
            $salary = ! empty($job['salary']) ? ' — '.$job['salary'] : '';
            $lines[] = "• {$title}{$salary}";
        }

        if ($count > 8) {
            $lines[] = '• … and '.($count - 8).' more';
        }

        return implode("\n", $lines);
    }

    protected function extractText(string $relPath): string
    {
        $full = Storage::disk('public')->path($relPath);
        $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            try {
                return (new PdfParser())->parseFile($full)->getText();
            } catch (Throwable $e) {
                return '';
            }
        }

        try {
            $ocr = app(\App\Services\Chat\OCRService::class)->scan($relPath);

            return is_array($ocr) ? (string) ($ocr['raw_text'] ?? '') : '';
        } catch (Throwable $e) {
            return '';
        }
    }

    protected function isAffirmative(string $lower): bool
    {
        if (in_array($lower, ['confirm', 'yes', 'y', 'ok', 'publish', 'post', 'go ahead', 'do it'], true)) {
            return true;
        }

        return (bool) preg_match('/^(yes|yeah|yep|ok|okay|sure|confirm|publish)\b/', $lower);
    }

    protected function isCancel(string $lower): bool
    {
        return in_array($lower, ['cancel', 'stop', 'no', 'discard', 'restart'], true)
            || str_contains($lower, 'cancel');
    }

    protected function cacheKey(): string
    {
        return 'sophia_agency_job_'.session()->getId();
    }

    public function isQaActive(): bool
    {
        return Cache::has($this->qaCacheKey());
    }

    public function startQa(): string
    {
        Cache::put($this->qaCacheKey(), [
            'step' => 'title',
            'data' => [],
        ], now()->addHours(2));

        return "📝 **Guided job posting**\n\n"
            ."I'll ask a few questions and publish the job for you.\n\n"
            .'**Step 1:** What is the **job title**?';
    }

    /**
     * @return array{reply: string, actions?: array}|null
     */
    public function handleQa(string $message): ?array
    {
        $state = Cache::get($this->qaCacheKey());
        if (! is_array($state)) {
            return null;
        }

        $lower = strtolower(trim($message));
        $step = $state['step'] ?? 'title';
        $data = $state['data'] ?? [];

        if ($this->isCancel($lower)) {
            Cache::forget($this->qaCacheKey());

            return [
                'reply' => '🔙 Guided job posting cancelled.',
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        return match ($step) {
            'title' => $this->qaAfterTitle($message, $data),
            'location' => $this->qaAfterLocation($message, $data),
            'salary' => $this->qaAfterSalary($message, $data),
            'vacancies' => $this->qaAfterVacancies($message, $data),
            'description' => $this->qaAfterDescription($message, $data),
            'confirm' => $this->qaPublish($message, $data),
            default => $this->qaRestart(),
        };
    }

    /** @param  array<string, mixed>  $data */
    protected function qaAfterTitle(string $message, array $data): array
    {
        if (mb_strlen(trim($message)) < 2) {
            return ['reply' => 'Please type the **job title** (at least 2 characters).'];
        }
        $data['job_title'] = trim($message);
        Cache::put($this->qaCacheKey(), ['step' => 'location', 'data' => $data], now()->addHours(2));

        return ['reply' => "**Step 2:** Where is the job **location**? (country / city)"];
    }

    /** @param  array<string, mixed>  $data */
    protected function qaAfterLocation(string $message, array $data): array
    {
        $data['location'] = trim($message) ?: 'Overseas';
        Cache::put($this->qaCacheKey(), ['step' => 'salary', 'data' => $data], now()->addHours(2));

        return ['reply' => '**Step 3:** What is the **salary**? (or type *skip*)'];
    }

    /** @param  array<string, mixed>  $data */
    protected function qaAfterSalary(string $message, array $data): array
    {
        if (! in_array(strtolower(trim($message)), ['skip', 'later', 'n/a'], true)) {
            $data['custom_salary'] = trim($message);
            $data['salary'] = trim($message);
        }
        Cache::put($this->qaCacheKey(), ['step' => 'vacancies', 'data' => $data], now()->addHours(2));

        return ['reply' => '**Step 4:** How many **vacancies**? (number)'];
    }

    /** @param  array<string, mixed>  $data */
    protected function qaAfterVacancies(string $message, array $data): array
    {
        $data['vacancies'] = max(1, (int) preg_replace('/\D/', '', $message) ?: 1);
        Cache::put($this->qaCacheKey(), ['step' => 'description', 'data' => $data], now()->addHours(2));

        return ['reply' => '**Step 5:** Short **job description** (or type *skip*).'];
    }

    /** @param  array<string, mixed>  $data */
    protected function qaAfterDescription(string $message, array $data): array
    {
        if (! in_array(strtolower(trim($message)), ['skip', 'later'], true)) {
            $data['description'] = '<p>'.e(trim($message)).'</p>';
        }
        Cache::put($this->qaCacheKey(), ['step' => 'confirm', 'data' => $data], now()->addHours(2));

        $title = $data['job_title'] ?? 'Job';
        $loc = $data['location'] ?? '—';
        $sal = $data['custom_salary'] ?? 'Competitive';
        $vac = $data['vacancies'] ?? 1;

        return [
            'reply' => "📋 **Review:**\n• Title: **{$title}**\n• Location: {$loc}\n• Salary: {$sal}\n• Vacancies: {$vac}\n\nType *confirm* to publish, or *cancel*.",
            'actions' => [
                ['key' => 'confirm_qa', 'label' => 'Publish job', 'type' => 'action', 'value' => 'confirm_job_posting_qa'],
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    protected function qaPublish(string $message, array $data): array
    {
        if (! $this->isAffirmative(strtolower(trim($message)))) {
            return ['reply' => 'Type *confirm* to publish, or *cancel* to discard.'];
        }

        Cache::forget($this->qaCacheKey());

        try {
            $job = app(AgencyStoreService::class)->createFromParsedJob($data, []);
            $url = route('agency.myjob');

            return [
                'reply' => "🎉 Published **{$job->title}**.\n\n👉 <a href='{$url}'>View your jobs</a>",
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        } catch (Throwable $e) {
            return ['reply' => '⚠️ Could not publish: '.$e->getMessage()];
        }
    }

    protected function qaRestart(): array
    {
        Cache::forget($this->qaCacheKey());

        return ['reply' => $this->startQa()];
    }

    protected function qaCacheKey(): string
    {
        return 'sophia_agency_job_qa_'.session()->getId();
    }
}
