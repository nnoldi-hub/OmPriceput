<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const TYPES = ['lucrare', 'propriu'];

    public const TYPE_LABELS = [
        'lucrare' => 'Lucrare',
        'propriu' => 'Propriu',
    ];

    public const STATUSES = ['activ', 'in_pauza', 'incheiat'];

    public const STATUS_LABELS = [
        'activ' => 'Activ',
        'in_pauza' => 'In pauza',
        'incheiat' => 'Incheiat',
    ];

    protected $fillable = [
        'name', 'type', 'client_id', 'status', 'description',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
