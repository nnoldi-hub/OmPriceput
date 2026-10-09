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
        'section',
        'description',
        'quantity',
        'unit',
        'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
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

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function getSubtotalAttribute(): float
    {
        if ($this->isClientSupplied()) {
            return 0.0;
        }

        return (float) $this->quantity * (float) $this->unit_price;
    }
}
