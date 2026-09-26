<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ProductCategory extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'parent_id',
    'image',
    'description',
];


}
