<?php

namespace App\Notifications\Website;

use App\Models\ProtectorRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ProtectorClearanceUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(public ProtectorRecord $record)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $candidate = $this->record->candidate;
        $name = trim(($candidate->first_name ?? '').' '.($candidate->last_name ?? '')) ?: 'The candidate';

        $title = match ($this->record->clearance_status) {
            'cleared' => "Protector clearance approved for {$name}.",
            'rejected' => "Protector clearance rejected for {$name}: ".($this->record->rejection_reason ?: 'see details.'),
            default => "Protector clearance status updated for {$name}.",
        };

        return [
            'title' => $title,
            'url' => route('agency.protector.index'),
        ];
    }
}
