<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    protected $fillable = ['day_of_week', 'start_time', 'end_time', 'is_active'];

    protected $casts = [
        'day_of_week' => 'integer',
        'is_active' => 'boolean',
    ];
}