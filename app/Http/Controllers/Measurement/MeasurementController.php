<?php

namespace App\Http\Controllers\Measurement;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
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
        $measurements = Measurement::with(['client:id,name', 'project:id,name', 'objects'])
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
        $measurement->load(['client:id,name', 'project:id,name', 'objects']);

        $area = $measurement->totalArea();
        $perimeter = $measurement->totalPerimeter();
        $areaPrice = (float) (Setting::get('pricing_area_m2') ?? '0');
        $perimeterPrice = (float) (Setting::get('pricing_perimeter_ml') ?? '0');

        return Inertia::render('Measurements/Show', [
            'measurement' => $this->toArray($measurement),
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Measurement::STATUS_LABELS,
            'pricing' => [
                'area_m2' => $areaPrice,
                'perimeter_ml' => $perimeterPrice,
                'estimate' => round($area * $areaPrice + $perimeter * $perimeterPrice, 2),
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
            'objects' => $measurement->objects->map(fn (MeasurementObject $object) => [
                'id' => $object->id,
                'name' => $object->name,
                'length' => $object->length,
                'width' => $object->width,
                'height' => $object->height,
                'quantity' => $object->quantity,
                'notes' => $object->notes,
                'area' => $object->area(),
                'perimeter' => $object->perimeter(),
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
            'length' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'width' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'height' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return $request->only(['name', 'length', 'width', 'height', 'quantity', 'notes']);
    }

    private function hasStatusChange(Request $request, Measurement $measurement): bool
    {
        return $request->has('status') && $request->input('status') !== $measurement->status;
    }
}
