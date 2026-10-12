<?php

namespace App\Http\Controllers\Measurement;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Measurement;
use App\Models\MeasurementObject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->map(fn (Measurement $measurement) => [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'location' => $measurement->location,
                'status' => $measurement->status,
                'status_label' => $measurement->statusLabel(),
                'client_name' => $measurement->client?->name,
                'project_name' => $measurement->project?->name,
                'measured_at' => $measurement->measured_at?->toDateString(),
                'total_area' => $measurement->totalArea(),
                'total_perimeter' => $measurement->totalPerimeter(),
                'objects_count' => $measurement->objects->count(),
                'notes' => $measurement->notes,
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
            ]);

        return Inertia::render('Measurements/Index', [
            'measurements' => $measurements,
            'clients' => Client::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => Measurement::STATUS_LABELS,
            'measurementsCount' => $measurements->count(),
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
