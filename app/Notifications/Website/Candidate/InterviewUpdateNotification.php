<?php

namespace App\Notifications\Website\Candidate;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterviewUpdateNotification extends Notification
{
    use Queueable;

    public function __construct(
        public $user,
        public string $companyName,
        public $job,
        public string $action,
        public ?string $interviewDate = null,
        public ?string $interviewLocation = null,
    ) {}

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $jobTitle = $this->job?->title ?? 'a role';
        $message = (new MailMessage)
            ->greeting('Dear '.$this->user->name);

        return match ($this->action) {
            'invite' => $message
                ->subject("Interview invitation: {$jobTitle}")
                ->line("{$this->companyName} has invited you to an interview for {$jobTitle}.")
                ->line($this->dateLine())
                ->line($this->locationLine())
                ->action('View applications', route('candidate.appliedjob', ['status' => 'interview']))
                ->line('Please be prepared and check your email for further details.'),
            'reschedule' => $message
                ->subject("Interview rescheduled: {$jobTitle}")
                ->line("{$this->companyName} has rescheduled your interview for {$jobTitle}.")
                ->line($this->dateLine())
                ->line($this->locationLine())
                ->action('View applications', route('candidate.appliedjob', ['status' => 'interview'])),
            'accept', 'completed' => $message
                ->subject("Interview update: {$jobTitle}")
                ->line($this->action === 'accept'
                    ? "Good news — {$this->companyName} has accepted you after the interview for {$jobTitle}."
                    : "{$this->companyName} has marked your interview for {$jobTitle} as completed.")
                ->action('View applications', route('candidate.appliedjob')),
            'reject' => $message
                ->subject("Interview update: {$jobTitle}")
                ->line("{$this->companyName} will not be moving forward with your interview for {$jobTitle}.")
                ->line('Thank you for your interest — keep applying to other roles.')
                ->action('View applications', route('candidate.appliedjob')),
            default => $message
                ->subject("Interview update: {$jobTitle}")
                ->line("There is an update on your interview for {$jobTitle} with {$this->companyName}.")
                ->action('View applications', route('candidate.appliedjob')),
        };
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Interview '.$this->action.' — '.($this->job?->title ?? 'job'),
            'url' => route('candidate.appliedjob', ['status' => 'interview']),
            'job_id' => $this->job?->id,
            'company_name' => $this->companyName,
            'action' => $this->action,
        ];
    }

    protected function dateLine(): string
    {
        return $this->interviewDate
            ? 'Interview date: '.$this->interviewDate
            : 'Interview date will be confirmed soon.';
    }

    protected function locationLine(): string
    {
        return $this->interviewLocation
            ? 'Location / mode: '.$this->interviewLocation
            : 'Location details will follow.';
    }
}
