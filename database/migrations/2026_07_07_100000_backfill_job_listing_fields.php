<?php

use App\Models\Job;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jobs')
            ->where(function ($query) {
                $query->whereNull('job_roles')->orWhere('job_roles', '');
            })
            ->update(['job_roles' => 'public']);

        Job::query()
            ->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })
            ->whereNotNull('company_id')
            ->with('company:id,country')
            ->chunkById(100, function ($jobs) {
                foreach ($jobs as $job) {
                    if (filled($job->company?->country)) {
                        $job->update(['country' => $job->company->country]);
                    }
                }
            });

        Job::query()
            ->where(function ($q) {
                $q->whereNull('country')->orWhere('country', '');
            })
            ->whereNotNull('agency_id')
            ->with('agency:id,country')
            ->chunkById(100, function ($jobs) {
                foreach ($jobs as $job) {
                    if (filled($job->agency?->country)) {
                        $job->update(['country' => $job->agency->country]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Non-reversible data backfill.
    }
};
