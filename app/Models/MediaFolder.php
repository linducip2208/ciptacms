<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class MediaFolder extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'parent_id',
    'name',
    'slug',
];
    public function files(){ return $this->hasMany(MediaFile::class,'folder_id'); }
public function children(){ return $this->hasMany(MediaFolder::class,'parent_id'); }

}
