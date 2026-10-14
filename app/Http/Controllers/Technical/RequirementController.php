<?php

namespace App\Http\Controllers\Technical;

use App\Exports\ShoppingListExport;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Installation;
use App\Models\OfferItem;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Supplier;
use App\Services\EquipmentCatalogService;
use App\Services\MaterialRequirementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class RequirementController extends Controller
{
    public function __construct(private readonly MaterialRequirementService $requirements) {}

    public function index(Request $request): Response
    {
        $filters = $this->resolveFilters($request);
        $result = $this->requirements->build($filters);

        return Inertia::render('Technical/Requirements/Index', [
            'rows' => $result['rows'],
            'totals' => $result['totals'],
            'meta' => $result['meta'],
            'filters' => $this->filterPayload($request, $filters),
            'installations' => $this->installationOptions(),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function shopping(Request $request): Response
    {
        $filters = $this->resolveFilters($request);
        $result = $this->requirements->build($filters);

        $toBuy = collect($result['rows'])->where('to_buy', '>', 0)->values();

        return Inertia::render('Technical/Requirements/Shopping', [
            'groups' => $this->groupBySupplier($toBuy->all()),
            'totals' => $result['totals'],
            'meta' => $result['meta'],
            'filters' => $this->filterPayload($request, $filters),
            'installations' => $this->installationOptions(),
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $result = $this->requirements->build($filters);
        $rows = collect($result['rows'])->where('to_buy', '>', 0)->values()->all();

        return Pdf::loadView('pdfs.shopping-list', [
            'groups' => $this->groupBySupplier($rows),
            'totals' => $result['totals'],
            'meta' => $result['meta'],
            'settings' => Setting::allSettings(),
        ])->stream('lista-cumparaturi-'.now()->format('Y-m-d').'.pdf');
    }

    public function excel(Request $request)
    {
        $filters = $this->resolveFilters($request);
        $result = $this->requirements->build($filters);
        $rows = collect($result['rows'])->where('to_buy', '>', 0)->values()->all();

        return Excel::download(
            new ShoppingListExport($rows, $result['meta']),
            'lista-cumparaturi-'.now()->format('Y-m-d').'.xlsx'
        );
    }

    /**
     * Generează câte o comandă draft către furnizor pentru articolele de
     * cumpărat care au și articol în catalog, și furnizor asociat.
     */
    public function createPurchaseOrders(Request $request): RedirectResponse
    {
        $filters = $this->resolveFilters($request);
        $result = $this->requirements->build($filters);

        $toBuy = collect($result['rows'])->where('to_buy', '>', 0);
        $orderable = $toBuy
            ->filter(fn (array $row) => ! empty($row['equipment_id']) && ! empty($row['supplier_id']))
            ->values();
        $skipped = $toBuy->count() - $orderable->count();

        if ($orderable->isEmpty()) {
            return back()->with('error', 'Nu există materiale de comandat cu furnizor asociat. Leagă furnizori de articole sau cumpără manual cele fără furnizor.');
        }

        $created = 0;

        DB::transaction(function () use ($orderable, &$created, $result) {
            foreach ($orderable->groupBy('supplier_id') as $supplierId => $items) {
                $created++;
                $lines = $items->map(fn (array $row) => [
                    'equipment_id' => $row['equipment_id'],
                    'quantity' => $row['to_buy'],
                    'unit_cost' => $row['unit_cost'],
                ])->values()->all();

                $order = PurchaseOrder::create([
                    'supplier_id' => $supplierId,
                    'order_number' => 'PO-'.now()->format('YmdHis').'-'.str_pad((string) $created, 2, '0', STR_PAD_LEFT),
                    'status' => 'draft',
                    'notes' => 'Generata din lista de cumparat ('.$result['meta']['period_label'].').',
                    'total_amount' => round(collect($lines)->sum(fn (array $line) => $line['quantity'] * $line['unit_cost']), 2),
                ]);

                $order->items()->createMany($lines);

                AuditLog::record(
                    request()->user(),
                    'purchase_order.requirement_created',
                    "Comanda {$order->order_number} a fost generata din necesarul de materiale.",
                    $order,
                    ['items_count' => count($lines)],
                );
            }
        });

        $message = "Au fost generate {$created} comenzi draft către furnizori.";
        if ($skipped > 0) {
            $message .= " {$skipped} articole au fost ignorate (fără articol în catalog sau fără furnizor).";
        }

        return redirect()->route('technical.purchase-orders.index')->with('success', $message);
    }

    /**
     * Adaugă în catalog un material scris liber pe deviz, direct din lista de
     * necesar / cumpărat, apoi leagă liniile identice din ofertă.
     */
    public function storeItemInCatalog(Request $request, OfferItem $item, EquipmentCatalogService $catalog): RedirectResponse
    {
        if ($item->section !== OfferItem::SECTION_MATERIALS) {
            return back()->with('error', 'Doar materialele din deviz pot fi adăugate în catalog.');
        }

        if ($item->equipment_id) {
            return back()->with('success', 'Linia este deja legată de un articol din catalog.');
        }

        $equipment = $catalog->addFromOfferItem($item, $request->user());

        return back()->with('success', "\"{$equipment->name}\" a fost adăugat în catalog.");
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFilters(Request $request): array
    {
        $installationId = $request->integer('installation_id') ?: null;
        $showAll = $request->boolean('all');
        $from = $request->date('from');
        $to = $request->date('to');

        if (! $installationId && ! $showAll && ! $from && ! $to) {
            $from = Carbon::now()->startOfWeek();
            $to = Carbon::now()->endOfWeek();
        }

        return [
            'installation_id' => $installationId,
            'from' => $from,
            'to' => $to,
            'only_missing' => $request->boolean('only_missing'),
            'include_unscheduled' => $request->boolean('include_unscheduled', true),
            'supplier_id' => $request->integer('supplier_id') ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function filterPayload(Request $request, array $filters): array
    {
        return [
            'installation_id' => $filters['installation_id'],
            'from' => $filters['from']?->toDateString(),
            'to' => $filters['to']?->toDateString(),
            'only_missing' => $filters['only_missing'],
            'include_unscheduled' => $filters['include_unscheduled'],
            'supplier_id' => $filters['supplier_id'],
            'all' => $request->boolean('all'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function installationOptions(): array
    {
        return Installation::query()
            ->with('client:id,name')
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderByRaw('scheduled_at is null desc')
            ->orderBy('scheduled_at')
            ->limit(200)
            ->get(['id', 'client_id', 'type', 'scheduled_at'])
            ->map(fn (Installation $installation) => [
                'id' => $installation->id,
                'label' => ($installation->client?->name ?? 'Client').' · '.$installation->typeLabel()
                    .' · '.($installation->scheduled_at?->format('d.m.Y H:i') ?? 'de programat'),
            ])
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function groupBySupplier(array $rows): array
    {
        $groups = collect($rows)
            ->groupBy(fn (array $row) => $row['supplier_id'] ?? 0)
            ->map(function ($items, $supplierId) {
                $first = $items->first();

                return [
                    'supplier_id' => $supplierId ? (int) $supplierId : null,
                    'supplier_name' => $first['supplier_name'] ?? 'Fără furnizor asociat',
                    'items' => $items->values()->all(),
                    'estimated_cost' => round((float) $items->sum('estimated_cost'), 2),
                ];
            })
            ->sortBy(fn (array $group) => $group['supplier_id'] === null ? 'ZZZ' : $group['supplier_name'])
            ->values()
            ->all();

        return $groups;
    }
}
