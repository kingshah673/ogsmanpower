<?php

namespace App\Notifications;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendCompanyDocumentResubmitNotification extends Notification
{
    use Queueable;

    public function __construct(public Company $company)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $note = trim((string) $this->company->document_review_note);

        return (new MailMessage)
            ->subject(__('employer_documents_resubmit_subject'))
            ->greeting(__('hello').' '.$notifiable->name)
            ->line(__('employer_documents_resubmit_intro'))
            ->when($note !== '', fn (MailMessage $mail) => $mail->line($note))
            ->action(__('upload_documents'), route('company.verify.documents.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('employer_documents_resubmit_subject'),
            'message' => $this->company->document_review_note ?: __('employer_documents_resubmit_intro'),
            'url' => route('company.verify.documents.index'),
        ];
    }
}
