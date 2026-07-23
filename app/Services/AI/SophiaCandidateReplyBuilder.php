<?php

namespace App\Services\AI;

/**
 * Deterministic seeker replies for application / shortlist questions.
 */
class SophiaCandidateReplyBuilder
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

        if (! self::wantsApplications($lower)) {
            return null;
        }

        $apps = $facts['applications'] ?? [];
        $shortlisted = $facts['shortlisted_jobs'] ?? [];
        $byStatus = $facts['applications_by_status'] ?? [];
        $appsUrl = $facts['applications_page'] ?? null;
        $shortlistUrl = $facts['shortlisted_page'] ?? $appsUrl;

        if (self::wantsShortlisted($lower)) {
            return self::renderShortlisted($shortlisted, $byStatus, $shortlistUrl, $appsUrl);
        }

        if (self::wantsSelected($lower)) {
            $selected = array_values(array_filter(
                $apps,
                fn ($row) => is_array($row) && ($row['status'] ?? '') === 'selected'
            ));

            return self::renderStatusList('selected', $selected, $appsUrl);
        }

        if (self::wantsRejected($lower)) {
            $rejected = array_values(array_filter(
                $apps,
                fn ($row) => is_array($row) && ($row['status'] ?? '') === 'rejected'
            ));

            return self::renderStatusList('rejected', $rejected, $appsUrl);
        }

        return self::renderAll($apps, $byStatus, $appsUrl);
    }

    protected static function wantsApplications(string $lower): bool
    {
        return str_contains($lower, 'shortlist')
            || str_contains($lower, 'short listed')
            || str_contains($lower, 'short-listed')
            || str_contains($lower, 'application')
            || str_contains($lower, 'applied')
            || str_contains($lower, 'which job')
            || str_contains($lower, 'my job')
            || str_contains($lower, 'job status')
            || str_contains($lower, 'selected for')
            || str_contains($lower, 'rejected');
    }

    protected static function wantsShortlisted(string $lower): bool
    {
        return str_contains($lower, 'shortlist')
            || str_contains($lower, 'short listed')
            || str_contains($lower, 'short-listed');
    }

    protected static function wantsSelected(string $lower): bool
    {
        return str_contains($lower, 'selected');
    }

    protected static function wantsRejected(string $lower): bool
    {
        return str_contains($lower, 'rejected');
    }

    /**
     * @param  array<int, array<string, mixed>>  $shortlisted
     * @param  array<string, int>  $byStatus
     */
    protected static function renderShortlisted(array $shortlisted, array $byStatus, ?string $shortlistUrl, ?string $appsUrl): string
    {
        $count = count($shortlisted);

        if ($count === 0) {
            $total = (int) ($byStatus['pending'] ?? 0)
                + (int) ($byStatus['selected'] ?? 0)
                + (int) ($byStatus['rejected'] ?? 0)
                + (int) ($byStatus['shortlisted'] ?? 0);

            $reply = "You are not shortlisted for any jobs right now.";
            if ($total > 0) {
                $reply .= ' You have **'.$total.'** other application(s) still in progress.';
            }
            if ($appsUrl) {
                $reply .= "\n\n👉 <a href='".e($appsUrl)."'>My applications</a>";
            }

            return $reply;
        }

        $lines = ["You're **shortlisted** for **{$count}** ".($count === 1 ? 'job' : 'jobs').":\n"];
        foreach ($shortlisted as $row) {
            $title = e($row['job_title'] ?? 'Job');
            $company = e($row['company'] ?? 'Employer');
            $extra = ! empty($row['country']) ? ' · '.e($row['country']) : '';
            $lines[] = "• **{$title}** at {$company}{$extra}";
        }

        if ($shortlistUrl) {
            $lines[] = "\n👉 <a href='".e($shortlistUrl)."'>View shortlisted applications</a>";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected static function renderStatusList(string $status, array $rows, ?string $appsUrl): string
    {
        $count = count($rows);
        if ($count === 0) {
            $reply = "You have no **{$status}** applications right now.";
            if ($appsUrl) {
                $reply .= "\n\n👉 <a href='".e($appsUrl)."'>My applications</a>";
            }

            return $reply;
        }

        $lines = ["You have **{$count}** **{$status}** ".($count === 1 ? 'application' : 'applications').":\n"];
        foreach ($rows as $row) {
            $title = e($row['job_title'] ?? 'Job');
            $company = e($row['company'] ?? 'Employer');
            $lines[] = "• **{$title}** at {$company}";
        }

        if ($appsUrl) {
            $lines[] = "\n👉 <a href='".e($appsUrl)."'>My applications</a>";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, array<string, mixed>>  $apps
     * @param  array<string, int>  $byStatus
     */
    protected static function renderAll(array $apps, array $byStatus, ?string $appsUrl): string
    {
        if ($apps === []) {
            $reply = "You haven't applied to any jobs yet.";
            if ($appsUrl) {
                $reply .= "\n\n👉 <a href='".e($appsUrl)."'>My applications</a>";
            }

            return $reply;
        }

        $lines = [
            'Here are your applications '
            .'(**'.(int) ($byStatus['shortlisted'] ?? 0).'** shortlisted · '
            .'**'.(int) ($byStatus['pending'] ?? 0).'** pending · '
            .'**'.(int) ($byStatus['selected'] ?? 0).'** selected · '
            .'**'.(int) ($byStatus['rejected'] ?? 0).'** rejected):'
            ."\n",
        ];

        foreach ($apps as $row) {
            $title = e($row['job_title'] ?? 'Job');
            $company = e($row['company'] ?? 'Employer');
            $status = e($row['status'] ?? 'pending');
            $lines[] = "• **{$title}** at {$company} — *{$status}*";
        }

        if ($appsUrl) {
            $lines[] = "\n👉 <a href='".e($appsUrl)."'>Open Applied Jobs</a>";
        }

        return implode("\n", $lines);
    }
}
