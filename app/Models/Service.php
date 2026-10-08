<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use HasFactory;

    public const TRADES = [
        'electric' => 'Electric',
        'sanitar' => 'Sanitar',
        'vopsire' => 'Vopsire si zugraveli',
        'montaj' => 'Montaj mobilier',
        'general' => 'Intretinere generala',
    ];

    public const UNITS = ['ora', 'buc', 'm2', 'metru', 'set', 'vizita'];

    protected $fillable = [
        'name',
        'category',
        'unit',
        'cost_price',
        'sale_price',
        'description',
        'is_active',
        'duration_minutes',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
    ];

    public function offerItems(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }
    public function toolboxes(): BelongsToMany
{
    return $this->belongsToMany(Toolbox::class, 'service_toolbox');
}
}