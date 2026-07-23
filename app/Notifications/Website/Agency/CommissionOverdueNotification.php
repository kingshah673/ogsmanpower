<?php

namespace App\Notifications\Website\Agency;

use App\Models\Commission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommissionOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(public Commission $commission, public int $daysPending)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $amount = $this->commission->currency.' '.number_format($this->commission->amount, 2);

        return [
            'title' => "Commission #{$this->commission->id} ({$amount}) has been pending for {$this->daysPending} days.",
            'url' => route('agency.commissions.index'),
        ];
    }
}
