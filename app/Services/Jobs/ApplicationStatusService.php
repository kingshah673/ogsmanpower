<?php

namespace App\Services\Jobs;

use App\Models\AppliedJob;
use App\Notifications\Website\Agency\CandidateStatusUpdatedNotification;
use App\Notifications\Website\Candidate\ApplicationStatusNotification;
use App\Notifications\Website\Candidate\InterviewUpdateNotification;
use App\Notifications\Website\Candidate\ShortlistedJobNotification;
use App\Services\Candidates\CandidatePublicCodeService;
use Illuminate\Support\Facades\Notification;

class ApplicationStatusService
{
    public function get($candidateId)
    {
        return AppliedJob::query()
            ->with('job')
            ->where('candidate_id', $candidateId)
            ->latest()
            ->take(10)
            ->get();
    }

    /**
     * Persist application status and notify the seeker on key transitions.
     *
     * @param  array{interview_date?: mixed, interview_location?: mixed, interview_outcome?: mixed}  $extra
     */
    public function updateStatus(AppliedJob $application, string $status, array $extra = []): AppliedJob
    {
        $previous = $application->status;
        $application->status = $status;

        if (array_key_exists('interview_date', $extra)) {
            $application->interview_date = $extra['interview_date'];
        }
        if (array_key_exists('interview_location', $extra)) {
            $application->interview_location = $extra['interview_location'];
        }
        if (array_key_exists('interview_outcome', $extra)) {
            $application->interview_outcome = $extra['interview_outcome'];
        }

        if ($status === 'interview' && empty($application->interview_outcome)) {
            $application->interview_outcome = 'scheduled';
        }

        $application->save();

        if ($status === 'shortlisted' && $previous !== 'shortlisted') {
            $this->notifyShortlisted($application);
        }

        if ($status === 'interview' && $previous !== 'interview') {
            $this->notifyInterview($application, 'invite');
        }

        if ($status === 'selected' && $previous !== 'selected') {
            $this->notifySelectedVisaComingSoon($application);
            $this->syncPublicCode($application);
            $this->accrueCommission($application);
        }

        if ($status !== $previous) {
            if (! in_array($status, ['shortlisted', 'interview', 'selected'], true)) {
                $this->notifyGenericStatusChange($application, $status);
            }
            $this->notifyAgencyOfStatusChange($application, $status);
        }

        return $application;
    }

    /**
     * Candidate-facing fallback notice for statuses without a dedicated
     * notification (e.g. rejected, forwarded).
     */
    public function notifyGenericStatusChange(AppliedJob $application, string $status): void
    {
        $application->loadMissing(['candidate.user', 'job.company.user']);

        $user = $application->candidate?->user;
        if (! $user) {
            return;
        }

        Notification::send($user, new ApplicationStatusNotification($application, $status));
    }

    /**
     * Notify the sourcing agency (if any) when an employer changes the
     * status of a candidate the agency forwarded — skip when the agency
     * itself made the change (self-notification).
     */
    public function notifyAgencyOfStatusChange(AppliedJob $application, string $status): void
    {
        if (! $application->agency_id) {
            return;
        }

        $actingAgencyId = auth('user')->user()?->agency?->id ?? auth()->user()?->agency?->id;
        if ($actingAgencyId && (int) $actingAgencyId === (int) $application->agency_id) {
            return;
        }

        $application->loadMissing(['candidate.user', 'job.company.user', 'agency.user']);

        $user = $application->agency?->user;
        if (! $user) {
            return;
        }

        Notification::send($user, new CandidateStatusUpdatedNotification($application, $status));
    }

    /**
     * Interview-module actions: accept, reject, reschedule, complete.
     */
    public function updateInterview(AppliedJob $application, string $action, array $extra = []): AppliedJob
    {
        $action = strtolower($action);

        return match ($action) {
            'invite' => $this->updateStatus($application, 'interview', [
                'interview_date' => $extra['interview_date'] ?? $application->interview_date,
                'interview_location' => $extra['interview_location'] ?? $application->interview_location,
                'interview_outcome' => 'scheduled',
            ]),
            'reschedule' => tap($application, function (AppliedJob $app) use ($extra) {
                $app->status = 'interview';
                $app->interview_outcome = 'rescheduled';
                if (! empty($extra['interview_date'])) {
                    $app->interview_date = $extra['interview_date'];
                }
                if (array_key_exists('interview_location', $extra)) {
                    $app->interview_location = $extra['interview_location'];
                }
                $app->save();
                $this->notifyInterview($app, 'reschedule');
            }),
            'accept' => tap($application, function (AppliedJob $app) {
                $app->status = 'selected';
                $app->interview_outcome = 'completed';
                $app->save();
                $this->notifyInterview($app, 'accept');
                $this->notifySelectedVisaComingSoon($app);
                $this->syncCandidatePublicCode($app);
                $this->accrueCommission($app);
            }),
            'complete', 'completed' => tap($application, function (AppliedJob $app) {
                $app->status = 'interview';
                $app->interview_outcome = 'completed';
                $app->save();
                $this->notifyInterview($app, 'completed');
            }),
            'reject' => tap($application, function (AppliedJob $app) {
                $app->status = 'rejected';
                $app->interview_outcome = 'rejected';
                $app->save();
                $this->notifyInterview($app, 'reject');
            }),
            default => throw new \InvalidArgumentException('Unknown interview action: '.$action),
        };
    }

    public function notifyShortlisted(AppliedJob $application): void
    {
        $application->loadMissing(['candidate.user', 'job.company.user']);

        $user = $application->candidate?->user;
        if (! $user || ! $user->email) {
            return;
        }

        $companyName = $application->job?->company?->user?->name
            ?? $application->job?->company?->name
            ?? 'the employer';

        Notification::send(
            $user,
            new ShortlistedJobNotification($user, $companyName, $application->job)
        );
    }

    public function notifyInterview(AppliedJob $application, string $action): void
    {
        $application->loadMissing(['candidate.user', 'job.company.user']);

        $user = $application->candidate?->user;
        if (! $user || ! $user->email) {
            return;
        }

        $companyName = $application->job?->company?->user?->name
            ?? $application->job?->company?->name
            ?? 'the employer';

        $date = $application->interview_date
            ? (is_object($application->interview_date)
                ? $application->interview_date->format('M d, Y')
                : (string) $application->interview_date)
            : null;

        Notification::send(
            $user,
            new InterviewUpdateNotification(
                $user,
                $companyName,
                $application->job,
                $action,
                $date,
                $application->interview_location
            )
        );
    }

    public function notifySelectedVisaComingSoon(AppliedJob $application): void
    {
        $application->loadMissing(['candidate.user', 'job.company.user']);
        $user = $application->candidate?->user;
        if (! $user) {
            return;
        }

        $companyName = $application->job?->company?->user?->name
            ?? $application->job?->company?->name
            ?? 'the employer';

        app(\App\Services\VisaProcessing\VisaProcessingNotifier::class)
            ->notifySelectedComingSoon($user, $application->job, $companyName);
    }

    protected function syncPublicCode(AppliedJob $application): void
    {
        $application->loadMissing(['candidate.user', 'job']);
        if (! $application->candidate) {
            return;
        }

        app(CandidatePublicCodeService::class)->sync(
            $application->candidate,
            $application->job
        );
    }

    protected function syncCandidatePublicCode(AppliedJob $application): void
    {
        $this->syncPublicCode($application);
    }

    protected function accrueCommission(AppliedJob $application): void
    {
        app(\App\Services\Agency\CommissionAccrualService::class)->accrueForSelection($application);
    }
}
