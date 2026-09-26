<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppearanceOption extends Model
{
    protected $table = 'appearance_options';

    protected $fillable = ['tenant_id', 'group', 'key', 'value', 'type'];

    public static function get(string $group, string $key, $default = null)
    {
        $row = static::query()->where('group', $group)->where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function put(string $group, string $key, $value, string $type = 'text'): void
    {
        static::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : (string) $value, 'type' => $type]
        );
    }
}
