<?php

namespace App\Models\Cp;

use Illuminate\Database\Eloquent\Model;

class GalleryImage extends Model
{
    protected $table = 'cp_gallery_images';

    protected $fillable = ['album_id', 'path', 'caption', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function album()
    {
        return $this->belongsTo(GalleryAlbum::class, 'album_id');
    }
}
