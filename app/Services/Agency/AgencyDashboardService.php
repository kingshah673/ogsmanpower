<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\AppliedJob;
use App\Models\Commission;
use App\Models\ProtectorRecord;
use App\Models\VpCase;
use Illuminate\Support\Carbon;

class AgencyDashboardService
{
    public function build(Agency $agency): array
    {
        $agencyId = $agency->id;
        $data = [];

        $data['openJobCount'] = $agency->jobs()->active()->count();
        $data['pendingJobCount'] = $agency->jobs()->pending()->count();

        $data['recentJobs'] = $agency->jobs()
            ->latest()
            ->take(4)
            ->with('agency.user', 'job_type')
            ->withCount('appliedJobs')
            ->get();

        $data['savedCandidates'] = $agency->bookmarkCandidates()->count();

        $data['applicants'] = AppliedJob::where('agency_id', $agencyId)->count();

        // Daily applications (last 30 days) for the chart
        $dailyApplications = AppliedJob::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('agency_id', $agencyId)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $data['chartDates'] = $dailyApplications->pluck('date')->toArray();
        $data['chartCounts'] = $dailyApplications->pluck('count')->toArray();

        $countryData = AppliedJob::with('candidate')
            ->selectRaw('candidates.country, COUNT(*) as count')
            ->join('candidates', 'applied_jobs.candidate_id', '=', 'candidates.id')
            ->whereNotNull('applied_jobs.candidate_id')
            ->where('applied_jobs.agency_id', $agencyId)
            ->groupBy('candidates.country')
            ->orderByDesc('count')
            ->get();

        $data['countryNames'] = $countryData->pluck('country')->toArray();
        $data['countryApplications'] = $countryData->pluck('count')->toArray();

        $genderData = AppliedJob::with('candidate')
            ->selectRaw('candidates.gender, COUNT(*) as count')
            ->join('candidates', 'applied_jobs.candidate_id', '=', 'candidates.id')
            ->whereNotNull('applied_jobs.candidate_id')
            ->where('applied_jobs.agency_id', $agencyId)
            ->groupBy('candidates.gender')
            ->get();

        $data['genderLabels'] = $genderData->pluck('gender')->toArray();
        $data['genderCounts'] = $genderData->pluck('count')->toArray();

        // Real month-over-month KPI trends (replaces hardcoded % in the view)
        $data['trends'] = $this->buildTrends($agencyId);

        $data['commissionTotals'] = [
            'pending' => (float) Commission::where('agency_id', $agencyId)->where('status', Commission::STATUS_PENDING)->sum('amount'),
            'approved' => (float) Commission::where('agency_id', $agencyId)->where('status', Commission::STATUS_APPROVED)->sum('amount'),
            'paid' => (float) Commission::where('agency_id', $agencyId)->where('status', Commission::STATUS_PAID)->sum('amount'),
        ];

        $data['visaCaseCounts'] = [
            'in_progress' => VpCase::where('agency_id', $agencyId)->where('status', 'in_progress')->count(),
            'completed' => VpCase::where('agency_id', $agencyId)->where('status', 'completed')->count(),
            'deployed' => VpCase::where('agency_id', $agencyId)->whereNotNull('deployed_at')->count(),
        ];

        $data['protectorPending'] = ProtectorRecord::where('agency_id', $agencyId)
            ->where('clearance_status', 'pending')
            ->count();

        return $data;
    }

    /**
     * Compare this-month vs last-month counts for the headline KPIs so the
     * dashboard shows a real growth/decline percentage instead of a fake one.
     */
    protected function buildTrends(int $agencyId): array
    {
        $startOfThisMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $applicantsThisMonth = AppliedJob::where('agency_id', $agencyId)
            ->where('created_at', '>=', $startOfThisMonth)
            ->count();
        $applicantsLastMonth = AppliedJob::where('agency_id', $agencyId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $selectedThisMonth = AppliedJob::where('agency_id', $agencyId)
            ->where('status', 'selected')
            ->where('created_at', '>=', $startOfThisMonth)
            ->count();
        $selectedLastMonth = AppliedJob::where('agency_id', $agencyId)
            ->where('status', 'selected')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        $commissionThisMonth = (float) Commission::where('agency_id', $agencyId)
            ->where('created_at', '>=', $startOfThisMonth)
            ->sum('amount');
        $commissionLastMonth = (float) Commission::where('agency_id', $agencyId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('amount');

        return [
            'applicants' => $this->percentChange($applicantsLastMonth, $applicantsThisMonth),
            'selected' => $this->percentChange($selectedLastMonth, $selectedThisMonth),
            'commission' => $this->percentChange($commissionLastMonth, $commissionThisMonth),
        ];
    }

    protected function percentChange(float $previous, float $current): array
    {
        if ($previous <= 0) {
            $percent = $current > 0 ? 100.0 : 0.0;
        } else {
            $percent = round((($current - $previous) / $previous) * 100, 1);
        }

        return [
            'previous' => $previous,
            'current' => $current,
            'percent' => $percent,
            'direction' => $percent >= 0 ? 'up' : 'down',
        ];
    }
}
