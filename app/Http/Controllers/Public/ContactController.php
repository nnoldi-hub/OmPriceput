<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Installation;
use App\Models\Page;
use App\Models\Service;
use App\Notifications\NewLeadReceived;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

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
                ->get(['id', 'name', 'category', 'unit', 'description']),
        ]);
    }

    public function store(Request $request, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'job_type' => ['nullable', Rule::in(Installation::TYPES)],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
            'privacy_consent' => ['accepted'],
        ]);

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

        $visit = empty($data['job_type']) ? null : $this->createVisit($request, $client, $data);

        $recipients = Role::whereIn('name', ['admin', 'vanzari'])
            ->with('users')
            ->get()
            ->flatMap(fn (Role $role) => $role->users)
            ->unique('id');

        Notification::send($recipients, new NewLeadReceived($client));

        foreach ($recipients->whereNotNull('phone') as $recipient) {
            $sms->send(
                $recipient->phone,
                $visit
                    ? "Cerere de deviz: {$client->name} ({$client->phone}). De programat."
                    : "Lead nou: {$client->name} ({$client->phone}).",
            );
        }

        return back()->with(
            'success',
            $visit
                ? 'Cererea ta a fost trimisa. Revenim cu confirmarea orei pentru constatare.'
                : 'Cererea ta a fost trimisa. Te vom contacta in cel mai scurt timp.',
        );
    }

    /**
     * Programarea de constatare pornita din cererea de deviz: fara data si fara
     * mester, ca sa poata fi introdusa in lista "de programat".
     */
    private function createVisit(Request $request, Client $client, array $data): Installation
    {
        $services = Service::whereIn('id', $data['service_ids'] ?? [])->pluck('name');

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
            'status' => 'scheduled',
            'checklist' => Installation::defaultChecklist('verificare'),
            'photos' => $photos ?: null,
            'customer_notes' => collect([
                'Tip lucrare solicitat: '.(Installation::TYPE_LABELS[$data['job_type']] ?? $data['job_type']),
                $services->isNotEmpty() ? 'Servicii dorite: '.$services->join(', ') : null,
                $data['notes'] ?? null,
            ])->filter()->implode("\n"),
            'notes' => 'Constatare creata automat din cererea de deviz online.',
        ]);
    }
}