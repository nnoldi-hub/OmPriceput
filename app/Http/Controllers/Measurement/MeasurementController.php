<?php

namespace App\Http\Controllers\Measurement;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\PriceItem;
use App\Models\Project;
use App\Models\Setting;
use App\Support\ImageOptimizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MeasurementController extends Controller
{
    public function index(): Response
    {
        $measurements = Measurement::with(['client:id,name', 'project:id,name', 'objects', 'offer:id,measurement_id,title,total_amount'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Measurement $measurement) => $this->toArray($measurement));

        return Inertia::render('Measurements/Index', [
            'measurements' => $measurements,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Measurement::STATUS_LABELS,
            'measurementsCount' => $measurements->count(),
        ]);
    }

    public function show(Measurement $measurement): Response
    {
        $measurement->load(['client:id,name', 'project:id,name', 'objects', 'offer:id,measurement_id,title,total_amount']);

        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        return Inertia::render('Measurements/Show', [
            'measurement' => $this->toArray($measurement),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'projects' => Project::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Measurement::STATUS_LABELS,
            'objectTypes' => MeasurementObject::TYPES,
            'fieldDefs' => MeasurementObject::FIELDS,
            'pricing' => [
                'area_m2' => $areaPrice,
                'perimeter_ml' => $perimeterPrice,
                'estimate' => $this->estimate($measurement),
                'type_prices' => PriceItem::query()
                    ->where('is_active', true)
                    ->orderBy('position')
                    ->get(['object_type', 'price'])
                    ->mapWithKeys(fn (PriceItem $item) => [$item->object_type => (float) $item->price]),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, false);

        $measurement = Measurement::create($data);

        return redirect()->back()->with('success', 'Masuratoare creata.');
    }

    public function update(Request $request, Measurement $measurement): RedirectResponse
    {
        $data = $this->validated($request, true);

        if ($this->hasStatusChange($request, $measurement)) {
            $data['measured_at'] = $data['measured_at'] ?? now();
        }

        $measurement->update($data);

        return redirect()->back()->with('success', 'Masuratoare actualizata.');
    }

    public function destroy(Measurement $measurement): RedirectResponse
    {
        $measurement->delete();

        return redirect()->back()->with('success', 'Masuratoare stearsa.');
    }

    public function storeObject(Request $request, Measurement $measurement): RedirectResponse
    {
        $data = $this->validatedObject($request);
        $data['position'] = (int) $measurement->objects()->max('position') + 1;

        $measurement->objects()->create($data);

        return redirect()->back()->with('success', 'Obiect adaugat.');
    }

    public function updateObject(Request $request, MeasurementObject $object): RedirectResponse
    {
        $object->update($this->validatedObject($request));

        return redirect()->back()->with('success', 'Obiect actualizat.');
    }

    public function destroyObject(MeasurementObject $object): RedirectResponse
    {
        $object->delete();

        return redirect()->back()->with('success', 'Obiect sters.');
    }

    public function storePhoto(Request $request, Measurement $measurement): RedirectResponse
    {
        Validator::make($request->all(), [
            'photos' => ['required', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,webp,gif', 'max:10240'],
        ])->validate();

        $optimizer = app(ImageOptimizer::class);
        $photos = $measurement->photos ?? [];

        foreach ($request->file('photos', []) as $photo) {
            $photos[] = Storage::disk('public')->url($optimizer->store($photo, 'measurements', 'public'));
        }

        $measurement->update(['photos' => array_values($photos)]);

        return redirect()->back()->with('success', 'Poze adaugate.');
    }

    public function destroyPhoto(Request $request, Measurement $measurement): RedirectResponse
    {
        Validator::make($request->all(), [
            'photo' => ['required', 'string'],
        ])->validate();

        $url = $request->input('photo');
        $photos = $measurement->photos ?? [];

        if (($key = array_search($url, $photos, true)) !== false) {
            Storage::disk('public')->delete($this->photoPath($url));
            unset($photos[$key]);
            $measurement->update(['photos' => array_values($photos)]);
        }

        return redirect()->back()->with('success', 'Poza stearsa.');
    }

    public function createOffer(Request $request, Measurement $measurement): RedirectResponse
    {
        if ($existing = $measurement->offer) {
            return redirect()->route('sales.offers.show', $existing)
                ->with('success', 'Masuratoarea are deja o oferta.');
        }

        $clientId = $measurement->client_id;
        if ($request->filled('client_id')) {
            $clientId = (int) $request->input('client_id');
        }

        if (! $clientId || ! Client::whereKey($clientId)->exists()) {
            return back()->with('error', 'Atribuie mai intai un client masuratorii si salveaza detaliile.');
        }

        $measurement->load('objects');
        $items = $this->offerItemsFromMeasurement($measurement);

        if (empty($items)) {
            return back()->with('error', 'Adauga macar un obiect cu dimensiuni inainte de a genera oferta.');
        }

        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        $offer = Offer::create([
            'client_id' => $clientId,
            'user_id' => $request->user()->id,
            'measurement_id' => $measurement->id,
            'project_id' => $measurement->project_id,
            'title' => 'Oferta - '.$measurement->name,
            'job_type' => 'instalare',
            'status' => 'draft',
            'valid_until' => now()->addDays(Offer::DEFAULT_VALIDITY_DAYS)->toDateString(),
            'notes' => 'Generata din masuratoarea #'.$measurement->id
                .($measurement->location ? ' - '.$measurement->location : ''),
            'total_amount' => $this->offerTotal($items),
        ]);

        $offer->items()->createMany($items);

        return redirect()->route('sales.offers.show', $offer)
            ->with('success', 'Oferta creata din masuratoare.');
    }

    private function offerItemsFromMeasurement(Measurement $measurement): array
    {
        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        $items = [];
        $hasRoute = false;

        foreach ($measurement->objects as $object) {
            $type = $object->object_type;
            $price = PriceItem::priceFor($type);

            if ($type === 'camera' || $object->unit === 'mp') {
                if ($object->area() > 0) {
                    $dims = ($object->length && $object->width)
                        ? sprintf(' (%.1f x %.1f m)', (float) $object->length, (float) $object->width)
                        : '';
                    $items[] = [
                        'section' => OfferItem::SECTION_LABOR,
                        'description' => 'Montaj suprafata - '.$object->name.$dims,
                        'quantity' => max(1, (int) round($object->area())),
                        'unit' => 'mp',
                        'unit_price' => $price ?? $areaPrice,
                    ];
                } else {
                    $items[] = [
                        'section' => OfferItem::SECTION_LABOR,
                        'description' => $object->name,
                        'quantity' => max(1, (int) $object->quantity),
                        'unit' => 'buc',
                        'unit_price' => 0.0,
                    ];
                }

                continue;
            }

            $attrs = $object->attributesSummary();
            $suffix = $attrs !== '' ? ' ('.$attrs.')' : '';

            if ($object->unit === 'ml') {
                $hasRoute = true;
                $items[] = [
                    'section' => OfferItem::SECTION_LABOR,
                    'description' => $object->typeLabel().' - '.$object->name.$suffix,
                    'quantity' => max(1, (int) round($object->unitQuantity())),
                    'unit' => 'ml',
                    'unit_price' => $price ?? $perimeterPrice,
                ];

                continue;
            }

            $items[] = [
                'section' => OfferItem::SECTION_LABOR,
                'description' => $object->typeLabel().' - '.$object->name.$suffix,
                'quantity' => max(1, (int) $object->quantity),
                'unit' => 'buc',
                'unit_price' => $price ?? 0.0,
            ];
        }

        if (! $hasRoute && $measurement->totalPerimeter() > 0) {
            $items[] = [
                'section' => OfferItem::SECTION_LABOR,
                'description' => 'Cablaj retea (perimetru)',
                'quantity' => max(1, (int) round($measurement->totalPerimeter())),
                'unit' => 'ml',
                'unit_price' => $perimeterPrice,
            ];
        }

        return $items;
    }

    private function estimate(Measurement $measurement): float
    {
        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        $total = $measurement->totalArea() * $areaPrice + $measurement->totalPerimeter() * $perimeterPrice;

        foreach ($measurement->objects as $object) {
            if ($object->object_type === 'camera') {
                continue;
            }

            $price = PriceItem::priceFor($object->object_type)
                ?? ($object->unit === 'ml' ? $perimeterPrice : 0.0);

            $total += $object->unitQuantity() * $price;
        }

        return round($total, 2);
    }

    private function offerTotal(array $items): float
    {
        return round(collect($items)->sum(
            fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']
        ), 2);
    }

    private function photoPath(string $url): string
    {
        $base = Storage::disk('public')->url('/');

        if ($base !== '/' && str_starts_with($url, $base)) {
            return substr($url, strlen($base));
        }

        if (str_starts_with($url, '/storage/')) {
            return substr($url, strlen('/storage/'));
        }

        return $url;
    }

    private function toArray(Measurement $measurement): array
    {
        return [
            'id' => $measurement->id,
            'name' => $measurement->name,
            'location' => $measurement->location,
            'status' => $measurement->status,
            'status_label' => $measurement->statusLabel(),
            'client_id' => $measurement->client_id,
            'client_name' => $measurement->client?->name,
            'project_name' => $measurement->project?->name,
            'measured_at' => $measurement->measured_at?->toDateString(),
            'total_area' => $measurement->totalArea(),
            'total_perimeter' => $measurement->totalPerimeter(),
            'objects_count' => $measurement->objects->count(),
            'notes' => $measurement->notes,
            'photos' => $measurement->photos ?? [],
            'offer' => $measurement->offer ? [
                'id' => $measurement->offer->id,
                'title' => $measurement->offer->title,
                'total_amount' => (float) $measurement->offer->total_amount,
            ] : null,
            'objects' => $measurement->objects->map(fn (MeasurementObject $object) => [
                'id' => $object->id,
                'name' => $object->name,
                'object_type' => $object->object_type,
                'type_label' => $object->typeLabel(),
                'unit' => $object->unit,
                'length' => $object->length,
                'width' => $object->width,
                'height' => $object->height,
                'quantity' => $object->quantity,
                'specs' => (object) ($object->specs ?? []),
                'notes' => $object->notes,
                'area' => $object->area(),
                'perimeter' => $object->perimeter(),
                'unit_quantity' => $object->unitQuantity(),
                'attributes_summary' => $object->attributesSummary(),
            ]),
        ];
    }

    private function validated(Request $request, bool $isUpdate): array
    {
        Validator::make($request->all(), [
            'name' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'status' => ['nullable', Rule::in(Measurement::STATUSES)],
            'notes' => ['nullable', 'string'],
            'measured_at' => ['nullable', 'date'],
        ])->validate();

        return $request->only(['name', 'location', 'client_id', 'project_id', 'status', 'notes', 'measured_at']);
    }

    private function validatedObject(Request $request): array
    {
        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'object_type' => ['nullable', Rule::in(array_keys(MeasurementObject::TYPES))],
            'length' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
            'specs' => ['nullable', 'array'],
            'specs.*' => ['nullable'],
        ])->validate();

        $data = $request->only(['name', 'length', 'width', 'height', 'quantity', 'notes']);
        $type = $request->input('object_type') ?: 'camera';

        $data['object_type'] = $type;
        $data['unit'] = MeasurementObject::TYPES[$type]['unit'] ?? 'buc';

        if ($request->has('specs')) {
            $data['specs'] = $request->input('specs') ?: null;
        }

        return $data;
    }

    private function hasStatusChange(Request $request, Measurement $measurement): bool
    {
        return $request->has('status') && $request->input('status') !== $measurement->status;
    }
}
