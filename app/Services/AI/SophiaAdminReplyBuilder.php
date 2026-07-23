<?php

namespace App\Services\AI;

/**
 * Deterministic admin replies built from FACTS when OpenAI is slow, cached, or unavailable.
 */
class SophiaAdminReplyBuilder
{
    /**
     * @param  array<string, mixed>  $facts
     */
    public static function build(string $message, array $facts): ?string
    {
        $lower = strtolower(trim($message));

        if ($lower === '') {
            return null;
        }

        if (self::wantsAllTime($lower) || self::wantsVerified($lower) || self::wantsOverall($lower)) {
            return self::renderCombined($facts);
        }

        if (self::wantsPlans($lower)) {
            return self::renderPlans($facts);
        }

        if (self::wantsCombined($lower)) {
            return self::renderCombined($facts);
        }

        if (self::wantsCompanies($lower)) {
            return self::renderSection('Companies', $facts['companies'] ?? null);
        }

        if (self::wantsCandidates($lower)) {
            return self::renderSection('Job Seekers', $facts['job_seekers'] ?? null);
        }

        if (self::wantsJobs($lower)) {
            return self::renderJobs($facts['jobs'] ?? null);
        }

        return self::renderCombined($facts);
    }

    protected static function wantsAllTime(string $lower): bool
    {
        return str_contains($lower, 'all time')
            || str_contains($lower, 'all-time')
            || str_contains($lower, 'total registration')
            || str_contains($lower, 'overall stat');
    }

    protected static function wantsVerified(string $lower): bool
    {
        return str_contains($lower, 'verified')
            || str_contains($lower, 'verification');
    }

    protected static function wantsOverall(string $lower): bool
    {
        return str_contains($lower, 'overall')
            || str_contains($lower, 'all types')
            || str_contains($lower, 'all user');
    }

    protected static function wantsPlans(string $lower): bool
    {
        return str_contains($lower, 'plan')
            || str_contains($lower, 'subscription')
            || str_contains($lower, 'selling')
            || str_contains($lower, 'revenue')
            || str_contains($lower, 'purchase');
    }

    protected static function wantsCombined(string $lower): bool
    {
        return str_contains($lower, 'recent registration')
            || str_contains($lower, 'this month')
            || str_contains($lower, 'summary')
            || str_contains($lower, 'stats')
            || str_contains($lower, 'report');
    }

    protected static function wantsCompanies(string $lower): bool
    {
        return str_contains($lower, 'compan')
            || str_contains($lower, 'employer');
    }

    protected static function wantsCandidates(string $lower): bool
    {
        return str_contains($lower, 'candidate')
            || str_contains($lower, 'seeker')
            || str_contains($lower, 'job seeker');
    }

    protected static function wantsJobs(string $lower): bool
    {
        return str_contains($lower, 'job')
            || str_contains($lower, 'vacanc')
            || str_contains($lower, 'posting');
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderCombined(array $facts): string
    {
        $parts = [];

        if (isset($facts['all_time'])) {
            $parts[] = self::renderAllTime($facts);
        }

        $parts[] = self::renderSection('Job Seekers', $facts['job_seekers'] ?? null, 'registered_this_month', 'recent_registrations');
        $parts[] = self::renderSection('Companies', $facts['companies'] ?? null, 'registered_this_month', 'recent_registrations');
        $parts[] = self::renderJobs($facts['jobs'] ?? null);

        return implode('', array_filter($parts));
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderAllTime(array $facts): string
    {
        $all = $facts['all_time'] ?? null;

        if (! is_array($all)) {
            return '<p>I do not have all-time totals for your admin role. Ask a superadmin or check the dashboard.</p>';
        }

        if (($all['access'] ?? null) === 'denied') {
            return '<p>Your role does not include permission to view all-time user statistics.</p>';
        }

        $period = e($facts['reporting_period'] ?? now()->format('F Y'));
        $html = '<h4 class="sophia-h">All-time user statistics</h4>';
        $html .= '<p><em>Current month reporting period: <strong>'.$period.'</strong></em></p>';
        $html .= '<ul class="sophia-list">';
        $html .= '<li><strong>Job seekers (all time):</strong> '.(int) ($all['job_seekers'] ?? 0).'</li>';
        $html .= '<li><strong>Employers (all time):</strong> '.(int) ($all['employers'] ?? 0).'</li>';
        $html .= '<li><strong>Agencies (all time):</strong> '.(int) ($all['agencies'] ?? 0).'</li>';
        $html .= '<li><strong>Agents / Facilitators (all time):</strong> '.(int) ($all['sub_agents'] ?? 0).'</li>';
        $html .= '<li><strong>Jobs posted (all time):</strong> '.(int) ($all['jobs_total'] ?? 0).'</li>';
        $html .= '<li><strong>Email-verified users:</strong> '.(int) ($all['email_verified_users'] ?? 0).'</li>';
        $html .= '<li><strong>Verified company profiles:</strong> '.(int) ($all['verified_company_profiles'] ?? 0).'</li>';
        $html .= '<li><strong>Verified agency profiles:</strong> '.(int) ($all['verified_agency_profiles'] ?? 0).'</li>';
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<string, mixed>|null  $plans
     */
    protected static function renderPlans(?array $plans): string
    {
        if (! is_array($plans)) {
            return '<p>Plan sales data is not available for your role.</p>';
        }

        if (($plans['access'] ?? null) === 'denied') {
            return '<p>Your role does not include permission to view plan or subscription sales.</p>';
        }

        $top = $plans['top_selling'] ?? [];

        if ($top === []) {
            return '<p>No plan purchase records found yet.</p>';
        }

        $html = '<h4 class="sophia-h">Top selling plans</h4><ul class="sophia-list">';
        foreach ($top as $row) {
            $name = e($row['plan_name'] ?? 'Unknown plan');
            $count = (int) ($row['purchase_count'] ?? 0);
            $active = (int) ($row['active_subscriptions'] ?? 0);
            $html .= "<li><strong>{$name}</strong> — {$count} purchase(s), {$active} active subscription(s)</li>";
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<string, mixed>|null  $section
     */
    protected static function renderSection(
        string $title,
        ?array $section,
        string $countKey = 'registered_this_month',
        string $listKey = 'recent_registrations',
    ): string {
        if (! is_array($section)) {
            return '';
        }

        if (($section['access'] ?? null) === 'denied') {
            return '<h4 class="sophia-h">'.e($title).'</h4><p>Access denied for your admin role.</p>';
        }

        $count = (int) ($section[$countKey] ?? 0);
        $html = '<h4 class="sophia-h">'.e($title).'</h4>';
        $html .= '<p><strong>Registered this month:</strong> '.$count.'</p>';

        $items = $section[$listKey] ?? [];

        if (! is_array($items) || $items === []) {
            $html .= '<p><strong>Recent registrations:</strong> None</p>';

            return $html;
        }

        $html .= '<p><strong>Recent registrations:</strong></p><ul class="sophia-list">';
        foreach ($items as $row) {
            $name = e($row['name'] ?? 'Unknown');
            $email = e($row['email'] ?? '');
            $date = e($row['registered_at'] ?? $row['posted_at'] ?? '');
            $line = "<strong>{$name}</strong>";
            if ($email !== '') {
                $line .= " — {$email}";
            }
            if ($date !== '') {
                $line .= " ({$date})";
            }
            $html .= "<li>{$line}</li>";
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * @param  array<string, mixed>|null  $jobs
     */
    protected static function renderJobs(?array $jobs): string
    {
        if (! is_array($jobs)) {
            return '';
        }

        if (($jobs['access'] ?? null) === 'denied') {
            return '<h4 class="sophia-h">Jobs</h4><p>Access denied for your admin role.</p>';
        }

        $html = '<h4 class="sophia-h">Jobs</h4>';
        $html .= '<p><strong>Posted this month:</strong> '.(int) ($jobs['posted_this_month'] ?? 0).'</p>';
        $html .= '<p><strong>Active jobs:</strong> '.(int) ($jobs['active_jobs'] ?? 0).'</p>';

        $recent = $jobs['recent_posts'] ?? [];
        $active = [];
        $expired = [];

        foreach ($recent as $job) {
            $status = strtolower((string) ($job['status'] ?? ''));
            $title = $job['title'] ?? 'Untitled';
            if (in_array($status, ['active', 'published'], true)) {
                $active[] = $title;
            } else {
                $expired[] = $title;
            }
        }

        if ($active !== []) {
            $html .= '<p><strong>Recent active posts:</strong></p><ul class="sophia-list">';
            foreach ($active as $title) {
                $html .= '<li>'.e($title).'</li>';
            }
            $html .= '</ul>';
        }

        if ($expired !== []) {
            $html .= '<p><strong>Recent expired / inactive:</strong></p><ul class="sophia-list">';
            foreach ($expired as $title) {
                $html .= '<li>'.e($title).'</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }
}
