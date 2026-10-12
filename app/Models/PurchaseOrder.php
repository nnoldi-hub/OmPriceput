<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['supplier_id', 'order_number', 'status', 'ordered_at', 'received_at', 'total_amount', 'notes', 'supplier_invoice_number', 'document_path'];

    protected $casts = ['ordered_at' => 'date', 'received_at' => 'date', 'total_amount' => 'decimal:2'];

    protected $appends = ['document_url'];

    public function getDocumentUrlAttribute(): ?string
    {
        return $this->document_path ? Storage::url($this->document_path) : null;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
