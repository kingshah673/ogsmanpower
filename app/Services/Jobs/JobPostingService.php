<?php

namespace App\Services\Jobs;

use App\Models\Job;
use App\Services\Notifications\NotificationService;

class JobPostingService
{
    protected $notification;

    public function __construct(
        NotificationService $notification
    ) {

        $this->notification = $notification;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Job
    |--------------------------------------------------------------------------
    */

    public function create(
        $company,
        array $data
    ) {

        /*
        |--------------------------------------------------------------------------
        | CREATE JOB
        |--------------------------------------------------------------------------
        */

        $job = Job::create([

            'company_id'
                => $company->id,

            'title'
                => $data['title']
                ?? null,

            'slug'
                => \Str::slug(
                    $data['title']
                    ?? 'job'
                )
                .
                '-'
                .
                time(),

            'description'
                => $data['description']
                ?? 'Job posted via AI Chatbot.',

            'salary'
                => $data['salary']
                ?? null,

            'location'
                => $data['country']
                ?? null,

            'country'
                => $data['country']
                ?? null,

            'vacancies'
                => $data['quantity']
                ?? 1,

            'deadline'
                => $data['deadline']
                ?? null,

            'status'
                => 1
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEND COMPANY NOTIFICATION
        |--------------------------------------------------------------------------
        */

        if (
            isset($company->user)
        ) {

            $this->notification
                ->send(

                    $company->user,

                    "Your job '{$job->title}' has been posted successfully.",

                    'Job Posted Successfully'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | RETURN JOB
        |--------------------------------------------------------------------------
        */

        return $job;
    }

    /*
    |--------------------------------------------------------------------------
    | Update Job
    |--------------------------------------------------------------------------
    */

    public function update(
        Job $job,
        array $data
    ) {

        $job->update([

            'title'
                => $data['title']
                ?? $job->title,

            'description'
                => $data['description']
                ?? $job->description,

            'salary'
                => $data['salary']
                ?? $job->salary,

            'location'
                => $data['country']
                ?? $job->location,

            'country'
                => $data['country']
                ?? $job->country,

            'vacancies'
                => $data['quantity']
                ?? $job->vacancies,

            'deadline'
                => $data['deadline']
                ?? $job->deadline
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
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

                    "Your job '{$job->title}' has been updated successfully.",

                    'Job Updated'
                );
        }

        return $job;
    }

    /*
    |--------------------------------------------------------------------------
    | Close Job
    |--------------------------------------------------------------------------
    */

    public function close(
        Job $job
    ) {

        $job->update([

            'status' => 0
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
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

                    "Your job '{$job->title}' has been closed.",

                    'Job Closed'
                );
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Job
    |--------------------------------------------------------------------------
    */

    public function delete(
        Job $job
    ) {

        /*
        |--------------------------------------------------------------------------
        | SEND NOTIFICATION
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

                    "Your job '{$job->title}' has been deleted.",

                    'Job Deleted'
                );
        }

        $job->delete();

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Company Jobs
    |--------------------------------------------------------------------------
    */

    public function companyJobs(
        $companyId
    ) {

        return Job::query()

            ->where(
                'company_id',
                $companyId
            )

            ->latest()

            ->paginate(20);
    }

    /*
    |--------------------------------------------------------------------------
    | Find Job
    |--------------------------------------------------------------------------
    */

    public function find(
        $jobId
    ) {

        return Job::find($jobId);
    }
}