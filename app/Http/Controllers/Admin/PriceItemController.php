<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MeasurementObject;
use App\Models\PriceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PriceItemController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Pricing/Index', [
            'priceItems' => PriceItem::query()
                ->orderBy('position')
                ->orderBy('object_type')
                ->get(),
            'objectTypes' => MeasurementObject::TYPES,
            'units' => PriceItem::UNITS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['position'] ??= (int) PriceItem::max('position') + 1;

        PriceItem::create($data);

        return redirect()->back()->with('success', 'Tarif adaugat.');
    }

    public function update(Request $request, PriceItem $priceItem): RedirectResponse
    {
        $priceItem->update($this->validateData($request));

        return redirect()->back()->with('success', 'Tarif actualizat.');
    }

    public function destroy(PriceItem $priceItem): RedirectResponse
    {
        $priceItem->delete();

        return redirect()->back()->with('success', 'Tarif sters.');
    }

    private function validateData(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'object_type' => ['required', Rule::in(array_keys(MeasurementObject::TYPES))],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', Rule::in(PriceItem::UNITS)],
            'price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
