<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Services\OpenAI\OpenAIService;

/**
 * Generates a short natural-language performance summary for the agency's
 * dashboard using the same KPI data the charts are built from, so recruiters
 * get a quick narrative read instead of only numbers.
 */
class AgencySummaryAIService
{
    public function __construct(
        protected OpenAIService $openAI,
        protected AgencyDashboardService $dashboardService,
        protected AgencyReportService $reportService,
    ) {
    }

    public function generate(Agency $agency): ?string
    {
        $cacheKey = 'agency_ai_summary_'.$agency->id.'_'.now()->format('Y-m-d');

        return cache()->remember($cacheKey, now()->addHours(12), function () use ($agency) {
            $dashboard = $this->dashboardService->build($agency);
            $funnel = $this->reportService->build($agency, 'applicant-tracking');

            $prompt = $this->buildPrompt($agency, $dashboard, $funnel);

            $summary = $this->openAI->ask($prompt, 'agency_dashboard_summary', $agency->user_id);

            return $summary ?: $this->fallbackSummary($dashboard, $funnel);
        });
    }

    protected function buildPrompt(Agency $agency, array $dashboard, array $funnel): string
    {
        $trend = $dashboard['trends']['applicants']['percent'] ?? 0;
        $commission = $dashboard['commissionTotals'] ?? ['pending' => 0, 'approved' => 0, 'paid' => 0];
        $visa = $dashboard['visaCaseCounts'] ?? ['in_progress' => 0, 'completed' => 0, 'deployed' => 0];

        $funnelLines = collect($funnel['rows'] ?? [])
            ->map(fn ($row) => "{$row[0]}: {$row[1]}")
            ->implode(', ');

        return <<<PROMPT
You are a recruitment operations analyst. Write a concise 3-4 sentence
performance summary (plain text, no markdown) for a recruitment agency's
dashboard based on this data. Be specific with numbers, mention one risk or
opportunity, and keep an encouraging but professional tone.

Open jobs: {$dashboard['openJobCount']}
Pending jobs: {$dashboard['pendingJobCount']}
Total applicants: {$dashboard['applicants']}
Applicant growth vs last month: {$trend}%
Applicant funnel: {$funnelLines}
Commission pending: {$commission['pending']}, approved: {$commission['approved']}, paid: {$commission['paid']}
Visa cases in progress: {$visa['in_progress']}, completed: {$visa['completed']}, deployed: {$visa['deployed']}
PROMPT;
    }

    protected function fallbackSummary(array $dashboard, array $funnel): string
    {
        $trend = $dashboard['trends']['applicants']['percent'] ?? 0;
        $direction = $trend >= 0 ? 'up' : 'down';

        return sprintf(
            'You currently have %d open jobs and %d total applicants, %s %s%% vs last month. '
            .'%d candidates are in visa processing and %d have been deployed so far.',
            $dashboard['openJobCount'] ?? 0,
            $dashboard['applicants'] ?? 0,
            $direction,
            abs($trend),
            $dashboard['visaCaseCounts']['in_progress'] ?? 0,
            $dashboard['visaCaseCounts']['deployed'] ?? 0
        );
    }
}
