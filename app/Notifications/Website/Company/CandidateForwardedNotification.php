<?php

namespace App\Notifications\Website\Company;

use App\Models\AppliedJob;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CandidateForwardedNotification extends Notification
{
    use Queueable;

    public function __construct(public AppliedJob $application)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $candidateName = trim(($this->application->candidate?->first_name ?? '') . ' ' . ($this->application->candidate?->last_name ?? ''))
            ?: ($this->application->candidate?->user?->name ?? 'A candidate');

        $agencyName = $this->application->agency?->user?->name ?? $this->application->agency?->company_name ?? 'A recruitment agency';

        return [
            'title' => "{$agencyName} forwarded {$candidateName} for \"{$this->application->job?->title}\".",
            'url' => route('company.job.application', ['job' => $this->application->job_id]),
        ];
    }
}
