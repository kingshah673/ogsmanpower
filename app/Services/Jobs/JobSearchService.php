<?php

namespace App\Services\Jobs;

use App\Models\Job;

class JobSearchService
{
    /*
    |--------------------------------------------------------------------------
    | Search Jobs
    |--------------------------------------------------------------------------
    */

    public function search(
        $keyword
    ) {

        return Job::query()

            ->where(
                'status',
                1
            )

            ->where(function ($q)
            use ($keyword) {

                $q->where(
                    'title',
                    'LIKE',
                    "%{$keyword}%"
                )

                ->orWhere(
                    'description',
                    'LIKE',
                    "%{$keyword}%"
                );
            })

            ->latest()

            ->take(5)

            ->get();
    }
}