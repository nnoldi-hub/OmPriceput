<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\Installation;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestConfirmation extends Notification implements ShouldQueue
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
        return config('notifications.mail_enabled') ? ['mail'] : [];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Am primit cererea ta de la '.Setting::get('company_name'))
            ->view('emails.request-received', [
                'recipientName' => $this->client->name,
                'companyName' => Setting::get('company_name'),
                'phone' => Setting::get('company_phone'),
                'hours' => Setting::get('company_hours'),
                'visit' => $this->visit,
                'services' => $this->services,
                'logoUrl' => asset('branding/op-logo.png'),
            ]);
    }
}
