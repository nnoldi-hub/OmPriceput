<?php

namespace App\Http\Controllers\Technical;

use App\Http\Controllers\Controller;
use App\Models\Equipment;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EquipmentController extends Controller
{
    public function index(Request $request): Response
    {
        $equipment = Equipment::query()
            ->with('supplier:id,name')
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->string('category')->toString(), fn ($query, $category) => $query->where('category', $category))
            ->when($request->boolean('low_stock'), fn ($query) => $query->whereColumn('stock_quantity', '<=', 'minimum_stock'))
            ->when($request->has('shop_visible'), fn ($query) => $query->where('is_visible_in_shop', $request->boolean('shop_visible')))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Technical/Equipment/Index', [
            'equipment' => $equipment,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only('search', 'category', 'low_stock', 'shop_visible'),
            'categories' => Equipment::CATEGORIES,
            'units' => Equipment::UNITS,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Technical/Equipment/Create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'categories' => Equipment::CATEGORIES,
            'units' => Equipment::UNITS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['image_path'] = $this->storeImage($request);

        if ($data['is_visible_in_shop']) {
            $data['slug'] = $this->uniqueSlug($data['name']);
        }

        Equipment::create($data);

        return redirect()->route('technical.equipment.index')->with('success', 'Echipament adaugat.');
    }

    public function edit(Equipment $equipment): Response
    {
        return Inertia::render('Technical/Equipment/Edit', [
            'equipment' => $equipment,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'categories' => Equipment::CATEGORIES,
            'units' => Equipment::UNITS,
        ]);
    }

    public function update(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $this->validateData($request);

        if ($image = $this->storeImage($request)) {
            $data['image_path'] = $image;
        }

        if ($data['is_visible_in_shop'] && ! $equipment->slug) {
            $data['slug'] = $this->uniqueSlug($data['name']);
        }

        $equipment->update($data);

        return redirect()->route('technical.equipment.index')->with('success', 'Articol actualizat.');
    }

    public function adjustStock(Request $request, Equipment $equipment): RedirectResponse
    {
        $data = $request->validate([
            'delta' => ['required', 'integer'],
        ]);

        $equipment->update([
            'stock_quantity' => max(0, $equipment->stock_quantity + $data['delta']),
        ]);

        return back()->with('success', 'Stoc actualizat.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $equipment->delete();

        return redirect()->route('technical.equipment.index')->with('success', 'Articol sters din stoc.');
    }

    private function validateData(Request $request): array
    {
        $request->merge([
            'cost_price' => $request->input('cost_price', 0),
            'markup_percent' => $request->input('markup_percent', 0),
            'minimum_stock' => $request->input('minimum_stock', 5),
            'is_active' => $request->boolean('is_active', true),
            'is_visible_in_shop' => $request->boolean('is_visible_in_shop', false),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(Equipment::CATEGORIES))],
            'sku' => ['nullable', 'string', 'max:100'],
            'unit' => ['required', Rule::in(Equipment::UNITS)],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'markup_percent' => ['required', 'numeric', 'min:0', 'max:1000'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
            'is_visible_in_shop' => ['required', 'boolean'],
            'shop_description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        return $request->file('image')->store('equipment', 'public');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Equipment::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
