<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\OfferItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Transformă o linie scrisă liber pe un deviz într-un articol de catalog și
 * leagă toate liniile identice din ofertă de articolul nou creat.
 */
class EquipmentCatalogService
{
    public function addFromOfferItem(OfferItem $item, ?User $actor = null): Equipment
    {
        return DB::transaction(function () use ($item, $actor) {
            $equipment = Equipment::create([
                'name' => $item->description,
                'category' => 'consumabil',
                'unit' => $this->normalizeUnit($item->unit),
                'unit_price' => $item->unit_price ?? 0,
                'cost_price' => 0,
                'markup_percent' => 0,
                'stock_quantity' => 0,
                'is_active' => true,
            ]);

            $description = mb_strtolower(trim((string) $item->description));

            OfferItem::query()
                ->where('offer_id', $item->offer_id)
                ->whereNull('equipment_id')
                ->where('section', OfferItem::SECTION_MATERIALS)
                ->get()
                ->filter(fn (OfferItem $line) => mb_strtolower(trim((string) $line->description)) === $description)
                ->each(fn (OfferItem $line) => $line->update(['equipment_id' => $equipment->id]));

            AuditLog::record(
                $actor,
                'equipment.created_from_offer',
                "Articolul \"{$equipment->name}\" a fost adăugat în catalog din oferta #{$item->offer_id}.",
                $equipment,
                ['offer_id' => $item->offer_id],
            );

            return $equipment;
        });
    }

    public function normalizeUnit(?string $unit): string
    {
        $unit = mb_strtolower(trim((string) $unit));

        $aliases = [
            'mp' => 'm2',
            'm²' => 'm2',
            'metri' => 'metru',
            'ml' => 'metru',
            'm' => 'metru',
            'bucata' => 'buc',
            'bucati' => 'buc',
            'bucăți' => 'buc',
            'l' => 'litru',
            'litri' => 'litru',
            'ore' => 'ora',
        ];

        $unit = $aliases[$unit] ?? $unit;

        return in_array($unit, Equipment::UNITS, true) ? $unit : 'buc';
    }
}
