<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Measurement extends Model
{
    use HasFactory;

    public const STATUSES = ['in_progress', 'completed'];

    public const STATUS_LABELS = [
        'in_progress' => 'In lucru',
        'completed' => 'Finalizata',
    ];

    protected $fillable = [
        'name', 'location', 'client_id', 'project_id', 'status', 'notes', 'measured_at', 'photos',
    ];

    protected $casts = [
        'measured_at' => 'datetime',
        'photos' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function objects(): HasMany
    {
        return $this->hasMany(MeasurementObject::class)->orderBy('position')->orderBy('id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Suprafata totala in mp (lungime * latime * cantitate), doar pentru obiectele cu ambele dimensiuni.
     */
    public function totalArea(): float
    {
        return round($this->objects->reduce(fn (float $carry, MeasurementObject $object) => $carry + $object->area(), 0.0), 2);
    }

    /**
     * Perimetru total in ml pentru obiectele cu ambele dimensiuni.
     */
    public function totalPerimeter(): float
    {
        return round($this->objects->reduce(fn (float $carry, MeasurementObject $object) => $carry + $object->perimeter(), 0.0), 2);
    }
}
