<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'company_name' => 'Om Priceput',
        'company_email' => 'contact@ompriceput.test',
        'company_phone' => '0700 000 000',
        'company_address' => '',
        'company_hours' => 'Luni - Vineri, 08:00 - 19:00',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_linkedin' => '',
        'invoice_series' => 'OP',
        'vat_percentage' => '19',
        'minimum_profit_margin' => '20',
        'operational_reminders_email_enabled' => '0',
        'shop_enabled' => '0',
        'shop_free_shipping_threshold' => '500',
        'shop_shipping_cost' => '25',
        'google_analytics_id' => '',
        'google_tag_manager_id' => '',
        'meta_pixel_id' => '',
    ];

    public static function get(string $key, ?string $default = null): ?string
    {
        $values = Cache::rememberForever('app_settings', fn () => static::pluck('value', 'key')->all());

        return $values[$key] ?? $default ?? self::DEFAULTS[$key] ?? null;
    }

    public static function allSettings(): array
    {
        $stored = Cache::rememberForever('app_settings', fn () => static::pluck('value', 'key')->all());

        return array_merge(self::DEFAULTS, $stored);
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('app_settings');
    }

    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            static::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('app_settings');
    }
}
