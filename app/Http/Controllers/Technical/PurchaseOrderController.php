<?php

namespace App\Http\Controllers\Technical;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Technical/PurchaseOrders/Index', [
            'orders' => PurchaseOrder::with('supplier:id,name')->withCount('items')->latest()->paginate(15),
        ]);
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $purchaseOrder->load(['supplier', 'items.equipment']);

        return Inertia::render('Technical/PurchaseOrders/Show', ['order' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($purchaseOrder->status === 'draft', 422, 'Doar comenzile draft pot fi editate.');

        return Inertia::render('Technical/PurchaseOrders/Edit', [
            'order' => $purchaseOrder->load('items'),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'equipment' => Equipment::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'unit']),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status === 'draft', 422, 'Doar comenzile draft pot fi editate.');
        $data = $this->validateOrder($request);
        DB::transaction(function () use ($request, $data, $purchaseOrder) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'],
                'ordered_at' => $data['ordered_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'total_amount' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_cost']),
            ]);
            $purchaseOrder->items()->delete();
            $purchaseOrder->items()->createMany($data['items']);
            $this->storeDocument($request, $purchaseOrder);
            AuditLog::record($request->user(), 'purchase_order.updated', "Comanda {$purchaseOrder->order_number} a fost actualizata.", $purchaseOrder);
        });

        return redirect()->route('technical.purchase-orders.show', $purchaseOrder);
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(in_array($purchaseOrder->status, ['draft', 'ordered', 'partially_received'], true), 422, 'Comanda nu poate fi anulata.');
        $purchaseOrder->update(['status' => 'cancelled']);
        AuditLog::record($request->user(), 'purchase_order.cancelled', "Comanda {$purchaseOrder->order_number} a fost anulata.", $purchaseOrder);

        return back()->with('success', 'Comanda a fost anulata.');
    }

    public function create(): Response
    {
        return Inertia::render('Technical/PurchaseOrders/Create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'equipment' => Equipment::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'cost_price', 'unit']),
        ]);
    }

    public function replenish(): RedirectResponse
    {
        $equipment = Equipment::query()
            ->with('supplier')
            ->where('is_active', true)
            ->lowStock()
            ->whereNotNull('supplier_id')
            ->get();

        $created = 0;
        DB::transaction(function () use ($equipment, &$created) {
            foreach ($equipment->groupBy('supplier_id') as $supplierId => $items) {
                $lines = $items->map(fn (Equipment $item) => [
                    'equipment_id' => $item->id,
                    'quantity' => max(((int) $item->minimum_stock * 2) - (int) $item->stock_quantity, 1),
                    'unit_cost' => $item->cost_price ?? 0,
                ])->values()->all();
                $order = PurchaseOrder::create([
                    'supplier_id' => $supplierId,
                    'order_number' => 'PO-'.now()->format('YmdHis').'-'.random_int(10, 99),
                    'status' => 'draft',
                    'notes' => 'Generata automat din recomandarea de reaprovizionare.',
                    'total_amount' => collect($lines)->sum(fn (array $line) => $line['quantity'] * $line['unit_cost']),
                ]);
                $order->items()->createMany($lines);
                $created++;
                AuditLog::record(request()->user(), 'purchase_order.replenishment_created', "Comanda recomandata {$order->order_number} a fost generata.", $order, ['items_count' => count($lines)]);
            }
        });

        return back()->with('success', $created
            ? "Au fost generate {$created} comenzi recomandate."
            : 'Nu exista materiale sub prag cu furnizor asociat.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateOrder($request);

        $order = DB::transaction(function () use ($data) {
            $order = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'],
                'order_number' => 'PO-'.now()->format('YmdHis').'-'.random_int(10, 99),
                'status' => 'ordered',
                'ordered_at' => $data['ordered_at'] ?? today(),
                'notes' => $data['notes'] ?? null,
                'supplier_invoice_number' => $data['supplier_invoice_number'] ?? null,
                'total_amount' => collect($data['items'])->sum(fn ($item) => $item['quantity'] * $item['unit_cost']),
            ]);
            $order->items()->createMany($data['items']);
            AuditLog::record(
                request()->user(),
                'purchase_order.created',
                "Comanda {$order->order_number} a fost creata.",
                $order,
                ['total_amount' => (float) $order->total_amount, 'items_count' => count($data['items'])],
            );

            return $order;
        });
        $this->storeDocument($request, $order);

        return redirect()->route('technical.purchase-orders.index')->with('success', "Comanda {$order->order_number} a fost creata.");
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'ordered_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:100'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.equipment_id' => ['required', 'exists:equipment,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function storeDocument(Request $request, PurchaseOrder $order): void
    {
        if (! $request->hasFile('document')) {
            return;
        }
        if ($order->document_path) {
            Storage::disk('public')->delete($order->document_path);
        }
        $order->update(['document_path' => $request->file('document')->store('purchase-order-documents', 'public')]);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->status !== 'received', 422, 'Comanda este deja receptionata.');

        $data = $request->validate(['quantities' => ['nullable', 'array'], 'quantities.*' => ['numeric', 'min:0']]);
        DB::transaction(function () use ($purchaseOrder, $data) {
            $purchaseOrder->load('items.equipment');
            $receivedTotal = 0;
            foreach ($purchaseOrder->items as $item) {
                $remaining = (float) $item->quantity - (float) $item->received_quantity;
                $quantity = array_key_exists($item->id, $data['quantities'] ?? [])
                    ? min($remaining, (float) $data['quantities'][$item->id])
                    : $remaining;
                if ($quantity > 0) {
                    $item->equipment->increment('stock_quantity', $quantity);
                    $item->equipment->update(['cost_price' => $item->unit_cost]);
                    $item->increment('received_quantity', $quantity);
                    $receivedTotal += $quantity * (float) $item->unit_cost;
                }
            }
            if ($receivedTotal > 0) {
                Expense::create([
                    'supplier_id' => $purchaseOrder->supplier_id,
                    'description' => 'Comanda '.$purchaseOrder->order_number,
                    'category' => 'material',
                    'amount' => $receivedTotal,
                    'expense_date' => today(),
                    'document_number' => $purchaseOrder->supplier_invoice_number ?: $purchaseOrder->order_number,
                    'document_path' => $purchaseOrder->document_path,
                    'notes' => 'Generata automat la receptionarea comenzii.',
                ]);
            }
            $complete = $purchaseOrder->items()->whereColumn('received_quantity', '<', 'quantity')->doesntExist();
            $purchaseOrder->update(['status' => $complete ? 'received' : 'partially_received', 'received_at' => $complete ? today() : null]);
            AuditLog::record(
                request()->user(),
                'purchase_order.received',
                "Recepție inregistrata pentru comanda {$purchaseOrder->order_number}.",
                $purchaseOrder,
                ['status' => $purchaseOrder->status, 'amount' => $receivedTotal],
            );
        });

        return back()->with('success', 'Recepția a fost înregistrată. Stocul și cheltuielile au fost actualizate.');
    }
}
