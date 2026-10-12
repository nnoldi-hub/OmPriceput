<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaItem extends Model
{
    use HasFactory;

    public const TYPES = ['task', 'nota', 'idee', 'obiectiv', 'masuratoare'];

    public const TYPE_LABELS = [
        'task' => 'Task',
        'nota' => 'Nota',
        'idee' => 'Idee',
        'obiectiv' => 'Obiectiv',
        'masuratoare' => 'Masuratoare',
    ];

    public const STATUSES = ['inbox', 'de_facut', 'in_lucru', 'gata', 'arhivat'];

    public const STATUS_LABELS = [
        'inbox' => 'Inbox',
        'de_facut' => 'De facut',
        'in_lucru' => 'In lucru',
        'gata' => 'Gata',
        'arhivat' => 'Arhivat',
    ];

    public const PRIORITIES = ['low', 'normal', 'high'];

    public const PRIORITY_LABELS = [
        'low' => 'Scazuta',
        'normal' => 'Normala',
        'high' => 'Ridicata',
    ];

    protected $fillable = [
        'title', 'text', 'type', 'status', 'priority',
        'due_at', 'reminder_at', 'repeat', 'project_id', 'pinned_at', 'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'reminder_at' => 'datetime',
        'pinned_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'gata';
    }
}
