<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Equipment extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'consumabil' => 'Consumabile',
        'scula' => 'Scule',
        'piesa' => 'Piese de schimb',
        'instalatii' => 'Instalatii si aparate',
        'altele' => 'Diverse',
    ];

    public const UNITS = ['buc', 'metru', 'm2', 'kg', 'set', 'litru', 'ora'];

    protected $fillable = [
        'name',
        'category',
        'sku',
        'unit',
        'unit_price',
        'cost_price',
        'stock_quantity',
        'minimum_stock',
        'description',
        'is_active',
        'client_id',
        'supplier_id',
        'markup_percent',
        'location',
        'warranty_until',
        'installed_at',
        'is_visible_in_shop',
        'slug',
        'image_path',
        'shop_description',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'markup_percent' => 'decimal:2',
        'is_active' => 'boolean',
        'is_visible_in_shop' => 'boolean',
        'minimum_stock' => 'integer',
        'warranty_until' => 'date',
        'installed_at' => 'date',
    ];

    public function offerItems(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function getProfitAmountAttribute(): float
    {
        return (float) $this->unit_price - (float) $this->cost_price;
    }

    public function scopeVisibleInShop($query)
    {
        return $query->where('is_active', true)->where('is_visible_in_shop', true);
    }

    public function activeDiscount(): ?Discount
    {
        return Discount::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('scope', 'product')->where('equipment_id', $this->id)
                    ->orWhere(function ($q) {
                        $q->where('scope', 'category')->where('category', $this->category);
                    });
            })
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByRaw("CASE WHEN scope = 'product' THEN 0 ELSE 1 END")
            ->first();
    }

    public function getShopPriceAttribute(): float
    {
        $discount = $this->activeDiscount();

        if (! $discount) {
            return (float) $this->unit_price;
        }

        return $discount->applyTo((float) $this->unit_price);
    }

    public function getShopDiscountAmountAttribute(): float
    {
        return round((float) $this->unit_price - $this->shop_price, 2);
    }
}
