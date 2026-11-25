<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferItem extends Model
{
    use HasFactory;

    public const SECTION_MATERIALS = 'materials';

    public const SECTION_LABOR = 'labor';

    public const SECTION_CLIENT_MATERIALS = 'client_materials';

    public const SECTIONS = [
        self::SECTION_MATERIALS,
        self::SECTION_LABOR,
        self::SECTION_CLIENT_MATERIALS,
    ];

    public const SECTION_LABELS = [
        self::SECTION_MATERIALS => 'Materiale necesare',
        self::SECTION_LABOR => 'Manopera',
        self::SECTION_CLIENT_MATERIALS => 'Materiale achizitionate de client',
    ];

    protected $fillable = [
        'offer_id',
        'equipment_id',
        'service_id',
        'chapter_id',
        'section',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'unit_cost',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (OfferItem $item): void {
            if (empty($item->section) || ! in_array($item->section, self::SECTIONS, true)) {
                $item->section = $item->equipment_id
                    ? self::SECTION_MATERIALS
                    : self::SECTION_LABOR;
            }
        });
    }

    public function isClientSupplied(): bool
    {
        return $this->section === self::SECTION_CLIENT_MATERIALS;
    }

    public function isPriced(): bool
    {
        return ! $this->isClientSupplied();
    }

    public function sectionLabel(): string
    {
        return self::SECTION_LABELS[$this->section] ?? self::SECTION_LABELS[self::SECTION_LABOR];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(OfferChapter::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function isUncatalogued(): bool
    {
        return $this->isPriced() && ! $this->equipment_id && ! $this->service_id;
    }

    /** Costul unitar completat pe linie are prioritate față de cel din catalog. */
    public function unitCost(): ?float
    {
        if ($this->unit_cost !== null) {
            return round((float) $this->unit_cost, 2);
        }

        if ($this->equipment_id && $this->equipment?->cost_price !== null) {
            return round((float) $this->equipment->cost_price, 2);
        }

        if ($this->service_id && $this->service?->cost_price !== null) {
            return round((float) $this->service->cost_price, 2);
        }

        return null;
    }

    public function lineCost(): ?float
    {
        $unitCost = $this->unitCost();

        return $unitCost === null ? null : round((float) $this->quantity * $unitCost, 2);
    }

    /** Marja se calculează doar când există un cost completat. */
    public function marginPercent(): ?float
    {
        $value = $this->subtotal;
        $cost = $this->lineCost();

        if ($value <= 0 || $cost === null) {
            return null;
        }

        return round((($value - $cost) / $value) * 100, 2);
    }

    public function getSubtotalAttribute(): float
    {
        if ($this->isClientSupplied()) {
            return 0.0;
        }

        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }
}
