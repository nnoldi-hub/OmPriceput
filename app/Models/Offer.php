<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Offer extends Model
{
    use HasFactory;

    public const DEFAULT_VALIDITY_DAYS = 30;

    public const STATUSES = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

    public const STATUS_LABELS = [
        'draft' => 'Draft',
        'sent' => 'Trimisa',
        'accepted' => 'Acceptata',
        'rejected' => 'Respinsa',
        'expired' => 'Expirata',
    ];

    protected $fillable = [
        'client_id',
        'user_id',
        'visit_id',
        'measurement_id',
        'project_id',
        'title',
        'job_type',
        'status',
        'total_amount',
        'valid_until',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
    ];

    protected $appends = ['profitability_report'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Installation::class, 'visit_id');
    }

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(OfferChapter::class)->orderBy('position')->orderBy('id');
    }

    public function itemsForSection(?string $section = null): Collection
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();

        if ($section === null) {
            return $items;
        }

        return $items->where('section', $section)->values();
    }

    public function sectionSubtotal(string $section): float
    {
        if ($section === OfferItem::SECTION_CLIENT_MATERIALS) {
            return 0.0;
        }

        return round($this->itemsForSection($section)->sum('subtotal'), 2);
    }

    public function materialsSubtotal(): float
    {
        return $this->sectionSubtotal(OfferItem::SECTION_MATERIALS);
    }

    public function laborSubtotal(): float
    {
        return $this->sectionSubtotal(OfferItem::SECTION_LABOR);
    }

    public function pricedSubtotal(): float
    {
        return round($this->materialsSubtotal() + $this->laborSubtotal(), 2);
    }

    /** Zilele de valabilitate configurate; fallback la constanta istorică. */
    public static function validityDays(): int
    {
        $days = (int) (Setting::get('offer_validity_days', (string) self::DEFAULT_VALIDITY_DAYS) ?? self::DEFAULT_VALIDITY_DAYS);

        return $days > 0 ? $days : self::DEFAULT_VALIDITY_DAYS;
    }

    public static function defaultValidUntil(): string
    {
        return now()->startOfDay()->addDays(self::validityDays())->toDateString();
    }

    /** Data emiterii, folosită ca bază pentru calculul valabilității. */
    public function issueDate(): Carbon
    {
        return ($this->created_at ?? now())->copy()->startOfDay();
    }

    /**
     * Data explicită este păstrată, dar nu poate fi mai mică decât emiterea.
     * În lipsa ei se folosește emitere + zilele din setări.
     */
    public function validUntilDate(): Carbon
    {
        $issue = $this->issueDate();
        $validUntil = $this->valid_until?->copy()->startOfDay();

        if (! $validUntil || $validUntil->lt($issue)) {
            $validUntil = $issue->copy()->addDays(self::validityDays());
        }

        return $validUntil;
    }

    /**
     * Centralizatorul pe capitole. Capitolul „General” este virtual: toate
     * liniile vechi, fără chapter_id, rămân acolo și nu se pierd.
     *
     * @return array{chapters: array<int, array<string, mixed>>, materials: float, labor: float, subtotal: float, vat_percentage: float, is_vat_payer: bool, vat_amount: float, total_with_vat: float}
     */
    public function summaryByChapter(): array
    {
        $this->loadMissing('chapters', 'items');

        $chapters = $this->chapters->sortBy(['position', 'id'])->values();
        $rows = collect();

        // Secțiunea C este listă informativă și nu face parte din centralizator.
        $generalItems = $this->items->filter(fn (OfferItem $item) => $item->chapter_id === null && $item->isPriced());
        if ($generalItems->isNotEmpty() || $chapters->isEmpty()) {
            $rows->push($this->chapterSummaryRow(null, 'General', -1, $generalItems));
        }

        foreach ($chapters as $chapter) {
            $items = $this->items->filter(fn (OfferItem $item) => (int) $item->chapter_id === (int) $chapter->id);
            $rows->push($this->chapterSummaryRow((int) $chapter->id, $chapter->name, (int) $chapter->position, $items));
        }

        $subtotal = round((float) $rows->sum('total'), 2);
        $vatPercentage = round((float) (Setting::get('vat_percentage') ?? 0), 2);
        $vatAmount = $vatPercentage > 0 ? round($subtotal * $vatPercentage / 100, 2) : 0.0;

        return [
            'chapters' => $rows->all(),
            'materials' => round((float) $rows->sum('materials'), 2),
            'labor' => round((float) $rows->sum('labor'), 2),
            'subtotal' => $subtotal,
            'vat_percentage' => $vatPercentage,
            'is_vat_payer' => $vatPercentage > 0,
            'vat_amount' => $vatAmount,
            'total_with_vat' => round($subtotal + $vatAmount, 2),
        ];
    }

    /** @return array<string, mixed> */
    private function chapterSummaryRow(?int $id, string $name, int $position, Collection $items): array
    {
        $materials = round((float) $items->where('section', OfferItem::SECTION_MATERIALS)->sum('subtotal'), 2);
        $labor = round((float) $items->where('section', OfferItem::SECTION_LABOR)->sum('subtotal'), 2);

        return [
            'id' => $id,
            'name' => $name,
            'position' => $position,
            'line_count' => $items->count(),
            'materials' => $materials,
            'labor' => $labor,
            'total' => round($materials + $labor, 2),
        ];
    }

    /**
     * Lista curentă de materiale a devizului, folosită la generarea și
     * actualizarea programării („Necesar materiale”).
     *
     * @return array<int, array<string, mixed>>
     */
    public function installationMaterialItems(): array
    {
        $this->loadMissing('items.equipment');

        return $this->items
            ->where('section', OfferItem::SECTION_MATERIALS)
            ->values()
            ->map(fn (OfferItem $item) => [
                'equipment_id' => $item->equipment_id,
                'name' => $item->equipment?->name ?? $item->description,
                'unit' => $item->unit ?: ($item->equipment?->unit ?? 'buc'),
                'quantity' => (int) $item->quantity,
            ])->all();
    }

    /** @return array<int, array<string, mixed>> */
    public function installationServiceItems(): array
    {
        $this->loadMissing('items.service');

        return $this->items
            ->where('section', OfferItem::SECTION_LABOR)
            ->values()
            ->map(fn (OfferItem $item) => [
                'service_id' => $item->service_id,
                'name' => $item->service?->name ?? $item->description,
                'unit' => $item->unit ?: ($item->service?->unit ?? 'ora'),
                'quantity' => (int) $item->quantity,
            ])->all();
    }

    /** Datele de marjă pentru pagina internă; nu sunt incluse în PDF sau în vederea clientului. */
    public function lineMargins(): array
    {
        $this->loadMissing('items.equipment', 'items.service');

        return $this->items->mapWithKeys(fn (OfferItem $item) => [
            $item->id => [
                'cost' => $item->lineCost(),
                'margin_percent' => $item->marginPercent(),
                'uncatalogued' => $item->isUncatalogued(),
            ],
        ])->all();
    }

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class);
    }

    public function jobTypeLabel(): string
    {
        return Installation::TYPE_LABELS[$this->job_type] ?? $this->job_type;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getProfitabilityReportAttribute(): array
    {
        $this->loadMissing('items.equipment', 'items.service');
        $cost = 0.0;
        $uncostedItems = 0;

        foreach ($this->items as $item) {
            if ($item->isClientSupplied()) {
                continue;
            }
            $lineCost = $item->lineCost();
            if ($lineCost === null) {
                $uncostedItems++;

                continue;
            }
            $cost += $lineCost;
        }

        $value = (float) $this->total_amount;
        $profit = $value - $cost;

        return [
            'offer_value' => round($value, 2),
            'estimated_cost' => round($cost, 2),
            'estimated_profit' => round($profit, 2),
            'margin_percent' => $value > 0 ? round(($profit / $value) * 100, 2) : 0,
            'uncosted_items' => $uncostedItems,
            'minimum_margin_percent' => (float) Setting::get('minimum_profit_margin', '20'),
        ];
    }
}
