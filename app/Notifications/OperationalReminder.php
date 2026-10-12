<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationalReminder extends Notification
{
    use Queueable;

    public function __construct(
        public string $kind,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->emailEnabled()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->title)
            ->greeting($this->title)
            ->line($this->message);

        if ($this->url) {
            $mail->action('Deschide in aplicatie', $this->url);
        }

        return $mail->line('Aceasta notificare a fost trimisa automat de platforma '.Setting::get('company_name').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }

    private function emailEnabled(): bool
    {
        return config('notifications.mail_enabled', false)
            || Setting::get('operational_reminders_email_enabled', '0') === '1';
    }
}
