<?php

namespace App\Services\Interview;

use App\Models\InterviewSchedule;
use App\Services\Notifications\NotificationService;

class InterviewSchedulingService
{
    protected $notification;

    public function __construct(
        NotificationService $notification
    ) {

        $this->notification = $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | Schedule Interview
    |--------------------------------------------------------------------------
    */

    public function schedule(

        $candidate,

        $job,

        $company,

        $dateTime,

        $platform = 'Google Meet'

    ) {

        /*
        |--------------------------------------------------------------------------
        | GENERATE MEETING LINK
        |--------------------------------------------------------------------------
        */

        $meetingLink =

            'https://meet.google.com/'
            .
            strtolower(
                \Str::random(3)
            )
            .
            '-'
            .
            strtolower(
                \Str::random(4)
            )
            .
            '-'
            .
            strtolower(
                \Str::random(3)
            );

        /*
        |--------------------------------------------------------------------------
        | CREATE INTERVIEW
        |--------------------------------------------------------------------------
        */

        $interview =
            InterviewSchedule::create([

                'candidate_id'
                    => $candidate->id,

                'job_id'
                    => $job->id
                    ?? null,

                'company_id'
                    => $company->id
                    ?? null,

                'created_by'
                    => auth()->id(),

                'interview_at'
                    => $dateTime,

                'meeting_link'
                    => $meetingLink,

                'platform'
                    => $platform,

                'status'
                    => 'scheduled'
            ]);

        /*
        |--------------------------------------------------------------------------
        | SEND CANDIDATE NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (
            isset($candidate->user)
        ) {

            $message =

                "🎤 Interview Scheduled\n\n"

                .

                "Job: "
                .
                ($job->title ?? 'Job')

                .

                "\n"

                .

                "Date: "
                .
                date(
                    'd M Y h:i A',
                    strtotime($dateTime)
                )

                .

                "\n"

                .

                "Platform: "
                .
                $platform

                .

                "\n"

                .

                "Meeting Link:\n"
                .
                $meetingLink;

            $this->notification
                ->send(

                    $candidate->user,

                    $message,

                    'Interview Scheduled'
                );
        }

        return $interview;
    }
}