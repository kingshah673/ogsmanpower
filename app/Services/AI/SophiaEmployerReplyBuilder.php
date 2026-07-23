<?php

namespace App\Services\AI;

/**
 * Deterministic employer replies from company FACTS.
 */
class SophiaEmployerReplyBuilder
{
    /**
     * @param  array<string, mixed>  $facts
     */
    public static function build(string $message, array $facts): ?string
    {
        $lower = strtolower(trim($message));
        if ($lower === '' || ($facts['company_setup'] ?? null) === 'incomplete') {
            return null;
        }

        if (self::wantsVerification($lower)) {
            return self::renderVerification($facts);
        }

        if (self::wantsShortlisted($lower)) {
            return self::renderShortlisted($facts);
        }

        if (self::wantsApplicants($lower)) {
            return self::renderApplicants($facts);
        }

        if (self::wantsJobs($lower)) {
            return self::renderJobs($facts);
        }

        if (self::wantsHowToPost($lower)) {
            return self::renderHowToPost($facts);
        }

        if (self::wantsDashboardSummary($lower)) {
            return self::renderSummary($facts);
        }

        return null;
    }

    protected static function wantsVerification(string $lower): bool
    {
        return str_contains($lower, 'verif')
            || str_contains($lower, 'document')
            || str_contains($lower, 'license')
            || str_contains($lower, 'trade license')
            || str_contains($lower, 'approval');
    }

    protected static function wantsShortlisted(string $lower): bool
    {
        return str_contains($lower, 'shortlist')
            || str_contains($lower, 'short listed')
            || str_contains($lower, 'short-listed');
    }

    protected static function wantsApplicants(string $lower): bool
    {
        return str_contains($lower, 'applicant')
            || str_contains($lower, 'application')
            || str_contains($lower, 'who applied')
            || str_contains($lower, 'candidates for')
            || str_contains($lower, 'how many apply')
            || str_contains($lower, 'how many applied')
            || (str_contains($lower, 'candidate') && ! str_contains($lower, 'register'));
    }

    protected static function wantsJobs(string $lower): bool
    {
        return str_contains($lower, 'my job')
            || str_contains($lower, 'my jobs')
            || str_contains($lower, 'active job')
            || str_contains($lower, 'posted job')
            || str_contains($lower, 'which job')
            || str_contains($lower, 'list job')
            || str_contains($lower, 'job status')
            || str_contains($lower, 'vacancies')
            || (str_contains($lower, 'job') && (str_contains($lower, 'how many') || str_contains($lower, 'show') || str_contains($lower, 'open')));
    }

    protected static function wantsHowToPost(string $lower): bool
    {
        return str_contains($lower, 'post a job')
            || str_contains($lower, 'post job')
            || str_contains($lower, 'create job')
            || str_contains($lower, 'create a job')
            || str_contains($lower, 'publish job')
            || str_contains($lower, 'how do i post')
            || str_contains($lower, 'how to post');
    }

    protected static function wantsDashboardSummary(string $lower): bool
    {
        return str_contains($lower, 'summary')
            || str_contains($lower, 'overview')
            || str_contains($lower, 'dashboard')
            || str_contains($lower, 'how am i doing')
            || str_contains($lower, 'status of my company')
            || $lower === 'help'
            || str_contains($lower, 'what can you do');
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderVerification(array $facts): string
    {
        $status = e((string) ($facts['document_verification_status'] ?? 'unknown'));
        $missing = $facts['missing_verification_documents'] ?? [];
        $pages = $facts['pages'] ?? [];
        $verified = ! empty($facts['is_profile_verified']);

        if ($verified && $missing === []) {
            $reply = "✅ Your company profile is **verified** (status: *{$status}*).";
        } elseif ($missing !== []) {
            $labels = implode(', ', array_map(fn ($d) => '**'.e((string) $d).'**', $missing));
            $reply = "Verification status: **{$status}**.\n\nStill needed: {$labels}.";
        } else {
            $reply = "Verification status: **{$status}**.";
        }

        if (! empty($pages['verify_documents'])) {
            $reply .= "\n\n👉 <a href='".e($pages['verify_documents'])."'>Upload / view documents</a>";
        }

        return $reply;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderShortlisted(array $facts): string
    {
        $rows = $facts['shortlisted_applicants'] ?? [];
        $count = (int) (($facts['applicants_count']['shortlisted'] ?? null) ?? count($rows));
        $pages = $facts['pages'] ?? [];

        if ($count === 0 || $rows === []) {
            $reply = 'You have **no shortlisted applicants** right now.';
        } else {
            $reply = "You have **{$count}** shortlisted ".($count === 1 ? 'applicant' : 'applicants').":\n";
            foreach (array_slice($rows, 0, 12) as $row) {
                $name = e($row['candidate_name'] ?? 'Applicant');
                $job = e($row['job_title'] ?? 'Job');
                $reply .= "\n• **{$name}** — {$job}";
            }
        }

        $url = $pages['shortlisted_applicants'] ?? $pages['applicants'] ?? null;
        if ($url) {
            $reply .= "\n\n👉 <a href='".e($url)."'>Review shortlisted applicants</a>";
        }

        return $reply;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderApplicants(array $facts): string
    {
        $counts = $facts['applicants_count'] ?? [];
        $rows = $facts['recent_applicants'] ?? [];
        $pages = $facts['pages'] ?? [];
        $total = (int) ($counts['total'] ?? count($rows));

        $reply = 'Applicants overview: **'.$total.'** total'
            .' · **'.(int) ($counts['pending'] ?? 0).'** pending'
            .' · **'.(int) ($counts['shortlisted'] ?? 0).'** shortlisted'
            .' · **'.(int) ($counts['selected'] ?? 0).'** selected'
            .' · **'.(int) ($counts['rejected'] ?? 0).'** rejected.';

        if ($rows !== []) {
            $reply .= "\n\nRecent applicants:\n";
            foreach (array_slice($rows, 0, 10) as $row) {
                $name = e($row['candidate_name'] ?? 'Applicant');
                $job = e($row['job_title'] ?? 'Job');
                $status = e($row['status'] ?? 'pending');
                $reply .= "\n• **{$name}** — {$job} (*{$status}*)";
            }
        }

        if (! empty($pages['applicants'])) {
            $reply .= "\n\n👉 <a href='".e($pages['applicants'])."'>Open Applicants</a>";
        }

        return $reply;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderJobs(array $facts): string
    {
        $counts = $facts['jobs_count'] ?? [];
        $jobs = $facts['jobs'] ?? [];
        $pages = $facts['pages'] ?? [];

        $reply = 'Your jobs: **'.(int) ($counts['total'] ?? 0).'** total'
            .' · **'.(int) ($counts['active'] ?? 0).'** active'
            .' · **'.(int) ($counts['expired'] ?? 0).'** expired'
            .' · **'.(int) ($counts['pending'] ?? 0).'** pending.';

        if ($jobs !== []) {
            $reply .= "\n\nRecent postings:\n";
            foreach (array_slice($jobs, 0, 12) as $job) {
                $title = e($job['title'] ?? 'Job');
                $status = e($job['status'] ?? 'unknown');
                $country = ! empty($job['country']) ? ' · '.e($job['country']) : '';
                $reply .= "\n• **{$title}** (*{$status}*){$country}";
            }
        } else {
            $reply .= "\n\nYou haven't posted any jobs yet.";
        }

        if (! empty($pages['my_jobs'])) {
            $reply .= "\n\n👉 <a href='".e($pages['my_jobs'])."'>My Jobs</a>";
        }
        if (! empty($pages['post_job'])) {
            $reply .= " · <a href='".e($pages['post_job'])."'>Post a job</a>";
        }

        return $reply;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderHowToPost(array $facts): string
    {
        $pages = $facts['pages'] ?? [];
        $reply = "You can post a job in three ways:\n"
            ."1. **Post a job** form on your employer portal\n"
            ."2. Ask me to **Upload job advertisement** (PDF/image)\n"
            ."3. Ask me for **Post job (guided Q&A)**\n";

        if (! empty($pages['post_job'])) {
            $reply .= "\n👉 <a href='".e($pages['post_job'])."'>Open Post a job</a>";
        }

        return $reply;
    }

    /**
     * @param  array<string, mixed>  $facts
     */
    protected static function renderSummary(array $facts): string
    {
        $jobs = $facts['jobs_count'] ?? [];
        $apps = $facts['applicants_count'] ?? [];
        $pages = $facts['pages'] ?? [];
        $missing = $facts['missing_verification_documents'] ?? [];

        $reply = "Here's your employer snapshot for **".e((string) ($facts['company_name'] ?? 'your company'))."**:\n"
            .'• Jobs: **'.(int) ($jobs['active'] ?? 0).'** active / **'.(int) ($jobs['total'] ?? 0)."** total\n"
            .'• Applicants: **'.(int) ($apps['total'] ?? 0).'** total (**'.(int) ($apps['shortlisted'] ?? 0)."** shortlisted)\n"
            .'• Verification: **'.e((string) ($facts['document_verification_status'] ?? 'unknown')).'**';

        if ($missing !== []) {
            $reply .= "\n• Missing docs: ".implode(', ', array_map(fn ($d) => e((string) $d), $missing));
        }

        $reply .= "\n\nI can tell you your **jobs**, **applicants**, **shortlists**, or **verification** status — just ask.";

        if (! empty($pages['dashboard'])) {
            $reply .= "\n\n👉 <a href='".e($pages['dashboard'])."'>Dashboard</a>";
        }

        return $reply;
    }
}
