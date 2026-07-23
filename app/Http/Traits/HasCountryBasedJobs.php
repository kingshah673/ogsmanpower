<?php

namespace App\Http\Traits;

trait HasCountryBasedJobs
{
    public function filterCountryBasedJobs($jobs)
    {
        return applyJobCountryScope($jobs);
    }
}
