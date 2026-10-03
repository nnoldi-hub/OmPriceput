<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Equipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DiscountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Shop/Discounts/Index', [
            'discounts' => Discount::with('equipment:id,name')->latest()->paginate(15),
            'categories' => Equipment::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Shop/Discounts/Create', [
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'category']),
            'categories' => Equipment::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Discount::create($this->validateData($request));

        return redirect()->route('admin.discounts.index')->with('success', 'Reducere adaugata.');
    }

    public function edit(Discount $discount): Response
    {
        return Inertia::render('Admin/Shop/Discounts/Edit', [
            'discount' => $discount,
            'equipment' => Equipment::orderBy('name')->get(['id', 'name', 'category']),
            'categories' => Equipment::CATEGORIES,
        ]);
    }

    public function update(Request $request, Discount $discount): RedirectResponse
    {
        $discount->update($this->validateData($request));

        return redirect()->route('admin.discounts.index')->with('success', 'Reducere actualizata.');
    }

    public function destroy(Discount $discount): RedirectResponse
    {
        $discount->delete();

        return redirect()->route('admin.discounts.index')->with('success', 'Reducere stearsa.');
    }

    private function validateData(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active', true)]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:product,category'],
            'equipment_id' => ['nullable', 'required_if:scope,product', 'exists:equipment,id'],
            'category' => ['nullable', 'required_if:scope,category', Rule::in(array_keys(Equipment::CATEGORIES))],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
