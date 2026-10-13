<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    /**
     * Drivere (SMS_DRIVER din .env):
     *  - log     : doar scrie in log (implicit, fara costuri)
     *  - smslink : SMSLink.ro (SMSLINK_CONNECTION_ID + SMSLINK_PASSWORD)
     *  - http    : gateway generic (SMS_API_URL + SMS_API_KEY)
     *
     * Mesajele sunt transliterate in ASCII (fara diacritice), ca sa ramana
     * SMS-uri de 160 de caractere, nu de 70.
     */
    public function send(string $phone, string $message): SmsLog
    {
        $message = Str::ascii($message);
        $driver = config('services.sms.driver', 'log');

        if ($driver === 'smslink' && config('services.sms.connection_id') && config('services.sms.password')) {
            return $this->sendViaSmsLink($phone, $message);
        }

        if ($driver === 'http' && config('services.sms.api_url')) {
            return $this->sendViaHttp($phone, $message);
        }

        Log::info("[SMS:log] to {$phone}: {$message}");

        return SmsLog::create([
            'phone' => $phone,
            'message' => $message,
            'driver' => 'log',
            'status' => 'sent',
        ]);
    }

    /** Numar mobil romanesc in format 07xxxxxxxx sau null daca nu este valid. */
    public function normalizePhone(?string $phone): ?string
    {
        $digits = preg_replace('/[^\d+]/', '', (string) $phone);
        $digits = preg_replace('/^(\+40|0040|40)(?=7)/', '0', $digits);

        return preg_match('/^07\d{8}$/', $digits) ? $digits : null;
    }

    private function sendViaSmsLink(string $phone, string $message): SmsLog
    {
        try {
            $params = [
                'connection_id' => config('services.sms.connection_id'),
                'password' => config('services.sms.password'),
                'to' => $phone,
                'message' => $message,
            ];

            if ($sender = config('services.sms.sender')) {
                $params['sender'] = $sender;
            }

            $response = Http::asForm()
                ->timeout(10)
                ->post(config('services.sms.api_url') ?: 'https://secure.smslink.ro/sms/gateway/communicate/index.php', $params);

            $body = trim($response->body());
            $ok = $response->successful() && ! str_starts_with(strtoupper($body), 'ERROR');

            Log::info("[SMS:smslink] to {$phone}: {$body}");

            return SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'driver' => 'smslink',
                'status' => $ok ? 'sent' : 'failed',
                'error' => $ok ? null : $body,
            ]);
        } catch (\Throwable $e) {
            return SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'driver' => 'smslink',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendViaHttp(string $phone, string $message): SmsLog
    {
        try {
            $response = Http::timeout(10)->post(config('services.sms.api_url'), [
                'to' => $phone,
                'message' => $message,
                'sender' => config('services.sms.sender'),
                'api_key' => config('services.sms.api_key'),
            ]);

            return SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'driver' => 'http',
                'status' => $response->successful() ? 'sent' : 'failed',
                'error' => $response->successful() ? null : $response->body(),
            ]);
        } catch (\Throwable $e) {
            return SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'driver' => 'http',
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
