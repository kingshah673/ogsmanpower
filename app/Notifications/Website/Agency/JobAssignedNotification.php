<?php

namespace App\Notifications\Website\Agency;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JobAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public Job $job)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $companyName = $this->job->company?->user?->name ?? $this->job->company?->name ?? 'An employer';

        return [
            'title' => "{$companyName} assigned you the job \"{$this->job->title}\" — please accept or decline.",
            'url' => route('agency.available.jobs'),
        ];
    }
}
