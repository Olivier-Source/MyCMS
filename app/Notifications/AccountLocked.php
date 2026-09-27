<?php

namespace App\Notifications;

use App\Cms\SiteSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLocked extends Notification
{
    public function __construct(private string $duration, private ?string $ip) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Security alert: login blocked'))
            ->greeting(__('Hello,'))
            ->line(__('Several attempts to log in to the administration of your site failed with your e-mail address.'))
            ->line(__('For security reasons, logins are blocked for :duration.', ['duration' => $this->duration]))
            ->line(__('IP address of the attempts: :ip.', ['ip' => $this->ip ?: __('unknown')]))
            ->line(__('If it was you, simply wait before trying again. Otherwise, no action is needed: your account remains protected. Tell your technical contact if these alerts happen again.'))
            ->salutation('— '.app(SiteSettings::class)->siteName());
    }
}
