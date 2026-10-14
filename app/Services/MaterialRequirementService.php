<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Offer;
use App\Models\OfferItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Leagă ofertele acceptate și programările viitoare de catalogul de materiale,
 * calculând necesarul pe articol și ce mai trebuie cumpărat după stoc.
 */
class MaterialRequirementService
{
    /**
     * @param  array{installation_id?: int|null, from?: CarbonInterface|null, to?: CarbonInterface|null, only_missing?: bool, include_unscheduled?: bool, supplier_id?: int|null}  $filters
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, float|int>, meta: array<string, mixed>}
     */
    public function build(array $filters = []): array
    {
        $installationId = $filters['installation_id'] ?? null;
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;
        $includeUnscheduled = (bool) ($filters['include_unscheduled'] ?? true);

        $lines = $this->collectLines($installationId, $from, $to, $includeUnscheduled);
        $rows = $this->aggregate($lines);

        if (! empty($filters['only_missing'])) {
            $rows = $rows->where('to_buy', '>', 0)->values();
        }

        if (! empty($filters['supplier_id'])) {
            $rows = $rows->where('supplier_id', (int) $filters['supplier_id'])->values();
        }

        return [
            'rows' => $rows->values()->all(),
            'totals' => $this->totals($rows),
            'meta' => [
                'installation_id' => $installationId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'include_unscheduled' => $includeUnscheduled,
                'period_label' => $this->periodLabel($from, $to, $installationId),
            ],
        ];
    }

    /**
     * Liniile brute de material, din programări active și din oferte acceptate încă neprogramate.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function collectLines(?int $installationId, ?CarbonInterface $from, ?CarbonInterface $to, bool $includeUnscheduled): Collection
    {
        $lines = collect();

        $installations = Installation::query()
            ->with([
                'client:id,name',
                'offer.items.equipment:id,name,unit,unit_price,cost_price,supplier_id',
            ])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->when($installationId, fn ($query) => $query->whereKey($installationId))
            ->when(! $installationId && $from && $to, function ($query) use ($from, $to, $includeUnscheduled) {
                $query->where(function ($q) use ($from, $to, $includeUnscheduled) {
                    $q->whereBetween('scheduled_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
                    if ($includeUnscheduled) {
                        $q->orWhereNull('scheduled_at');
                    }
                });
            })
            ->get();

        foreach ($installations as $installation) {
            $label = $installation->client?->name ?? 'Programare #'.$installation->id;

            if ($installation->offer_id && $installation->offer) {
                foreach ($installation->offer->items as $item) {
                    if ($item->section !== OfferItem::SECTION_MATERIALS) {
                        continue;
                    }

                    $lines->push([
                        'equipment_id' => $item->equipment_id ? (int) $item->equipment_id : null,
                        'offer_item_id' => $item->id,
                        'name' => $item->equipment?->name ?? $item->description,
                        'unit' => $item->unit ?: ($item->equipment?->unit ?? 'buc'),
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'source' => 'installation',
                        'label' => $label,
                        'date' => $installation->scheduled_at,
                        'installation_id' => $installation->id,
                        'offer_id' => $installation->offer_id,
                    ]);
                }

                continue;
            }

            foreach ($installation->material_items ?? [] as $item) {
                $lines->push([
                    'equipment_id' => ! empty($item['equipment_id']) ? (int) $item['equipment_id'] : null,
                    'offer_item_id' => null,
                    'name' => (string) ($item['name'] ?? ''),
                    'unit' => (string) ($item['unit'] ?? 'buc'),
                    'quantity' => (float) ($item['quantity'] ?? 0),
                    'unit_price' => null,
                    'source' => 'installation',
                    'label' => $label,
                    'date' => $installation->scheduled_at,
                    'installation_id' => $installation->id,
                    'offer_id' => $installation->offer_id,
                ]);
            }
        }

        if (! $installationId) {
            $offers = Offer::query()
                ->where('status', 'accepted')
                ->whereDoesntHave('installations')
                ->with(['client:id,name', 'items.equipment:id,name,unit'])
                ->get();

            foreach ($offers as $offer) {
                foreach ($offer->items as $item) {
                    if ($item->section !== OfferItem::SECTION_MATERIALS) {
                        continue;
                    }
                    $lines->push([
                        'equipment_id' => $item->equipment_id ? (int) $item->equipment_id : null,
                        'offer_item_id' => $item->id,
                        'name' => $item->equipment?->name ?? $item->description,
                        'unit' => $item->unit ?: ($item->equipment?->unit ?? 'buc'),
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'source' => 'offer',
                        'label' => ($offer->client?->name ?? 'Oferta').' (oferta #'.$offer->id.')',
                        'date' => $offer->created_at,
                        'installation_id' => null,
                        'offer_id' => $offer->id,
                    ]);
                }
            }
        }

        return $lines;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $lines
     * @return Collection<int, array<string, mixed>>
     */
    private function aggregate(Collection $lines): Collection
    {
        $equipmentIds = $lines->pluck('equipment_id')->filter()->unique()->values();
        $equipment = $equipmentIds->isEmpty()
            ? collect()
            : Equipment::query()->with('supplier:id,name')->whereIn('id', $equipmentIds)->get()->keyBy('id');

        return $lines
            ->groupBy(fn (array $line) => $line['equipment_id']
                ? 'eq:'.$line['equipment_id']
                : 'free:'.mb_strtolower(trim($line['name'])))
            ->map(function (Collection $group, string $key) use ($equipment) {
                $first = $group->first();
                $record = $first['equipment_id'] ? $equipment->get($first['equipment_id']) : null;

                $needed = round((float) $group->sum('quantity'), 2);
                $inStock = $record ? (float) $record->stock_quantity : 0.0;
                $toBuy = max(0, round($needed - $inStock, 2));
                $unitCost = $record ? (float) ($record->cost_price ?? 0) : 0.0;
                $unitPrice = $record
                    ? (float) $record->unit_price
                    : (float) $group->pluck('unit_price')->filter()->first();

                return [
                    'key' => $key,
                    'equipment_id' => $first['equipment_id'],
                    'name' => $record?->name ?? $first['name'],
                    'unit' => $first['unit'] ?: ($record?->unit ?? 'buc'),
                    'needed' => $needed,
                    'in_stock' => round($inStock, 2),
                    'to_buy' => $toBuy,
                    'unit_cost' => $unitCost,
                    'unit_price' => round($unitPrice, 2),
                    'estimated_cost' => round($toBuy * $unitCost, 2),
                    'supplier_id' => $record?->supplier_id,
                    'supplier_name' => $record?->supplier?->name,
                    'in_catalog' => (bool) $record,
                    'sources' => $group->map(fn (array $line) => [
                        'label' => $line['label'],
                        'quantity' => round((float) $line['quantity'], 2),
                        'date' => $line['date']?->toDateString(),
                        'installation_id' => $line['installation_id'],
                        'offer_id' => $line['offer_id'],
                        'offer_item_id' => $line['offer_item_id'] ?? null,
                    ])->values()->all(),
                ];
            })
            ->sortBy('name')
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return array<string, float|int>
     */
    private function totals(Collection $rows): array
    {
        return [
            'lines' => $rows->count(),
            'to_buy_lines' => $rows->where('to_buy', '>', 0)->count(),
            'needed' => round((float) $rows->sum('needed'), 2),
            'to_buy' => round((float) $rows->sum('to_buy'), 2),
            'estimated_cost' => round((float) $rows->sum('estimated_cost'), 2),
        ];
    }

    private function periodLabel(?CarbonInterface $from, ?CarbonInterface $to, ?int $installationId): string
    {
        if ($installationId) {
            return 'Lucrare selectată';
        }

        if ($from && $to) {
            return $from->format('d.m.Y').' - '.$to->format('d.m.Y');
        }

        return 'Toate programările viitoare';
    }
}
