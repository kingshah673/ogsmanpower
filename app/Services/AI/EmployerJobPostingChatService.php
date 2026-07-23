<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Guided employer job posting from advertisement uploads inside Sophia chat.
 * Reuses GPTJobParserService + ParsedJobBatchStoreService (same as Post Job page).
 */
class EmployerJobPostingChatService
{
    public function isActive(): bool
    {
        return Cache::has($this->cacheKey());
    }

    public function looksLikeJobIntent(string $message, ?string $attachmentPath = null): bool
    {
        $lower = strtolower(trim($message));

        $keywords = [
            'job', 'post', 'posting', 'advertisement', 'advert', 'ad ', 'vacancy',
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
        if ($user->role !== 'company' || ! $user->company) {
            return null;
        }

        if ($attachmentPath) {
            return $this->handleAttachment($user, $attachmentPath);
        }

        if (! $this->isActive()) {
            return null;
        }

        $message = trim($message);
        $lower = strtolower($message);

        if ($this->isCancel($lower)) {
            Cache::forget($this->cacheKey());

            return [
                'reply' => "🔙 Job posting cancelled. You can upload another advertisement anytime, or open **Post a job** from your dashboard.",
                'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
            ];
        }

        if ($this->isAffirmative($lower)) {
            return $this->publishParsedJobs($user);
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
    protected function handleAttachment(User $user, string $attachmentPath): array
    {
        if (empty(config('services.openai.key'))) {
            return [
                'reply' => '⚠️ AI job extraction is not configured. Please use **Post a job** on your dashboard to upload advertisements.',
            ];
        }

        try {
            $text = $this->extractText($attachmentPath);

            if (mb_strlen(trim($text)) < 30) {
                return [
                    'reply' => '⚠️ I could not read that file clearly. Please upload a sharper photo or text-based PDF of the job advertisement.',
                ];
            }

            $parsed = app(GPTJobParserService::class)->parse($text);

            if (empty($parsed['is_job_posting'])) {
                return [
                    'reply' => "🤔 That doesn't look like a job advertisement.\n\n"
                        ."If it's a **verification document**, say *upload document*.\n"
                        ."For job ads, upload a PDF or clear image of the vacancy flyer.",
                ];
            }

            Cache::put($this->cacheKey(), [
                'parsed' => $parsed,
                'path' => $attachmentPath,
            ], now()->addHours(2));

            $summary = $this->formatParsedSummary($parsed);

            return [
                'reply' => "✅ **Job advertisement parsed!**\n\n{$summary}\n\n"
                    ."Type *confirm* to publish, or *cancel* to discard.",
                'actions' => [
                    ['key' => 'post_jobs', 'label' => 'Publish parsed jobs', 'type' => 'action', 'value' => 'confirm_job_posting'],
                    ['key' => 'post_job_page', 'label' => 'Review on Post Job page', 'type' => 'link', 'url' => route('company.job.create')],
                ],
            ];
        } catch (Throwable $e) {
            Log::error('[Sophia] employer job parse: '.$e->getMessage());

            return [
                'reply' => '⚠️ Something went wrong parsing that advertisement. Please try again or use **Post a job** on your dashboard.',
            ];
        }
    }

    /**
     * @return array{reply: string, actions?: array}
     */
    protected function publishParsedJobs(User $user): array
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

        try {
            $result = app(ParsedJobBatchStoreService::class)->execute($jobs, $shared);
        } catch (Throwable $e) {
            Log::error('[Sophia] employer job publish: '.$e->getMessage());

            return [
                'reply' => '⚠️ Could not publish jobs: '.$e->getMessage(),
            ];
        }

        Cache::forget($this->cacheKey());

        $lines = [];
        foreach ($result['created'] ?? [] as $job) {
            $lines[] = '• **'.($job['title'] ?? 'Job').'**';
        }

        $reply = empty($lines)
            ? '⚠️ No jobs were published. You may have reached your plan limit.'
            : "🎉 **Published ".count($lines)." job(s):**\n\n".implode("\n", $lines);

        if (! empty($result['skipped'])) {
            $reply .= "\n\n⏭️ Skipped: ".count($result['skipped']).' (plan limit).';
        }

        if (! empty($result['failed'])) {
            $reply .= "\n\n❌ Failed: ".count($result['failed']).'.';
        }

        $jobsUrl = $this->safeRoute('company.myjob') ?? $this->safeRoute('company.dashboard');
        if ($jobsUrl) {
            $reply .= "\n\n👉 <a href='{$jobsUrl}'>View your jobs</a>";
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
            $salary = '';
            if (! empty($job['salary'])) {
                $salary = ' — '.$job['salary'];
            }
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
                Log::warning('[EmployerJobChat] PDF: '.$e->getMessage());

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

        $phrases = ['go for it', 'go ahead', 'post them', 'publish them', 'looks good', 'post all', 'yes please'];

        foreach ($phrases as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
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
        return 'sophia_employer_job_'.session()->getId();
    }

    protected function qaCacheKey(): string
    {
        return 'sophia_employer_job_qa_'.session()->getId();
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
            ."**Step 1:** What is the **job title**? (e.g. Heavy Equipment Operator)";
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
                'reply' => "🔙 Guided job posting cancelled. You can upload an advertisement or try again anytime.",
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

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string}
     */
    protected function qaAfterTitle(string $message, array $data): array
    {
        if (mb_strlen(trim($message)) < 2) {
            return ['reply' => 'Please type the **job title** (at least 2 characters).'];
        }

        $data['job_title'] = trim($message);
        Cache::put($this->qaCacheKey(), ['step' => 'location', 'data' => $data], now()->addHours(2));

        return ['reply' => "✅ Title: **{$data['job_title']}**\n\n**Step 2:** Where is the job **location**? (city / country)"];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string}
     */
    protected function qaAfterLocation(string $message, array $data): array
    {
        $data['location'] = trim($message) ?: 'Not specified';
        Cache::put($this->qaCacheKey(), ['step' => 'salary', 'data' => $data], now()->addHours(2));

        return ['reply' => "**Step 3:** What is the **salary** or pay range? (e.g. 1500 USD/month)"];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string}
     */
    protected function qaAfterSalary(string $message, array $data): array
    {
        $data['salary'] = trim($message) ?: 'Negotiable';
        Cache::put($this->qaCacheKey(), ['step' => 'vacancies', 'data' => $data], now()->addHours(2));

        return ['reply' => "**Step 4:** How many **vacancies**? (type a number)"];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string}
     */
    protected function qaAfterVacancies(string $message, array $data): array
    {
        $num = (int) preg_replace('/\D/', '', $message);
        $data['vacancies'] = max(1, $num ?: 1);
        Cache::put($this->qaCacheKey(), ['step' => 'description', 'data' => $data], now()->addHours(2));

        return ['reply' => "**Step 5:** Brief **job description** and requirements (or type *skip*)."];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string, actions?: array}
     */
    protected function qaAfterDescription(string $message, array $data): array
    {
        if (! $this->isCancel(strtolower($message)) && strtolower(trim($message)) !== 'skip') {
            $data['description'] = trim($message);
        }

        Cache::put($this->qaCacheKey(), ['step' => 'confirm', 'data' => $data], now()->addHours(2));

        $summary = "**{$data['job_title']}**\n"
            ."• Location: {$data['location']}\n"
            ."• Salary: {$data['salary']}\n"
            ."• Vacancies: {$data['vacancies']}\n";

        if (! empty($data['description'])) {
            $summary .= "• Description: {$data['description']}\n";
        }

        return [
            'reply' => "📋 **Review your job:**\n\n{$summary}\nType **confirm** to publish, or **cancel** to discard.",
            'actions' => [
                ['key' => 'confirm_qa_job', 'label' => 'Publish job', 'type' => 'action', 'value' => 'confirm_job_posting_qa'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{reply: string, actions?: array}
     */
    protected function qaPublish(string $message, array $data): array
    {
        if (! $this->isAffirmative(strtolower(trim($message)))) {
            return ['reply' => 'Type **confirm** to publish this job, or **cancel** to discard.'];
        }

        Cache::forget($this->qaCacheKey());

        $jobs = [[
            'job_title' => $data['job_title'] ?? 'Untitled',
            'location' => $data['location'] ?? null,
            'salary' => $data['salary'] ?? null,
            'vacancies' => $data['vacancies'] ?? 1,
            'description' => $data['description'] ?? null,
        ]];

        try {
            $result = app(ParsedJobBatchStoreService::class)->execute($jobs, []);
        } catch (Throwable $e) {
            Log::error('[Sophia] employer job QA publish: '.$e->getMessage());

            return ['reply' => '⚠️ Could not publish: '.$e->getMessage()];
        }

        $created = count($result['created'] ?? []);

        return [
            'reply' => $created > 0
                ? "🎉 **Job published:** {$data['job_title']}\n\n"
                    .(($url = $this->safeRoute('company.myjob')) ? "👉 <a href='{$url}'>View your jobs</a>" : '')
                : '⚠️ Job was not published. You may have reached your plan limit.',
            'actions' => app(SophiaContextService::class)->build()['actions'] ?? [],
        ];
    }

    protected function safeRoute(string $name): ?string
    {
        try {
            return route($name);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @return array{reply: string}
     */
    protected function qaRestart(): array
    {
        Cache::forget($this->qaCacheKey());

        return ['reply' => $this->startQa()];
    }
}
