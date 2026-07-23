<?php

namespace App\Services\AI;

use App\Services\Website\Company\CompanyStoreService;
use Illuminate\Support\Facades\Log;

class ParsedJobBatchStoreService
{
    public function __construct(
        protected CompanyStoreService $companyStore
    ) {}

    /**
     * @param  list<array<string, mixed>>  $jobs
     * @param  array<string, mixed>  $shared
     * @return array{created: list<array{id: int, title: string, slug: string}>, skipped: list<array{title: string, reason: string}>, failed: list<array{title: string, message: string}>}
     */
    public function execute(array $jobs, array $shared = []): array
    {
        storePlanInformation();

        $created = [];
        $skipped = [];
        $failed = [];

        foreach ($jobs as $job) {
            $title = (string) ($job['job_title'] ?? 'Untitled');

            storePlanInformation();
            $limit = (int) (session('user_plan')->job_limit ?? 0);
            if ($limit < 1) {
                $skipped[] = [
                    'title' => $title,
                    'reason' => __('you_have_reached_your_plan_limit_please_upgrade_your_plan'),
                ];
                continue;
            }

            try {
                $stored = $this->companyStore->createFromParsedJob($job, $shared);
                $created[] = [
                    'id' => $stored->id,
                    'title' => $stored->title,
                    'slug' => $stored->slug,
                ];
            } catch (\Throwable $e) {
                Log::error('Parsed job batch store failed', [
                    'title' => $title,
                    'message' => $e->getMessage(),
                ]);
                $failed[] = [
                    'title' => $title,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return compact('created', 'skipped', 'failed');
    }
}
