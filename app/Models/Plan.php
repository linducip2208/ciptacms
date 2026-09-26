<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Plan extends Model
{
    use HasFactory;

protected $fillable = [
    'name',
    'slug',
    'price',
    'currency',
    'interval',
    'features',
    'limits',
    'is_active',
    'sort_order',
];
protected $casts = [
    'features' => 'array',
    'limits' => 'array',
    'is_active' => 'boolean',
];


}
