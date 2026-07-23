<?php

namespace App\Notifications\Website\Agency;

use App\Models\AppliedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CandidateStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public AppliedJob $application, public string $status)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $candidateName = trim(($this->application->candidate?->first_name ?? '') . ' ' . ($this->application->candidate?->last_name ?? ''))
            ?: ($this->application->candidate?->user?->name ?? 'Your candidate');

        $jobTitle = $this->application->job?->title ?? 'a job';
        $companyName = $this->application->job?->company?->user?->name ?? $this->application->job?->company?->name ?? 'the employer';

        return [
            'title' => "{$candidateName} is now \"{$this->status}\" for \"{$jobTitle}\" with {$companyName}.",
            'url' => route('agency.applications'),
        ];
    }
}
