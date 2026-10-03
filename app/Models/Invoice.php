<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'offer_id',
        'invoice_number',
        'fgo_id',
        'fgo_status',
        'fgo_synced_at',
        'fgo_error',
        'amount',
        'paid_amount',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'issued_at' => 'date',
        'due_at' => 'date',
        'paid_at' => 'date',
        'fgo_synced_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return round(max((float) $this->amount - (float) $this->paid_amount, 0), 2);
    }

    public static function nextInvoiceNumber(): string
    {
        $series = Setting::get('invoice_series', 'OP');
        $count = self::count() + 1;

        return sprintf('%s-%s-%04d', $series, now()->format('Y'), $count);
    }
}
