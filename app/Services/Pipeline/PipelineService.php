<?php

namespace App\Services\Pipeline;

use App\Models\AppliedJob;
use App\Services\Notifications\NotificationService;

class PipelineService
{
    protected $notification;

    public function __construct(
        NotificationService $notification
    ) {

        $this->notification = $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE PIPELINE STATUS
    |--------------------------------------------------------------------------
    */

    public function update(

        AppliedJob $application,

        $status,

        $remarks = null

    ) {

        /*
        |--------------------------------------------------------------------------
        | UPDATE STATUS
        |--------------------------------------------------------------------------
        */

        $application->update([

            'status'
                => $status,

            'remarks'
                => $remarks
        ]);

        /*
        |--------------------------------------------------------------------------
        | CANDIDATE
        |--------------------------------------------------------------------------
        */

        $candidate =
            $application->candidate;

        /*
        |--------------------------------------------------------------------------
        | JOB
        |--------------------------------------------------------------------------
        */

        $job =
            $application->job;

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (

            $candidate
            &&
            $candidate->user

        ) {

            $message =

                "📌 Application Status Updated\n\n"

                .

                "Job: "
                .
                ($job->title ?? 'Job')

                .

                "\n"

                .

                "New Status: "
                .
                ucfirst($status);

            if ($remarks) {

                $message .=

                    "\n"

                    .

                    "Remarks: "
                    .
                    $remarks;
            }

            $this->notification
                ->send(

                    $candidate->user,

                    $message,

                    'Application Status Updated'
                );
        }

        return $application;
    }

    /*
    |--------------------------------------------------------------------------
    | SHORTLIST
    |--------------------------------------------------------------------------
    */

    public function shortlist(
        AppliedJob $application
    ) {

        return $this->update(

            $application,

            'shortlisted'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INTERVIEW
    |--------------------------------------------------------------------------
    */

    public function interview(
        AppliedJob $application
    ) {

        return $this->update(

            $application,

            'interview'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MEDICAL
    |--------------------------------------------------------------------------
    */

    public function medical(
        AppliedJob $application
    ) {

        return $this->update(

            $application,

            'medical'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VISA PROCESS
    |--------------------------------------------------------------------------
    */

    public function visa(
        AppliedJob $application
    ) {

        return $this->update(

            $application,

            'visa_process'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DEPLOYED
    |--------------------------------------------------------------------------
    */

    public function deployed(
        AppliedJob $application
    ) {

        return $this->update(

            $application,

            'deployed'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT
    |--------------------------------------------------------------------------
    */

    public function reject(

        AppliedJob $application,

        $remarks = null

    ) {

        return $this->update(

            $application,

            'rejected',

            $remarks
        );
    }
}