<?php

namespace App\Notifications;

use App\Models\Offer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfferAvailable extends Notification
{
    use Queueable;

    public function __construct(public Offer $offer) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'Oferta trimisa clientului',
            'message' => 'Oferta „'.$this->offer->title.'” a fost trimisa clientului.',
            'offer_id' => $this->offer->id,
        ];
    }
}
