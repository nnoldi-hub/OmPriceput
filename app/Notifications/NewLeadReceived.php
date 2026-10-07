<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeadReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Client $client,
        public ?Installation $visit = null,
        public array $services = [],
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Lead nou: '.$this->client->name)
            ->line('A fost primită o nouă cerere de deviz de pe site.')
            ->line('Nume: '.$this->client->name)
            ->line('Telefon: '.$this->client->phone)
            ->line('Oraș: '.($this->client->city ?? '-'))
            ->line('Mesaj: '.($this->client->notes ?? '-'));

        if ($this->visit?->scheduled_at) {
            $mail->line('Programare: '.$this->visit->scheduled_at->format('d.m.Y H:i'));
        }

        if ($this->services !== []) {
            $mail->line('Servicii: '.implode(', ', $this->services));
        }

        return $mail->action(
            $this->visit ? 'Vezi programarea' : 'Vezi în CRM',
            $this->visit
                ? route('technical.installations.show', $this->visit)
                : route('sales.dashboard'),
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'client_id' => $this->client->id,
            'name' => $this->client->name,
            'phone' => $this->client->phone,
            'visit_id' => $this->visit?->id,
            'scheduled_at' => $this->visit?->scheduled_at?->toDateTimeString(),
        ];
    }
}
