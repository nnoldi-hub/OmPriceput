<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'due_at', 'reminder_at', 'repeat', 'project_id', 'pinned_at', 'completed_at', 'reminded_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'reminder_at' => 'datetime',
        'pinned_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(AgendaTag::class, 'agenda_item_tag', 'agenda_item_id', 'agenda_tag_id')->orderBy('name');
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

    /**
     * Repetarea in zile: "daily" (1), "weekly" (7), "monthly" (30), "yearly" (365)
     * sau o combinatie tip "3d", "2w", "1m". Returneaza null daca nu exista repetare.
     */
    public static function repeatIntervalDays(?string $repeat): ?int
    {
        if (blank($repeat)) {
            return null;
        }

        $repeat = strtolower(trim($repeat));

        $units = [
            'daily' => 1,
            'weekly' => 7,
            'monthly' => 30,
            'yearly' => 365,
            'd' => 1,
            'w' => 7,
            'm' => 30,
            'y' => 365,
        ];

        if (isset($units[$repeat])) {
            return $units[$repeat];
        }

        if (preg_match('/^(\d+)([dwmy])$/', $repeat, $matches)) {
            return (int) $matches[1] * $units[$matches[2]];
        }

        return null;
    }

    /**
     * Completeaza un item repetitiv: avanseaza termenul si mementoul,
     * il lasa deschis si inregistreaza ultima completare.
     */
    public function advanceRepeat(): void
    {
        $days = static::repeatIntervalDays($this->repeat);

        if ($days === null) {
            $this->update(['status' => 'gata', 'completed_at' => now()]);

            return;
        }

        $base = $this->due_at ?? now();

        $this->update([
            'status' => 'de_facut',
            'due_at' => (clone $base)->addDays($days),
            'reminder_at' => $this->reminder_at ? (clone $this->reminder_at)->addDays($days) : null,
            'completed_at' => now(),
        ]);
    }
}
