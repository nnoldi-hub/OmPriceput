<?php

namespace App\Models;

use App\Support\PageViewRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageView extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'visitor_id',
        'user_id',
        'url',
        'path',
        'page_title',
        'method',
        'ip_address',
        'device_type',
        'browser',
        'platform',
        'user_agent',
        'referer',
        'referer_domain',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'is_bot',
        'created_at',
    ];

    protected $casts = [
        'is_bot' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHuman($query)
    {
        $query->where('is_bot', false)
            ->whereNull('user_id')
            ->where('method', 'GET')
            ->whereNotIn('path', PageViewRules::EXACT_IGNORED_PATHS);

        foreach (PageViewRules::IGNORED_PREFIXES as $prefix) {
            $query->where(function ($query) use ($prefix) {
                $query->where('path', '!=', $prefix)
                    ->where('path', 'not like', $prefix.'/%');
            });
        }

        foreach (PageViewRules::IGNORED_EXTENSIONS as $extension) {
            $query->where('path', 'not like', '%'.$extension);
        }

        return $query;
    }

    public function scopePeriod($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
