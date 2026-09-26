<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class RoomType extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'property_id',
    'name',
    'slug',
    'description',
    'base_price',
    'capacity',
    'amenities',
    'images',
];
protected $casts = [
    'amenities' => 'array',
    'images' => 'array',
];


}
