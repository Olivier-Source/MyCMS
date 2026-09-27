<?php

namespace App\Notifications;

use App\Cms\SiteSettings;
use App\Models\ContactMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification
{
    public function __construct(private ContactMessage $message, private bool $includeContent) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = app(SiteSettings::class)->siteName();

        $mail = (new MailMessage)
            ->subject(__('New message received on :site', ['site' => $site]))
            ->greeting(__('Hello,'))
            ->line(__('A new message has been sent from the contact form of your site.'));

        if ($this->includeContent) {
            $mail->line('**'.__('From:').'** '.$this->message->name.' ('.$this->message->email.')')
                ->line('**'.__('Subject:').'** '.($this->message->subject ?: '—'))
                ->line($this->message->message);
        } else {
            $mail->line(__('To preserve confidentiality, its content is not copied into this e-mail.'));
        }

        return $mail->action(__('Read the message'), route('admin.messages.show', $this->message))
            ->salutation('— '.$site);
    }
}
