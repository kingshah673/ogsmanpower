<?php

namespace App\Services\Agency;

use App\Models\Agency;
use App\Models\AppliedJob;
use App\Models\Commission;
use App\Models\ProtectorRecord;
use App\Models\VpCase;

class AgencyReportService
{
    public const TYPES = [
        'recruitment-status' => 'Recruitment Status',
        'job-posting' => 'Job Posting Performance',
        'applicant-tracking' => 'Applicant Tracking (Funnel)',
        'visa-medical' => 'Visa & Medical Processing',
        'payment-commission' => 'Payment & Commission',
    ];

    public function build(Agency $agency, string $type): array
    {
        return match ($type) {
            'recruitment-status' => $this->recruitmentStatus($agency),
            'job-posting' => $this->jobPosting($agency),
            'applicant-tracking' => $this->applicantTracking($agency),
            'visa-medical' => $this->visaMedical($agency),
            'payment-commission' => $this->paymentCommission($agency),
            default => ['title' => 'Unknown report', 'headings' => [], 'rows' => [], 'summary' => []],
        };
    }

    protected function recruitmentStatus(Agency $agency): array
    {
        $rows = AppliedJob::where('agency_id', $agency->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $total = $rows->sum('total');

        return [
            'title' => self::TYPES['recruitment-status'],
            'headings' => ['Status', 'Total', 'Share'],
            'rows' => $rows->map(fn ($r) => [
                ucfirst($r->status ?: 'unset'),
                $r->total,
                $total > 0 ? round($r->total / $total * 100, 1).'%' : '0%',
            ])->toArray(),
            'summary' => ['Total applications' => $total],
        ];
    }

    protected function jobPosting(Agency $agency): array
    {
        $jobs = $agency->jobs()
            ->withCount('appliedJobs')
            ->latest()
            ->get(['id', 'title', 'status', 'total_views', 'created_at', 'deadline']);

        return [
            'title' => self::TYPES['job-posting'],
            'headings' => ['Job Title', 'Status', 'Views', 'Applicants', 'Posted', 'Deadline'],
            'rows' => $jobs->map(fn ($j) => [
                $j->title,
                ucfirst($j->status),
                $j->total_views ?? 0,
                $j->applied_jobs_count,
                optional($j->created_at)->format('Y-m-d'),
                $j->deadline ? \Illuminate\Support\Carbon::parse($j->deadline)->format('Y-m-d') : 'N/A',
            ])->toArray(),
            'summary' => [
                'Total jobs' => $jobs->count(),
                'Active jobs' => $jobs->where('status', 'active')->count(),
                'Total applicants across jobs' => $jobs->sum('applied_jobs_count'),
            ],
        ];
    }

    protected function applicantTracking(Agency $agency): array
    {
        $funnel = [
            'applied' => AppliedJob::where('agency_id', $agency->id)->count(),
            'shortlisted' => AppliedJob::where('agency_id', $agency->id)->where('status', 'shortlisted')->count(),
            'interview' => AppliedJob::where('agency_id', $agency->id)->where('status', 'interview')->count(),
            'selected' => AppliedJob::where('agency_id', $agency->id)->where('status', 'selected')->count(),
            'forwarded' => AppliedJob::where('agency_id', $agency->id)->where('status', 'forwarded')->count(),
            'rejected' => AppliedJob::where('agency_id', $agency->id)->where('status', 'rejected')->count(),
        ];

        $rows = [];
        foreach ($funnel as $stage => $count) {
            $rows[] = [ucfirst($stage), $count];
        }

        return [
            'title' => self::TYPES['applicant-tracking'],
            'headings' => ['Stage', 'Count'],
            'rows' => $rows,
            'summary' => [
                'Conversion (selected / applied)' => $funnel['applied'] > 0
                    ? round($funnel['selected'] / $funnel['applied'] * 100, 1).'%'
                    : '0%',
            ],
        ];
    }

    protected function visaMedical(Agency $agency): array
    {
        $cases = VpCase::where('agency_id', $agency->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get();

        $protector = ProtectorRecord::where('agency_id', $agency->id)
            ->selectRaw('clearance_status, COUNT(*) as total')
            ->groupBy('clearance_status')
            ->get();

        $rows = [];
        foreach ($cases as $c) {
            $rows[] = ['Visa case: '.ucfirst(str_replace('_', ' ', $c->status)), $c->total];
        }
        foreach ($protector as $p) {
            $rows[] = ['Protector clearance: '.ucfirst($p->clearance_status), $p->total];
        }

        return [
            'title' => self::TYPES['visa-medical'],
            'headings' => ['Category', 'Total'],
            'rows' => $rows,
            'summary' => [
                'Deployed workers' => VpCase::where('agency_id', $agency->id)->whereNotNull('deployed_at')->count(),
            ],
        ];
    }

    protected function paymentCommission(Agency $agency): array
    {
        $rows = Commission::where('agency_id', $agency->id)
            ->with(['candidate.user', 'job'])
            ->latest()
            ->get();

        return [
            'title' => self::TYPES['payment-commission'],
            'headings' => ['ID', 'Candidate', 'Job', 'Amount', 'Currency', 'Status', 'Created'],
            'rows' => $rows->map(fn ($c) => [
                $c->id,
                optional($c->candidate?->user)->name ?? 'N/A',
                $c->job->title ?? 'N/A',
                $c->amount,
                $c->currency,
                ucfirst($c->status),
                optional($c->created_at)->format('Y-m-d'),
            ])->toArray(),
            'summary' => [
                'Total pending' => (float) Commission::where('agency_id', $agency->id)->where('status', 'pending')->sum('amount'),
                'Total approved' => (float) Commission::where('agency_id', $agency->id)->where('status', 'approved')->sum('amount'),
                'Total paid' => (float) Commission::where('agency_id', $agency->id)->where('status', 'paid')->sum('amount'),
            ],
        ];
    }
}
