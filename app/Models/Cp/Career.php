<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Career extends Model
{
    use Auditable, BelongsToTenant, HasSlug, SoftDeletes;

    protected $table = 'cp_careers';

    protected $fillable = [
        'tenant_id', 'position', 'slug', 'description', 'requirements', 'location',
        'employment_type', 'deadline', 'status', 'sort_order',
    ];

    protected $casts = [
        'requirements' => 'array',
        'deadline' => 'date',
        'sort_order' => 'integer',
    ];

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'career_id');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderByDesc('id');
    }

    public function seo()
    {
        return $this->morphOne(\App\Models\SeoMeta::class, 'seoable');
    }
}
