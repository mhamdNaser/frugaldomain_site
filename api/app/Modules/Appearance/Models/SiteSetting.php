<?php

namespace App\Modules\Appearance\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['key', 'value'];

    protected $casts = ['value' => 'array'];

    public static function get(string $key): ?array
    {
        return static::where('key', $key)->value('value');
    }

    public static function put(string $key, array $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value])->touch();
    }
}
