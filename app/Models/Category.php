<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Category extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'type',
    'description',
    'parent_id',
    'image',
];
    public function posts(){ return $this->hasMany(Post::class); }

}
