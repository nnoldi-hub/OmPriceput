<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\OfferItem;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Transformă liniile scrise liber pe un deviz în articole de catalog și le
 * leagă de linia corespunzătoare. Dedublarea ignoră majusculele și diacriticele.
 */
class EquipmentCatalogService
{
    public function addFromOfferItem(OfferItem $item, ?User $actor = null): Equipment
    {
        return DB::transaction(function () use ($item, $actor) {
            $equipment = $this->findOrCreateMaterial([
                'name' => (string) $item->description,
                'unit' => $item->unit,
                'unit_price' => $item->unit_price,
                'cost_price' => $item->unit_cost,
            ], $actor, ['offer_id' => $item->offer_id]);

            $description = self::normalizeName((string) $item->description);

            OfferItem::query()
                ->where('offer_id', $item->offer_id)
                ->whereNull('equipment_id')
                ->where('section', OfferItem::SECTION_MATERIALS)
                ->get()
                ->filter(fn (OfferItem $line) => self::normalizeName((string) $line->description) === $description)
                ->each(fn (OfferItem $line) => $line->update(['equipment_id' => $equipment->id]));

            return $equipment;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public function findOrCreateMaterial(array $data, ?User $actor = null, array $metadata = []): Equipment
    {
        $name = trim((string) ($data['name'] ?? ''));
        $normalized = self::normalizeName($name);

        $equipment = Equipment::query()
            ->get(['id', 'name'])
            ->first(fn (Equipment $existing) => self::normalizeName($existing->name) === $normalized);

        if (! $equipment) {
            $equipment = Equipment::create([
                'name' => $name,
                'category' => 'consumabil',
                'unit' => $this->normalizeUnit($data['unit'] ?? null, Equipment::UNITS, 'buc'),
                'unit_price' => round((float) ($data['unit_price'] ?? 0), 2),
                'cost_price' => round((float) ($data['cost_price'] ?? 0), 2),
                'markup_percent' => 0,
                'stock_quantity' => 0,
                'is_active' => true,
            ]);

            $this->recordCreation(
                $actor,
                'equipment.created_from_offer',
                "Articolul \"{$equipment->name}\" a fost adăugat în catalog din deviz.",
                $equipment,
                $metadata,
            );
        }

        return $equipment;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $metadata
     */
    public function findOrCreateService(array $data, ?User $actor = null, array $metadata = []): Service
    {
        $name = trim((string) ($data['name'] ?? ''));
        $normalized = self::normalizeName($name);

        $service = Service::query()
            ->get(['id', 'name'])
            ->first(fn (Service $existing) => self::normalizeName($existing->name) === $normalized);

        if (! $service) {
            $service = Service::create([
                'name' => $name,
                'category' => 'general',
                'unit' => $this->normalizeUnit($data['unit'] ?? null, Service::UNITS, 'ora'),
                'cost_price' => round((float) ($data['cost_price'] ?? 0), 2),
                'sale_price' => round((float) ($data['unit_price'] ?? 0), 2),
                'duration_minutes' => 60,
                'is_active' => true,
            ]);

            $this->recordCreation(
                $actor,
                'service.created_from_offer',
                "Serviciul \"{$service->name}\" a fost adăugat în catalog din deviz.",
                $service,
                $metadata,
            );
        }

        return $service;
    }

    /** Comparație folosită la dedublarea articolelor: fără majuscule și fără diacritice. */
    public static function normalizeName(?string $name): string
    {
        $name = Str::transliterate(Str::lower(trim((string) $name)), '');

        return (string) preg_replace('/[^a-z0-9]+/', '', $name);
    }

    /**
     * @param  array<int, string>  $allowed
     * @param  array<string, mixed>  $metadata
     */
    private function recordCreation(?User $actor, string $action, string $description, Model $model, array $metadata): void
    {
        AuditLog::record($actor, $action, $description, $model, $metadata);
    }

    /** @param  array<int, string>  $allowed */
    public function normalizeUnit(?string $unit, array $allowed = Equipment::UNITS, string $fallback = 'buc'): string
    {
        $unit = Str::transliterate(Str::lower(trim((string) $unit)), '');

        $aliases = [
            'mp' => 'm2',
            'm²' => 'm2',
            'metri' => 'metru',
            'ml' => 'metru',
            'm' => 'metru',
            'bucata' => 'buc',
            'bucati' => 'buc',
            'l' => 'litru',
            'litri' => 'litru',
            'ore' => 'ora',
            'vizite' => 'vizita',
        ];

        $unit = $aliases[$unit] ?? $unit;

        return in_array($unit, $allowed, true) ? $unit : $fallback;
    }
}
