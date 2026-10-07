<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Installation;
use App\Models\Page;
use App\Models\Service;
use App\Notifications\NewLeadReceived;
use App\Notifications\RequestConfirmation;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/Contact', ['page' => Page::published()->where('slug', 'contact')->first()]);
    }

    public function quote(): Response
    {
        return Inertia::render('Public/QuoteRequest', [
            'page' => Page::published()->where('slug', 'contact')->first(),
            'types' => Installation::TYPE_LABELS,
            'trades' => Service::TRADES,
            'services' => Service::where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'unit', 'description', 'sale_price', 'duration_minutes']),
        ]);
    }

    public function dates(Request $request, AvailabilityService $availability): JsonResponse
{
    $request->validate(['service_ids' => ['nullable', 'array'], 'service_ids.*' => ['integer']]);

    $minutes = $this->durationFor($request->input('service_ids', []));

    return response()->json([
        'dates' => $availability->availableDates($minutes)->all(),
        'duration' => $minutes,
    ]);
}

public function slots(Request $request, AvailabilityService $availability): JsonResponse
{
    $request->validate([
        'date' => ['required', 'date_format:Y-m-d'],
        'service_ids' => ['nullable', 'array'],
        'service_ids.*' => ['integer'],
    ]);

    $minutes = $this->durationFor($request->input('service_ids', []));

    $slots = $availability
        ->slotsForDate(CarbonImmutable::createFromFormat('Y-m-d', $request->input('date')), $minutes)
        ->map(fn (CarbonImmutable $s) => $s->format('H:i'))
        ->values();

    return response()->json(['slots' => $slots]);
}

/** Durata totala, calculata mereu pe server. Minim 60 min (si pentru o simpla constatare). */
private function durationFor(array $serviceIds): int
{
    $ids = array_filter(array_map('intval', $serviceIds));

    $sum = $ids
        ? (int) Service::whereIn('id', $ids)->where('is_active', true)->sum('duration_minutes')
        : 0;

    return max($sum, 60);
}
    public function store(Request $request, SmsService $sms, AvailabilityService $availability): RedirectResponse
{
    $data = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['required', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
        'city' => ['nullable', 'string', 'max:255'],
        'address' => ['nullable', 'string', 'max:255'],
        'notes' => ['nullable', 'string', 'max:2000'],
        'job_type' => ['required_with:scheduled_at', 'nullable', Rule::in(Installation::TYPES)],
        'service_ids' => ['nullable', 'array'],
        'service_ids.*' => ['integer', 'exists:services,id'],
        'scheduled_at' => ['nullable', 'date_format:Y-m-d H:i'],
        'photos' => ['nullable', 'array', 'max:5'],
        'photos.*' => ['image', 'max:5120'],
        'privacy_consent' => ['accepted'],
    ]);

    $duration = $this->durationFor($data['service_ids'] ?? []);
    $start = empty($data['scheduled_at'])
        ? null
        : CarbonImmutable::createFromFormat('Y-m-d H:i', $data['scheduled_at']);

    $services = Service::whereIn('id', $data['service_ids'] ?? [])->get(['id', 'name']);

    $register = fn () => DB::transaction(function () use ($request, $data, $availability, $start, $duration, $services) {
        // verificarea finala, chiar inainte de salvare
        if ($start && ! $availability->isSlotFree($start, $duration)) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Intervalul ales tocmai a fost ocupat. Va rugam alegeti alt interval.',
            ]);
        }

        $client = Client::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'source' => 'web',
            'status' => 'lead',
            'privacy_consent_at' => now(),
            'privacy_consent_ip' => $request->ip(),
        ]);

        $visit = empty($data['job_type'])
            ? null
            : $this->createVisit($request, $client, $data, $start, $duration, $services);

        return [$client, $visit];
    });

    // un singur client poate rezerva la un moment dat, ca doi oameni sa nu ia acelasi slot
    [$client, $visit] = $start
        ? Cache::lock('booking', 10)->block(5, $register)
        : $register();

    $recipients = Role::whereIn('name', ['admin', 'vanzari'])
        ->with('users')
        ->get()
        ->flatMap(fn (Role $role) => $role->users)
        ->unique('id');

    Notification::send($recipients, new NewLeadReceived($client, $visit, $services->pluck('name')->all()));

    if ($data['email'] ?? null) {
        Notification::route('mail', $data['email'])
            ->notify(new RequestConfirmation($client, $visit, $services->pluck('name')->all()));
    }

    $when = $start ? $start->format('d.m.Y H:i') : null;

    foreach ($recipients->whereNotNull('phone') as $recipient) {
        $sms->send(
            $recipient->phone,
            $visit
                ? "Cerere de deviz: {$client->name} ({$client->phone})".($when ? ", {$when}. De confirmat." : '. De programat.')
                : "Lead nou: {$client->name} ({$client->phone}).",
        );
    }

    return back()->with(
        'success',
        $when
            ? "Am primit cererea pentru {$when}. Revenim cu confirmarea."
            : ($visit
                ? 'Cererea a fost inregistrata. Revenim cu confirmarea orei pentru constatare.'
                : 'Cererea a fost inregistrata. Va contactam in cel mai scurt timp.'),
    );
}

private function createVisit(Request $request, Client $client, array $data, ?CarbonImmutable $start, int $duration, Collection $services): Installation
{
    $serviceNames = $services->pluck('name');

    $photos = collect($request->file('photos', []))
        ->filter()
        ->map(fn ($photo) => Storage::disk('public')->url($photo->store('quote-requests', 'public')))
        ->values()
        ->all();

    return Installation::create([
        'client_id' => $client->id,
        'type' => 'verificare',
        'requested_type' => $data['job_type'],
        'address' => trim(($data['address'] ?? '').' '.($data['city'] ?? '')) ?: null,
        'scheduled_at' => $start,
        'labor_hours' => round($duration / 60, 2),
        'status' => 'scheduled',
        'checklist' => Installation::defaultChecklist('verificare'),
        'photos' => $photos ?: null,
        'customer_notes' => collect([
            'Tip lucrare solicitat: '.(Installation::TYPE_LABELS[$data['job_type']] ?? $data['job_type']),
            $serviceNames->isNotEmpty() ? 'Servicii dorite: '.$serviceNames->join(', ') : null,
            $data['notes'] ?? null,
        ])->filter()->implode("\n"),
        'notes' => 'Constatare creata automat din cererea de deviz online.',
    ]);
}
}