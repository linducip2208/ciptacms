<?php

namespace App\Models\Cp;

use App\Core\Traits\Auditable;
use App\Core\Traits\BelongsToTenant;
use App\Core\Traits\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryAlbum extends Model
{
    use Auditable, BelongsToTenant, HasSlug, SoftDeletes;

    protected $table = 'cp_gallery_albums';

    protected $fillable = [
        'tenant_id', 'title', 'slug', 'description', 'cover', 'status', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function images()
    {
        return $this->hasMany(GalleryImage::class, 'album_id')->orderBy('sort_order');
    }

    public function scopePublished($q)
    {
        return $q->where('status', 'published');
    }

    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('title');
    }
}
