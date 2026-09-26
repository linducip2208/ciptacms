<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable, BelongsToTenant, HasSlug, SoftDeletes;

    protected $table = 'cp_products';

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'image', 'gallery', 'excerpt', 'description',
        'features', 'cta_label', 'cta_url', 'status', 'sort_order',
    ];

    protected $casts = [
        'gallery' => 'array',
        'features' => 'array',
        'sort_order' => 'integer',
    ];

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('title');
    }

    public function seo()
    {
        return $this->morphOne(\App\Models\SeoMeta::class, 'seoable');
    }
}
