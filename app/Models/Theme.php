<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Theme extends Model
{
    use HasFactory;

protected $fillable = [
    'slug',
    'name',
    'version',
    'author',
    'description',
    'meta',
    'settings',
    'is_active',
];
protected $casts = [
    'meta' => 'array',
    'settings' => 'array',
    'is_active' => 'boolean',
];


}
