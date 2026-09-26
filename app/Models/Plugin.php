<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Plugin extends Model
{
    use HasFactory;

protected $fillable = [
    'slug',
    'name',
    'version',
    'author',
    'description',
    'meta',
    'config',
    'is_installed',
    'is_active',
];
protected $casts = [
    'meta' => 'array',
    'config' => 'array',
    'is_installed' => 'boolean',
    'is_active' => 'boolean',
];


}
