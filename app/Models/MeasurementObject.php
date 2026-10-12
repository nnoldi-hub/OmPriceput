<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementObject extends Model
{
    use HasFactory;

    /**
     * Tipuri de obiecte masurate. "fields" = dimensiuni fizice (coloane),
     * "attributes" = caracteristici specifice salvate in JSON.
     */
    public const TYPES = [
        'camera' => ['label' => 'Camera / suprafata', 'unit' => 'mp', 'fields' => ['length', 'width', 'height'], 'attributes' => []],
        'priza' => ['label' => 'Priza', 'unit' => 'buc', 'fields' => [], 'attributes' => []],
        'intrerupator' => ['label' => 'Intrerupator', 'unit' => 'buc', 'fields' => [], 'attributes' => []],
        'doza' => ['label' => 'Doza', 'unit' => 'buc', 'fields' => [], 'attributes' => []],
        'traseu' => ['label' => 'Traseu cablu', 'unit' => 'ml', 'fields' => ['length'], 'attributes' => []],
        'tablou' => ['label' => 'Tablou electric', 'unit' => 'buc', 'fields' => [], 'attributes' => ['sigurante', 'faze']],
        'suport' => ['label' => 'Suport / prindere', 'unit' => 'buc', 'fields' => [], 'attributes' => ['gauri', 'tip_perete', 'dibluri']],
        'altul' => ['label' => 'Altul', 'unit' => 'buc', 'fields' => [], 'attributes' => []],
    ];

    /**
     * Definitii campuri (dimensiuni + atribute) folosite pentru afisare si editare.
     */
    public const FIELDS = [
        'length' => ['label' => 'Lungime', 'unit' => 'm', 'type' => 'number'],
        'width' => ['label' => 'Latime', 'unit' => 'm', 'type' => 'number'],
        'height' => ['label' => 'Inaltime', 'unit' => 'm', 'type' => 'number'],
        'sigurante' => ['label' => 'Nr. sigurante', 'type' => 'number'],
        'faze' => ['label' => 'Faze', 'type' => 'select', 'options' => ['Monofazat', 'Trifazat']],
        'gauri' => ['label' => 'Nr. gauri', 'type' => 'number'],
        'tip_perete' => ['label' => 'Tip perete', 'type' => 'select', 'options' => ['Caramida', 'Beton', 'BCA', 'Rigips', 'Lemn', 'Piatra']],
        'dibluri' => ['label' => 'Dibluri', 'type' => 'text'],
    ];

    protected $fillable = [
        'measurement_id', 'name', 'object_type', 'unit', 'length', 'width', 'height', 'quantity', 'specs', 'notes', 'position',
    ];

    protected $casts = [
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'quantity' => 'integer',
        'specs' => 'array',
        'position' => 'integer',
    ];

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->object_type]['label'] ?? $this->object_type;
    }

    public function typeFields(): array
    {
        return self::TYPES[$this->object_type]['fields'] ?? [];
    }

    public function typeAttributes(): array
    {
        return self::TYPES[$this->object_type]['attributes'] ?? [];
    }

    /**
     * Suprafata in mp (lungime * latime * cantitate), doar pentru obiectele cu ambele dimensiuni.
     */
    public function area(): float
    {
        if ($this->length === null || $this->width === null) {
            return 0.0;
        }

        return round($this->length * $this->width * $this->quantity, 2);
    }

    /**
     * Perimetru in ml pentru obiectele cu ambele dimensiuni.
     */
    public function perimeter(): float
    {
        if ($this->length === null || $this->width === null) {
            return 0.0;
        }

        return round(2 * ($this->length + $this->width) * $this->quantity, 2);
    }

    /**
     * Cantitatea in unitatea obiectului (mp/ml/buc), folosita la generarea ofertei.
     */
    public function unitQuantity(): float
    {
        return match ($this->unit) {
            'mp' => $this->area(),
            'ml' => round(((float) ($this->length ?? 0)) * $this->quantity, 2) ?: (float) $this->quantity,
            default => (float) $this->quantity,
        };
    }

    /**
     * Rezumat lizibil al caracteristicilor (ex: "Nr. sigurante: 12, Faze: Trifazat").
     */
    public function attributesSummary(): string
    {
        $parts = [];

        foreach ($this->specs ?? [] as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $label = self::FIELDS[$key]['label'] ?? $key;
            $parts[] = $label.': '.$value;
        }

        return implode(', ', $parts);
    }
}
