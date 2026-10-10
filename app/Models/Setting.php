<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public const DEFAULTS = [
        'store_name' => 'Kasirku',
        'store_address' => '',
        'store_phone' => '',
        'receipt_footer' => 'Terima kasih telah berbelanja!',
        'tax_percent' => '0',
        'printer_width' => '58',
        'printer_auto_print' => '0',
    ];

    public static function get(string $key): string
    {
        return Cache::remember("setting:{$key}", now()->addHour(), function () use ($key) {
            return static::find($key)?->value ?? static::DEFAULTS[$key] ?? '';
        });
    }

    public static function put(string $key, string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
