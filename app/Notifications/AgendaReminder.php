<?php

namespace App\Notifications;

use App\Models\AgendaItem;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgendaReminder extends Notification
{
    use Queueable;

    public function __construct(public AgendaItem $item) {}

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
        return (new MailMessage)
            ->subject("Mementou agenda: {$this->item->title}")
            ->greeting('Mementou din agenda')
            ->line($this->message())
            ->when($this->url(), fn (MailMessage $mail, string $url) => $mail->action('Deschide in aplicatie', $url))
            ->line('Aceasta notificare a fost trimisa automat de platforma '.Setting::get('company_name').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'agenda_reminder',
            'title' => $this->item->title,
            'message' => $this->message(),
            'url' => $this->url(),
        ];
    }

    private function message(): string
    {
        $context = $this->item->project ? " (proiect: {$this->item->project->name})" : '';

        return "Ai un mementou in agenda: {$this->item->title}{$context}.";
    }

    private function url(): ?string
    {
        if ($this->item->project) {
            return route('agenda.projects.show', $this->item->project);
        }

        return route('agenda.inbox');
    }

    private function emailEnabled(): bool
    {
        return config('notifications.mail_enabled', false)
            || Setting::get('agenda_reminders_email_enabled', '0') === '1';
    }
}
