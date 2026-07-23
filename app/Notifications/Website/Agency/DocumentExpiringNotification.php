<?php

namespace App\Notifications\Website\Agency;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(public Candidate $candidate, public string $docLabel, public string $expiryDate, public bool $expired)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $name = trim(($this->candidate->first_name ?? '').' '.($this->candidate->last_name ?? ''))
            ?: ($this->candidate->user?->name ?? 'A candidate');

        $title = $this->expired
            ? "{$this->docLabel} for {$name} expired on {$this->expiryDate}."
            : "{$this->docLabel} for {$name} expires on {$this->expiryDate} — please renew soon.";

        return [
            'title' => $title,
            'url' => route('agency.candidates.documents', $this->candidate->id),
        ];
    }
}
