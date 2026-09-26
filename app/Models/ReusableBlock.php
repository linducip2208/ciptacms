<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class ReusableBlock extends Model
{
    use HasFactory;

protected $fillable = [
    'tenant_id',
    'name',
    'slug',
    'blocks',
    'is_global',
];
protected $casts = [
    'blocks' => 'array',
    'is_global' => 'boolean',
];


}
