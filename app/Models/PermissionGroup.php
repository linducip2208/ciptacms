<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class PermissionGroup extends Model
{
    use HasFactory;

protected $fillable = [
    'name',
    'slug',
    'description',
];
    public function permissions(){ return $this->hasMany(Permission::class,'group_id'); }

}
