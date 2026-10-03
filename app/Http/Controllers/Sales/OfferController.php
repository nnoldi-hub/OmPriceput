<?php

namespace App\Http\Controllers\Sales;

use App\Exports\OffersExport;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\Service;
use App\Models\User;
use App\Notifications\OfferSent;
use App\Notifications\OfferAvailable;
use App\Services\SmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class OfferController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(
            new OffersExport($request->only('search', 'status')),
            'oferte-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    public function index(Request $request): Response
    {
        $offers = Offer::query()
            ->with('client:id,name,company_name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->whereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $pipeline = Offer::query()
            ->selectRaw('status, count(*) as total, sum(total_amount) as amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return Inertia::render('Sales/Offers/Index', [
            'offers' => $offers,
            'filters' => $request->only('search', 'status'),
            'pipeline' => $pipeline,
        ]);
    }

    public function create(Request $request): Response
    {
        $visit = null;
        if ($request->integer('visit_id')) {
            $visit = Installation::with('client:id,name,company_name')->find($request->integer('visit_id'));
        }

        return Inertia::render('Sales/Offers/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company_name']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'unit_price', 'unit']),
            'services' => Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price', 'unit']),
            'types' => Installation::TYPE_LABELS,
            'preselectedClientId' => $visit?->client_id ?: ($request->integer('client_id') ?: null),
            'preselectedVisit' => $visit ? [
                'id' => $visit->id,
                'label' => 'Constatare #'.$visit->id.' - '.($visit->client?->name ?? ''),
            ] : null,
            'preselectedJobType' => $visit?->requested_type ?: ($request->string('job_type')->toString() ?: 'instalare'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        $offer = DB::transaction(function () use ($data, $request) {
            $offer = Offer::create([
                'client_id' => $data['client_id'],
                'user_id' => $request->user()->id,
                'visit_id' => $data['visit_id'] ?? null,
                'title' => $data['title'],
                'job_type' => $data['job_type'],
                'status' => 'draft',
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
            ]);

            $offer->items()->createMany($data['items']);

            return $offer;
        });

        if ($offer->status === 'sent') {
            $this->notifySentOffer($offer, $request->user());
        }

        return redirect()->route('sales.offers.show', $offer)->with('success', 'Deviz creat cu succes.');
    }

    public function show(Offer $offer): Response
    {
        $offer->load(['client', 'user:id,name', 'items.equipment:id,name,cost_price', 'items.service:id,name,cost_price']);

        return Inertia::render('Sales/Offers/Show', [
            'offer' => $offer,
            'profitability' => $offer->profitability_report,
        ]);
    }

    public function edit(Offer $offer): Response
    {
        $offer->load('items');

        return Inertia::render('Sales/Offers/Edit', [
            'offer' => $offer,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company_name']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'unit_price', 'unit']),
            'services' => Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price', 'unit']),
            'types' => Installation::TYPE_LABELS,
            'visits' => $offer->client_id
                ? Installation::where('client_id', $offer->client_id)
                    ->where('type', 'verificare')
                    ->latest('id')
                    ->limit(50)
                    ->get(['id', 'scheduled_at'])
                : collect(),
        ]);
    }

    public function update(Request $request, Offer $offer): RedirectResponse
    {
        $data = $this->validateData($request);

        DB::transaction(function () use ($data, $offer) {
            $offer->update([
                'client_id' => $data['client_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'title' => $data['title'],
                'job_type' => $data['job_type'],
                'status' => in_array($offer->status, ['sent', 'accepted', 'rejected'], true) ? 'draft' : $data['status'],
                'valid_until' => $data['valid_until'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total_amount' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_price']),
            ]);

            $offer->items()->delete();
            $offer->items()->createMany($data['items']);
        });

        return redirect()->route('sales.offers.show', $offer)->with('success', 'Deviz actualizat cu succes.');
    }

    public function updateStatus(Request $request, Offer $offer, SmsService $sms): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],
        ]);

        $offer->update($data);

        if ($data['status'] === 'accepted') {
            $offer->client->update(['status' => 'client']);
            $offer->load(['items.equipment', 'items.service']);
            $this->createInstallationFromAcceptedOffer($offer);
        }

        if ($data['status'] === 'sent') {
            $this->notifySentOffer($offer, $request->user());
            if ($offer->client->phone) {
                $sms->send($offer->client->phone, "Ti-am trimis oferta \"{$offer->title}\". Te asteptam cu intrebari!");
            }
        }

        $message = 'Status oferta actualizat.';
        if ($data['status'] === 'accepted' && $offer->profitability_report['margin_percent'] < $offer->profitability_report['minimum_margin_percent']) {
            $message .= ' Atentie: marja estimata este sub pragul configurat.';
        }

        return back()->with('success', $message);
    }

    private function notifySentOffer(Offer $offer, User $sender): void
    {
        $offer->loadMissing('client.user');

        if ($offer->client->user) {
            $offer->client->user->notify(new OfferSent($offer));
        } elseif ($offer->client->email && config('notifications.mail_enabled')) {
            Notification::route('mail', $offer->client->email)->notify(new OfferSent($offer));
        }

        Notification::send(
            User::role(['admin', 'vanzari'])->where('users.id', '!=', $sender->id)->get(),
            new OfferAvailable($offer),
        );
    }

    private function createInstallationFromAcceptedOffer(Offer $offer): void
    {
        if (Installation::where('offer_id', $offer->id)->exists()) {
            return;
        }

        $type = in_array($offer->job_type, Installation::TYPES, true) ? $offer->job_type : 'instalare';

        Installation::create([
            'client_id' => $offer->client_id,
            'offer_id' => $offer->id,
            'type' => $type,
            'requested_type' => $type,
            'address' => trim(($offer->client->address ?? '').' '.($offer->client->city ?? '')),
            'status' => 'scheduled',
            'checklist' => Installation::defaultChecklist($type),
            'material_items' => $offer->items->whereNotNull('equipment_id')->map(fn ($item) => [
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment?->name ?? $item->description,
                'unit' => $item->equipment?->unit ?? 'buc',
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
            'service_items' => $offer->items->whereNotNull('service_id')->map(fn ($item) => [
                'service_id' => $item->service_id,
                'name' => $item->service?->name ?? $item->description,
                'unit' => $item->service?->unit ?? 'ora',
                'quantity' => (int) $item->quantity,
            ])->values()->all(),
            'notes' => 'Lucrare generata automat la acceptarea devizului #'.$offer->id.'.',
        ]);
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $offer->delete();

        return redirect()->route('sales.offers.index')->with('success', 'Oferta stearsa.');
    }

    public function pdf(Offer $offer): HttpResponse
    {
        $offer->load(['client', 'items.equipment:id,name', 'items.service:id,name']);

        return Pdf::loadView('pdfs.offer', ['offer' => $offer])->stream("oferta-{$offer->id}.pdf");
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'visit_id' => ['nullable', 'exists:installations,id'],
            'title' => ['required', 'string', 'max:255'],
            'job_type' => ['required', Rule::in(Installation::TYPES)],
            'status' => ['required', 'in:draft,sent,accepted,rejected,expired'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.equipment_id' => ['nullable', 'exists:equipment,id'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);
    }
}
