<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementObject extends Model
{
    use HasFactory;

    protected $fillable = [
        'measurement_id', 'name', 'length', 'width', 'height', 'quantity', 'notes', 'position',
    ];

    protected $casts = [
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'quantity' => 'integer',
        'position' => 'integer',
    ];

    public function measurement(): BelongsTo
    {
        return $this->belongsTo(Measurement::class);
    }

    public function area(): float
    {
        if ($this->length === null || $this->width === null) {
            return 0.0;
        }

        return round($this->length * $this->width * $this->quantity, 2);
    }

    public function perimeter(): float
    {
        if ($this->length === null || $this->width === null) {
            return 0.0;
        }

        return round(2 * ($this->length + $this->width) * $this->quantity, 2);
    }
}
