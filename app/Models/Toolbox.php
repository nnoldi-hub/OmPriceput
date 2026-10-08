<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Toolbox extends Model
{
    protected $fillable = ['name', 'slug', 'contents', 'always_carry', 'sort_order'];

    protected $casts = ['always_carry' => 'boolean'];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_toolbox');
    }

    /** Cutiile necesare pentru o listă de servicii (Master e mereu inclusă). */
    public static function forServices(iterable $serviceIds): Collection
    {
        $ids = collect($serviceIds)->filter()->unique()->values();

        return static::query()
            ->where('always_carry', true)
            ->orWhereHas('services', fn ($q) => $q->whereIn('services.id', $ids))
            ->orderBy('sort_order')
            ->get();
    }
}