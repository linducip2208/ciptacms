<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoRedirect extends Model
{
    protected $table = 'seo_redirects';

    protected $fillable = [
        'tenant_id', 'from_path', 'to_path', 'status_code', 'hits', 'is_active',
    ];

    protected $casts = [
        'hits' => 'integer',
        'status_code' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
