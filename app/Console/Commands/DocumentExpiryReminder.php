<?php

namespace App\Console\Commands;

use App\Models\Agency;
use App\Models\Candidate;
use App\Models\CandidateDocument;
use App\Notifications\Website\Agency\DocumentExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class DocumentExpiryReminder extends Command
{
    protected $signature = 'documents:expiry-reminder {--days=30 : Notify when a document expires within this many days}';

    protected $description = 'Notify agencies about candidate documents (passport / medical / police certificate) expiring soon or already expired.';

    public function handle(): void
    {
        $windowDays = (int) $this->option('days');
        $horizon = now()->addDays($windowDays);
        $notified = 0;

        $candidates = Candidate::query()
            ->whereNotNull('agency_id')
            ->whereNotNull('passport_expiry_date')
            ->where('passport_expiry_date', '<=', $horizon)
            ->get(['id', 'agency_id', 'first_name', 'last_name', 'passport_expiry_date']);

        foreach ($candidates as $candidate) {
            $notified += $this->notifyAgency(
                $candidate,
                'Passport',
                Carbon::parse($candidate->passport_expiry_date)
            );
        }

        $documents = CandidateDocument::query()
            ->whereNotNull('candidate_id')
            ->where(function ($q) use ($horizon) {
                $q->where('medical_expiry_date', '<=', $horizon)
                    ->orWhere('police_certificate_expiry_date', '<=', $horizon);
            })
            ->with('candidate:id,agency_id,first_name,last_name')
            ->get();

        foreach ($documents as $document) {
            $candidate = $document->candidate;
            if (! $candidate || ! $candidate->agency_id) {
                continue;
            }

            if ($document->medical_expiry_date && $document->medical_expiry_date->lte($horizon)) {
                $notified += $this->notifyAgency($candidate, 'Medical certificate', $document->medical_expiry_date);
            }

            if ($document->police_certificate_expiry_date && $document->police_certificate_expiry_date->lte($horizon)) {
                $notified += $this->notifyAgency($candidate, 'Police character certificate', $document->police_certificate_expiry_date);
            }
        }

        $this->info("Document expiry reminders sent: {$notified}.");
    }

    protected function notifyAgency(Candidate $candidate, string $docLabel, Carbon $expiry): int
    {
        $agency = Agency::with('user')->find($candidate->agency_id);
        if (! $agency || ! $agency->user) {
            return 0;
        }

        Notification::send($agency->user, new DocumentExpiringNotification(
            $candidate,
            $docLabel,
            $expiry->format('d M Y'),
            $expiry->isPast()
        ));

        return 1;
    }
}
