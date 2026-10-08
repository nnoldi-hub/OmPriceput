<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Installation extends Model
{
    use HasFactory;

    public const TYPES = [
        'instalare',
        'reparatie',
        'mentenanta',
        'verificare',
        'urgenta',
    ];

    public const TYPE_LABELS = [
        'instalare' => 'Instalare / montaj',
        'reparatie' => 'Reparatie',
        'mentenanta' => 'Mentenanta',
        'verificare' => 'Verificare / constatare',
        'urgenta' => 'Interventa urgenta',
    ];

    public const STATUSES = [
        'scheduled' => 'Programata',
        'in_progress' => 'In desfasurare',
        'completed' => 'Finalizata',
        'cancelled' => 'Anulata',
    ];

    public const CHECKLIST_TEMPLATES = [
        'instalare' => [
            'Verificare locatie si masuratori',
            'Pregatirea zonei de lucru',
            'Demontare vechiul obiect (daca este cazul)',
            'Montare si fixare',
            'Conectare la retele (curent / apa / internet)',
            'Testare functionala completa',
            'Curatenie si predare loc de lucru',
        ],
        'reparatie' => [
            'Diagnostic defect',
            'Scoaterea piesei defecte',
            'Montaj piesa noua',
            'Verificare etanseitate / functionare',
            'Testare punctuala a instalatiei',
            'Curatenie si predare loc de lucru',
        ],
        'mentenanta' => [
            'Verificare instalatii',
            'Curatare / intretinere',
            'Inlocuire consumabile uzuale',
            'Testare functionala',
            'Intocmire raport cu recomandari',
        ],
        'verificare' => [
            'Contactare client si confirmare adresa',
            'Identificarea problemei',
            'Fotografiere / filmare pentru documentare',
            'Identificarea materialelor necesare',
            'Estimare orientativa de pret',
            'Programarea vizitei de lucru',
        ],
        'urgenta' => [
            'Contactare client si evaluarea urgenței',
            'Intervenție imediată',
            'Remediere provizorie',
            'Identificarea cauzei reale',
            'Verificare funcțională',
            'Raport de intervenție',
        ],
    ];

    protected $fillable = [
        'client_id',
        'offer_id',
        'technician_id',
        'type',
        'requested_type',
        'address',
        'latitude',
        'longitude',
        'scheduled_at',
        'labor_hours',
        'status',
        'notes',
        'checklist',
        'materials',
        'material_items',
        'service_items',
        'stock_consumed_at',
        'photos',
        'customer_name',
        'customer_notes',
        'completed_at',
        'report_number',
        'handover_at',
        'technician_signature',
        'customer_signature',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'labor_hours' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'checklist' => 'array',
        'materials' => 'array',
        'material_items' => 'array',
        'service_items' => 'array',
        'photos' => 'array',
        'completed_at' => 'datetime',
        'handover_at' => 'datetime',
        'stock_consumed_at' => 'datetime',
    ];

    protected $appends = ['cost_report'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    public function isVisit(): bool
    {
        return $this->type === 'verificare';
    }

    public function isUnscheduled(): bool
    {
        return empty($this->scheduled_at)
            && in_array($this->status, ['scheduled', 'in_progress'], true);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function getActualExpenseTotalAttribute(): float
    {
        return round((float) $this->expenses()->sum('amount'), 2);
    }

    public static function defaultChecklist(?string $type = null): array
    {
        $template = self::CHECKLIST_TEMPLATES[$type] ?? self::CHECKLIST_TEMPLATES['instalare'];

        return collect($template)
            ->map(fn (string $label) => ['label' => $label, 'done' => false])
            ->all();
    }

    public function getCostReportAttribute(): array
    {
        $materialItems = collect($this->material_items ?? []);
        $serviceItems = collect($this->service_items ?? []);

        $equipment = Equipment::query()
            ->whereIn('id', $materialItems->pluck('equipment_id')->filter()->unique())
            ->get()
            ->keyBy('id');
        $services = Service::query()
            ->whereIn('id', $serviceItems->pluck('service_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        $materialCost = $materialItems->sum(
            fn (array $item): float => (float) ($item['quantity'] ?? 0)
                * (float) ($equipment->get($item['equipment_id'])?->cost_price ?? 0)
        );
        $laborCost = $serviceItems->sum(
            fn (array $item): float => (float) ($item['quantity'] ?? 0)
                * (float) ($services->get($item['service_id'])?->cost_price ?? 0)
        );
        $offerValue = (float) ($this->offer?->total_amount ?? 0);
        $totalCost = $materialCost + $laborCost;
        $actualExpenses = (float) $this->expenses()->sum('amount');

        return [
            'offer_value' => round($offerValue, 2),
            'material_cost' => round($materialCost, 2),
            'labor_cost' => round($laborCost, 2),
            'total_cost' => round($totalCost, 2),
            'actual_expenses' => round($actualExpenses, 2),
            'actual_total_cost' => round($totalCost + $actualExpenses, 2),
            'estimated_profit' => round($offerValue - $totalCost, 2),
            'final_profit' => $this->status === 'completed' ? round($offerValue - $totalCost - $actualExpenses, 2) : null,
        ];
    }
    public function requiredToolboxes(): Collection
{
    return Toolbox::forServices(collect($this->service_items ?? [])->pluck('service_id'));
}
}
