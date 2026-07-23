<?php

namespace App\Notifications\Website\Candidate;

use App\Models\AppliedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic candidate-facing status-change notice for statuses that don't
 * already have a dedicated notification (shortlisted/interview/selected do).
 */
class ApplicationStatusNotification extends Notification
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
        $jobTitle = $this->application->job?->title ?? 'a job';
        $companyName = $this->application->job?->company?->user?->name ?? $this->application->job?->company?->name ?? 'the employer';

        $label = match ($this->status) {
            'rejected' => "Your application for \"{$jobTitle}\" with {$companyName} was not successful this time.",
            'forwarded' => "Your profile was forwarded to {$companyName} for \"{$jobTitle}\".",
            default => "Your application status for \"{$jobTitle}\" with {$companyName} is now \"{$this->status}\".",
        };

        return [
            'title' => $label,
            'url' => $this->application->job?->slug ? route('website.job.details', $this->application->job->slug) : url('/'),
        ];
    }
}
