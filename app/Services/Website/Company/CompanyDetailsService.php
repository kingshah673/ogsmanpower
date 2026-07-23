<?php

namespace App\Services\Website\Company;

use App\Models\Company;
use App\Models\Job;
use Carbon\Carbon;

class CompanyDetailsService
{
    /**
     * Get company details
     */
    public function execute($user): array
    {
        $companyDetails = Company::with('organization', 'industry', 'team_size:id')
            ->where('user_id', $user->id)
            ->withCount([
                'jobs as activejobs' => function ($q) {
                    $q->where('status', 'active');
                    $q->where('deadline', '>=', Carbon::now()->toDateString());
                },
            ])
            ->withCount([
                'bookmarkCandidateCompany as candidatemarked' => function ($q) {
                    $q->where('user_id', auth()->id());
                },
            ])
            ->withCasts(['candidatemarked' => 'boolean'])
            ->first();

        // Show all open jobs for this employer — do not apply the visitor's country picker filter.
        $open_jobs = applyCandidateAgeFilter(
            Job::withoutEdited()
                ->with('company', 'job_type')
                ->companyJobs($companyDetails->id)
                ->openPosition()
        )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return [
            'user' => $user,
            'companyDetails' => $companyDetails,
            'open_jobs' => $open_jobs,
        ];
    }
}
