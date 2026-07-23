<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\Job;

/**
 * Deterministic (rule-based) candidate <-> job matcher for the agency's own
 * talent pool. Scores each of the agency's candidates against a job's
 * requirements so recruiters can quickly see who to shortlist first.
 */
class CandidateJobMatcherService
{
    public function topMatches(Agency $agency, Job $job, int $limit = 10): array
    {
        $candidates = Candidate::where('agency_id', $agency->id)
            ->with('user')
            ->get();

        $scored = $candidates->map(function (Candidate $candidate) use ($job) {
            [$score, $reasons] = $this->score($candidate, $job);

            return [
                'candidate' => $candidate,
                'score' => $score,
                'reasons' => $reasons,
            ];
        });

        return $scored->sortByDesc('score')->take($limit)->values()->all();
    }

    protected function score(Candidate $candidate, Job $job): array
    {
        $score = 0;
        $reasons = [];

        if ($job->role_id && $candidate->role_id && (int) $job->role_id === (int) $candidate->role_id) {
            $score += 40;
            $reasons[] = 'Role matches';
        }

        if ($job->country && $candidate->country && mb_strtolower($job->country) === mb_strtolower($candidate->country)) {
            $score += 20;
            $reasons[] = 'Same country';
        }

        if (! $job->gender || $job->gender === 'any' || (! $candidate->gender) || mb_strtolower($job->gender) === mb_strtolower((string) $candidate->gender)) {
            $score += 10;
        }

        if ($candidate->expected_salary && ($job->min_salary || $job->max_salary)) {
            $min = (float) ($job->min_salary ?: 0);
            $max = (float) ($job->max_salary ?: PHP_INT_MAX);
            if ($candidate->expected_salary >= $min && $candidate->expected_salary <= $max) {
                $score += 15;
                $reasons[] = 'Salary expectation within range';
            }
        }

        if ($candidate->age && ($job->min_age || $job->max_age)) {
            $minAge = (int) ($job->min_age ?: 0);
            $maxAge = (int) ($job->max_age ?: 100);
            if ($candidate->age >= $minAge && $candidate->age <= $maxAge) {
                $score += 15;
                $reasons[] = 'Age within range';
            }
        }

        return [$score, $reasons];
    }
}
