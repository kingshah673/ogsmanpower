<?php

namespace App\Notifications\Website\Candidate;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShortlistedJobNotification extends Notification
{
    use Queueable;

    public $user;

    public $companyName;

    public $job;

    public function __construct($user, string $companyName, $job)
    {
        $this->user = $user;
        $this->companyName = $companyName;
        $this->job = $job;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $jobTitle = $this->job?->title ?? 'a role';
        $url = $this->job?->slug
            ? route('candidate.appliedjob', ['status' => 'shortlisted'])
            : route('candidate.appliedjob');

        return (new MailMessage)
            ->greeting('Dear '.$this->user->name)
            ->subject("You've been shortlisted for {$jobTitle}")
            ->line("Good news — {$this->companyName} has shortlisted you for the position of {$jobTitle}.")
            ->line('They may contact you about next steps. You can review your application status anytime in your Applied Jobs page.')
            ->action('View Applied Jobs', $url)
            ->line('Thank you for choosing '.config('app.name').'.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => "You've been shortlisted for ".($this->job?->title ?? 'a job'),
            'url' => route('candidate.appliedjob', ['status' => 'shortlisted']),
            'job_id' => $this->job?->id,
            'company_name' => $this->companyName,
        ];
    }
}
