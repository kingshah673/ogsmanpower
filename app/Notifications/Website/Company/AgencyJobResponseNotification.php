<?php

namespace App\Notifications\Website\Company;

use App\Models\Agency;
use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AgencyJobResponseNotification extends Notification
{
    use Queueable;

    public function __construct(public Job $job, public Agency $agency, public string $status, public ?string $reason = null)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $agencyName = $this->agency->user?->name ?? $this->agency->company_name ?? 'An agency';

        $title = $this->status === 'accepted'
            ? "{$agencyName} accepted your job \"{$this->job->title}\"."
            : "{$agencyName} declined your job \"{$this->job->title}\"" . ($this->reason ? ": {$this->reason}" : '.');

        return [
            'title' => $title,
            'url' => route('company.myjob'),
        ];
    }
}
