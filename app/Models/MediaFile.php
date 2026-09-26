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
    'meta',
    'variants',
    'optimized',
    'status',
];
protected $casts = [
    'meta' => 'array',
    'variants' => 'array',
    'optimized' => 'boolean',
];
    public function folder(){ return $this->belongsTo(MediaFolder::class,'folder_id'); }

}
