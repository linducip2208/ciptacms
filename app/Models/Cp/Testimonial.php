<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Testimonial extends Model
{
    use Auditable, BelongsToTenant, SoftDeletes;

    protected $table = 'cp_testimonials';

    protected $fillable = [
        'tenant_id', 'customer', 'company', 'photo', 'rating', 'testimonial',
        'status', 'sort_order',
    ];

    protected $casts = [
        'rating' => 'integer',
        'sort_order' => 'integer',
    ];

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderByDesc('id');
    }
}
