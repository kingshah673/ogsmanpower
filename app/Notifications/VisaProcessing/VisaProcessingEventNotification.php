<?php

namespace App\Notifications\VisaProcessing;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VisaProcessingEventNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $greeting,
        public array $lines,
        public string $actionLabel,
        public string $actionUrl,
        public string $title,
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->greeting($this->greeting);

        foreach (array_filter($this->lines) as $line) {
            $mail->line($line);
        }

        return $mail
            ->action($this->actionLabel, $this->actionUrl)
            ->line('Thank you for using '.config('app.name').'.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => $this->title,
            'url' => $this->actionUrl,
            'subject' => $this->subject,
        ];
    }
}
