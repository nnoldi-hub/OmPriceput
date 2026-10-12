<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_order_id', 'equipment_id', 'quantity', 'received_quantity', 'unit_cost'];

    protected $casts = ['quantity' => 'decimal:2', 'received_quantity' => 'decimal:2', 'unit_cost' => 'decimal:2'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
