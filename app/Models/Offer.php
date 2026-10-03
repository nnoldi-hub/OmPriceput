<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'visit_id',
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

    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class);
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
            $unitCost = $item->equipment?->cost_price ?? $item->service?->cost_price;
            if ($unitCost === null) {
                $uncostedItems++;
                continue;
            }
            $cost += (float) $item->quantity * (float) $unitCost;
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
