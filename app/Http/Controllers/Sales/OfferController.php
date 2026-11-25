<?php

namespace App\Http\Controllers\Sales;

use App\Exports\OffersExport;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\OfferChapter;
use App\Models\OfferItem;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\OfferAvailable;
use App\Notifications\OfferSent;
use App\Services\EquipmentCatalogService;
use App\Services\SmsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'unit_price', 'cost_price', 'unit']),
            'services' => Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price', 'cost_price', 'unit']),
            'types' => Installation::TYPE_LABELS,
            'preselectedClientId' => $visit?->client_id ?: ($request->integer('client_id') ?: null),
            'preselectedVisit' => $visit ? [
                'id' => $visit->id,
                'label' => 'Constatare #'.$visit->id.' - '.($visit->client?->name ?? ''),
            ] : null,
            'preselectedJobType' => $visit?->requested_type ?: ($request->string('job_type')->toString() ?: 'instalare'),
            'defaultValidUntil' => Offer::defaultValidUntil(),
            'chapterSuggestions' => $this->chapterSuggestions(),
            'equipmentUnits' => Equipment::UNITS,
            'serviceUnits' => Service::UNITS,
            'minimumMargin' => (float) (Setting::get('minimum_profit_margin', '20') ?? 20),
            'vatPercentage' => (float) (Setting::get('vat_percentage') ?? 0),
        ]);
    }

    public function store(Request $request, EquipmentCatalogService $catalog): RedirectResponse
    {
        $data = $this->validateData($request);
        $items = $this->prepareItems($data['items']);
        $intents = $this->catalogIntents($data['items']);

        $offer = DB::transaction(function () use ($data, $items, $intents, $request, $catalog) {
            $offer = Offer::create([
                'client_id' => $data['client_id'],
                'user_id' => $request->user()->id,
                'visit_id' => $data['visit_id'] ?? null,
                'title' => $data['title'],
                'job_type' => $data['job_type'],
                'status' => 'draft',
                'valid_until' => $data['valid_until'] ?? Offer::defaultValidUntil(),
                'vat_applied' => $data['vat_applied'] ?? true,
                'notes' => $data['notes'] ?? null,
                'total_amount' => $this->totalFromItems($items),
            ]);

            $refMap = $this->syncChapters($offer, $data['chapters'] ?? []);
            $created = $offer->items()->createMany($this->withChapterIds($items, $refMap));

            $this->persistCatalogItems($created, $intents, $request->user(), $offer, $catalog);

            return $offer;
        });

        if ($offer->status === 'sent') {
            $this->notifySentOffer($offer, $request->user());
        }

        return redirect()->route('sales.offers.show', $offer)->with('success', 'Deviz creat cu succes.');
    }

    public function show(Offer $offer): Response
    {
        $offer->load(['client', 'user:id,name', 'chapters', 'items.equipment:id,name,cost_price', 'items.service:id,name,cost_price']);

        return Inertia::render('Sales/Offers/Show', [
            'offer' => $offer,
            'profitability' => $offer->profitability_report,
            'summary' => $offer->summaryByChapter(),
            'lineMargins' => $offer->lineMargins(),
        ]);
    }

    public function edit(Offer $offer): Response
    {
        $offer->load(['items', 'chapters']);

        return Inertia::render('Sales/Offers/Edit', [
            'offer' => $offer,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company_name']),
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'unit_price', 'cost_price', 'unit']),
            'services' => Service::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sale_price', 'cost_price', 'unit']),
            'types' => Installation::TYPE_LABELS,
            'visits' => $offer->client_id
                ? Installation::where('client_id', $offer->client_id)
                    ->where('type', 'verificare')
                    ->latest('id')
                    ->limit(50)
                    ->get(['id', 'scheduled_at'])
                : collect(),
            'chapterSuggestions' => $this->chapterSuggestions($offer),
            'equipmentUnits' => Equipment::UNITS,
            'serviceUnits' => Service::UNITS,
            'minimumMargin' => (float) (Setting::get('minimum_profit_margin', '20') ?? 20),
            'vatPercentage' => (float) (Setting::get('vat_percentage') ?? 0),
        ]);
    }

    public function update(Request $request, Offer $offer, EquipmentCatalogService $catalog): RedirectResponse
    {
        $data = $this->validateData($request);
        $items = $this->prepareItems($data['items']);
        $intents = $this->catalogIntents($data['items']);

        DB::transaction(function () use ($data, $items, $intents, $offer, $catalog, $request) {
            $offer->update([
                'client_id' => $data['client_id'],
                'visit_id' => $data['visit_id'] ?? null,
                'title' => $data['title'],
                'job_type' => $data['job_type'],
                'status' => in_array($offer->status, ['sent', 'accepted', 'rejected'], true) ? 'draft' : $data['status'],
                'valid_until' => $data['valid_until'] ?? Offer::defaultValidUntil(),
                'vat_applied' => $data['vat_applied'] ?? true,
                'notes' => $data['notes'] ?? null,
                'total_amount' => $this->totalFromItems($items),
            ]);

            $refMap = $this->syncChapters($offer, $data['chapters'] ?? []);
            $offer->items()->delete();
            $created = $offer->items()->createMany($this->withChapterIds($items, $refMap));

            $this->persistCatalogItems($created, $intents, $request->user(), $offer, $catalog);
            $this->syncInstallationFromOffer($offer);
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
            $this->createInstallationFromAcceptedOffer($offer);
            $this->syncInstallationFromOffer($offer);
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
            'material_items' => $offer->installationMaterialItems(),
            'service_items' => $offer->installationServiceItems(),
            'notes' => 'Lucrare generata automat la acceptarea devizului #'.$offer->id.'.',
        ]);
    }

    /**
     * „Necesar materiale” trebuie să citească devizul acceptat, nu copia veche
     * din programare (care rămâne goală după editarea ofertei).
     */
    private function syncInstallationFromOffer(Offer $offer): void
    {
        if ($offer->installations()->doesntExist()) {
            return;
        }

        $materials = $offer->installationMaterialItems();
        $services = $offer->installationServiceItems();

        foreach ($offer->installations as $installation) {
            $installation->update([
                'material_items' => $materials,
                'service_items' => $services,
            ]);
        }
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $offer->delete();

        return redirect()->route('sales.offers.index')->with('success', 'Oferta stearsa.');
    }

    /**
     * Adaugă în catalogul de materiale o linie scrisă liber pe ofertă, apoi o
     * leagă de articolul nou creat. Astfel devizul alimentează singur catalogul.
     */
    public function storeItemInCatalog(Request $request, Offer $offer, OfferItem $item, EquipmentCatalogService $catalog): RedirectResponse
    {
        abort_unless($item->offer_id === $offer->id, 404);

        if ($item->equipment_id) {
            return back()->with('success', 'Linia este deja legată de un articol din catalog.');
        }

        $equipment = $catalog->addFromOfferItem($item, $request->user());

        return back()->with('success', "\"{$equipment->name}\" a fost adăugat în catalog.");
    }

    public function pdf(Offer $offer): HttpResponse
    {
        $offer->load(['client', 'chapters', 'items.equipment:id,name', 'items.service:id,name']);

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
            'vat_applied' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'chapters' => ['nullable', 'array', 'max:50'],
            'chapters.*.ref' => ['required_with:chapters', 'string', 'max:40'],
            'chapters.*.id' => ['nullable', 'integer', 'min:1'],
            'chapters.*.name' => ['required', 'string', 'max:100'],
            'chapters.*.position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'chapters.*.removed' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.section' => ['nullable', Rule::in(OfferItem::SECTIONS)],
            'items.*.equipment_id' => ['nullable', 'exists:equipment,id'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.chapter_ref' => ['nullable', 'string', 'max:40'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'items.*.save_to_catalog' => ['nullable', 'boolean'],
        ]);
    }

    private function prepareItems(array $items): array
    {
        return collect($items)->map(function (array $item): array {
            $section = $item['section'] ?? null;

            if (! in_array($section, OfferItem::SECTIONS, true)) {
                $section = ! empty($item['equipment_id'])
                    ? OfferItem::SECTION_MATERIALS
                    : OfferItem::SECTION_LABOR;
            }

            $isClientSupplied = $section === OfferItem::SECTION_CLIENT_MATERIALS;

            return [
                'equipment_id' => $isClientSupplied ? null : ($item['equipment_id'] ?? null),
                'service_id' => $isClientSupplied ? null : ($item['service_id'] ?? null),
                'chapter_ref' => $item['chapter_ref'] ?? null,
                'section' => $section,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? null,
                'unit_price' => $isClientSupplied ? 0 : ($item['unit_price'] ?? 0),
                'unit_cost' => $isClientSupplied ? null : ($item['unit_cost'] ?? null),
            ];
        })->all();
    }

    /** Indicii liniilor care trebuie salvate în catalog la salvarea devizului. */
    private function catalogIntents(array $items): array
    {
        return collect($items)
            ->filter(fn (array $item): bool => ! empty($item['save_to_catalog']))
            ->keys()
            ->all();
    }

    /**
     * Sincronizează capitolele în aceeași tranzacție cu oferta și returnează
     * corespondența dintre cheile temporare din formular și ID-urile reale.
     *
     * @param  array<int, array<string, mixed>>  $chapters
     * @return array<string, int|null>
     */
    private function syncChapters(Offer $offer, array $chapters): array
    {
        $existingIds = $offer->chapters()->pluck('id')->all();
        $refMap = [];

        foreach ($existingIds as $id) {
            $refMap[(string) $id] = (int) $id;
        }

        $removeIds = [];

        foreach ($chapters as $chapter) {
            $ref = (string) ($chapter['ref'] ?? '');
            $id = ! empty($chapter['id']) ? (int) $chapter['id'] : null;

            if (! empty($chapter['removed'])) {
                $refMap[$ref] = null;
                if ($id && in_array($id, $existingIds, true)) {
                    $removeIds[] = $id;
                }

                continue;
            }

            $attributes = [
                'name' => $chapter['name'],
                'position' => (int) ($chapter['position'] ?? 0),
            ];

            if ($id) {
                if (! in_array($id, $existingIds, true)) {
                    throw ValidationException::withMessages([
                        'chapters' => 'Un capitol selectat nu aparține acestui deviz.',
                    ]);
                }

                $offer->chapters()->whereKey($id)->update($attributes);
                $refMap[$ref] = $id;
                $refMap[(string) $id] = $id;

                continue;
            }

            $created = $offer->chapters()->create($attributes);
            $refMap[$ref] = (int) $created->id;
        }

        if ($removeIds !== []) {
            // Liniile mutate în „General” înainte de ștergerea capitolului.
            $offer->items()->whereIn('chapter_id', $removeIds)->update(['chapter_id' => null]);
            $offer->chapters()->whereIn('id', $removeIds)->delete();
        }

        return $refMap;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, int|null>  $refMap
     * @return array<int, array<string, mixed>>
     */
    private function withChapterIds(array $items, array $refMap): array
    {
        return collect($items)->map(function (array $item) use ($refMap): array {
            $ref = $item['chapter_ref'] ?? null;
            unset($item['chapter_ref']);
            $item['chapter_id'] = $ref ? ($refMap[(string) $ref] ?? null) : null;

            return $item;
        })->all();
    }

    /**
     * Articolele marcate „Salvează în catalog” se creează doar aici, în
     * tranzacția de salvare a devizului. O ofertă abandonată nu scrie nimic.
     *
     * @param  array<int, int>  $indexes
     */
    private function persistCatalogItems(Collection $createdItems, array $indexes, ?User $actor, Offer $offer, EquipmentCatalogService $catalog): void
    {
        foreach ($indexes as $index) {
            $item = $createdItems->get($index);

            if (! $item || $item->isClientSupplied() || $item->equipment_id || $item->service_id) {
                continue;
            }

            $data = [
                'name' => $item->description,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'cost_price' => $item->unit_cost,
            ];
            $metadata = ['offer_id' => $offer->id];

            if ($item->section === OfferItem::SECTION_MATERIALS) {
                $catalogItem = $catalog->findOrCreateMaterial($data, $actor, $metadata);
                $item->update(['equipment_id' => $catalogItem->id]);

                continue;
            }

            $catalogItem = $catalog->findOrCreateService($data, $actor, $metadata);
            $item->update(['service_id' => $catalogItem->id]);
        }
    }

    /** @return array<int, string> */
    private function chapterSuggestions(?Offer $offer = null): array
    {
        return OfferChapter::query()
            ->when($offer, fn ($query) => $query->where('offer_id', '!=', $offer->id))
            ->distinct()
            ->orderBy('name')
            ->limit(50)
            ->pluck('name')
            ->all();
    }

    private function totalFromItems(array $items): float
    {
        return round(collect($items)
            ->reject(fn (array $item): bool => $item['section'] === OfferItem::SECTION_CLIENT_MATERIALS)
            ->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']), 2);
    }
}
