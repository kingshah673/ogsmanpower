<?php

namespace App\Services\Website\Candidate;

use App\Http\Resources\Job\JobListResource;
use App\Models\AppliedJob;
use App\Models\Candidate;
use App\Models\Job;

class DashboardService
{
    public function execute($is_api = false)
    {
        $candidate = Candidate::where('user_id', auth()->id())->first();

        if (empty($candidate)) {
            $candidate = new Candidate;
            $candidate->user_id = auth()->id();
            $candidate->save();
        }

        $base = AppliedJob::query()->where('candidate_id', $candidate->id);

        $statusCounts = [
            'all' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'shortlisted' => (clone $base)->where('status', 'shortlisted')->count(),
            'interview' => (clone $base)->where('status', 'interview')->count(),
            'selected' => (clone $base)->where('status', 'selected')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
        ];

        $appliedJobs = $statusCounts['all'];
        $favoriteJobs = $candidate->bookmarkJobs()->count();

        $appliedJobIds = AppliedJob::query()
            ->where('candidate_id', $candidate->id)
            ->pluck('job_id');

        $newJobs = Job::query()
            ->active()
            ->when($appliedJobIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $appliedJobIds))
            ->where('created_at', '>=', now()->subDays(14))
            ->count();

        $jobs = $candidate->appliedJobs()->withCount(['bookmarkJobs as bookmarked' => function ($q) use ($candidate) {
            $q->where('candidate_id', $candidate->id);
        }])
            ->latest()
            ->limit(4)
            ->get(['id', 'company_id', 'title', 'slug', 'role_id', 'job_type_id', 'country', 'salary_mode', 'min_salary', 'max_salary', 'custom_salary', 'deadline_active']);

        $user = auth($is_api ? 'api' : 'user')->user();
        $notifications = $user ? $user->notifications()->count() : 0;
        $unreadNotifications = $user ? $user->unreadNotifications()->count() : 0;

        return [
            'appliedJobs' => $appliedJobs,
            'favoriteJobs' => $favoriteJobs,
            'newJobs' => $newJobs,
            'statusCounts' => $statusCounts,
            'notifications' => $notifications,
            'unreadNotifications' => $unreadNotifications,
            'jobs' => JobListResource::collection($jobs),
            'candidate' => $is_api ? '' : $candidate,
        ];
    }
}
