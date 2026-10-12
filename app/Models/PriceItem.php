<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceItem extends Model
{
    use HasFactory;

    public const UNITS = ['mp', 'ml', 'buc'];

    protected $fillable = [
        'object_type', 'name', 'unit', 'price', 'is_active', 'position',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Pretul de manopera activ pentru un tip de obiect, sau null daca nu exista.
     */
    public static function priceFor(string $objectType): ?float
    {
        $item = static::query()
            ->where('object_type', $objectType)
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        return $item ? (float) $item->price : null;
    }

    public function typeLabel(): string
    {
        return MeasurementObject::TYPES[$this->object_type]['label'] ?? $this->object_type;
    }
}
