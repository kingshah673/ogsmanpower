<?php

namespace App\Services\Website\Agency;

use App\Models\Agency;
use App\Models\Job;
use Carbon\Carbon;

class AgencyDetailsService
{
    /**
     * Get agency details
     */
    public function execute($user): array
    {
        $agencyDetails = Agency::with('organization', 'industry', 'team_size:id')
            ->where('user_id', $user->id)
            ->withCount([
                'jobs as activejobs' => function ($q) {
                    $q->where('status', 'active');
                    $q->where('deadline', '>=', Carbon::now()->toDateString());
                },
            ])
            ->withCount([
                'bookmarkCandidateAgency as candidatemarked' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
            ])
            ->withCasts(['candidatemarked' => 'boolean'])
            ->first();

        $open_jobs = applyCandidateAgeFilter(
            Job::withoutEdited()
                ->with('agency', 'job_type')
                ->agencyJobs($agencyDetails->id)
                ->openPosition()
        )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return [
            'user' => $user,
            'agencyDetails' => $agencyDetails,
            'open_jobs' => $open_jobs,
        ];
    }
}
