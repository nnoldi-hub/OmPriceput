<?php

namespace App\Notifications;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ActivityReminder extends Notification
{
    use Queueable;

    public function __construct(public Activity $activity) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activitate restanta: '.$this->activity->title)
            ->greeting('Salut, '.$notifiable->name.'!')
            ->line('Ai o activitate comerciala care necesita atentie.')
            ->line('Client: '.$this->activity->client->name)
            ->line('Activitate: '.$this->activity->title)
            ->line('Termen: '.($this->activity->due_at?->format('d.m.Y H:i') ?? 'fara termen'))
            ->action('Deschide agenda', route('sales.activities.index', ['status' => 'pending']));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'activity_id' => $this->activity->id,
            'client_id' => $this->activity->client_id,
            'title' => $this->activity->title,
            'due_at' => $this->activity->due_at?->toIso8601String(),
        ];
    }
}
