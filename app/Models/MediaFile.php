<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MediaFile extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'folder_id',
    'uuid',
    'disk',
    'path',
    'filename',
    'original_name',
    'mime',
    'size',
    'width',
    'height',
    'alt',
    'title',
    'caption',
    'description',
    'folder_name',
    'meta',
    'variants',
    'optimized',
    'status',
];
protected $casts = [
    'meta' => 'array',
    'variants' => 'array',
    'optimized' => 'boolean',
    'size' => 'integer',
    'width' => 'integer',
    'height' => 'integer',
];
    public function folder(){ return $this->belongsTo(MediaFolder::class,'folder_id'); }

    public function scopeImages($q) { return $q->where('mime', 'like', 'image/%'); }
    public function scopeVideos($q) { return $q->where('mime', 'like', 'video/%'); }
    public function scopeAudio($q) { return $q->where('mime', 'like', 'audio/%'); }
    public function scopeDocuments($q)
    {
        return $q->where(function ($w) {
            $w->whereIn('mime', [
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet',
                'text/plain', 'text/csv',
            ]);
        });
    }

}
