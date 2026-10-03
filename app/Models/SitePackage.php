<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePackage extends Model
{
    protected $fillable = [
        'key',
        'name',
        'price_from',
        'vizite_an',
        'timp_raspuns',
        'prioritate',
        'features',
        'highlight',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'vizite_an' => 'integer',
        'features' => 'array',
        'highlight' => 'boolean',
        'active' => 'boolean',
    ];
}
