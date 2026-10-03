<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return config('notifications.mail_enabled') ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isClient = $notifiable instanceof AnonymousNotifiable
            || (method_exists($notifiable, 'hasAnyRole') && $notifiable->hasAnyRole(['client', 'client-manager']));

        return (new MailMessage)
            ->subject('Actualizare cerere #'.$this->ticket->id)
            ->view('emails.ticket-updated', [
                'recipientName' => $notifiable->name ?? $this->ticket->client?->name,
                'notificationMessage' => $this->message,
                'ticket' => $this->ticket,
                'actionLabel' => $isClient ? 'Vezi portalul client' : 'Vezi tichetele',
                'actionUrl' => $isClient
                    ? route('client.tickets.index')
                    : route('technical.tickets.show', $this->ticket),
                'logoUrl' => asset('branding/op-logo.png'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Actualizare cerere',
            'message' => $this->message,
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
        ];
    }
}
