<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Installation;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pagina deschisă din SMS-ul primit de administrator: confirmă sau refuză
 * o programare dintr-o atingere. Accesul se face doar cu tokenul secret din link.
 */
class BookingActionController extends Controller
{
    public function show(string $token): Response
    {
        $installation = $this->find($token);

        return response()
            ->view('booking.action', [
                'i' => $installation,
                'token' => $token,
                'services' => collect($installation->service_items ?? [])->pluck('name')->filter()->all(),
            ])
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function update(Request $request, string $token, SmsService $sms): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', 'in:confirm,cancel']]);
        $installation = $this->find($token);

        $result = DB::transaction(function () use ($installation, $data) {
            $i = Installation::query()->lockForUpdate()->findOrFail($installation->id);

            if (! in_array($i->status, ['scheduled', 'in_progress'], true)) {
                return 'locked';
            }

            if ($data['action'] === 'cancel') {
                $i->update(['status' => 'cancelled']);

                return 'cancelled';
            }

            if (! $i->scheduled_at) {
                return 'no_time';
            }

            if ($i->scheduled_at->isPast()) {
                return 'past';
            }

            if ($i->confirmed_at) {
                return 'already';
            }

            $i->update(['confirmed_at' => now()]);

            return 'confirmed';
        });

        $installation->refresh()->load('client');
        $this->notifyClient($sms, $installation, $result);

        $messages = [
            'confirmed' => 'Programarea a fost confirmată. Clientul a primit SMS.',
            'cancelled' => 'Programarea a fost refuzată. Clientul a primit SMS.',
            'already' => 'Programarea era deja confirmată.',
            'locked' => 'Programarea a fost deja finalizată sau anulată.',
            'no_time' => 'Cererea nu are o oră aleasă. Sună clientul pentru a stabili ora.',
            'past' => 'Ora programării a trecut deja.',
        ];

        return redirect()
            ->route('booking.action', $token)
            ->with('status', $messages[$result] ?? 'Gata.');
    }

    private function notifyClient(SmsService $sms, Installation $i, string $result): void
    {
        if (! in_array($result, ['confirmed', 'cancelled'], true)) {
            return;
        }

        $to = $sms->normalizePhone($i->client?->phone);

        if (! $to || ! $i->scheduled_at) {
            return;
        }

        $when = $i->scheduled_at->format('d.m.Y H:i');
        $contact = Setting::get('company_phone');
        $contactText = $contact ? " Telefon: {$contact}." : '';

        $text = $result === 'confirmed'
            ? "Om Priceput: programarea ta din {$when} a fost confirmata. Te asteptam!{$contactText}"
            : "Om Priceput: din pacate nu putem onora intervalul {$when}. Te contactam pentru un alt termen.{$contactText}";

        $sms->send($to, $text);
    }

    private function find(string $token): Installation
    {
        abort_unless(Str::length($token) === 24, 404);

        return Installation::query()->with('client')->where('action_token', $token)->firstOrFail();
    }
}
