<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portfolio extends Model
{
    use Auditable, BelongsToTenant, HasSlug, SoftDeletes;

    protected $table = 'cp_portfolios';

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'client', 'category', 'project_date',
        'excerpt', 'description', 'technology', 'images', 'url', 'status', 'sort_order',
    ];

    protected $casts = [
        'technology' => 'array',
        'images' => 'array',
        'project_date' => 'date',
        'sort_order' => 'integer',
    ];

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeOrdered($q)
    {
        return $q->orderByDesc('project_date')->orderBy('sort_order');
    }

    public function seo()
    {
        return $this->morphOne(\App\Models\SeoMeta::class, 'seoable');
    }
}
