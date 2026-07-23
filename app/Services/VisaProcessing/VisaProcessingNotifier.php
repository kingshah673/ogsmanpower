<?php

namespace App\Services\VisaProcessing;

use App\Models\User;
use App\Models\VpCase;
use App\Models\VpCaseStep;
use App\Notifications\VisaProcessing\VisaProcessingEventNotification;
use App\Support\VisaLiability;
use Illuminate\Support\Facades\Notification;

class VisaProcessingNotifier
{
    public function notifySelectedComingSoon($candidateUser, $job, string $companyName): void
    {
        if (! $candidateUser) {
            return;
        }
        Notification::send($candidateUser, new VisaProcessingEventNotification(
            subject: 'Visa processing is coming soon',
            greeting: 'Dear '.$candidateUser->name,
            lines: [
                "Congratulations — you've been selected for ".($job->title ?? 'a role')." at {$companyName}.",
                'Visa processing will begin once your employer starts the country-specific paperwork. We will notify you when it is your turn.',
            ],
            actionLabel: 'View Applied Jobs',
            actionUrl: route('candidate.appliedjob', ['status' => 'selected']),
            title: 'Selected — visa processing coming soon',
        ));
    }

    public function caseStarted(VpCase $case): void
    {
        $case->loadMissing(['steps', 'candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $first = $case->steps->sortBy('sort_order')->first();
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Visa processing started — '.$case->country_name,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                'Your employer has started visa processing for '.$case->country_name.'.',
                'There are '.$case->steps->count().' steps in total.',
                $first ? 'First step: '.$first->name.$this->firstStepHintForCandidate($first) : null,
                $workerLabel,
            ]),
            actionLabel: 'Open Visa Processing',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Visa case started ('.$case->country_name.')',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Visa processing started — '.$case->country_name,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                'You have started visa processing for '.$case->country_name.'.',
                'There are '.$case->steps->count().' steps in total.',
                $first ? 'First step: '.$first->name.$this->firstStepHintForEmployer($first) : null,
                $workerLabel,
            ]),
            actionLabel: 'Open Visa Processing',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Visa case started ('.$case->country_name.')',
        ));

        if ($first) {
            $this->notifyAgencyForStep($case, $first, 'Visa processing started — '.$case->country_name);
        }
    }

    public function stepProgressed(VpCase $case, VpCaseStep $completedStep, ?VpCaseStep $nextStep, string $completedBySide): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);
        $completedBy = $this->sideLabel($completedBySide);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Visa step completed — '.$completedStep->name,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                'Step completed: '.$completedStep->name,
                'Completed by: '.$completedBy,
                $workerLabel,
                $nextStep ? $this->nextStepLineForCandidate($nextStep) : 'This was the final step — the case will be marked complete shortly.',
            ]),
            actionLabel: 'Open Visa Processing',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Step completed — '.$completedStep->name,
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Visa step completed — '.$completedStep->name,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                'Step completed: '.$completedStep->name,
                'Completed by: '.$completedBy,
                $workerLabel,
                $nextStep ? $this->nextStepLineForEmployer($nextStep) : 'This was the final step — the case will be marked complete shortly.',
            ]),
            actionLabel: 'Open Visa Processing',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Step completed — '.$completedStep->name,
        ));

        if ($nextStep) {
            $this->notifyAgencyForStep($case, $nextStep, 'Visa step update — '.$nextStep->name);
        }
    }

    /** @deprecated Use stepProgressed from advanceOrComplete; kept for any direct callers. */
    public function stepBecameActive(VpCase $case, VpCaseStep $step): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: $this->isSeekerTurn($step) ? 'Your turn: '.$step->name : 'Visa step update: '.$step->name,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                $this->isSeekerTurn($step)
                    ? 'It is your turn on visa step: '.$step->name.'.'
                    : 'The active step is now: '.$step->name.' ('.$this->assigneeLabel($step->assignee).' action required).',
                $step->description ?: null,
                $workerLabel,
            ]),
            actionLabel: $this->isSeekerTurn($step) ? 'Complete Step' : 'View Progress',
            actionUrl: route('candidate.visa-processing.index'),
            title: $this->isSeekerTurn($step) ? 'Your turn — '.$step->name : 'Step update — '.$step->name,
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: $this->isEmployerTurn($step) ? 'Your turn: '.$step->name : 'Visa step update: '.$step->name,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                $this->isEmployerTurn($step)
                    ? 'It is your turn on visa step: '.$step->name.'.'
                    : 'The active step is now: '.$step->name.' ('.$this->assigneeLabel($step->assignee).' action required).',
                $step->description ?: null,
                $workerLabel,
            ]),
            actionLabel: $this->isEmployerTurn($step) ? 'Open Visa Processing' : 'View Progress',
            actionUrl: $this->companyCaseUrl($case),
            title: $this->isEmployerTurn($step) ? 'Your turn — '.$step->name : 'Step update — '.$step->name,
        ));

        $this->notifyAgencyForStep($case, $step, $this->isAgencyTurn($step) ? 'Your turn: '.$step->name : 'Visa step update: '.$step->name);
    }

    public function stepSentBack(VpCase $case, VpCaseStep $step, string $reason): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Visa step sent back — please fix',
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: [
                'Your employer sent back step: '.$step->name.'.',
                'Reason: '.$reason,
                'Please correct and resubmit.',
                $workerLabel,
            ],
            actionLabel: 'Fix & Resubmit',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Visa step sent back',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Visa step sent back to candidate',
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: [
                'You sent back step: '.$step->name.' to the candidate.',
                'Reason: '.$reason,
                'The candidate has been asked to correct and resubmit.',
                $workerLabel,
            ],
            actionLabel: 'View Case',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Step sent back',
        ));
    }

    public function documentRejected(VpCase $case, VpCaseStep $step, $requirement, string $reason): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $label = is_object($requirement) ? ($requirement->label ?? 'document') : 'document';
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Document needs correction — '.$label,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: [
                'Your employer reviewed "'.$label.'" on step: '.$step->name.'.',
                'Reason: '.$reason,
                'Please upload a corrected version and save the step again.',
                $workerLabel,
            ],
            actionLabel: 'Open Visa Processing',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Document rejected',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Document rejected — '.$label,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: [
                'You rejected "'.$label.'" on step: '.$step->name.'.',
                'Reason: '.$reason,
                'The candidate has been notified to upload a corrected version.',
                $workerLabel,
            ],
            actionLabel: 'View Case',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Document rejected',
        ));
    }

    public function documentApproved(VpCase $case, VpCaseStep $step, $requirement): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'nominatedWorker', 'job']);
        $label = is_object($requirement) ? ($requirement->label ?? 'document') : 'document';
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Document approved — '.$label,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: [
                'Your employer approved "'.$label.'" on step: '.$step->name.'.',
                'No further action is needed for this item unless you are asked to update it.',
                $workerLabel,
            ],
            actionLabel: 'Open Visa Processing',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Document approved',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Document approved — '.$label,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: [
                'You approved "'.$label.'" on step: '.$step->name.'.',
                'The candidate has been notified.',
                $workerLabel,
            ],
            actionLabel: 'View Case',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Document approved',
        ));
    }

    public function caseCompleted(VpCase $case): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'agency.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Visa processing completed',
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                'All visa processing steps for '.$case->country_name.' are complete.',
                $workerLabel,
            ]),
            actionLabel: 'View Case',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Visa processing completed',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Visa processing completed',
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                'All visa processing steps for '.$case->country_name.' are complete.',
                $workerLabel,
            ]),
            actionLabel: 'View Case',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Visa processing completed',
        ));

        $agencyUser = $this->agencyUser($case);
        if ($agencyUser) {
            Notification::send($agencyUser, new VisaProcessingEventNotification(
                subject: 'Visa processing completed',
                greeting: 'Dear '.$agencyUser->name,
                lines: array_filter([
                    'All visa processing steps for '.$case->country_name.' are complete.',
                    $workerLabel,
                ]),
                actionLabel: 'View Case',
                actionUrl: $this->agencyCaseUrl($case),
                title: 'Visa processing completed',
            ));
        }
    }

    public function workerDeployed(VpCase $case): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'agency.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);
        $flightLine = $case->flight_date
            ? 'Flight: '.$case->flight_date->format('d M Y').($case->flight_airline ? ' via '.$case->flight_airline : '').($case->flight_ticket_number ? ' (Ticket #'.$case->flight_ticket_number.')' : '')
            : null;

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Worker deployed — '.$case->country_name,
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                'The worker has been deployed to '.$case->country_name.'.',
                $flightLine,
                $workerLabel,
            ]),
            actionLabel: 'View Case',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Worker deployed',
        ));

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'You have been marked as deployed — '.$case->country_name,
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                'Your employer has confirmed your deployment to '.$case->country_name.'.',
                $flightLine,
                $workerLabel,
            ]),
            actionLabel: 'View Status',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Deployment confirmed',
        ));

        $agencyUser = $this->agencyUser($case);
        if ($agencyUser) {
            Notification::send($agencyUser, new VisaProcessingEventNotification(
                subject: 'Worker deployed — '.$case->country_name,
                greeting: 'Dear '.$agencyUser->name,
                lines: array_filter([
                    'The worker has been deployed to '.$case->country_name.'.',
                    $flightLine,
                    $workerLabel,
                ]),
                actionLabel: 'View Case',
                actionUrl: $this->agencyCaseUrl($case),
                title: 'Worker deployed',
            ));
        }
    }

    public function caseCancelled(VpCase $case, string $reason): void
    {
        $case->loadMissing(['candidate.user', 'company.user', 'agency.user', 'nominatedWorker', 'job']);
        $workerLabel = $this->workerLabel($case);

        $this->notifyCandidate($case, new VisaProcessingEventNotification(
            subject: 'Visa processing cancelled',
            greeting: 'Dear '.($case->candidate?->user?->name ?? 'Candidate'),
            lines: array_filter([
                'Visa processing for '.$case->country_name.' was cancelled.',
                'Reason: '.$reason,
                $workerLabel,
            ]),
            actionLabel: 'View Status',
            actionUrl: route('candidate.visa-processing.index'),
            title: 'Visa case cancelled',
        ));

        $this->notifyEmployer($case, new VisaProcessingEventNotification(
            subject: 'Visa processing cancelled',
            greeting: 'Dear '.($this->employerUser($case)?->name ?? 'Employer'),
            lines: array_filter([
                'Visa processing for '.$case->country_name.' was cancelled.',
                'Reason: '.$reason,
                $workerLabel,
            ]),
            actionLabel: 'View Status',
            actionUrl: $this->companyCaseUrl($case),
            title: 'Visa case cancelled',
        ));

        $agencyUser = $this->agencyUser($case);
        if ($agencyUser) {
            Notification::send($agencyUser, new VisaProcessingEventNotification(
                subject: 'Visa processing cancelled',
                greeting: 'Dear '.$agencyUser->name,
                lines: array_filter([
                    'Visa processing for '.$case->country_name.' was cancelled.',
                    'Reason: '.$reason,
                    $workerLabel,
                ]),
                actionLabel: 'View Status',
                actionUrl: $this->agencyCaseUrl($case),
                title: 'Visa case cancelled',
            ));
        }
    }

    protected function notifyCandidate(VpCase $case, VisaProcessingEventNotification $notification): void
    {
        $user = $case->candidate?->user;
        if ($user) {
            Notification::send($user, $notification);
        }
    }

    protected function notifyEmployer(VpCase $case, VisaProcessingEventNotification $notification): void
    {
        $user = $this->employerUser($case);
        if ($user) {
            Notification::send($user, $notification);
        }
    }

    protected function notifyAgencyForStep(VpCase $case, VpCaseStep $step, string $subject): void
    {
        if (! in_array($step->assignee, [VisaLiability::AGENCY, VisaLiability::SHARED], true)) {
            return;
        }

        $agencyUser = $this->agencyUser($case);
        if (! $agencyUser) {
            return;
        }

        $workerLabel = $this->workerLabel($case);
        $isTurn = $this->isAgencyTurn($step);

        Notification::send($agencyUser, new VisaProcessingEventNotification(
            subject: $subject,
            greeting: 'Dear '.$agencyUser->name,
            lines: array_filter([
                $isTurn
                    ? 'It is your turn on visa step: '.$step->name.'.'
                    : 'The active step is now: '.$step->name.' ('.$this->assigneeLabel($step->assignee).' action required).',
                $step->description ?: null,
                $workerLabel,
            ]),
            actionLabel: $isTurn ? 'Open Visa Processing' : 'View Progress',
            actionUrl: $this->agencyCaseUrl($case),
            title: $isTurn ? 'Your turn — '.$step->name : 'Step update — '.$step->name,
        ));
    }

    protected function workerLabel(VpCase $case): ?string
    {
        if ($case->nominated_worker_id) {
            $name = $case->nominatedWorker?->full_name;

            return $name ? 'Worker: '.$name : 'Worker #'.$case->nominated_worker_id;
        }

        if ($case->candidate_id) {
            $case->loadMissing('candidate.user');
            $name = trim((string) ($case->candidate?->user?->name ?? ''));
            $code = trim((string) ($case->candidate?->public_code ?? ''));

            if ($name !== '' && $code !== '') {
                return 'Candidate: '.$name.' ('.$code.')';
            }
            if ($name !== '') {
                return 'Candidate: '.$name;
            }
            if ($code !== '') {
                return 'Candidate: '.$code;
            }

            return 'Candidate #'.$case->candidate_id;
        }

        return null;
    }

    protected function sideLabel(string $side): string
    {
        return match ($side) {
            'seeker' => 'the candidate',
            'employer' => 'the employer',
            'agency' => 'the agency',
            default => 'a party',
        };
    }

    protected function assigneeLabel(string $assignee): string
    {
        return match ($assignee) {
            VisaLiability::SEEKER => 'candidate',
            VisaLiability::EMPLOYER => 'employer',
            VisaLiability::AGENCY => 'agency',
            VisaLiability::SHARED => 'employer/agency',
            VisaLiability::GOVERNMENT => 'government',
            default => $assignee,
        };
    }

    protected function isSeekerTurn(VpCaseStep $step): bool
    {
        return $step->assignee === VisaLiability::SEEKER;
    }

    protected function isEmployerTurn(VpCaseStep $step): bool
    {
        return in_array($step->assignee, [VisaLiability::EMPLOYER, VisaLiability::SHARED], true);
    }

    protected function isAgencyTurn(VpCaseStep $step): bool
    {
        return in_array($step->assignee, [VisaLiability::AGENCY, VisaLiability::SHARED], true);
    }

    protected function firstStepHintForCandidate(VpCaseStep $step): string
    {
        return $this->isSeekerTurn($step) ? ' — this one is yours.' : ' — waiting on your employer.';
    }

    protected function firstStepHintForEmployer(VpCaseStep $step): string
    {
        return $this->isEmployerTurn($step) ? ' — this one is yours.' : ' — waiting on the candidate or agency.';
    }

    protected function nextStepLineForCandidate(VpCaseStep $next): string
    {
        return 'Next step: '.$next->name.($this->isSeekerTurn($next) ? ' — it is your turn.' : ' — waiting on '.$this->assigneeLabel($next->assignee).'.');
    }

    protected function nextStepLineForEmployer(VpCaseStep $next): string
    {
        return 'Next step: '.$next->name.($this->isEmployerTurn($next) ? ' — it is your turn.' : ' — waiting on '.$this->assigneeLabel($next->assignee).'.');
    }

    protected function employerUser(VpCase $case): ?User
    {
        return $case->company?->user;
    }

    protected function agencyUser(VpCase $case): ?User
    {
        return $case->agency?->user;
    }

    protected function companyCaseUrl(VpCase $case): string
    {
        $case->loadMissing('nominatedWorker');
        if ($case->nominated_worker_id) {
            if ($case->nominatedWorker?->batch_id) {
                return route('company.nominated-workers.batches.show', $case->nominatedWorker->batch_id);
            }

            return route('company.nominated-workers.show', $case->nominated_worker_id);
        }

        return route('company.visa-processing.show', $case->id);
    }

    protected function agencyCaseUrl(VpCase $case): string
    {
        $case->loadMissing('nominatedWorker');
        if ($case->nominated_worker_id) {
            if ($case->nominatedWorker?->batch_id) {
                return route('agency.nominated-workers.batches.show', $case->nominatedWorker->batch_id);
            }

            return route('agency.nominated-workers.show', $case->nominated_worker_id);
        }

        return route('agency.visa-processing.show', $case->id);
    }

    protected function urlFor(User $user, VpCase $case): string
    {
        if ($user->role === 'candidate') {
            return route('candidate.visa-processing.index');
        }
        if ($user->role === 'agency') {
            return $this->agencyCaseUrl($case);
        }

        return $this->companyCaseUrl($case);
    }
}
