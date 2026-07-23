<?php

namespace App\Services\Jobs;

use App\Models\AppliedJob;
use App\Models\Candidate;
use App\Models\Job;
use App\Services\Notifications\NotificationService;

class JobApplyService
{
    protected $notification;

    public function __construct(
        NotificationService $notification
    ) {

        $this->notification = $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply Candidate To Job
    |--------------------------------------------------------------------------
    */

    public function apply(
        $jobId,
        $candidateId
    ) {

        /*
        |--------------------------------------------------------------------------
        | FIND JOB
        |--------------------------------------------------------------------------
        */

        $job =
            Job::find($jobId);

        if (!$job) {

            return [

                'success' => false,

                'message'
                    => 'Job not found.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FIND CANDIDATE
        |--------------------------------------------------------------------------
        */

        $candidate =
            Candidate::with('user')
                ->find($candidateId);

        if (!$candidate) {

            return [

                'success' => false,

                'message'
                    => 'Candidate not found.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING APPLICATION
        |--------------------------------------------------------------------------
        */

        $exists =
            AppliedJob::where(
                'job_id',
                $jobId
            )
            ->where(
                'candidate_id',
                $candidateId
            )
            ->exists();

        if ($exists) {

            return [

                'success' => false,

                'message'
                    => 'You already applied for this job.'
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE APPLICATION
        |--------------------------------------------------------------------------
        */

        $application =
            AppliedJob::create([

                'job_id'
                    => $jobId,

                'candidate_id'
                    => $candidateId,

                'company_id'
                    => $job->company_id,

                'agency_id'
                    => $job->agency_id,

                'status'
                    => 'pending'
            ]);

        /*
        |--------------------------------------------------------------------------
        | SEND CANDIDATE NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if ($candidate->user) {

            $this->notification
                ->send(

                    $candidate->user,

                    "Your application for '{$job->title}' has been submitted successfully.",

                    'Job Application Submitted'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | SEND COMPANY NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (

            isset($job->company)
            &&
            isset($job->company->user)

        ) {

            $this->notification
                ->send(

                    $job->company->user,

                    "A new candidate applied for '{$job->title}'.",

                    'New Job Application'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN SUCCESS
        |--------------------------------------------------------------------------
        */

        return [

            'success' => true,

            'application'
                => $application,

            'message'
                => 'Application submitted successfully.'
        ];
    }
}