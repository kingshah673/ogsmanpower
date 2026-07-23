<?php

namespace App\Console\Commands;

use App\Models\Commission;
use App\Notifications\Website\Agency\CommissionOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CommissionOverdueReminder extends Command
{
    protected $signature = 'commissions:overdue-reminder {--days=30 : Flag commissions pending/approved for longer than this many days}';

    protected $description = 'Notify agencies about commissions that have been pending or approved (i.e. not yet paid) for too long.';

    public function handle(): void
    {
        $thresholdDays = (int) $this->option('days');
        $cutoff = now()->subDays($thresholdDays);

        $commissions = Commission::query()
            ->whereIn('status', [Commission::STATUS_PENDING, Commission::STATUS_APPROVED])
            ->whereNotNull('agency_id')
            ->where('created_at', '<=', $cutoff)
            ->with('agency.user')
            ->get();

        $notified = 0;

        foreach ($commissions as $commission) {
            if (! $commission->agency || ! $commission->agency->user) {
                continue;
            }

            $daysPending = (int) $commission->created_at->diffInDays(now());

            Notification::send($commission->agency->user, new CommissionOverdueNotification($commission, $daysPending));
            $notified++;
        }

        $this->info("Commission overdue reminders sent: {$notified}.");
    }
}
