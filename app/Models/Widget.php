<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    protected $table = 'widgets';

    protected $fillable = [
        'tenant_id', 'sidebar', 'type', 'title', 'config', 'sort_order', 'is_visible',
    ];

    protected $casts = [
        'config' => 'array',
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
    ];

    public function scopeVisible($q)
    {
        return $q->where('is_visible', true);
    }
}
