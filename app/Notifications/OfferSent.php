<?php

namespace App\Notifications;

use App\Models\Offer;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OfferSent extends Notification
{
    use Queueable;

    public function __construct(public Offer $offer)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return config('notifications.mail_enabled') ? ['database', 'mail'] : ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Devizul tau de la '.Setting::get('company_name'))
            ->view('emails.offer-sent', [
                'recipientName' => $this->offer->client->name,
                'offer' => $this->offer,
                'logoUrl' => asset('branding/op-logo.png'),
            ]);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Deviz nou',
            'message' => 'Ai primit devizul „'.$this->offer->title.'”.',
            'offer_id' => $this->offer->id,
        ];
    }
}
