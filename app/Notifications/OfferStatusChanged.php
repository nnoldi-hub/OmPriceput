<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Offer $offer,
        public string $status,
        public ?string $message = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return config('notifications.mail_enabled') ? ['database', 'mail'] : ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Raspuns client la deviz',
            'message' => 'Devizul „'.$this->offer->title.'” a fost '.($this->status === 'accepted' ? 'acceptat' : 'respins').'.',
            'offer_id' => $this->offer->id,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Raspuns client la deviz #'.$this->offer->id)
            ->view('emails.offer-status-changed', [
                'recipientName' => $notifiable->name,
                'offer' => $this->offer,
                'status' => $this->status,
                'clientMessage' => $this->message,
                'offerUrl' => route('sales.offers.show', $this->offer),
                'logoUrl' => asset('branding/op-logo.png'),
            ]);
    }
}
